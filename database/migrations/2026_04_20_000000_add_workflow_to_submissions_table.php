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
            // Track which PAIR user enhanced the submission
            $table->unsignedBigInteger('enhanced_by')->nullable()->after('user_id');
            $table->foreign('enhanced_by')->references('id')->on('users')->onDelete('set null');
            
            // Timestamp for when enhancement happened
            $table->timestamp('enhanced_at')->nullable()->after('enhanced_caption');
            
            // Workflow status to track the approval process
            $table->enum('workflow_status', [
                'pending_submission',      // Initial org submission
                'pending_pair_review',      // PAIR reviewing
                'pending_org_approval',     // Org reviewing enhanced caption
                'approved',                 // Org approved, ready to post
                'rejected',                 // Org rejected enhancement
                'posted'                    // Final posted
            ])->default('pending_submission')->after('status');
            
            // Track org approval/rejection reason
            $table->text('org_review_notes')->nullable()->after('workflow_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['enhanced_by']);
            $table->dropColumn(['enhanced_by', 'enhanced_at', 'workflow_status', 'org_review_notes']);
        });
    }
};
