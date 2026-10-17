<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Native\Desktop\Facades\Shell;

class OpenCompanyWebController extends Controller
{
    public function __invoke(Company $company): Response|RedirectResponse
    {
        abort_if(blank($company->web), 404);

        $url = Str::startsWith($company->web, ['http://', 'https://'])
            ? $company->web
            : 'https://'.$company->web;

        if (config('nativephp-internal.running')) {
            Shell::openExternal($url);

            return response()->noContent();
        }

        return redirect()->away($url);
    }
}
