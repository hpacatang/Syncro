<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            
            $table->dropColumn('workflow_status');
        });

        
        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('workflow_status', [
                'submitted',
                'under_peer_review',
                'revised',
                'approved',
                'rejected',
                'posted'
            ])->default('submitted')->after('status');
        });
    }

    
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('workflow_status');
        });
    }
};
