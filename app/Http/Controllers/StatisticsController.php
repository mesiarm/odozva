<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function __invoke(): View
    {
        $counts = Company::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = collect(ApplicationStatus::cases())
            ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)]);

        $total = $byStatus->sum();
        $sent = $byStatus->only(collect(ApplicationStatus::cases())->filter->hasBeenSent()->map->value->all())->sum();
        $replied = $sent - $byStatus[ApplicationStatus::Sent->value];
        $interviews = Company::query()->where('had_interview', true)->count();

        return view('statistics', [
            'byStatus' => $byStatus,
            'statuses' => ApplicationStatus::cases(),
            'total' => $total,
            'sent' => $sent,
            'replied' => $replied,
            'interviews' => $interviews,
            'replyRate' => $sent > 0 ? (int) round($replied / $sent * 100) : 0,
            'interviewRate' => $sent > 0 ? (int) round($interviews / $sent * 100) : 0,
            'recent' => Company::query()->sentOut()->latest('updated_at')->limit(5)->get(),
        ]);
    }
}
