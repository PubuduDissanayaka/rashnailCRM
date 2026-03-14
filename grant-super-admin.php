<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::first();

if ($user) {
    // Grant Super Admin (bypasses all permission checks)
    $user->givePermissionTo(Spatie\Permission\Models\Permission::all());

    echo "User: " . $user->email . "\n";
    echo "Direct permissions granted: " . $user->permissions->count() . "\n";
    echo "All permissions (role + direct): " . $user->getAllPermissions()->count() . "\n";

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    echo "\nPermission cache cleared.\n";
    echo "Testing permission check...\n";
    echo "Has 'view users' permission: " . ($user->hasPermissionTo('view users') ? 'Yes' : 'No') . "\n";
} else {
    echo "No users found.\n";
}
