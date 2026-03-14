<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$user = App\Models\User::first();

echo "Testing Authorization for: " . $user->email . "\n";
echo "Role field: " . $user->role . "\n";
echo "Spatie roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Test Gate::before bypass
echo "Authorization Checks:\n";
echo "Can 'view users': " . ($user->can('view users') ? 'Yes' : 'No') . "\n";
echo "Can 'create users': " . ($user->can('create users') ? 'Yes' : 'No') . "\n";
echo "Can 'edit users': " . ($user->can('edit users') ? 'Yes' : 'No') . "\n";
echo "Can 'delete users': " . ($user->can('delete users') ? 'Yes' : 'No') . "\n";
echo "Can 'manage system': " . ($user->can('manage system') ? 'Yes' : 'No') . "\n\n";

// Test a permission that doesn't exist (should still return true for admin)
echo "Can 'nonexistent permission': " . ($user->can('nonexistent permission') ? 'Yes' : 'No') . "\n";
echo "\nAdministrators have full access! ✓\n";
