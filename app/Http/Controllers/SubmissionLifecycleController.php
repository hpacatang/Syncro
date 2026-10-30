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
            'posted_at' => 'nullable|date',
            'scheduled_post_at' => 'nullable|date',
        ]);

        $target = SubmissionLifecycleStatus::tryFromString($request->input('status'));
        if (! $target) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lifecycle status.',
            ], 422);
        }

        if ($submission->workflow_status === 'posted' && $target !== SubmissionLifecycleStatus::Posted) {
            return response()->json([
                'success' => false,
                'message' => 'This submission has been published and posted. It is view only and cannot be undone.',
            ], 422);
        }

        if ($submission->workflow_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'This submission has been rejected. A rejection is final and cannot be undone.',
            ], 422);
        }

        if (in_array($target, [SubmissionLifecycleStatus::Revised, SubmissionLifecycleStatus::Rejected], true)) {
            $request->validate(['notes' => 'required|string|min:10']);
        }

        try {
            $context = [];
            if ($request->filled('notes')) {
                $context['org_review_notes'] = $request->input('notes');
            }

            if ($target === SubmissionLifecycleStatus::Posted && $submission->workflow_status === 'posted') {
                if ($submission->isPublicationTimeReached()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Publication date and time cannot be edited because the scheduled publication time has arrived and the post is locked.',
                    ], 422);
                }

                $newTime = null;
                if ($request->filled('posted_at')) {
                    $newTime = \Carbon\Carbon::parse($request->input('posted_at'));
                    $submission->posted_at = $newTime;
                }
                if ($request->filled('scheduled_post_at')) {
                    $newTime = \Carbon\Carbon::parse($request->input('scheduled_post_at'));
                    $submission->scheduled_post_at = $newTime;
                }

                if ($request->filled('notes')) {
                    $step = PairUpdateFormatter::step(
                        $request->user(),
                        'Updated publication date & time',
                        $request->input('notes')
                    );
                    $submission->pair_feedback = PairUpdateFormatter::append($submission->pair_feedback, $step);
                }

                if ($newTime && ! $newTime->isFuture()) {
                    \App\Services\SocialMediaPublisher::publish($submission, $request->user());
                }

                $submission->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Posted date and time updated successfully.',
                    'data' => $this->formatSubmission($submission->fresh()),
                ]);
            }

            if ($target === SubmissionLifecycleStatus::Posted) {
                $targetTime = $request->input('posted_at') ? \Carbon\Carbon::parse($request->input('posted_at')) : now();
                $context['posted_at'] = $targetTime;
                if ($request->filled('scheduled_post_at')) {
                    $context['scheduled_post_at'] = \Carbon\Carbon::parse($request->input('scheduled_post_at'));
                } else {
                    $context['scheduled_post_at'] = $targetTime;
                }
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

            if ($target === SubmissionLifecycleStatus::Posted) {
                $publishTime = $updated->posted_at ?? now();
                if (! $publishTime->isFuture()) {
                    \App\Services\SocialMediaPublisher::publish($updated, $request->user());
                    $updated->save();
                }
            }

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

        if ($request->user()->isOrg()) {
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
