<?php

namespace App\Providers;

use App\Helpers\Utils;
use App\Models\Report;
use App\Models\User;
use App\Observers\ReportObserver;
use Filament\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Route;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Report::observe(ReportObserver::class);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole(Utils::getSuperAdminName())) {
                return true;
            }

            return null;
        });
    }

    public function registerRoutes(): void
    {
        Route::get('/admin', [HomeController::class, 'index'])->name('admin.dashboard');
    }
}
