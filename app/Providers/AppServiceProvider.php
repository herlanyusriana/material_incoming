<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\View\Compilers\ResettingBladeCompiler;
use Illuminate\View\DynamicComponent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new ResettingBladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
                $app['config']->get('view.check_cache_timestamps', true),
            ), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin Bypass
        Gate::before(function (User $user, $ability) {
            $role = strtolower((string) ($user->role ?? ''));
            if ($role === 'admin') {
                return true;
            }
        });

        // Dynamic Gates from Matrix
        $permissions = config('role_permissions.defined_permissions', []);
        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                $role = strtolower((string) ($user->role ?? ''));
                $allowedPermissions = config("role_permissions.roles.{$role}", []);

                return in_array($permission, $allowedPermissions) || in_array('*', $allowedPermissions);
            });
        }
    }
}
