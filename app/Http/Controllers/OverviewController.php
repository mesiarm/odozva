<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OverviewController extends Controller
{
    private const DRAFTS_SHOWN = 3;

    public function __invoke(Request $request): View
    {
        $status = ApplicationStatus::tryFrom($request->string('stav')->toString());
        $search = $request->string('q')->trim()->toString();

        $companies = Company::query()
            ->when($status, fn ($query, ApplicationStatus $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->byRelevance()
            ->get();

        $counts = $this->statusCounts();
        $unsentCount = $counts[ApplicationStatus::Unsent->value];

        return view('overview', [
            'companies' => $companies,
            'interviewCompany' => Company::query()->where('status', ApplicationStatus::Interview)->first(),
            'activeStatus' => $status,
            'search' => $search,
            'filters' => [null, ...array_filter(
                [...ApplicationStatus::tracked(), ApplicationStatus::Unsent, ApplicationStatus::Unsuitable],
                fn (ApplicationStatus $filter): bool => $filter !== ApplicationStatus::Waiting || $counts[$filter->value] > 0,
            )],
            'statusCounts' => $counts,
            'totalCount' => array_sum($counts),
            'statuses' => ApplicationStatus::cases(),
            'summary' => $this->summary($counts),
            'drafts' => Company::query()
                ->where('status', ApplicationStatus::Unsent)
                ->orderBy('name')
                ->limit(self::DRAFTS_SHOWN)
                ->get(),
            'moreDrafts' => max(0, $unsentCount - self::DRAFTS_SHOWN),
            'later' => $this->laterTopics(),
        ]);
    }

    /**
     * Number of companies per status value, including statuses nobody has yet.
     *
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        $counts = Company::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ApplicationStatus::cases())
            ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * @param  array<string, int>  $counts
     * @return array{sent: int, replies: int, interviews: int, replyRate: int, rejected: int, unsent: int}
     */
    private function summary(array $counts): array
    {
        $countOf = fn (ApplicationStatus ...$statuses): int => (int) collect($statuses)
            ->sum(fn (ApplicationStatus $status): int => $counts[$status->value]);

        $sent = $countOf(...array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $s): bool => $s->hasBeenSent()));
        $answered = $sent - $countOf(ApplicationStatus::Sent);

        return [
            'sent' => $sent,
            'replies' => $answered,
            'interviews' => $countOf(ApplicationStatus::Interview),
            'replyRate' => $sent > 0 ? (int) round($answered / $sent * 100) : 0,
            'rejected' => $countOf(ApplicationStatus::Rejected),
            'unsent' => $counts[ApplicationStatus::Unsent->value],
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function laterTopics(): Collection
    {
        return Company::query()
            ->toBase()
            ->whereNotNull('future')
            ->selectRaw('future, count(*) as total')
            ->groupBy('future')
            ->orderBy('future')
            ->get()
            ->map(fn (object $row): string => $row->future.' · '.$row->total.' '.match (true) {
                $row->total === 1 => 'firma',
                $row->total < 5 => 'firmy',
                default => 'firiem',
            });
    }
}
