<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DepartmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_route_submission_to_department_or_pair(): void
    {
        $department = $this->createDepartment('School of Engineering');
        $student = User::factory()->create([
            'role' => 'org',
            'department_id' => $department->id,
        ]);

        $departmentSubmission = $this->actingAs($student)->postJson('/api/submissions', [
            'original_caption' => 'Department event caption',
            'review_destination' => 'department',
        ])->assertCreated()->json('data');

        $pairSubmission = $this->actingAs($student)->postJson('/api/submissions', [
            'original_caption' => 'School-wide event caption',
            'review_destination' => 'pair',
        ])->assertCreated()->json('data');

        $this->assertSame($department->id, $departmentSubmission['review_department_id']);
        $this->assertNull($pairSubmission['review_department_id']);
    }

    public function test_department_account_can_submit_only_to_pair(): void
    {
        $department = $this->createDepartment('School of Engineering');
        $departmentAccount = User::factory()->department()->create([
            'department_id' => $department->id,
        ]);

        $this->actingAs($departmentAccount)
            ->get(route('department.submit'))
            ->assertOk()
            ->assertSee('PAIR oversees official university publications')
            ->assertDontSee('destinationDepartment');

        $submission = $this->actingAs($departmentAccount)
            ->postJson('/api/submissions', [
                'original_caption' => 'University-wide announcement',
                'review_destination' => 'department',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame($departmentAccount->id, $submission['user_id']);
        $this->assertNull($submission['review_department_id']);

        $this->actingAs($departmentAccount)
            ->getJson('/api/submissions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $submission['id']);

        $this->actingAs($departmentAccount)
            ->postJson('/api/submissions/'.$submission['id'].'/transition', ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_department_reviewer_can_review_only_submissions_routed_to_their_department(): void
    {
        $department = $this->createDepartment('School of Engineering');
        $otherDepartment = $this->createDepartment('School of Business');
        $reviewer = User::factory()->department()->create(['department_id' => $department->id]);
        $student = User::factory()->org()->create(['department_id' => $department->id]);
        $otherStudent = User::factory()->org()->create(['department_id' => $otherDepartment->id]);
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => $department->id,
            'original_caption' => 'Department queue caption',
        ]);
        $otherSubmission = Submission::factory()->submitted()->create([
            'user_id' => $otherStudent->id,
            'review_department_id' => $otherDepartment->id,
        ]);
        $pairSubmission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
        ]);

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions'))
            ->assertOk()
            ->assertSee('Department queue caption');

        $this->actingAs($reviewer)
            ->postJson('/api/submissions/'.$submission->id.'/transition', ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.workflow_status', 'approved');

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $otherSubmission))
            ->assertNotFound();

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $pairSubmission))
            ->assertNotFound();
    }

    public function test_pair_can_view_submissions_routed_to_departments_and_directly_to_pair(): void
    {
        $department = $this->createDepartment('School of Engineering');
        $student = User::factory()->org()->create(['department_id' => $department->id]);
        $departmentSubmission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => $department->id,
        ]);
        $pairSubmission = Submission::factory()->submitted()->create([
            'user_id' => $student->id,
            'review_department_id' => null,
        ]);
        $pair = User::factory()->pair()->create();

        $this->actingAs($pair)
            ->get(route('dashboard.submissions.review', $departmentSubmission))
            ->assertOk();

        $this->actingAs($pair)
            ->get(route('dashboard.submissions.review', $pairSubmission))
            ->assertOk();
    }

    public function test_department_manager_can_manage_only_student_accounts_in_their_department(): void
    {
        $department = $this->createDepartment('School of Engineering');
        $otherDepartment = $this->createDepartment('School of Business');
        $manager = User::factory()->department()->create(['department_id' => $department->id]);
        $student = User::factory()->org()->create(['department_id' => $department->id]);
        $otherStudent = User::factory()->org()->create(['department_id' => $otherDepartment->id]);

        $this->actingAs($manager)
            ->get(route('department.users.index'))
            ->assertOk()
            ->assertSee('data-debounce-search-field', false)
            ->assertSee($student->name)
            ->assertDontSee($otherStudent->name);

        $this->actingAs($manager)
            ->get(route('department.users.create'))
            ->assertOk()
            ->assertSee('Create a student account for your Department.');

        $this->actingAs($manager)
            ->get(route('department.users.edit', $student))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('department.users.show', $otherStudent))
            ->assertNotFound();

        $this->actingAs($manager)
            ->post(route('department.users.store'), [
                'name' => 'new-engineering-student',
                'profile_name' => 'New Engineering Student',
                'email' => 'new-engineering-student@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'org',
                'department_id' => $otherDepartment->id,
            ])
            ->assertRedirect(route('department.users.index'));

        $created = User::where('name', 'new-engineering-student')->firstOrFail();
        $this->assertSame('org', $created->role);
        $this->assertSame($department->id, $created->department_id);
    }

    public function test_super_admin_can_search_departments_by_name_or_short_name(): void
    {
        $this->createDepartment('School of Engineering');
        $this->createDepartment('School of Business');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('departments.index', ['search' => 'Engineering']))
            ->assertOk()
            ->assertSee('School of Engineering')
            ->assertDontSee('School of Business');

        $this->actingAs($superAdmin)
            ->get(route('departments.index', ['search' => 'SB']))
            ->assertOk()
            ->assertSee('School of Business')
            ->assertDontSee('School of Engineering');
    }

    public function test_database_seeder_is_repeatable_and_seeds_approved_account_roles(): void
    {
        $departmentCount = Department::count();
        $userCount = User::count();
        $submissionCount = Submission::count();

        Artisan::call('db:seed');
        $countsAfterFirstSeed = [
            Department::count(),
            User::count(),
            Submission::count(),
        ];
        Artisan::call('db:seed');

        $this->assertSame($countsAfterFirstSeed, [
            Department::count(),
            User::count(),
            Submission::count(),
        ]);
        $this->assertGreaterThanOrEqual($departmentCount, Department::count());
        $this->assertGreaterThanOrEqual($userCount + 7, User::count());
        $this->assertGreaterThanOrEqual($submissionCount + 3, Submission::count());
        $this->assertDatabaseHas('users', [
            'name' => 'dept_engineering',
            'role' => 'department',
            'account_status' => 'approved',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'student_council',
            'role' => 'org',
            'account_status' => 'approved',
        ]);
    }

    private function createDepartment(string $name): Department
    {
        return Department::create([
            'department_name' => $name,
            'department_short_name' => strtoupper(substr(md5($name), 0, 5)),
        ]);
    }
}
