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
        Schema::table('submissions', function (Blueprint $table) {
            // Drop the existing column and recreate it
            $table->dropColumn('workflow_status');
        });

        // Recreate with proper enum constraint
        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('workflow_status', [
                'pending_submission',
                'pending_pair_review',
                'pending_org_approval',
                'approved',
                'rejected',
                'posted'
            ])->default('pending_submission')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('workflow_status');
        });
    }
};
