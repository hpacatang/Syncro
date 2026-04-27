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
            if (!Schema::hasColumn('submissions', 'pair_feedback')) {
                $table->text('pair_feedback')->nullable()->after('enhanced_caption')->comment('PAIR staff feedback/comments during enhancement');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'pair_feedback')) {
                $table->dropColumn(['pair_feedback']);
            }
        });
    }
};
