<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'profile_name')) {
                $table->string('profile_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('users')->whereNull('profile_name')->update([
            'profile_name' => DB::raw('name'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
            if (Schema::hasColumn('users', 'profile_name')) {
                $table->dropColumn('profile_name');
            }
        });
    }
};
