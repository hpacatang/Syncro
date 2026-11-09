<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->date('event_date')->nullable();
            $table->string('event_location')->nullable();
            $table->text('event_people')->nullable();
            $table->text('event_details')->nullable();
            $table->date('requested_post_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'event_date',
                'event_location',
                'event_people',
                'event_details',
                'requested_post_date',
            ]);
        });
    }
};
