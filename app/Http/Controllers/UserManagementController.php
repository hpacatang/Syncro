<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::orderBy('name');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('Users.Index', ['users' => $users]);
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('Users.Create', [
            'roles' => ['admin', 'pair', 'org', 'user'],
        ]);
    }

    /**
     * Store a newly created user in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,pair,org,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Log the user creation
        AuditLogService::logUserCreated($user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        if ($request->wantsJson()) {
            return response()->json($user, 201);
        }

        return redirect()->route('users.show', $user)->with('success', 'User created successfully');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        if (request()->wantsJson()) {
            return response()->json($user);
        }

        return view('Users.Show', ['user' => $user]);
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        return view('Users.Edit', [
            'user' => $user,
            'roles' => ['admin', 'pair', 'org', 'user'],
        ]);
    }

    /**
     * Update the specified user in storage
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,pair,org,user',
        ]);

        $changes = [];

        // Track changes for audit log
        if ($user->name !== $validated['name']) {
            $changes['name'] = ['old' => $user->name, 'new' => $validated['name']];
        }
        if ($user->email !== $validated['email']) {
            $changes['email'] = ['old' => $user->email, 'new' => $validated['email']];
        }
        if ($user->role !== $validated['role']) {
            $changes['role'] = ['old' => $user->role, 'new' => $validated['role']];
        }
        if ($request->filled('password')) {
            $changes['password'] = ['old' => '****', 'new' => '****'];
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        // Log the update if there were changes
        if (!empty($changes)) {
            AuditLogService::logUserUpdated($user->id, $changes);
        }

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.show', $user)->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user, Request $request)
    {
        // Prevent deleting the currently authenticated user
        if ($user->id === auth()->id()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot delete your own account'], 403);
            }
            return redirect()->back()->with('error', 'You cannot delete your own account');
        }

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $user->delete();

        // Log the deletion
        AuditLogService::logUserDeleted($user->id, $userData);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
