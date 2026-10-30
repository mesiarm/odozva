<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useSharedNativeDatabase();
    }

    /**
     * In development NativePHP always uses database/nativephp.sqlite. Point it at the database
     * of the packaged app instead so both show the same companies.
     */
    private function useSharedNativeDatabase(): void
    {
        $path = config('nativephp.shared_database');

        if ($path && config('database.default') === 'nativephp') {
            config(['database.connections.nativephp.database' => $path]);

            DB::purge('nativephp');
        }
    }
}
