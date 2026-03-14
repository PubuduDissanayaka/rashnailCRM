<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::first();

echo "User: " . $user->email . "\n";
echo "Role field value: " . $user->role . "\n";
echo "Spatie roles count: " . $user->roles()->count() . "\n";
echo "Spatie role names: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "Direct permissions count: " . $user->permissions()->count() . "\n";
