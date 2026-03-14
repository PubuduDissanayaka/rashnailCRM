<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['guard_name' => 'web']);

        $staffMembers = [
            [
                'name' => 'Nimal Perera',
                'email' => 'nimal@rashnail.com',
                'phone' => '+94771234567',
                'role' => 'staff',
            ],
            [
                'name' => 'Kamani Silva',
                'email' => 'kamani@rashnail.com',
                'phone' => '+94772345678',
                'role' => 'staff',
            ],
            [
                'name' => 'Dilani Fernando',
                'email' => 'dilani@rashnail.com',
                'phone' => '+94773456789',
                'role' => 'staff',
            ],
            [
                'name' => 'Priya Jayawardena',
                'email' => 'priya@rashnail.com',
                'phone' => '+94774567890',
                'role' => 'staff',
            ],
        ];

        foreach ($staffMembers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                    'phone' => $data['phone'],
                    'role' => $data['role'],
                    'status' => 'active',
                    'slug' => Str::slug($data['name']) . '-' . Str::random(4),
                ]
            );
            $user->assignRole($staffRole);
        }

        $this->command->info('Staff users seeded successfully.');
    }
}
