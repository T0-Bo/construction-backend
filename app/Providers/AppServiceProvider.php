<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Policies\BudgetPolicy;
use App\Policies\CompanyMemberPolicy;
use App\Policies\NotificationPolicy as DatabaseNotificationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReportPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);
        Gate::policy(User::class, CompanyMemberPolicy::class);
        RateLimiter::for('register', function ($request) {
            return [
                Limit::perMinute(5)->by('register-minute:'.$request->ip()),
                Limit::perHour(20)->by('register-hour:'.$request->ip()),
            ];
        });
    }
}
