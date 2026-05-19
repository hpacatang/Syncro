<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->after('name');
            });

            DB::table('users')
                ->whereNull('email')
                ->orderBy('id')
                ->each(function ($user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'email' => 'user'.$user->id.'@syncro.local',
                        ]);
                });

            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->unique()->change();
            });
        }

        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
