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
        if (! Schema::hasColumn('submissions', 'posted_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dateTime('posted_at')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'posted_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('posted_at');
            });
        }
    }
};
