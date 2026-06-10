<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure the default departments exist in departments table
        $departments = [
            'SCS' => 'School of Computer Studies',
            'SOE' => 'School of Engineering',
            'SAS' => 'School of Arts and Sciences',
            'SBM' => 'School of Business and Management',
        ];

        $deptIds = [];
        foreach ($departments as $short => $name) {
            $id = DB::table('departments')->where('department_short_name', $short)->value('id');
            if (!$id) {
                $id = DB::table('departments')->insertGetId([
                    'department_name' => $name,
                    'department_short_name' => $short,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $deptIds[$short] = $id;
        }

        // 2. Link existing department users to departments table
        $deptUsers = DB::table('users')->where('role', 'department')->get();
        foreach ($deptUsers as $user) {
            $targetShort = null;
            if (str_contains($user->name, 'engineering')) {
                $targetShort = 'SOE';
            } elseif (str_contains($user->name, 'arts')) {
                $targetShort = 'SAS';
            } elseif (str_contains($user->name, 'hrm') || str_contains($user->name, 'business')) {
                $targetShort = 'SBM';
            } elseif (str_contains($user->name, 'computer') || str_contains($user->name, 'it')) {
                $targetShort = 'SCS';
            }

            if ($targetShort && isset($deptIds[$targetShort])) {
                DB::table('users')->where('id', $user->id)->update([
                    'department_id' => $deptIds[$targetShort]
                ]);
            }
        }

        // 3. Fix organizations department_id from referencing users.id to referencing departments.id
        $orgUsers = DB::table('users')->where('role', 'org')->whereNotNull('department_id')->get();
        foreach ($orgUsers as $org) {
            $parentDeptUser = DB::table('users')->where('id', $org->department_id)->where('role', 'department')->first();
            if ($parentDeptUser && $parentDeptUser->department_id) {
                DB::table('users')->where('id', $org->id)->update([
                    'department_id' => $parentDeptUser->department_id
                ]);
            }
        }
    }

    public function down(): void
    {
        // Restoring is not strictly necessary or safe to auto-guess.
    }
};
