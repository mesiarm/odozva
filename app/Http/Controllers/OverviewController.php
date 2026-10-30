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
        $location = $request->string('lokalita')->trim()->toString() ?: null;

        $companies = Company::query()
            ->when($status, fn ($query, ApplicationStatus $status) => $query->where('status', $status))
            ->when($location, fn ($query, string $location) => $query->where('location', $location))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->byRelevance()
            ->get();

        $allCounts = $this->statusCounts();
        $counts = $location ? $this->statusCounts($location) : $allCounts;
        $unsentCount = $allCounts[ApplicationStatus::Unsent->value];
        $locations = $this->locations();

        return view('overview', [
            'companies' => $companies,
            'interviewCompany' => Company::query()->where('status', ApplicationStatus::Interview)->first(),
            'activeStatus' => $status,
            'search' => $search,
            'activeLocation' => $location,
            'locations' => $locations,
            'allCount' => array_sum($allCounts),
            'filters' => [null, ...array_filter(
                [...ApplicationStatus::tracked(), ApplicationStatus::Unsent, ApplicationStatus::Later, ApplicationStatus::Unsuitable],
                fn (ApplicationStatus $filter): bool => $filter !== ApplicationStatus::Waiting || $counts[$filter->value] > 0,
            )],
            'statusCounts' => $counts,
            'totalCount' => array_sum($counts),
            'statuses' => ApplicationStatus::cases(),
            'summary' => $this->summary($allCounts),
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
    private function statusCounts(?string $location = null): array
    {
        $counts = Company::query()
            ->toBase()
            ->when($location, fn ($query, string $location) => $query->where('location', $location))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ApplicationStatus::cases())
            ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Locations with the number of companies in each, Žilina first.
     *
     * @return Collection<int, object{location: string, total: int}>
     */
    private function locations(): Collection
    {
        return Company::query()
            ->toBase()
            ->whereNotNull('location')
            ->selectRaw('location, count(*) as total')
            ->groupBy('location')
            ->orderByRaw("location = 'Žilina' desc")
            ->orderBy('location')
            ->get();
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
            'interviews' => Company::query()->where('had_interview', true)->count(),
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
            ->where('status', ApplicationStatus::Later)
            ->selectRaw("coalesce(future, 'Bez témy') as topic, count(*) as total")
            ->groupBy('topic')
            ->orderBy('topic')
            ->get()
            ->map(fn (object $row): string => $row->topic.' · '.$row->total.' '.match (true) {
                $row->total === 1 => 'firma',
                $row->total < 5 => 'firmy',
                default => 'firiem',
            });
    }
}
