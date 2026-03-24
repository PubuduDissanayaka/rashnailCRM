<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        $roles = Role::with(['users', 'permissions'])->get();

        return view('users.roles', compact('roles'));
    }

    public function show($roleName)
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        // Find the role by name
        $role = Role::where('name', $roleName)->firstOrFail();

        // Load role with its permissions and users, including detailed user information
        $role->loadMissing(['permissions', 'users' => function($query) {
            $query->with(['roles']);
        }]);

        $allUsers = User::with('roles')->get(); // Load all users with their roles
        $allPermissions = Permission::all();

        return view('users.role-details', compact('role', 'allUsers', 'allPermissions'));
    }

    public function permissions()
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        $permissions = Permission::with('roles', 'users')->get();
        $allRoles = Role::all();

        return view('users.permissions', compact('permissions', 'allRoles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        // Assign permissions to the role if specified
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('users.roles')->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        // ── Assign Users action ───────────────────────────────
        if ($request->input('action') === 'assign_users') {
            $userIds = $request->input('assign_users', []);

            if (empty($userIds)) {
                return redirect()->route('users.role-details', $role->name)
                    ->with('error', 'No users selected.');
            }

            $assigned = 0;
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->syncRoles([$role->name]); // replaces any existing role
                    $user->update(['role' => $role->name]);
                    $assigned++;
                }
            }

            return redirect()->route('users.role-details', $role->name)
                ->with('success', $assigned . ' user(s) assigned to ' . $role->name . ' successfully.');
        }

        // ── Edit role name + permissions action ───────────────
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        // Only rename if not a protected role
        if (!in_array($role->name, ['administrator', 'staff'])) {
            $role->update(['name' => $request->name]);
        }

        // Sync permissions
        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('users.role-details', $role->name)
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        // Don't allow deleting the default roles
        if (in_array($role->name, ['administrator', 'staff'])) {
            return redirect()->back()->with('error', 'Cannot delete default roles.');
        }

        $role->delete();

        return redirect()->route('users.roles')->with('success', 'Role deleted successfully.');
    }

    /**
     * Update a specific permission
     */
    public function updatePermission(Request $request, $permissionId)
    {
        abort_unless(auth()->user()->can('manage system'), 403);

        $permission = Permission::findById($permissionId);

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        // Update permission name
        $permission->update(['name' => $request->name]);

        // Sync roles with the permission if provided
        if ($request->has('roles')) {
            $roles = Role::whereIn('name', $request->roles)->get();
            $permission->roles()->sync($roles);
        }

        return redirect()->route('users.permissions')->with('success', 'Permission updated successfully.');
    }
}
