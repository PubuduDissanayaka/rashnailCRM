<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the admin user if it doesn't exist
        $adminUser = User::updateOrCreate(
            [
                'email' => 'admin@rashnail.com',
            ],
            [
                'name' => 'Rash Nail',
                'email' => 'admin@rashnail.com',
                'password' => Hash::make('password'), // Default password is 'password'
                'role' => 'administrator', // Keep for backward compatibility
            ]
        );

        // Ensure the administrator role exists and assign it to the admin user
        $adminRole = Role::firstOrCreate(['name' => 'administrator'], ['name' => 'administrator', 'guard_name' => 'web']);

        // Assign the administrator role to the admin user
        $adminUser->assignRole($adminRole);
    }
}
