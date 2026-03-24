<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        // Check if user has permission to view users
        $this->authorize('view users');

        $users = User::all();
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Display users in contacts format.
     */
    public function contacts()
    {
        // Check if user has permission to view users
        $this->authorize('view users');

        $users = User::all();

        return view('users.contacts', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('create users');

        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $this->authorize('create users');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', \Illuminate\Validation\Rule::in(\Spatie\Permission\Models\Role::pluck('name'))],
            'status' => 'nullable|in:active,inactive,suspended',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
        ];

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $avatarExtension = $avatarFile->getClientOriginalExtension();
            $avatarFileName = time() . '_' . str_replace(' ', '_', strtolower($request->name)) . '.' . $avatarExtension;

            // Store the avatar in the public/avatars directory
            $avatarPath = $avatarFile->storeAs('public/avatars', $avatarFileName);

            $userData['avatar'] = $avatarFileName;
        }

        $user = User::create($userData);

        // Assign the role using Spatie permissions
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Allow admins to edit any user, but regular users can only edit themselves
        if (!auth()->user()->can('edit users') && $user->id !== Auth::id()) {
            abort(403, 'You are not authorized to edit other users.');
        }

        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('users.edit_user', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        // Allow admins to edit any user, but regular users can only edit themselves
        if (!auth()->user()->can('edit users') && $user->id !== Auth::id()) {
            abort(403, 'You are not authorized to edit other users.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => ['required', \Illuminate\Validation\Rule::in(\Spatie\Permission\Models\Role::pluck('name'))],
            'status' => 'required|in:active,inactive,suspended',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
        ];

        // Only update phone if provided
        if ($request->filled('phone')) {
            $userData['phone'] = $request->phone;
        }

        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = bcrypt($request->password);
        }

        $user->update($userData);

        // Sync the role using Spatie permissions
        $user->syncRoles($request->role);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Check if user has permission to view users
        $this->authorize('view users');

        // Allow the user to view their own profile, or users with view permission to view any user
        if (!auth()->user()->can('view users') && $user->id !== Auth::id()) {
            abort(403, 'You are not authorized to view this user profile.');
        }

        return view('users.show_user', compact('user'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete users');

        // Prevent users from deleting themselves
        if (Auth::id() === $user->id) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['message' => 'You cannot delete yourself.'], 403);
            }
            return redirect()->route('users.index')->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['message' => 'User deleted successfully.']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
    /**
     * Remove multiple users.
     */
    public function bulkDestroy(Request $request)
    {
        $this->authorize('delete users');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:users,id',
        ]);

        $ids = $request->ids;

        // Prevent users from deleting themselves
        if (in_array(Auth::id(), $ids)) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        User::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Users deleted successfully.']);
    }
}
