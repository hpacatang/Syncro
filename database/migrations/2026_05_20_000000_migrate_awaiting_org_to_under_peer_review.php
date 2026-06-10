<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('submissions')
            ->whereIn('workflow_status', ['awaiting_org_approval', 'pending_org_approval'])
            ->update(['workflow_status' => 'under_peer_review']);
    }

    public function down(): void
    {
        
    }
};
