<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('submissions', 'meta_publish_status')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('meta_publish_status', 20)->nullable()->index();
            });
        }
        if (! Schema::hasColumn('submissions', 'meta_post_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('meta_post_id')->nullable();
            });
        }
        if (! Schema::hasColumn('submissions', 'meta_post_url')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->text('meta_post_url')->nullable();
            });
        }
        if (! Schema::hasColumn('submissions', 'meta_publish_error')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->text('meta_publish_error')->nullable();
            });
        }
        if (! Schema::hasColumn('submissions', 'meta_publish_attempts')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->unsignedInteger('meta_publish_attempts')->default(0);
            });
        }
        if (! Schema::hasColumn('submissions', 'meta_publish_attempted_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->timestamp('meta_publish_attempted_at')->nullable();
            });
        }

        if (Schema::hasTable('submissions')
            && Schema::hasColumn('submissions', 'workflow_status')
            && Schema::hasColumn('submissions', 'posted_at')
            && Schema::hasColumn('submissions', 'scheduled_post_at')) {
            DB::table('submissions')
                ->where('workflow_status', 'posted')
                ->whereNull('meta_post_id')
                ->whereNotNull('posted_at')
                ->where('posted_at', '>', now())
                ->update([
                    'workflow_status' => 'approved',
                    'status' => 'approved',
                    'scheduled_post_at' => DB::raw('COALESCE(scheduled_post_at, posted_at)'),
                    'posted_at' => null,
                ]);

            DB::table('submissions')
                ->where('workflow_status', 'posted')
                ->whereNull('meta_post_id')
                ->where(function ($query) {
                    $query->whereNull('posted_at')
                        ->orWhere('posted_at', '<=', now());
                })
                ->update([
                    'meta_publish_status' => 'uncertain',
                    'meta_publish_error' => 'This was marked posted before publishing API confirmation was recorded. Verify the destination before taking further action.',
                ]);
        }
    }

    public function down(): void
    {
        foreach ([
            'meta_publish_attempted_at',
            'meta_publish_attempts',
            'meta_publish_error',
            'meta_post_url',
            'meta_post_id',
            'meta_publish_status',
        ] as $column) {
            if (Schema::hasColumn('submissions', $column)) {
                Schema::table('submissions', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
