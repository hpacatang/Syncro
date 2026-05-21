<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_STATUSES = [
        'pending',
        'submitted',
        'under_peer_review',
        'awaiting_org_approval',
        'approved',
        'rejected',
        'revised',
        'posted',
    ];

    private const LEGACY_MAP = [
        'pending_submission' => 'submitted',
        'pending_pair_review' => 'revised',
        'pending_org_approval' => 'awaiting_org_approval',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'posted' => 'posted',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('submissions', 'workflow_status')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('workflow_status', 32)->default('pending')->after('status');
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_workflow_status_check');
            DB::statement('ALTER TABLE submissions ALTER COLUMN workflow_status TYPE VARCHAR(32) USING workflow_status::text');
            DB::statement('ALTER TABLE submissions ALTER COLUMN workflow_status SET DEFAULT \'pending\'');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE submissions MODIFY workflow_status VARCHAR(32) NOT NULL DEFAULT \'pending\'');
        } elseif ($driver === 'sqlite') {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('workflow_status_tmp', 32)->nullable();
            });
            DB::statement('UPDATE submissions SET workflow_status_tmp = workflow_status');
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('workflow_status');
            });
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('workflow_status', 32)->default('pending');
            });
            DB::statement('UPDATE submissions SET workflow_status = workflow_status_tmp');
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('workflow_status_tmp');
            });
        }

        foreach (self::LEGACY_MAP as $legacy => $new) {
            DB::table('submissions')
                ->where('workflow_status', $legacy)
                ->update(['workflow_status' => $new]);
        }

        DB::table('submissions')
            ->where('workflow_status', 'under_review')
            ->update(['workflow_status' => 'under_peer_review']);

        DB::table('submissions')
            ->whereNull('workflow_status')
            ->update(['workflow_status' => 'pending']);

        if ($driver === 'mysql') {
            $enumList = implode("','", self::NEW_STATUSES);
            DB::statement("ALTER TABLE submissions MODIFY workflow_status ENUM('{$enumList}') NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            $allowed = "'".implode("','", self::NEW_STATUSES)."'";
            DB::statement("ALTER TABLE submissions ADD CONSTRAINT submissions_workflow_status_check CHECK (workflow_status IN ({$allowed}))");
        }

        DB::table('submissions')
            ->whereIn('workflow_status', ['approved', 'posted'])
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        $reverse = [
            'pending' => 'pending_submission',
            'submitted' => 'pending_submission',
            'under_peer_review' => 'pending_pair_review',
            'awaiting_org_approval' => 'pending_org_approval',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'revised' => 'pending_pair_review',
            'posted' => 'posted',
        ];

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_workflow_status_check');
            DB::statement('ALTER TABLE submissions ALTER COLUMN workflow_status TYPE VARCHAR(32)');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE submissions MODIFY workflow_status VARCHAR(32) NOT NULL DEFAULT 'pending_submission'");
        }

        foreach ($reverse as $new => $legacy) {
            DB::table('submissions')
                ->where('workflow_status', $new)
                ->update(['workflow_status' => $legacy]);
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE submissions MODIFY workflow_status ENUM(
                'pending_submission',
                'pending_pair_review',
                'pending_org_approval',
                'approved',
                'rejected',
                'posted'
            ) NOT NULL DEFAULT 'pending_submission'");
        }
    }
};
