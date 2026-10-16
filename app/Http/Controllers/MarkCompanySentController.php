<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;

class MarkCompanySentController extends Controller
{
    public function __invoke(Company $company): RedirectResponse
    {
        if ($company->status === ApplicationStatus::Unsent) {
            $company->update([
                'status' => ApplicationStatus::Sent,
                'sent_at' => today(),
            ]);
        }

        return redirect()->back(fallback: route('overview'));
    }
}
