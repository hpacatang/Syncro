<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            AuditLogService::logLogin();

            $user = auth()->user();

            if ($user->isStaffReviewer()) {
                return redirect()->intended(route('dashboard'));
            }

            if ($user->canSubmitPosts()) {
                return redirect()->intended(route('org.dashboard'));
            }

            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('users.index'));
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'name' => 'Invalid username or password!',
        ])->onlyInput('name');
    }

    public function register()
    {
        return view('auth.register', [
            'departments' => User::departments()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100|unique:users,name',
            'profile_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'department_id' => 'required|exists:users,id',
            'password' => 'required|string|min:6',
            'password2' => 'required|string|same:password',
        ]);

        $department = User::where('id', $validated['department_id'])
            ->where('role', 'department')
            ->first();

        if (! $department) {
            return back()
                ->withErrors(['department_id' => 'Please select a valid department.'])
                ->withInput();
        }

        User::create([
            'name' => $validated['name'],
            'profile_name' => $validated['profile_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'org',
            'department_id' => $department->id,
        ]);

        return redirect()->route('login')->with(
            'info',
            'Organization registered. Sign in with your username and password.'
        );
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();

        Auth::logout();

        AuditLogService::logLogout($userId);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
