<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::where('name', 'administrator')->first();

if ($role) {
    echo "Role: " . $role->name . "\n";
    echo "Role ID: " . $role->id . "\n";
    echo "\nPermissions assigned to this role:\n";
    $permissions = $role->permissions;
    if ($permissions->count() > 0) {
        foreach ($permissions as $permission) {
            echo "  - " . $permission->name . " (ID: " . $permission->id . ")\n";
        }
    } else {
        echo "  No permissions assigned!\n";
    }
} else {
    echo "Administrator role not found.\n";
}

echo "\n\nAll permissions in database:\n";
$allPermissions = Permission::all();
foreach ($allPermissions as $permission) {
    echo "  - " . $permission->name . " (ID: " . $permission->id . ", Guard: " . $permission->guard_name . ")\n";
}
