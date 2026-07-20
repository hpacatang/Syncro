<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\Submission;
use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'superadmin',
            'profile_name' => 'Super Admin User',
            'email' => 'super@syncro.local',
            'password' => Hash::make('super123'),
            'role' => 'super_admin',
        ]);

        User::create([
            'name' => 'admin',
            'profile_name' => 'Admin User',
            'email' => 'admin@syncro.local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

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
                'profile_name' => 'College of Engineering',
                'email' => 'engineering@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'department',
                'department_id' => $soe->id,
            ]
        );

        User::firstOrCreate(
            ['name' => 'dept_arts'],
            [
                'profile_name' => 'College of Arts & Sciences',
                'email' => 'arts@syncro.local',
                'password' => Hash::make('qweqwe123'),
                'role' => 'department',
                'department_id' => $sas->id,
            ]
        );

        User::create([
            'name' => 'pair_reviewer',
            'profile_name' => 'Maria Santos (PAIR)',
            'email' => 'pair@syncro.local',
            'password' => Hash::make('qweqwe123'),
            'role' => 'pair',
        ]);

        User::create([
            'name' => 'student_council',
            'profile_name' => 'Student Council',
            'email' => 'org@syncro.local',
            'password' => Hash::make('qweqwe123'),
            'role' => 'org',
            'department_id' => $soe->id,
        ]);

        User::create([
            'name' => 'marketing_club',
            'profile_name' => 'Marketing Club',
            'email' => 'marketing@syncro.local',
            'password' => Hash::make('qweqwe123'),
            'role' => 'org',
            'department_id' => $soe->id,
        ]);

        User::create([
            'name' => 'syncroadmin',
            'profile_name' => 'syncroadmin',
            'email' => 'syncroadmin@local.com',
            'password' => Hash::make('qweqwe123'),
            'role' => 'super_admin',
            'department_id' => null,
        ]);
        
        $pairUser = User::where('role', 'pair')->first();
        $orgUser = User::where('email', 'org@syncro.local')->first();

        $submission = Submission::factory()->pending()->create([
            'user_id' => $orgUser->id,
        ]);

        Submission::factory(2)->underReview()->create(['user_id' => $orgUser->id]);
        Submission::factory(2)->approved()->create(['user_id' => $orgUser->id]);

        if ($pairUser) {
            Feedback::factory()->create([
                'submission_id' => $submission->id,
                'user_id' => $pairUser->id,
            ]);
        }

        $this->command->info('Seeded: super admin, admin, 2 departments, PAIR, 2 orgs (same department), sample submissions.');
        $this->command->table(
            ['Role', 'Username', 'Password', 'Profile name'],
            [
                ['Super Admin', 'syncroadmin', 'qweqwe123', 'syncroadmin'],
                ['PAIR', 'pair_reviewer', 'qweqwe123', 'Maria Santos (PAIR)'],
                ['Department', 'dept_engineering', 'qweqwe123', 'College of Engineering'],
                ['Organization', 'student_council', 'org123', 'Student Council'],
                ['Super Admin', 'qweqwe1', 'qweqwe123', 'qweqwe1'],
            ]
        );
    }
}
