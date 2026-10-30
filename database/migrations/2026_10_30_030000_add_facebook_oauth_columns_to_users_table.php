<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds persistent Facebook OAuth ("Cloud Sync") columns exclusively utilized by Pair users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fb_user_id')->nullable()->index()->after('role');
            $table->text('fb_access_token')->nullable()->after('fb_user_id');
            $table->string('fb_page_id')->nullable()->after('fb_access_token');
            $table->text('fb_page_access_token')->nullable()->after('fb_page_id');
            $table->timestamp('fb_connected_at')->nullable()->index()->after('fb_page_access_token');
            $table->timestamp('fb_token_expires_at')->nullable()->after('fb_connected_at');
            $table->boolean('fb_sync_enabled')->default(false)->after('fb_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'fb_user_id',
                'fb_access_token',
                'fb_page_id',
                'fb_page_access_token',
                'fb_connected_at',
                'fb_token_expires_at',
                'fb_sync_enabled',
            ]);
        });
    }
};
