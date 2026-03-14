<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest can access login page
     */
    public function test_guest_can_access_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.2-sign-in');
    }

    /**
     * Test that authenticated user is redirected from login page
     */
    public function test_authenticated_user_redirected_from_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        // When already logged in, user should be redirected to dashboard
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test successful login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->followingRedirects()->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200); // After following redirects, user should be on dashboard
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'valid@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'valid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test login validation
     */
    public function test_login_requires_valid_data(): void
    {
        $response = $this->post('/login', [])
                         ->assertRedirect('/login');

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Test successful logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->post('/logout');

        $response->assertStatus(302); // Should redirect after logout
        $this->assertGuest();
    }

    /**
     * Test administrator role can access user management
     */
    public function test_administrator_role_can_access_user_management(): void
    {
        // Get or create the 'view users' permission and 'administrator' role
        $permission = Permission::firstOrCreate(['name' => 'view users']);
        $role = Role::firstOrCreate(['name' => 'administrator']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
    }

    /**
     * Test staff role cannot access user management
     */
    public function test_staff_role_cannot_access_user_management(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test user with 'view users' permission can access users
     */
    public function test_user_with_view_users_permission_can_access_users(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view users']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
    }

    /**
     * Test user without 'view users' permission cannot access users
     */
    public function test_user_without_view_users_permission_cannot_access_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test administrator can access role management
     */
    public function test_administrator_can_access_role_management(): void
    {
        // Get or create required permissions
        $permission = Permission::firstOrCreate(['name' => 'manage system']);

        $role = Role::firstOrCreate(['name' => 'administrator']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/users/roles');

        $response->assertStatus(200);
    }

    /**
     * Test staff cannot access role management
     */
    public function test_staff_cannot_access_role_management(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/users/roles');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test that unauthorized users cannot create users
     */
    public function test_unauthorized_user_cannot_create_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/users/create');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test user with create users permission can create users
     */
    public function test_user_with_create_users_permission_can_create_users(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'create users']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/users/create');

        $response->assertStatus(200);
        $response->assertViewIs('users.create');
    }

    /**
     * Test user with edit users permission can edit users
     */
    public function test_user_with_edit_users_permission_can_edit_users()
    {
        $permission = Permission::firstOrCreate(['name' => 'edit users']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        // Create a target user to edit
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)
                         ->get('/users/' . $targetUser->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewIs('users.edit_user');
    }

    /**
     * Test unauthorized access to profile page
     */
    public function test_unauthenticated_user_redirected_to_login_for_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    /**
     * Test authenticated user can access profile page
     */
    public function test_authenticated_user_can_access_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertViewIs('users.profile');
    }
}