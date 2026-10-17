<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LaterController;
use App\Http\Controllers\MarkCompanySentController;
use App\Http\Controllers\OpenCompanyWebController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', OverviewController::class)->name('overview');
Route::get('statistiky', StatisticsController::class)->name('statistics');
Route::get('do-buducna', LaterController::class)->name('later');

Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
Route::patch('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
Route::post('companies/{company}/web', OpenCompanyWebController::class)->name('companies.web');
Route::post('companies/{company}/sent', MarkCompanySentController::class)->name('companies.sent');
