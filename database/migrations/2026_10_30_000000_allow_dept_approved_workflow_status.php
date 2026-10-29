<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
        'submitted', 'dept_approved', 'under_peer_review', 'revised', 'approved', 'rejected', 'posted',
    ];

    /**
     * Allow the Department Head's initial approval state (dept_approved) in the
     * PostgreSQL workflow_status CHECK constraint.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->replaceConstraint(self::STATUSES);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::table('submissions')
            ->where('workflow_status', 'dept_approved')
            ->update(['workflow_status' => 'submitted']);

        $this->replaceConstraint(array_values(array_diff(self::STATUSES, ['dept_approved'])));
    }

    private function replaceConstraint(array $statuses): void
    {
        $list = implode(', ', array_map(fn (string $s) => "'{$s}'::text", $statuses));

        DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_workflow_status_check');
        DB::statement("ALTER TABLE submissions ADD CONSTRAINT submissions_workflow_status_check CHECK (workflow_status::text = ANY (ARRAY[{$list}]))");
    }
};
