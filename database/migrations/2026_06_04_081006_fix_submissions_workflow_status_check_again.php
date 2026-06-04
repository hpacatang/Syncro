<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_workflow_status_check');
        }
        
        // Ensure data integrity before creating constraint using query builder for database compatibility
        DB::table('submissions')
            ->where('workflow_status', 'pending_submission')
            ->update(['workflow_status' => 'submitted']);

        DB::table('submissions')
            ->whereIn('workflow_status', ['pending_pair_review', 'pending_org_approval', 'awaiting_org_approval', 'under_review'])
            ->update(['workflow_status' => 'under_peer_review']);

        DB::table('submissions')
            ->whereNotIn('workflow_status', ['submitted', 'under_peer_review', 'revised', 'approved', 'rejected', 'posted'])
            ->update(['workflow_status' => 'submitted']);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE submissions ADD CONSTRAINT submissions_workflow_status_check CHECK (workflow_status::text = ANY (ARRAY['submitted'::text, 'under_peer_review'::text, 'revised'::text, 'approved'::text, 'rejected'::text, 'posted'::text]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // nothing
    }
};
