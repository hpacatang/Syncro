<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\SubmissionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            if (auth()->user()->account_status !== 'approved') {
                $status = auth()->user()->account_status;
                Auth::logout();

                return back()->withErrors([
                    'name' => $status === 'pending'
                        ? 'Your account request is awaiting administrator approval.'
                        : 'Your account request was not approved.',
                ])->onlyInput('name');
            }

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
            'departments' => Department::orderBy('department_name')->get(),
        ]);
    }

    public function profile()
    {
        return view('Profile.userProfile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'profile_name' => 'required|string|min:2|max:150',
            'name' => ['required', 'string', 'min:3', 'max:100', Rule::unique('users', 'name')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_photo' => 'nullable|image|max:2048',
            'remove_profile_photo' => 'nullable|boolean',
            'current_password' => 'required_with:password|current_password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $changes = [];
        foreach (['profile_name', 'name', 'email'] as $field) {
            if ($user->{$field} !== $validated[$field]) {
                $changes[$field] = ['old' => $user->{$field}, 'new' => $validated[$field]];
            }
        }

        if ($request->hasFile('profile_photo')) {
            $photoDisk = config('filesystems.disks.supabase.key') ? 'supabase' : 'public';
            $newPhotoPath = $request->file('profile_photo')->store('profile-photos', $photoDisk);
            if (! $newPhotoPath) {
                return back()->withInput()->withErrors([
                    'profile_photo' => 'The profile photo could not be saved. Please try again.',
                ]);
            }

            $oldPhotoPath = $user->profile_photo_path;
            $user->profile_photo_path = $newPhotoPath;
            $changes['profile_photo_path'] = ['old' => $oldPhotoPath, 'new' => $newPhotoPath];
        } elseif ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            $oldPhotoPath = $user->profile_photo_path;
            $user->profile_photo_path = null;
            $changes['profile_photo_path'] = ['old' => $oldPhotoPath, 'new' => null];
        }

        foreach (['profile_name', 'name', 'email'] as $field) {
            $user->{$field} = $validated[$field];
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
            $changes['password'] = ['old' => '****', 'new' => '****'];
        }

        $user->save();

        if (isset($oldPhotoPath) && $oldPhotoPath !== $user->profile_photo_path) {
            $oldPhoto = SubmissionMedia::resolve($oldPhotoPath);
            if ($oldPhoto !== null && ! Storage::disk($oldPhoto['disk'])->delete($oldPhoto['path'])) {
                report(new \RuntimeException('Unable to remove the replaced profile photo.'));
            }
        }

        if ($changes !== []) {
            AuditLogService::logUserUpdated($user->id, $changes);
        }

        return redirect()->route('profile')->with('success', 'Your account details have been updated.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100|unique:users,name',
            'profile_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'department_id' => 'required|exists:departments,id',
            'password' => 'required|string|min:6',
            'password2' => 'required|string|same:password',
        ]);

        User::create([
            'name' => $validated['name'],
            'profile_name' => $validated['profile_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'org',
            'account_status' => 'pending',
            'department_id' => $validated['department_id'],
        ]);

        return redirect()->route('login')->with(
            'info',
            'Your account request was submitted. You can sign in after an administrator approves it.'
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
