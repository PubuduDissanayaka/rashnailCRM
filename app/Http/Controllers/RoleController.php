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

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $request->name]);

        // Determine the type of action
        if ($request->input('action') === 'assign_users') {
            // Only assign users to this role if specified
            if ($request->has('assign_users')) {
                foreach ($request->assign_users as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->assignRole($role->name);
                    }
                }
            }
        } else {
            // Update role name and permissions (not user assignments)
            // Sync permissions to the role
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                // If no permissions sent, remove all (in case user unchecked all)
                $role->syncPermissions([]);
            }

            // Assign users to this role if specified (for the main edit form)
            if ($request->has('assign_users')) {
                foreach ($request->assign_users as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->assignRole($role->name);
                    }
                }
            }
        }

        return redirect()->route('users.role-details', $role->name)->with('success', 'Role updated successfully.');
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
