<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'admin@rashnail.com')->first();

if ($user) {
    echo "✓ Admin user found!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Email:    admin@rashnail.com\n";
    echo "Password: password\n";
    echo "Name:     {$user->name}\n";
    echo "Role:     {$user->role}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "Spatie Roles:\n";
    foreach ($user->roles as $role) {
        echo "  • {$role->name}\n";
    }

    echo "\nPermissions ({$user->getAllPermissions()->count()}):\n";
    foreach ($user->getAllPermissions() as $permission) {
        echo "  ✓ {$permission->name}\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "You can now login with:\n";
    echo "  Email:    admin@rashnail.com\n";
    echo "  Password: password\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
} else {
    echo "✗ Admin user not found! Running seeder...\n";
    Artisan::call('db:seed', ['--class' => 'AdminUserSeeder']);
    Artisan::call('db:seed', ['--class' => 'RoleSeeder']);
    echo "✓ Seeders completed. Try running this script again.\n";
}
