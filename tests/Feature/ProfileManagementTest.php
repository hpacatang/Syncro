<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_update_profile_details_and_photo(): void
    {
        Storage::fake('public');
        config(['filesystems.disks.supabase.key' => null]);
        $department = Department::create([
            'department_name' => 'School of Engineering',
            'department_short_name' => 'SOE',
        ]);
        $user = User::factory()->create([
            'role' => 'org',
            'department_id' => $department->id,
        ]);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Personal details');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'profile_name' => 'Updated Display Name',
                'name' => 'updated-username',
                'email' => 'updated@example.test',
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Display Name', $user->profile_name);
        $this->assertSame('updated-username', $user->name);
        $this->assertSame('updated@example.test', $user->email);
        $this->assertSame('org', $user->role);
        $this->assertSame($department->id, $user->department_id);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $this->assertStringContainsString('submission-media', $user->profilePhotoUrl());

        $oldPhotoPath = $user->profile_photo_path;
        $this->actingAs($user)
            ->put(route('profile.update'), [
                'profile_name' => $user->profile_name,
                'name' => $user->name,
                'email' => $user->email,
                'remove_profile_photo' => '1',
            ])
            ->assertRedirect(route('profile'));

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($oldPhotoPath);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'profile_name' => $user->profile_name,
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'incorrect-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_profile_page_requires_authentication(): void
    {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    }
}
