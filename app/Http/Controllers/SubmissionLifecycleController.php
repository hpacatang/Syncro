<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\SocialMediaPublisher;
use App\Services\SubmissionLifecycleService;
use App\Submission\Enums\SubmissionLifecycleStatus;
use App\Submission\Exceptions\InvalidLifecycleTransitionException;
use App\Support\PairUpdateFormatter;
use App\Support\ReviewNotificationCleaner;
use Carbon\Carbon;
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
        ReviewNotificationCleaner::clearForSubmission($submission);

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

        if ($submission->meta_publish_status === 'publishing'
            && $target !== SubmissionLifecycleStatus::Posted) {
            return response()->json([
                'success' => false,
                'message' => 'A Facebook publishing request is in progress. Try this review action again after it completes.',
            ], 409);
        }

        if ($target === SubmissionLifecycleStatus::Posted) {
            if ($submission->workflow_status === 'posted') {
                return response()->json([
                    'success' => false,
                    'message' => 'This submission has already been published and cannot be published again.',
                ], 422);
            }

            if ($submission->isWebsiteArticle()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Website article publishing is not available until the university CMS API is configured.',
                ], 422);
            }

            if (! $this->lifecycle->canTransition($submission, $request->user(), $target)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This submission cannot be published from its current review state.',
                ], 422);
            }

            $publicationTime = $request->input('posted_at')
                ? Carbon::parse($request->input('posted_at'))
                : ($request->input('scheduled_post_at')
                    ? Carbon::parse($request->input('scheduled_post_at'))
                    : ($submission->scheduled_post_at ?? now()));

            if ($publicationTime->isFuture() && $submission->meta_publish_status === 'uncertain') {
                return response()->json([
                    'success' => false,
                    'message' => 'Verify the Facebook Page before rescheduling this unconfirmed publish attempt. After verification, retry manually.',
                ], 409);
            }

            $step = PairUpdateFormatter::step(
                $request->user(),
                $publicationTime->isFuture() ? 'Scheduled Facebook publishing' : 'Requested Facebook publishing',
                $request->input('notes')
            );
            $submission->pair_feedback = PairUpdateFormatter::append($submission->pair_feedback, $step);

            if ($publicationTime->isFuture()) {
                $submission->forceFill([
                    'scheduled_post_at' => $publicationTime,
                    'posted_at' => null,
                    'meta_publish_status' => null,
                    'meta_publish_error' => null,
                    'meta_publish_attempts' => 0,
                    'meta_publish_attempted_at' => null,
                ])->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Facebook publishing scheduled for '.$publicationTime->format('M j, Y g:i A').'.',
                    'data' => $this->formatSubmission($submission->fresh()),
                ]);
            }

            $publishResult = SocialMediaPublisher::publish($submission, $request->user(), true);
            if (! $publishResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $publishResult['message'],
                    'data' => $this->formatSubmission($submission->fresh()),
                ], 502);
            }

            $submission->refresh();
            $submission->pair_feedback = PairUpdateFormatter::append($submission->pair_feedback, $step);
            $context = [
                'posted_at' => now(),
                'scheduled_post_at' => $publicationTime,
                'pair_feedback' => $submission->pair_feedback,
            ];
            $updated = $this->lifecycle->transition(
                $submission,
                $target,
                $request->user(),
                $context
            );

            return response()->json([
                'success' => true,
                'message' => $publishResult['message'].' '.$publishResult['url'],
                'data' => $this->formatSubmission($updated),
            ]);
        }

        if (in_array($target, [SubmissionLifecycleStatus::Revised, SubmissionLifecycleStatus::Rejected], true)) {
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
            'meta_publish_status', 'meta_post_url', 'meta_publish_error',
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
            'meta_publish_status' => $submission->meta_publish_status,
            'meta_post_url' => $submission->meta_post_url,
            'meta_publish_error' => $submission->meta_publish_error,
        ];
    }
}
