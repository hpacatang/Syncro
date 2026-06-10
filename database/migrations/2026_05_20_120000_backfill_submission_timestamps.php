<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('submissions')
            ->whereNull('created_at')
            ->update([
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        
    }
};
