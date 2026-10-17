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

#[Fillable(['name', 'status', 'sent_at', 'channel', 'position', 'web', 'future', 'note', 'focus'])]
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
                return 'Nevhodné';
            }

            if ($this->status === ApplicationStatus::Unsent) {
                return 'Zatiaľ neposlané';
            }

            if ($this->sent_at === null) {
                return 'Poslané, dátum neuvedený';
            }

            $sentence = 'Poslané '.$this->sent_at->format('j. n.');

            return $this->channel ? $sentence.' · '.$this->channel : $sentence;
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
