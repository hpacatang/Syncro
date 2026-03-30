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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            // Connects the submission to the Org/User who created it
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // The actual content
            $table->text('original_caption'); 
            $table->text('enhanced_caption')->nullable(); // Nullable because it starts empty until PAIR uses the LLM
            $table->json('links')->nullable(); 
            $table->json('media_paths')->nullable(); 
            
            // Tracks where the post is in the approval process
            $table->enum('status', ['pending', 'under_review', 'approved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};