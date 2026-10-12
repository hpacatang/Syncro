<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private const CREATABLE_ROLES = ['department', 'pair'];

    public function accountRequests()
    {
        $users = User::with('department')
            ->where('account_status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return view('Users.AccountRequests', ['users' => $users]);
    }

    public function approveAccountRequest(User $user)
    {
        abort_unless($user->role === 'org', 404);
        if ($user->account_status !== 'pending') {
            return back()->with('error', 'This account request has already been reviewed.');
        }

        $user->update(['account_status' => 'approved']);
        AuditLogService::logUserUpdated($user->id, [
            'account_status' => ['old' => 'pending', 'new' => 'approved'],
        ]);

        return back()->with('success', 'Account request approved.');
    }

    public function rejectAccountRequest(User $user)
    {
        abort_unless($user->role === 'org', 404);
        if ($user->account_status !== 'pending') {
            return back()->with('error', 'This account request has already been reviewed.');
        }

        $user->update(['account_status' => 'rejected']);
        AuditLogService::logUserUpdated($user->id, [
            'account_status' => ['old' => 'pending', 'new' => 'rejected'],
        ]);

        return back()->with('success', 'Account request rejected.');
    }

    public function index(Request $request)
    {
        $query = User::with('department')->orderBy('name');
        $departmentManager = $this->isDepartmentManager($request);

        if ($departmentManager) {
            $query->where('role', 'org')
                ->where('department_id', $request->user()->department_id)
                ->where('account_status', 'approved');
        }

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

    public function create(Request $request)
    {
        if ($this->isDepartmentManager($request)) {
            abort_unless($request->user()->department_id, 403, 'Your account is not assigned to a department.');
        }

        return view('Users.Create', [
            'roles' => $this->isDepartmentManager($request) ? ['org'] : self::CREATABLE_ROLES,
            'departments' => $this->isDepartmentManager($request)
                ? collect([$request->user()->department])
                : Department::orderBy('department_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $departmentManager = $this->isDepartmentManager($request);
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100|unique:users,name',
            'profile_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in($departmentManager ? ['org'] : self::CREATABLE_ROLES)],
            'department_id' => [
                'nullable',
                'exists:departments,id',
                Rule::requiredIf(fn () => ! $departmentManager && $request->input('role') === 'department'),
            ],
        ]);

        if ($departmentManager && ! $request->user()->department_id) {
            abort(403, 'Your account is not assigned to a department.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'profile_name' => $validated['profile_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $departmentManager
                ? $request->user()->department_id
                : ($validated['department_id'] ?? null),
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

        return redirect()->route($departmentManager ? 'department.users.index' : 'users.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeDepartmentManagedUser($request, $user);
        $user->load('department', 'organizations');

        if (request()->wantsJson()) {
            return response()->json($user);
        }

        return view('Users.Show', ['user' => $user]);
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeDepartmentManagedUser($request, $user);
        $departmentManager = $this->isDepartmentManager($request);
        if ($departmentManager) {
            abort_unless($request->user()->department_id, 403, 'Your account is not assigned to a department.');
        }

        return view('Users.Edit', [
            'user' => $user,
            'roles' => $departmentManager ? ['org'] : array_merge(self::CREATABLE_ROLES, ['org', 'admin', 'super_admin']),
            'departments' => $departmentManager
                ? collect([$request->user()->department])
                : Department::orderBy('department_name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeDepartmentManagedUser($request, $user);
        $departmentManager = $this->isDepartmentManager($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100', Rule::unique('users')->ignore($user->id)],
            'profile_name' => 'required|string|min:2|max:150',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in($departmentManager ? ['org'] : ['super_admin', 'admin', 'pair', 'department', 'org'])],
            'department_id' => [
                'nullable',
                'exists:departments,id',
                Rule::requiredIf(fn () => ! $departmentManager && in_array($request->input('role'), ['org', 'department'], true)),
            ],
        ]);

        if ($departmentManager) {
            $validated['department_id'] = $request->user()->department_id;
        } elseif (in_array($validated['role'], ['org', 'department'], true)) {
            if (empty($validated['department_id'])) {
                return back()->withErrors(['department_id' => 'This role must belong to a department.']);
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

        return redirect()->route($departmentManager ? 'department.users.show' : 'users.show', $user)
            ->with('success', 'User updated successfully');
    }

    public function destroy(User $user, Request $request)
    {
        $this->authorizeDepartmentManagedUser($request, $user);
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

        return redirect()->route($this->isDepartmentManager($request) ? 'department.users.index' : 'users.index')
            ->with('success', 'User deleted successfully');
    }

    private function isDepartmentManager(Request $request): bool
    {
        return $request->routeIs('department.users.*');
    }

    private function authorizeDepartmentManagedUser(Request $request, User $user): void
    {
        if (! $this->isDepartmentManager($request)) {
            return;
        }

        $departmentId = $request->user()->department_id;
        abort_unless(
            $departmentId !== null
                && $user->isOrg()
                && $user->account_status === 'approved'
                && (int) $user->department_id === (int) $departmentId,
            404
        );
    }
}
