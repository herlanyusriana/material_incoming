<?php
$user = App\Models\User::where('name', 'Ida')->first();
if (!$user) {
    $user = App\Models\User::where('name', 'ida')->first();
}

if ($user) {
    echo "User found: " . $user->name . " (ID: " . $user->id . ")\n";
    echo "Role: '" . $user->role . "'\n";

    $configPerms = config("role_permissions.roles.{$user->role}");
    echo "Config permissions for '{$user->role}': " . json_encode($configPerms) . "\n";

    $canManageIncoming = Illuminate\Support\Facades\Gate::forUser($user)->allows('manage_incoming');
    echo "Gate check 'manage_incoming': " . ($canManageIncoming ? "YES" : "NO") . "\n";

    $defined = config('role_permissions.defined_permissions');
    echo "'manage_incoming' in defined_permissions? " . (in_array('manage_incoming', $defined) ? "YES" : "NO") . "\n";

} else {
    echo "User NOT FOUND\n";
}
