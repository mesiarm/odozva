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

        $unsentCount = Company::query()->where('status', ApplicationStatus::Unsent)->count();

        return view('overview', [
            'companies' => $companies,
            'interviewCompany' => Company::query()->where('status', ApplicationStatus::Interview)->first(),
            'activeStatus' => $status,
            'search' => $search,
            'filters' => [null, ...array_filter(
                ApplicationStatus::tracked(),
                fn (ApplicationStatus $tracked): bool => $tracked !== ApplicationStatus::Waiting,
            ), ApplicationStatus::Unsent, ApplicationStatus::Unsuitable],
            'statuses' => ApplicationStatus::cases(),
            'summary' => $this->summary($unsentCount),
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
     * @return array{sent: int, replies: int, interviews: int, replyRate: int, rejected: int, unsent: int}
     */
    private function summary(int $unsentCount): array
    {
        $counts = Company::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $countOf = fn (ApplicationStatus ...$statuses): int => (int) collect($statuses)
            ->sum(fn (ApplicationStatus $status): int => (int) ($counts[$status->value] ?? 0));

        $sent = $countOf(...array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $s): bool => $s->hasBeenSent()));
        $answered = $sent - $countOf(ApplicationStatus::Sent);

        return [
            'sent' => $sent,
            'replies' => $countOf(ApplicationStatus::Reply, ApplicationStatus::Interview),
            'interviews' => $countOf(ApplicationStatus::Interview),
            'replyRate' => $sent > 0 ? (int) round($answered / $sent * 100) : 0,
            'rejected' => $countOf(ApplicationStatus::Rejected),
            'unsent' => $unsentCount,
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
