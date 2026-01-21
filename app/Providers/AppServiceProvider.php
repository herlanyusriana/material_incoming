<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        // Super Admin Bypass
        Gate::before(function (User $user, $ability) {
            if ($user->role === 'admin') {
                return true;
            }
        });

        // Dynamic Gates from Matrix
        $permissions = config('role_permissions.defined_permissions', []);
        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                $role = $user->role;
                $allowedPermissions = config("role_permissions.roles.{$role}", []);

                return in_array($permission, $allowedPermissions) || in_array('*', $allowedPermissions);
            });
        }
    }
}
