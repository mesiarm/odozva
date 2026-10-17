<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\View\View;

class LaterController extends Controller
{
    public function __invoke(): View
    {
        $topics = Company::query()
            ->where('status', ApplicationStatus::Later)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Company $company): string => $company->future ?: 'Bez témy')
            ->sortKeys();

        return view('later', ['topics' => $topics]);
    }
}
