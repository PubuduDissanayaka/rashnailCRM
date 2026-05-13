<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Normalize user roles and ensure Spatie role assignments.
     */
    public function up(): void
    {
        // Ensure Spatie roles exist
        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Map non-standard role values to standard ones
        $roleMap = [
            'Administrator' => 'administrator',
            'Admin' => 'administrator',
            'Manger' => 'staff',        // typo fix
            'Manager' => 'staff',
            'Staff L4' => 'staff',       // custom level → staff
            'Staff L3' => 'staff',
            'Staff L2' => 'staff',
            'Staff L1' => 'staff',
        ];

        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $roleColumn = $user->role;
            $normalizedRole = null;

            // Check if it needs normalization
            if (isset($roleMap[$roleColumn])) {
                $normalizedRole = $roleMap[$roleColumn];
            } elseif (stripos($roleColumn, 'admin') !== false) {
                $normalizedRole = 'administrator';
            } elseif (stripos($roleColumn, 'staff') !== false || stripos($roleColumn, 'manager') !== false) {
                $normalizedRole = 'staff';
            }

            // Update the role column if needed
            if ($normalizedRole && $normalizedRole !== $roleColumn) {
                DB::table('users')->where('id', $user->id)->update(['role' => $normalizedRole]);
            }

            $targetRole = $normalizedRole ?? $roleColumn;

            // Ensure Spatie role assignment exists
            $spatieRoleId = ($targetRole === 'administrator' || stripos($targetRole, 'admin') !== false)
                ? $adminRole->id
                : $staffRole->id;

            $hasSpatieRole = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->exists();

            if (! $hasSpatieRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $spatieRoleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migration (no destructive rollback).
     */
    public function down(): void
    {
        // Intentionally left blank — we don't reverse data normalization
    }
};
