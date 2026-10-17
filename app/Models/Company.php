<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'status', 'sent_at', 'channel', 'position', 'web', 'future', 'reason', 'note', 'focus'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'sent_at' => 'date',
        ];
    }

    /**
     * Short sentence under the company name in the overview.
     *
     * @return Attribute<string, never>
     */
    protected function summaryLine(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === ApplicationStatus::Unsuitable) {
                return $this->reason ? 'Nevhodné: '.Str::limit($this->reason, 80) : 'Nevhodné';
            }

            if ($this->status === ApplicationStatus::Unsent) {
                return 'Zatiaľ neposlané';
            }

            return collect([
                $this->sent_at ? 'Poslané '.$this->sent_at->format('j. n.') : 'Poslané, dátum neuvedený',
                $this->channel,
                $this->status === ApplicationStatus::Rejected && $this->reason ? Str::limit($this->reason, 80) : null,
            ])->filter()->implode(' · ');
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function sentOut(Builder $query): Builder
    {
        return $query->whereNotIn('status', [ApplicationStatus::Unsent, ApplicationStatus::Unsuitable]);
    }

    /**
     * Most relevant statuses first, then the most recently sent.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function byRelevance(Builder $query): Builder
    {
        $cases = collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $status): string => "when '{$status->value}' then {$status->priority()}")
            ->implode(' ');

        return $query
            ->orderByRaw("case status {$cases} end")
            ->orderByDesc('sent_at')
            ->orderBy('name');
    }
}
