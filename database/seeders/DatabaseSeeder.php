<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Submission;
use App\Models\Feedback;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ===== Create Default Test Users =====
        
        // Admin user
        User::create([
            'name' => 'Admin User',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // PAIR staff - test user
        User::create([
            'name' => 'qweqwe1',
            'password' => Hash::make('qweqwe123'),
            'role' => 'pair',
        ]);

        // Organization - test user
        User::create([
            'name' => 'sampleorg',
            'password' => Hash::make('sample123'),
            'role' => 'org',
        ]);

        // ===== Create Additional Sample Users =====
        
        // Create PAIR staff members
        $pairStaff = User::factory(2)->pair()->create();
        $pairStaff[0]->update(['name' => 'Maria Santos (PAIR Editor)']);
        $pairStaff[1]->update(['name' => 'John Cruz (PAIR Manager)']);

        // Create organization/department users
        $orgUsers = User::factory(4)->org()->create();
        $orgUsers[0]->update(['name' => 'Student Council']);
        $orgUsers[1]->update(['name' => 'Engineering Department']);
        $orgUsers[2]->update(['name' => 'Marketing Club']);
        $orgUsers[3]->update(['name' => 'Arts Department']);

        // Get test org user
        $testOrgUser = User::where('name', 'sampleorg')->first();
        $allOrgUsers = User::where('role', 'org')->get();

        // Create submissions with various statuses
        // Pending submissions
        $pendingSubmissions = Submission::factory(3)->pending()->create([
            'user_id' => $allOrgUsers[0]->id,
        ]);

        // Under review submissions
        $underReviewSubmissions = Submission::factory(2)->underReview()->create([
            'user_id' => $allOrgUsers[1]->id,
        ]);

        // Approved submissions
        $approvedSubmissions = Submission::factory(3)->approved()->create([
            'user_id' => $allOrgUsers[2]->id,
        ]);

        // Additional submissions from different orgs
        Submission::factory(2)->pending()->create(['user_id' => $allOrgUsers[3]->id]);
        Submission::factory(2)->underReview()->create(['user_id' => $testOrgUser->id]);
        Submission::factory(4)->approved()->create(['user_id' => $allOrgUsers[0]->id]);

        // Create feedback for submissions
        $pairUser = User::where('role', 'pair')->first();
        
        foreach ($underReviewSubmissions as $submission) {
            Feedback::factory(2)->create([
                'submission_id' => $submission->id,
                'user_id' => $pairUser->id,
            ]);
        }

        foreach ($approvedSubmissions as $submission) {
            Feedback::factory(1)->create([
                'submission_id' => $submission->id,
                'user_id' => $pairUser->id,
                'message' => 'Approved! Ready for posting.',
            ]);
            Feedback::factory(1)->create([
                'submission_id' => $submission->id,
                'user_id' => $submission->user_id,
                'message' => 'Perfect! Thanks for the edits.',
            ]);
        }

        echo "\n✓ Sample data seeded successfully!\n";
        echo "  - 1 Admin user\n";
        echo "  - 3 PAIR staff members\n";
        echo "  - 5 Organization users\n";
        echo "  - 16 Submissions (with various statuses)\n";
        echo "  - Multiple feedback records\n\n";
    }
}
