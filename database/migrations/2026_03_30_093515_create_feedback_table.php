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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            // Connects this message to a specific submission
            $table->foreignId('submission_id')->constrained('submissions')->onDelete('cascade');
            
            // Connects this message to the person who sent it (PAIR or Org)
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // The actual comment/feedback
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};