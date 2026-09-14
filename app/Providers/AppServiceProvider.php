<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Models\Report;
use App\Observers\ReportObserver;
use App\Models\User;
use App\Models\Teacher;
use Filament\Http\Controllers\HomeController;
use Route;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Report::observe(ReportObserver::class);

        Gate::before(function (User $user, string $ability, mixed ...$arguments): ?bool {
            if (str_ends_with($ability, '_teacher') || in_array(Teacher::class, $arguments, true)
                || collect($arguments)->contains(fn (mixed $argument): bool => $argument instanceof Teacher)) {
                return null;
            }

            return $user->hasUnrestrictedAccess() ? true : null;
        });
    }

    public function registerRoutes(): void
    {
        Route::get('/admin', [HomeController::class, 'index'])->name('admin.dashboard');
    }
}
