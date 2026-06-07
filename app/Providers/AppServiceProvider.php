<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();

        Relation::morphMap([
            'fabric' => \App\Models\Fabric::class,
            'product' => \App\Models\ProductService::class,
        ]);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureGates(): void
    {
        Gate::define('manage-users', fn ($user) => $user->isAdmin());
        Gate::define('manage-inventory', fn ($user) => $user->isAdmin());
        Gate::define('manage-customers', fn ($user) => $user->isAdmin());
        Gate::define('manage-business', fn ($user) => $user->isAdmin());
        Gate::define('view-reports', fn ($user) => $user->isAdmin());
        Gate::define('view-payments', fn ($user) => true);
        Gate::define('delete-records', fn ($user) => $user->isAdmin());
    }
}
