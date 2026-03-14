<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions that match the ones in the RoleSeeder
        Permission::create(['name' => 'view customers']);
        Permission::create(['name' => 'create customers']);
        Permission::create(['name' => 'edit customers']);
        Permission::create(['name' => 'delete customers']);

        Permission::create(['name' => 'view appointments']);
        Permission::create(['name' => 'create appointments']);
        Permission::create(['name' => 'edit appointments']);
        Permission::create(['name' => 'delete appointments']);

        Permission::create(['name' => 'view services']);
        Permission::create(['name' => 'create services']);
        Permission::create(['name' => 'edit services']);
        Permission::create(['name' => 'delete services']);

        Permission::create(['name' => 'process transactions']);
        Permission::create(['name' => 'view transactions']);
        Permission::create(['name' => 'delete transactions']);

        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);

        Permission::create(['name' => 'view reports']);
        Permission::create(['name' => 'manage system']);
    }

    /**
     * Test administrator role has all permissions
     */
    public function test_administrator_has_all_permissions(): void
    {
        $administrator = Role::create(['name' => 'administrator']);
        $allPermissions = Permission::all();
        
        $user = User::factory()->create();
        $user->assignRole($administrator);
        $user->syncPermissions($allPermissions);

        foreach ($allPermissions as $permission) {
            $this->assertTrue($user->can($permission->name), "Administrator should have permission: {$permission->name}");
        }
    }

    /**
     * Test staff role has limited permissions
     */
    public function test_staff_has_limited_permissions(): void
    {
        $staff = Role::create(['name' => 'staff']);
        
        $limitedPermissions = collect([
            'view customers',
            'create customers',
            'edit customers',
            'view appointments',
            'create appointments',
            'edit appointments',
            'view services',
            'process transactions',
            'view transactions'
        ])->map(function ($permissionName) {
            return Permission::firstWhere('name', $permissionName);
        })->filter();

        $user = User::factory()->create();
        $user->assignRole($staff);
        $user->syncPermissions($limitedPermissions);

        // Check that staff has the allowed permissions
        foreach ($limitedPermissions as $permission) {
            $this->assertTrue($user->can($permission->name), "Staff should have permission: {$permission->name}");
        }

        // Check that staff doesn't have other permissions (like manage system)
        $this->assertFalse($user->can('manage system'));
        $this->assertFalse($user->can('delete users'));
        $this->assertFalse($user->can('create users'));
    }

    /**
     * Test unauthorized access to user management pages
     */
    public function test_unauthorized_user_cannot_access_user_management_pages(): void
    {
        $user = User::factory()->create(); // Regular user with no permissions

        // Test access to various user management pages
        $pages = [
            '/users',
            '/users/create',
            '/users/roles',
            '/users/permissions',
            '/users/role-details/1', // This would fail anyway due to missing role, but should still be 403, not 401/404
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);
            $response->assertStatus(403, "User should not have access to {$page}");
        }
    }

    /**
     * Test specific permission requirements for user CRUD operations
     */
    public function test_specific_permissions_for_user_operations(): void
    {
        // Test view users permission
        $viewUsersPermission = Permission::firstWhere('name', 'view users');
        $userWithViewPermission = User::factory()->create();
        $userWithViewPermission->givePermissionTo($viewUsersPermission);

        $response = $this->actingAs($userWithViewPermission)->get('/users');
        $response->assertStatus(200);

        // Test create users permission
        $createUsersPermission = Permission::firstWhere('name', 'create users');
        $userWithCreatePermission = User::factory()->create();
        $userWithCreatePermission->givePermissionTo($createUsersPermission);

        $response = $this->actingAs($userWithCreatePermission)->get('/users/create');
        $response->assertStatus(200);

        // Test edit users permission
        $editUsersPermission = Permission::firstWhere('name', 'edit users');
        $userWithEditPermission = User::factory()->create();
        $userWithEditPermission->givePermissionTo($editUsersPermission);

        // Create a target user to edit
        $targetUser = User::factory()->create();
        $response = $this->actingAs($userWithEditPermission)->get("/users/{$targetUser->id}/edit");
        $response->assertStatus(200);

        // Test delete users permission
        $deleteUsersPermission = Permission::firstWhere('name', 'delete users');
        $userWithDeletePermission = User::factory()->create();
        $userWithDeletePermission->givePermissionTo($deleteUsersPermission);

        // This is a POST request, so we'll check if the permission is respected during the delete operation
        $this->assertTrue($userWithDeletePermission->can('delete users'));
    }

    /**
     * Test unauthorized access to role management pages
     */
    public function test_unauthorized_user_cannot_access_role_management_pages(): void
    {
        $user = User::factory()->create(); // Regular user with no permissions

        $role = Role::create(['name' => 'test-role']);
        
        $pages = [
            '/users/roles',
            "/users/role-details/{$role->id}",
            '/users/permissions',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);
            $response->assertStatus(403, "User should not have access to {$page}");
        }
    }

    /**
     * Test authorized user can access role management pages
     */
    public function test_user_with_manage_system_permission_can_access_role_management(): void
    {
        $manageSystemPermission = Permission::firstWhere('name', 'manage system');
        $user = User::factory()->create();
        $user->givePermissionTo($manageSystemPermission);

        $pages = [
            '/users/roles' => 200,
            '/users/permissions' => 200,
        ];

        foreach ($pages as $page => $expectedStatus) {
            $response = $this->actingAs($user)->get($page);
            $response->assertStatus($expectedStatus, "User with manage system permission should have access to {$page}");
        }
    }

    /**
     * Test that role assignment works correctly
     */
    public function test_role_assignment_and_checking(): void
    {
        $adminRole = Role::create(['name' => 'administrator']);
        $staffRole = Role::create(['name' => 'staff']);
        
        $adminUser = User::factory()->create();
        $adminUser->assignRole($adminRole);

        $staffUser = User::factory()->create();
        $staffUser->assignRole($staffRole);

        $this->assertTrue($adminUser->hasRole('administrator'));
        $this->assertFalse($adminUser->hasRole('staff'));
        
        $this->assertTrue($staffUser->hasRole('staff'));
        $this->assertFalse($staffUser->hasRole('administrator'));
    }

    /**
     * Test permission assignment and checking
     */
    public function test_permission_assignment_and_checking(): void
    {
        $createUsersPermission = Permission::firstWhere('name', 'create users');
        $viewUsersPermission = Permission::firstWhere('name', 'view users');
        
        $user = User::factory()->create();
        $user->givePermissionTo($createUsersPermission);

        $this->assertTrue($user->can('create users'));
        $this->assertFalse($user->can('view users'));
        
        // Test assigning multiple permissions
        $user->givePermissionTo($viewUsersPermission);
        $this->assertTrue($user->can('create users'));
        $this->assertTrue($user->can('view users'));
    }

    /**
     * Test role-based access to profile update
     */
    public function test_profile_update_access(): void
    {
        $user = User::factory()->create();
        
        // Should be able to access profile edit page
        $response = $this->actingAs($user)->get('/profile/edit');
        $response->assertStatus(200);
        $response->assertViewIs('users.edit');

        // Should be able to update profile
        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
        $response->assertRedirect('/profile');
        
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    /**
     * Test that users cannot access other users' edit pages without proper permissions
     */
    public function test_user_cannot_access_other_user_edit_without_permission(): void
    {
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);

        // user1 tries to access user2's edit page
        $response = $this->actingAs($user1)
                         ->get("/users/{$user2->id}/edit");

        // Should be forbidden if user1 doesn't have edit users permission
        $response->assertStatus(403);
    }
}