<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\SubmissionLifecycleService;
use App\Submission\Enums\SubmissionLifecycleStatus;
use App\Submission\Exceptions\InvalidLifecycleTransitionException;
use App\Support\PairUpdateFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubmissionLifecycleController extends Controller
{
    public function __construct(
        private readonly SubmissionLifecycleService $lifecycle
    ) {}

    public function transition(Request $request, Submission $submission)
    {
        $this->authorize('transition', $submission);

        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string|max:5000',
        ]);

        $target = SubmissionLifecycleStatus::tryFromString($request->input('status'));
        if (! $target) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lifecycle status.',
            ], 422);
        }

        if ($target === SubmissionLifecycleStatus::Revised) {
            $request->validate(['notes' => 'required|string|min:10']);
        }

        try {
            $context = [];
            if ($request->filled('notes')) {
                $context['org_review_notes'] = $request->input('notes');
            }

            $step = PairUpdateFormatter::step(
                $request->user(),
                $target->actionLabel(),
                $request->input('notes')
            );
            $context['pair_feedback'] = PairUpdateFormatter::append($submission->pair_feedback, $step);

            $updated = $this->lifecycle->transition(
                $submission,
                $target,
                $request->user(),
                $context
            );

            return response()->json([
                'success' => true,
                'message' => 'Status updated to '.$target->label().'.',
                'data' => $this->formatSubmission($updated),
            ]);
        } catch (InvalidLifecycleTransitionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Lifecycle transition failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update submission status.',
            ], 500);
        }
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $allowed = $this->lifecycle->allowedTransitions($submission, $request->user());

        return response()->json([
            'success' => true,
            'data' => array_merge($this->formatSubmission($submission), [
                'allowed_transitions' => array_map(fn (SubmissionLifecycleStatus $s) => [
                    'value' => $s->value,
                    'label' => $s->label(),
                    'action_label' => $s->actionLabel(),
                ], $allowed),
            ]),
        ]);
    }

    public function updates(Request $request)
    {
        $this->authorize('viewAny', Submission::class);

        $request->validate([
            'since' => 'nullable|date',
            'ids' => 'nullable|string',
        ]);

        $query = Submission::query()->select([
            'id', 'workflow_status', 'status', 'updated_at', 'user_id',
        ]);

        if ($request->user()->canSubmitPosts()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->input('ids'))));
            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }
        }

        if ($request->filled('since')) {
            $query->where('updated_at', '>', $request->date('since'));
        }

        $submissions = $query->orderByDesc('updated_at')->limit(100)->get();

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'data' => $submissions->map(fn (Submission $s) => $this->formatSubmission($s)),
        ]);
    }

    private function formatSubmission(Submission $submission): array
    {
        $lifecycle = $submission->lifecycle();

        return [
            'id' => $submission->id,
            'workflow_status' => $lifecycle->value,
            'lifecycle_status' => $lifecycle->value,
            'lifecycle_label' => $lifecycle->label(),
            'badge_class' => $lifecycle->badgeClass(),
            'progress_color' => $lifecycle->progressColor(),
            'progress_index' => $lifecycle->progressIndex(),
            'status' => $submission->status,
            'updated_at' => $submission->updated_at?->toIso8601String(),
        ];
    }
}
