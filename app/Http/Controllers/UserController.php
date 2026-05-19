<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\AuditLogService;

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

        if (Auth::attempt($credentials)){
            $request->session()->regenerate();
            
            // Log the login action
            AuditLogService::logLogin();
            
            // Redirect based on user role
            $user = auth()->user();
            if ($user->role === 'pair' || $user->role === 'admin') {
                return redirect()->intended(route('dashboard'));
            } elseif ($user->role === 'org') {
                return redirect()->intended(route('org.dashboard'));
            }
            
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'name' => 'Invalid username or password!',
        ])->onlyInput('name');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'password2' => 'required|string|same:password',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        return redirect()->route('login')->with('info', 'Registration successful. Please login.');
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        
        Auth::logout();
        
        // Log the logout action
        AuditLogService::logLogout($userId);
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
