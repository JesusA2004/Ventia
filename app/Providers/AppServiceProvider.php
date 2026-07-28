<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActiveCompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ActiveCompanyContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Superadministrators (no company_id) bypass every permission check.
        Gate::before(fn (User $user, string $ability) => $user->isSuperAdmin() ? true : null);

        // Inertia's PropsResolver treats every JsonResource as Responsable and
        // calls toResponse() on it, which wraps single resources and
        // collections in a "data" key by default. Every frontend type in
        // this app assumes flat, unwrapped props (Category[], customer.name,
        // etc.), so leaving the default wrap on silently produces undefined
        // reads (e.g. "Historial de undefined"). PaginatedResource::make()
        // builds its own {data, links, meta} shape by hand and is unaffected
        // either way.
        JsonResource::withoutWrapping();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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
}
