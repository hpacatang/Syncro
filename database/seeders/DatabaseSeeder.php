<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Feedback;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['name' => 'syncroadmin'],
            [
                'profile_name' => 'Syncro Administrator',
                'email' => 'syncroadmin@local.com',
                'password' => Hash::make('qweqwe123'),
                'role' => 'super_admin',
                'account_status' => 'approved',
            ]
        );

        User::firstOrCreate(
            ['name' => 'admin'],
            [
                'profile_name' => 'Admin User',
                'email' => 'admin@syncro.local',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'account_status' => 'approved',
            ]
        );

        $soe = Department::firstOrCreate(
            ['department_short_name' => 'SOE'],
            ['department_name' => 'School of Engineering']
        );
        $sas = Department::firstOrCreate(
            ['department_short_name' => 'SAS'],
            ['department_name' => 'School of Arts and Sciences']
        );

        User::firstOrCreate(
            ['name' => 'dept_engineering'],
            [
                'profile_name' => 'School of Engineering',
                'email' => 'engineering@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'department',
                'account_status' => 'approved',
                'department_id' => $soe->id,
            ]
        );
        User::firstOrCreate(
            ['name' => 'dept_arts'],
            [
                'profile_name' => 'School of Arts & Sciences',
                'email' => 'arts@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'department',
                'account_status' => 'approved',
                'department_id' => $sas->id,
            ]
        );
        $pairUser = User::firstOrCreate(
            ['name' => 'pair_reviewer'],
            [
                'profile_name' => 'PAIR Reviewer',
                'email' => 'pair@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'pair',
                'account_status' => 'approved',
            ]
        );

        $student = User::firstOrCreate(
            ['name' => 'student_council'],
            [
                'profile_name' => 'Student Council',
                'email' => 'org@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'org',
                'account_status' => 'approved',
                'department_id' => $soe->id,
            ]
        );
        User::firstOrCreate(
            ['name' => 'marketing_club'],
            [
                'profile_name' => 'Marketing Club',
                'email' => 'marketing@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'org',
                'account_status' => 'approved',
                'department_id' => $soe->id,
            ]
        );

        $submission = Submission::firstOrCreate(
            [
                'user_id' => $student->id,
                'original_caption' => 'Sample: Student Council event announcement.',
            ],
            [
                'review_department_id' => $soe->id,
                'status' => 'pending',
                'workflow_status' => 'submitted',
                'links' => [],
                'media_paths' => [],
            ]
        );

        Submission::firstOrCreate(
            [
                'user_id' => $student->id,
                'original_caption' => 'Sample: University-wide announcement for PAIR.',
            ],
            [
                'review_department_id' => null,
                'status' => 'under_review',
                'workflow_status' => 'under_peer_review',
                'enhanced_caption' => 'A polished sample caption ready for review.',
                'links' => [],
                'media_paths' => [],
            ]
        );

        Submission::firstOrCreate(
            [
                'user_id' => $student->id,
                'original_caption' => 'Sample: Approved student organization update.',
            ],
            [
                'review_department_id' => $soe->id,
                'status' => 'approved',
                'workflow_status' => 'approved',
                'enhanced_caption' => 'An approved sample caption.',
                'links' => [],
                'media_paths' => [],
            ]
        );

        Feedback::firstOrCreate(
            [
                'submission_id' => $submission->id,
                'user_id' => $pairUser->id,
                'message' => 'Please include the event date and venue.',
            ]
        );

        $this->command?->info('Seeded approved admin, PAIR, department, and student demo accounts with sample submissions.');
        $this->command?->table(
            ['Role', 'Username'],
            [
                ['Super Admin', $superAdmin->name],
                ['PAIR', $pairUser->name],
                ['Department', 'dept_engineering'],
                ['Student', $student->name],
            ]
        );
    }
}
