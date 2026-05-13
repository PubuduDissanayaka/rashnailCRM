<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Normalize user role assignments.
     * - Creates Spatie roles for any role names found in users.role column
     * - Creates Spatie role assignments (model_has_roles) for users missing them
     * - Preserves custom role names (Manager, Staff L4, Reception, etc.)
     * - Only the administrator role is treated as admin; everything else is staff
     */
    public function up(): void
    {
        // Ensure administrator role exists (gets all permissions via RoleSeeder)
        Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);

        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $roleColumn = trim($user->role ?? '');

            if (empty($roleColumn)) {
                continue; // Skip users with no role
            }

            // Determine the Spatie role name
            $spatieRoleName = $roleColumn;

            // Fix known typos
            $typoFixes = [
                'Manger' => 'Manager',
                'manger' => 'Manager',
                'Adminstrator' => 'Administrator',
            ];
            if (isset($typoFixes[$roleColumn])) {
                $spatieRoleName = $typoFixes[$roleColumn];
                DB::table('users')->where('id', $user->id)->update(['role' => $spatieRoleName]);
            }

            // Ensure the Spatie role exists
            $role = Role::firstOrCreate(
                ['name' => $spatieRoleName, 'guard_name' => 'web']
            );

            // Check if user already has a Spatie role assignment
            $hasSpatieRole = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->exists();

            if (! $hasSpatieRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migration (non-destructive — we don't remove roles).
     */
    public function down(): void
    {
        // Intentionally blank — we don't destroy role data
    }
};
