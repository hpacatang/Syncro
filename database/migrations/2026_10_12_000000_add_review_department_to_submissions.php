<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('review_department_id')
                ->nullable()
                ->after('user_id')
                ->constrained('departments');
        });

        DB::table('submissions')
            ->join('users', 'users.id', '=', 'submissions.user_id')
            ->whereNotNull('users.department_id')
            ->select('submissions.id', 'users.department_id')
            ->get()
            ->each(function (object $submission): void {
                DB::table('submissions')
                    ->where('id', $submission->id)
                    ->update(['review_department_id' => $submission->department_id]);
            });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('review_department_id');
        });
    }
};
