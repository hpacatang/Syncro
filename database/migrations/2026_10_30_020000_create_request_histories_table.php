<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the RequestHistory audit table to strictly track every content request state change,
     * mutation lifecycle, actor responsibility, and contextual payload metadata.
     */
    public function up(): void
    {
        Schema::create('request_histories', function (Blueprint $table) {
            $table->id();

            // Submission relation
            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            // Actor relation
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('actor_role', 50)->nullable()->index();
            $table->string('action', 100)->index(); // e.g. created, transitioned, revised, approved, rejected, posted

            // Status mutations
            $table->string('from_status', 50)->nullable()->index();
            $table->string('to_status', 50)->index();

            // Contextual explanations
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Immutable snapshot & network attribution audit
            $table->jsonb('metadata')->nullable(); // diffs, IP, user-agent, headers
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            // High-throughput query indexes
            $table->index(['submission_id', 'created_at'], 'idx_req_hist_sub_created');
            $table->index(['actor_id', 'created_at'], 'idx_req_hist_actor_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_histories');
    }
};
