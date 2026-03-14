<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "=== User and Role Count Analysis ===\n\n";

// Total users
echo "1. Total Users in Database: " . User::count() . "\n\n";

// Users by role field
echo "2. Users by 'role' field:\n";
echo "   - administrator: " . User::where('role', 'administrator')->count() . "\n";
echo "   - staff: " . User::where('role', 'staff')->count() . "\n";
echo "   - other: " . User::whereNotIn('role', ['administrator', 'staff'])->count() . "\n\n";

// Spatie roles
echo "3. Spatie Roles:\n";
$roles = Role::with('users')->get();
foreach ($roles as $role) {
    echo "   - {$role->name}: " . $role->users->count() . " users (Spatie relationship)\n";
}

echo "\n4. Detailed Spatie Role Assignments:\n";
foreach ($roles as $role) {
    echo "   {$role->name} role:\n";
    if ($role->users->count() > 0) {
        foreach ($role->users as $user) {
            echo "      - {$user->name} ({$user->email}) [role field: {$user->role}]\n";
        }
    } else {
        echo "      (no users assigned)\n";
    }
    echo "\n";
}

// Users without Spatie roles
$usersWithoutRoles = User::doesntHave('roles')->get();
echo "5. Users without Spatie roles: " . $usersWithoutRoles->count() . "\n";
if ($usersWithoutRoles->count() > 0) {
    foreach ($usersWithoutRoles as $user) {
        echo "   - {$user->name} ({$user->email}) [role field: {$user->role}]\n";
    }
}

echo "\n=== End of Analysis ===\n";
