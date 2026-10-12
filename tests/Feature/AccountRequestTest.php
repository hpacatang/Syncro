<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_pending_account_that_cannot_sign_in(): void
    {
        $department = $this->createDepartment();

        $this->post(route('store'), [
            'name' => 'new-student',
            'profile_name' => 'New Student',
            'email' => 'new-student@example.test',
            'department_id' => $department->id,
            'password' => 'password123',
            'password2' => 'password123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('info');

        $user = User::where('name', 'new-student')->firstOrFail();
        $this->assertSame('pending', $user->account_status);
        $this->assertSame('org', $user->role);

        $this->post(route('authenticate'), [
            'name' => $user->name,
            'password' => 'password123',
        ])->assertRedirect()
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_admin_can_approve_a_pending_request_and_the_user_can_sign_in(): void
    {
        $user = User::factory()->create([
            'role' => 'org',
            'account_status' => 'pending',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('account-requests.index'))
            ->assertOk()
            ->assertSee($user->name);

        $this->actingAs($admin)
            ->post(route('account-requests.approve', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('approved', $user->fresh()->account_status);

        $this->post(route('authenticate'), [
            'name' => $user->name,
            'password' => 'password',
        ])->assertRedirect(route('org.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_reject_a_pending_request(): void
    {
        $user = User::factory()->create([
            'role' => 'org',
            'account_status' => 'pending',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('account-requests.reject', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('rejected', $user->fresh()->account_status);
    }

    public function test_non_admin_cannot_review_account_requests(): void
    {
        $user = User::factory()->org()->create();

        $this->actingAs($user)
            ->get(route('account-requests.index'))
            ->assertRedirect(route('org.dashboard'));
    }

    private function createDepartment(): Department
    {
        return Department::create([
            'department_name' => 'School of Engineering',
            'department_short_name' => 'SOE',
        ]);
    }
}
