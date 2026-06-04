<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private const CREATABLE_ROLES = ['department', 'pair'];

    public function index(Request $request)
    {
        $query = User::with('department')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('profile_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('Users.Index', ['users' => $users]);
    }

    public function create()
    {
        return view('Users.Create', [
            'roles' => self::CREATABLE_ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100|unique:users,name',
            'profile_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(self::CREATABLE_ROLES)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'profile_name' => $validated['profile_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        AuditLogService::logUserCreated($user->id, [
            'name' => $user->name,
            'profile_name' => $user->profile_name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        if ($request->wantsJson()) {
            return response()->json($user, 201);
        }

        return redirect()->route('users.index')->with('success', 'Account created successfully.');
    }

    public function show(User $user)
    {
        $user->load('department', 'organizations');

        if (request()->wantsJson()) {
            return response()->json($user);
        }

        return view('Users.Show', ['user' => $user]);
    }

    public function edit(User $user)
    {
        return view('Users.Edit', [
            'user' => $user,
            'roles' => array_merge(self::CREATABLE_ROLES, ['org', 'admin', 'super_admin']),
            'departments' => User::departments()->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100', Rule::unique('users')->ignore($user->id)],
            'profile_name' => 'required|string|min:2|max:150',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,pair,department,org',
            'department_id' => [
                'nullable',
                'exists:users,id',
                Rule::requiredIf(fn () => $request->input('role') === 'org'),
            ],
        ]);

        if ($validated['role'] === 'org') {
            $dept = User::where('id', $validated['department_id'] ?? null)
                ->where('role', 'department')
                ->exists();
            if (! $dept) {
                return back()->withErrors(['department_id' => 'Organizations must belong to a department account.']);
            }
        } else {
            $validated['department_id'] = null;
        }

        $changes = [];
        foreach (['name', 'profile_name', 'email', 'role', 'department_id'] as $field) {
            if (($user->{$field} ?? null) != ($validated[$field] ?? null)) {
                $changes[$field] = ['old' => $user->{$field}, 'new' => $validated[$field] ?? null];
            }
        }

        if ($request->filled('password')) {
            $changes['password'] = ['old' => '****', 'new' => '****'];
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (! empty($changes)) {
            AuditLogService::logUserUpdated($user->id, $changes);
        }

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('users.show', $user)->with('success', 'User updated successfully');
    }

    public function destroy(User $user, Request $request)
    {
        if ($user->id === auth()->id()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot delete your own account'], 403);
            }

            return redirect()->back()->with('error', 'You cannot delete your own account');
        }

        $userData = [
            'name' => $user->name,
            'profile_name' => $user->profile_name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $user->delete();

        AuditLogService::logUserDeleted($user->id, $userData);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
