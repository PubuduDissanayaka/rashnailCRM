<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::first();

if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
    echo "Has 'view users' permission (can): " . ($user->can('view users') ? 'Yes' : 'No') . "\n";
    echo "Has 'view users' permission (hasPermissionTo): " . ($user->hasPermissionTo('view users') ? 'Yes' : 'No') . "\n";
    echo "Has administrator role (hasRole): " . ($user->hasRole('administrator') ? 'Yes' : 'No') . "\n";
    echo "\nAll permissions:\n";
    foreach ($user->getAllPermissions() as $permission) {
        echo "  - " . $permission->name . "\n";
    }
} else {
    echo "No users found.\n";
}
