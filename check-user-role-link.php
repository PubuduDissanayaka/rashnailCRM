<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::first();

echo "User: " . $user->email . "\n";
echo "User ID: " . $user->id . "\n";
echo "User Guard: " . config('auth.defaults.guard') . "\n\n";

// Check model_has_roles table
$modelHasRoles = DB::table('model_has_roles')
    ->where('model_id', $user->id)
    ->where('model_type', get_class($user))
    ->get();

echo "Roles assigned to user (from model_has_roles table):\n";
if ($modelHasRoles->count() > 0) {
    foreach ($modelHasRoles as $link) {
        $role = Spatie\Permission\Models\Role::find($link->role_id);
        echo "  - Role ID: " . $link->role_id . " (" . ($role ? $role->name : 'Unknown') . ")\n";
    }
} else {
    echo "  No roles assigned in database!\n";
}

echo "\n\nUsing getRoleNames():\n";
foreach ($user->getRoleNames() as $roleName) {
    echo "  - " . $roleName . "\n";
}

// Try to check permission with guard
echo "\n\nTrying hasPermissionTo with guard:\n";
echo "hasPermissionTo('view users', 'web'): " . ($user->hasPermissionTo('view users', 'web') ? 'Yes' : 'No') . "\n";
