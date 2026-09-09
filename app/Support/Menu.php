<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class Menu
{
    /**
     * All modules from config.
     */
    public static function all(): array
    {
        return config('modules.modules', []);
    }

    /**
     * Get one module definition by key.
     */
    public static function get(string $key): ?array
    {
        $modules = static::all();

        return isset($modules[$key])
            ? $modules[$key] + ['key' => $key]
            : null;
    }

    /**
     * Items of a module visible to the given user.
     *
     * @return array<int, array>
     */
    public static function visibleItems(User $user, string $moduleKey): array
    {
        $module = static::get($moduleKey);

        if (! $module) {
            return [];
        }

        return array_values(array_filter(
            $module['items'],
            fn (array $item) => static::userCan($user, $item['permission'] ?? null),
        ));
    }

    /**
     * Modules (with their visible items) the user can access.
     *
     * @return array<string, array>
     */
    public static function visibleModules(User $user): array
    {
        $result = [];

        foreach (static::all() as $key => $module) {
            $items = array_values(array_filter(
                $module['items'] ?? [],
                fn (array $item) => static::userCan($user, $item['permission'] ?? null),
            ));

            if ($items !== []) {
                $result[$key] = $module + ['key' => $key, 'visible_items' => $items];
            }
        }

        return $result;
    }

    /**
     * Is a single item visible to the user?
     */
    public static function canSeeItem(User $user, string $moduleKey, string $itemKey): bool
    {
        foreach (static::get($moduleKey)['items'] ?? [] as $item) {
            if (($item['key'] ?? null) === $itemKey) {
                return static::userCan($user, $item['permission'] ?? null);
            }
        }

        return false;
    }

    /**
     * Module dashboard route URL.
     */
    public static function dashboardUrl(string $key): string
    {
        return route('module.dashboard', ['module' => $key]);
    }

    /**
     * Permission names used by the launcher, grouped per module.
     * Used by Role Management UI (K2 sync).
     *
     * @return array<string, array<int, string>> moduleLabel => [permissions]
     */
    public static function permissionsByModule(): array
    {
        $groups = [];

        foreach (static::all() as $key => $module) {
            foreach ($module['items'] ?? [] as $item) {
                $permission = $item['permission'] ?? null;

                if ($permission) {
                    $groups[$key] ??= [];

                    if (! in_array($permission, $groups[$key], true)) {
                        $groups[$key][] = $permission;
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * Human label(s) per permission, derived from the menu items that use it.
     *
     * @return array<string, array<int, string>> permission => [label keys]
     */
    public static function permissionLabels(): array
    {
        $labels = [];

        foreach (static::all() as $module) {
            foreach ($module['items'] ?? [] as $item) {
                $permission = $item['permission'] ?? null;

                if ($permission && isset($item['label'])) {
                    $labels[$permission] ??= [];

                    if (! in_array($item['label'], $labels[$permission], true)) {
                        $labels[$permission][] = $item['label'];
                    }
                }
            }
        }

        return $labels;
    }

    protected static function userCan(User $user, ?string $permission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $permission) {
            return true;
        }

        return Gate::check($permission);
    }
}
