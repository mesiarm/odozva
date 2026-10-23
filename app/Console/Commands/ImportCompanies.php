<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Signature('companies:import {path : UTF-8 CSV exported from a sheet of the IT firms spreadsheet ("IT firmy Žilina" or "Mimo Žiliny")} {--year= : Year of the dates in the notes (defaults to the current year)} {--location=Žilina : Location given to companies of the main sheet} {--dry-run : Show what would be imported without saving}')]
#[Description('Import companies from a CSV export of the IT firms spreadsheet; companies that already exist are left untouched')]
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
        $skipped = collect();

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $attributes = $this->attributesFor($row, $columns, $year);

            if ($attributes === null) {
                continue;
            }

            if (Company::query()->where('name', $attributes['name'])->exists()) {
                $skipped->push($attributes['name']);

                continue;
            }

            if (! $this->option('dry-run')) {
                Company::create($attributes);
            }

            $created->push($attributes['status']);
        }

        fclose($handle);

        $this->components->info(($this->option('dry-run') ? 'Naimportovalo by sa ' : 'Naimportovaných ').$created->count().' firiem, preskočených '.$skipped->count().' (už existujú).');

        if ($skipped->isNotEmpty() && $skipped->count() <= 10) {
            $this->line('Preskočené: '.$skipped->implode(', '));
        }

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
            'index' => '#',
            'name' => 'Firma',
            'web' => 'Web',
            'summary' => 'Zameranie',
            'note' => 'Poznámka',
            'activity' => 'Email',
            'unsuitable' => 'Nevhodné',
            'focus' => 'Čo robia',
            'position' => 'Pozícia',
            'place' => 'Poloha (',
            'via' => 'Kanál',
            'applied' => 'Prihlásené',
            'state' => 'Stav',
            'lastNote' => 'Posledná správa',
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

        if ($name === '' || (isset($columns['index']) && ! ctype_digit($cell('index')))) {
            return null;
        }

        return isset($columns['applied'])
            ? $this->applicationAttributes($cell, $name, $year)
            : $this->listAttributes($cell, $name, $year);
    }

    /**
     * A row of the main sheet: companies to approach, with the history of sent mails in the "Email" column.
     *
     * @return array<string, mixed>
     */
    private function listAttributes(callable $cell, string $name, int $year): array
    {
        $activity = $cell('activity');
        $unsuitable = $cell('unsuitable');
        $status = $this->statusFor($activity, $unsuitable);

        return [
            'name' => $name,
            'status' => $status,
            'location' => $this->option('location') ?: null,
            'sent_at' => $status->hasBeenSent() ? $this->sentDate($activity, $year) : null,
            'channel' => $status->hasBeenSent() ? $this->channel($activity) : null,
            'web' => $this->host($cell('web')),
            'reason' => $this->reasonFor($status, $activity, $unsuitable),
            'focus' => $cell('focus') ?: $cell('summary') ?: null,
            'note' => collect([$activity, $cell('note')])->filter()->implode("\n") ?: null,
        ];
    }

    /**
     * A row of the "Mimo Žiliny" sheet: applications that were sent to companies outside the main list.
     *
     * @return array<string, mixed>
     */
    private function applicationAttributes(callable $cell, string $name, int $year): array
    {
        $state = $cell('state');
        $lastNote = $cell('lastNote');
        $status = $this->applicationStatus($state);
        $position = $cell('position');

        return [
            'name' => $name,
            'status' => $status,
            'location' => $this->city($cell('place')),
            'position' => Str::contains($position, ['v e-maile nie je', 'v e-maile je skrátený']) ? null : ($position ?: null),
            'channel' => $status->hasBeenSent() ? ($cell('via') ?: null) : null,
            'sent_at' => $status->hasBeenSent() ? $this->sentDate($cell('applied'), $year) : null,
            'reason' => $status === ApplicationStatus::Unsuitable ? ($lastNote ?: null) : null,
            'note' => collect([
                $state !== 'Bez odpovede' ? $state : '',
                $status === ApplicationStatus::Unsuitable ? '' : $lastNote,
            ])->filter()->implode("\n") ?: null,
        ];
    }

    private function applicationStatus(string $state): ApplicationStatus
    {
        return match (true) {
            Str::startsWith($state, 'Zamietnuté') => ApplicationStatus::Rejected,
            Str::startsWith($state, 'Stiahol si sa') => ApplicationStatus::Unsuitable,
            Str::startsWith($state, 'Pozvánka') => ApplicationStatus::Interview,
            Str::contains($state, ['čaká sa', 'pozastavené', 'prijaté'], ignoreCase: true) => ApplicationStatus::Waiting,
            Str::contains($state, 'nedokončená', ignoreCase: true) => ApplicationStatus::Unsent,
            default => ApplicationStatus::Sent,
        };
    }

    /**
     * The city is the text before the first bracket; "neuvedená v e-maile" and similar means unknown.
     */
    private function city(string $place): ?string
    {
        $city = trim(Str::before($place, ' ('));

        if ($city === '' || Str::startsWith(Str::lower($city), ['neuved', 'neoveren'])) {
            return null;
        }

        return $city;
    }

    private function reasonFor(ApplicationStatus $status, string $activity, string $unsuitable): ?string
    {
        return match ($status) {
            ApplicationStatus::Unsuitable => $unsuitable !== '' ? $unsuitable : null,
            ApplicationStatus::Rejected => preg_match('/neprijali ma:\s*(.+)$/u', $activity, $matches) ? trim($matches[1]) : null,
            default => null,
        };
    }

    private function statusFor(string $activity, string $unsuitable): ApplicationStatus
    {
        return match (true) {
            $activity === '' && $unsuitable !== '' => ApplicationStatus::Unsuitable,
            $activity === '' => ApplicationStatus::Unsent,
            Str::contains($activity, 'zamietnut', ignoreCase: true) => ApplicationStatus::Rejected,
            Str::contains($activity, ['preverujú', 'čaká sa', 'čakám'], ignoreCase: true) => ApplicationStatus::Waiting,
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
