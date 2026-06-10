<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove the unused 'users' string column from departments table
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'users')) {
                $table->dropColumn('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('users')->nullable();
        });
    }
};
