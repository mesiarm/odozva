<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function create(): View
    {
        return view('companies.create', [
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create($this->withSentDate($request->validated()));

        return redirect()
            ->route('overview')
            ->with('opened', $company->id);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($this->withSentDate($request->validated(), $company));

        return redirect()
            ->back(fallback: route('overview'))
            ->with('opened', $company->id);
    }

    /**
     * Record today as the sent date the first time a company leaves the unsent status.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withSentDate(array $data, ?Company $company = null): array
    {
        if (($data['sent_at'] ?? null) === null && $company?->sent_at === null && ApplicationStatus::from($data['status'])->hasBeenSent()) {
            $data['sent_at'] = today();
        }

        return $data;
    }
}
