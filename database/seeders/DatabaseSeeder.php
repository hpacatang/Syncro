<?php

namespace Database\Seeders;

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


        User::create([
            'name' => 'pair_reviewer',
            'profile_name' => 'Maria Santos (PAIR)',
            'email' => 'pair@syncro.local',
            'password' => Hash::make('pair123'),
            'role' => 'pair',
            'department_id' => null,
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
                ['PAIR', 'pair_reviewer', 'pair123', 'Maria Santos (PAIR)'],
                ['Super Admin', 'syncroadmin', 'qweqwe123', 'syncroadmin'],
            ]
        );
    }
}
