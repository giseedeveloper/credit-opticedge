<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

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
        $this->configureSuperAdminGate();
        $this->configureDefaults();
        $this->configureQueryMacros();
    }

    /**
     * Grant the 'admin' role implicit access to every permission gate.
     * Returning true from before() short-circuits all subsequent checks.
     */
    protected function configureSuperAdminGate(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user->is_active) {
                return false;
            }

            if ($user->hasRole('admin')) {
                return true;
            }

            $module = Str::before($ability, '.');

            if ($module === $ability) {
                return null;
            }

            try {
                return $user->hasPermissionTo("{$module}.all") ? true : null;
            } catch (PermissionDoesNotExist) {
                return null;
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        RateLimiter::for('api-login', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('public-media', function (Request $request): Limit {
            return Limit::perMinute(240)->by($request->ip());
        });

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

    /**
     * Add database-portable case-insensitive search helpers for Livewire filters.
     */
    protected function configureQueryMacros(): void
    {
        Builder::macro('whereInsensitiveLike', function (string $column, string $pattern): Builder {
            $driver = $this->getQuery()->getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                return $this->where($column, 'ilike', $pattern);
            }

            $wrapped = $this->getQuery()->getGrammar()->wrap($column);

            return $this->whereRaw("LOWER({$wrapped}) LIKE ?", [Str::lower($pattern)]);
        });

        Builder::macro('orWhereInsensitiveLike', function (string $column, string $pattern): Builder {
            $driver = $this->getQuery()->getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                return $this->orWhere($column, 'ilike', $pattern);
            }

            $wrapped = $this->getQuery()->getGrammar()->wrap($column);

            return $this->whereRaw("LOWER({$wrapped}) LIKE ?", [Str::lower($pattern)], 'or');
        });
    }
}
