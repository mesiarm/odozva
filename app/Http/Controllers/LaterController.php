<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\View\View;

class LaterController extends Controller
{
    public function __invoke(): View
    {
        $topics = Company::query()
            ->whereNotNull('future')
            ->orderBy('name')
            ->get()
            ->groupBy('future')
            ->sortKeys();

        return view('later', ['topics' => $topics]);
    }
}
