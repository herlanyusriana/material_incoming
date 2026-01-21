<?php
$users = App\Models\User::all();
echo "Total Users: " . $users->count() . "\n";
foreach ($users as $u) {
    echo "ID: " . $u->id . ", Name: " . $u->name . ", Email: " . $u->email . ", Role: " . $u->role . "\n";
}
