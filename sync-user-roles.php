<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "=== Syncing User Roles ===\n\n";

// Get all users
$users = User::all();
echo "Found " . $users->count() . " total users\n\n";

$synced = 0;
$skipped = 0;

foreach ($users as $user) {
    $roleField = $user->role;

    if (!$roleField) {
        echo "⚠ User {$user->name} ({$user->email}) has no role field - skipping\n";
        $skipped++;
        continue;
    }

    // Check if Spatie role exists
    $spatieRole = Role::where('name', $roleField)->first();

    if (!$spatieRole) {
        echo "⚠ Role '{$roleField}' doesn't exist in Spatie roles - skipping user {$user->name}\n";
        $skipped++;
        continue;
    }

    // Check if user already has this role
    if ($user->hasRole($roleField)) {
        echo "✓ User {$user->name} already has Spatie role '{$roleField}'\n";
        $skipped++;
    } else {
        // Assign the role
        $user->assignRole($roleField);
        echo "✅ Assigned '{$roleField}' role to {$user->name} ({$user->email})\n";
        $synced++;
    }
}

echo "\n=== Sync Complete ===\n";
echo "✅ Synced: $synced users\n";
echo "⏭️ Skipped: $skipped users\n\n";

// Verify the results
echo "=== Verification ===\n";
$roles = Role::with('users')->get();
foreach ($roles as $role) {
    echo "{$role->name}: " . $role->users->count() . " users\n";
}

echo "\n✅ All done! The roles page should now show correct counts.\n";
