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
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create PAIR staff members
        $pairStaff = User::factory(3)->pair()->create();
        $pairStaff[0]->update(['name' => 'Maria Santos (PAIR Editor)']);
        $pairStaff[1]->update(['name' => 'John Cruz (PAIR Manager)']);
        $pairStaff[2]->update(['name' => 'Sarah Reyes (PAIR Staff)']);

        // Create organization/department users
        $orgUsers = User::factory(5)->org()->create();
        $orgUsers[0]->update(['name' => 'Student Council']);
        $orgUsers[1]->update(['name' => 'Engineering Department']);
        $orgUsers[2]->update(['name' => 'Marketing Club']);
        $orgUsers[3]->update(['name' => 'Arts Department']);
        $orgUsers[4]->update(['name' => 'HR Department']);

        // Create submissions with various statuses
        // Pending submissions
        $pendingSubmissions = Submission::factory(3)->pending()->create([
            'user_id' => $orgUsers[0]->id,
        ]);

        // Under review submissions
        $underReviewSubmissions = Submission::factory(2)->underReview()->create([
            'user_id' => $orgUsers[1]->id,
        ]);

        // Approved submissions
        $approvedSubmissions = Submission::factory(3)->approved()->create([
            'user_id' => $orgUsers[2]->id,
        ]);

        // Additional submissions from different orgs
        Submission::factory(2)->pending()->create(['user_id' => $orgUsers[3]->id]);
        Submission::factory(2)->underReview()->create(['user_id' => $orgUsers[4]->id]);
        Submission::factory(4)->approved()->create(['user_id' => $orgUsers[0]->id]);

        // Create feedback for submissions
        foreach ($underReviewSubmissions as $submission) {
            Feedback::factory(2)->fromPair()->create([
                'submission_id' => $submission->id,
            ]);
        }

        foreach ($approvedSubmissions as $submission) {
            Feedback::factory(1)->fromPair()->create([
                'submission_id' => $submission->id,
                'message' => 'Approved! Ready for posting.',
            ]);
            Feedback::factory(1)->fromOrg()->create([
                'submission_id' => $submission->id,
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
