<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Signature('companies:import {path : UTF-8 CSV exported from the main sheet of the IT firms spreadsheet} {--year= : Year of the dates in the notes (defaults to the current year)} {--dry-run : Show what would be imported without saving}')]
#[Description('Import companies from a CSV export of the "IT firmy Žilina" spreadsheet; companies that already exist are left untouched')]
class ImportCompanies extends Command
{
    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->components->error("Súbor {$path} sa nedá prečítať.");

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle, escape: '\\') ?: [];
        $columns = $this->resolveColumns($header);

        if (! isset($columns['name'])) {
            fclose($handle);
            $this->components->error('V súbore chýba stĺpec „Firma“.');

            return self::FAILURE;
        }

        $year = (int) ($this->option('year') ?: now()->year);
        $created = collect();
        $skipped = 0;

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $attributes = $this->attributesFor($row, $columns, $year);

            if ($attributes === null) {
                continue;
            }

            if (Company::query()->where('name', $attributes['name'])->exists()) {
                $skipped++;

                continue;
            }

            if (! $this->option('dry-run')) {
                Company::create($attributes);
            }

            $created->push($attributes['status']);
        }

        fclose($handle);

        $this->components->info(($this->option('dry-run') ? 'Naimportovalo by sa ' : 'Naimportovaných ').$created->count().' firiem, preskočených '.$skipped.' (už existujú).');

        $this->table(
            ['Stav', 'Počet'],
            $created->countBy(fn (ApplicationStatus $status): string => $status->label())
                ->map(fn (int $count, string $label): array => [$label, $count])
                ->values()
                ->all(),
        );

        return self::SUCCESS;
    }

    /**
     * Find the position of every column we use, tolerating a UTF-8 BOM and trailing notes in headers.
     *
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function resolveColumns(array $header): array
    {
        $wanted = [
            'name' => 'Firma',
            'web' => 'Web',
            'summary' => 'Zameranie',
            'note' => 'Poznámka',
            'activity' => 'Email',
            'unsuitable' => 'Nevhodné',
            'focus' => 'Čo robia',
        ];

        $columns = [];

        foreach ($header as $index => $title) {
            $title = trim(ltrim((string) $title, "\xEF\xBB\xBF"));

            foreach ($wanted as $key => $prefix) {
                if (! isset($columns[$key]) && $title !== '' && Str::startsWith($title, $prefix)) {
                    $columns[$key] = $index;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $columns
     * @return array<string, mixed>|null
     */
    private function attributesFor(array $row, array $columns, int $year): ?array
    {
        $cell = fn (string $key): string => trim((string) ($row[$columns[$key] ?? -1] ?? ''));

        $name = $cell('name');

        if ($name === '') {
            return null;
        }

        $activity = $cell('activity');
        $unsuitable = $cell('unsuitable');
        $status = $this->statusFor($activity, $unsuitable);

        return [
            'name' => $name,
            'status' => $status,
            'sent_at' => $status->hasBeenSent() ? $this->sentDate($activity, $year) : null,
            'channel' => $status->hasBeenSent() ? $this->channel($activity) : null,
            'web' => $this->host($cell('web')),
            'focus' => $cell('focus') ?: $cell('summary') ?: null,
            'note' => collect([
                $activity,
                $cell('note'),
                $unsuitable !== '' ? 'Nevhodné: '.$unsuitable : '',
            ])->filter()->implode("\n") ?: null,
        ];
    }

    private function statusFor(string $activity, string $unsuitable): ApplicationStatus
    {
        return match (true) {
            $activity === '' && $unsuitable !== '' => ApplicationStatus::Unsuitable,
            $activity === '' => ApplicationStatus::Unsent,
            Str::contains($activity, 'zamietnut', ignoreCase: true) => ApplicationStatus::Rejected,
            Str::contains($activity, 'prebehla komunikácia', ignoreCase: true) => ApplicationStatus::Reply,
            default => ApplicationStatus::Sent,
        };
    }

    private function sentDate(string $activity, int $year): ?Carbon
    {
        if (! preg_match('/(\d{1,2})\.\s*(\d{1,2})\./', $activity, $matches)) {
            return null;
        }

        return checkdate((int) $matches[2], (int) $matches[1], $year)
            ? Carbon::create($year, (int) $matches[2], (int) $matches[1])
            : null;
    }

    private function channel(string $activity): ?string
    {
        return match (true) {
            Str::contains($activity, 'Profesia', ignoreCase: true) => 'Profesia',
            Str::contains($activity, ['@', 'e-mail', 'email'], ignoreCase: true) => 'Email',
            Str::contains($activity, ['formulár', 'cez web'], ignoreCase: true) => 'Web',
            default => null,
        };
    }

    private function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::after($host, 'www.') : null;
    }
}
