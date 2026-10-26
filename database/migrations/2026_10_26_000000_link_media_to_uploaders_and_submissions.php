<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->cascadeOnDelete();
            $table->string('purpose', 32)->nullable();
            $table->string('storage_disk', 32)->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['submission_id', 'created_at']);
        });

        DB::table('submissions')
            ->select(['id', 'user_id', 'media_paths'])
            ->whereNotNull('media_paths')
            ->orderBy('id')
            ->chunkById(100, function ($submissions): void {
                foreach ($submissions as $submission) {
                    $paths = is_array($submission->media_paths)
                        ? $submission->media_paths
                        : json_decode((string) $submission->media_paths, true);

                    if (! is_array($paths)) {
                        continue;
                    }

                    foreach ($paths as $path) {
                        if (! is_string($path) || $path === '') {
                            continue;
                        }

                        DB::table('media')->insert([
                            'uploaded_by' => $submission->user_id,
                            'submission_id' => $submission->id,
                            'purpose' => 'submission',
                            'storage_disk' => null,
                            'path' => $path,
                            'original_name' => basename($path),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        if (Schema::hasColumn('users', 'profile_photo_path')) {
            DB::table('users')
                ->select(['id', 'profile_photo_path'])
                ->whereNotNull('profile_photo_path')
                ->orderBy('id')
                ->chunkById(100, function ($users): void {
                    foreach ($users as $user) {
                        DB::table('media')->insert([
                            'uploaded_by' => $user->id,
                            'purpose' => 'profile_photo',
                            'storage_disk' => null,
                            'path' => $user->profile_photo_path,
                            'original_name' => basename($user->profile_photo_path),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['submission_id']);
            $table->dropIndex(['uploaded_by', 'created_at']);
            $table->dropIndex(['submission_id', 'created_at']);
            $table->dropColumn([
                'uploaded_by',
                'submission_id',
                'purpose',
                'storage_disk',
                'path',
                'original_name',
                'mime_type',
                'size',
            ]);
        });
    }
};
