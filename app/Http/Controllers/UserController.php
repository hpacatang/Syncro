<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string|min:6',
            'password2' => 'required|string|same:password',
        ]);

        User::create([
            'name'=> $request->name,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')->with('info', 'Registration successful. Please login.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
