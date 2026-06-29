<?php

namespace App\Providers;

use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use App\Models\User;
use App\Policies\EquipmentPolicy;
use App\Policies\RequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Equipment::class, EquipmentPolicy::class);
        Gate::policy(LoanRequest::class, RequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
