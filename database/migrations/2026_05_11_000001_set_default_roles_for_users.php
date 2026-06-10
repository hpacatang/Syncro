<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    public function up(): void
    {
        
        DB::table('users')
            ->whereNull('role')
            ->orWhere('role', '=', '')
            ->update(['role' => 'user']);
    }

    
    public function down(): void
    {
        
    }
};
