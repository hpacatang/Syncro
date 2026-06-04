<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Models\Submission;
use App\Models\Feedback;
use App\Support\SubmissionWorkflowGroups;

class MainController extends Controller
{
    private const WORKFLOW_FILTERS = [
        'pending_submission',
        'pending_pair_review',
        'submitted',
        'under_peer_review',
        'revised',
        'approved',
        'rejected',
        'posted',
    ];

    private function applySubmissionFilters(Builder $query, Request $request, string $defaultStatus = 'all'): string
    {
        $filter = $request->get('status', $defaultStatus);

        switch ($filter) {
            case 'all':
                break;
            case 'pending':
                $query->whereIn('workflow_status', SubmissionWorkflowGroups::PENDING_QUEUE);
                break;
            default:
                $group = match ($filter) {
                    'pending_submission', 'submitted' => SubmissionWorkflowGroups::SUBMITTED,
                    'pending_pair_review', 'under_peer_review' => SubmissionWorkflowGroups::IN_PEER_REVIEW,
                    'ready_for_review' => SubmissionWorkflowGroups::READY_FOR_ORG_REVIEW,
                    'revised' => SubmissionWorkflowGroups::REVISED,
                    'approved' => SubmissionWorkflowGroups::APPROVED,
                    'rejected' => SubmissionWorkflowGroups::REJECTED,
                    'posted' => SubmissionWorkflowGroups::POSTED,
                    default => null,
                };
                if ($group !== null) {
                    $query->whereIn('workflow_status', $group);
                } elseif (in_array($filter, self::WORKFLOW_FILTERS, true)) {
                    $query->where('workflow_status', $filter);
                }
                break;
        }

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search, $request) {
                $q->where('original_caption', 'like', '%' . $search . '%')
                    ->orWhere('enhanced_caption', 'like', '%' . $search . '%');

                if ($request->user()->isStaffReviewer()) {
                    $q->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('profile_name', 'like', '%' . $search . '%');
                    });
                }
            });
        }

        $sortBy = $request->get('sort', 'created_at');
        $allowedSort = ['created_at', 'updated_at', 'id'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $filter;
    }
    public function index(Request $request)
    {
        $stats = [
            'total' => Submission::count(),
            'pending_submission' => Submission::whereIn('workflow_status', SubmissionWorkflowGroups::SUBMITTED)->count(),
            'pending_pair_review' => Submission::whereIn('workflow_status', array_merge(SubmissionWorkflowGroups::IN_PEER_REVIEW, SubmissionWorkflowGroups::REVISED))->count(),
            'approved' => Submission::where('workflow_status', 'approved')->count(),
            'pending' => Submission::whereIn('workflow_status', SubmissionWorkflowGroups::SUBMITTED)->count(),
            'under_review' => Submission::whereIn('workflow_status', array_merge(SubmissionWorkflowGroups::IN_PEER_REVIEW, SubmissionWorkflowGroups::REVISED))->count(),
        ];

        $query = Submission::with(['user.department']);
        $filter = $this->applySubmissionFilters($query, $request, 'pending');
        $recentSubmissions = $query->limit(10)->get();

        return view('main.dashboard', [
            'stats' => $stats,
            'submissions' => $recentSubmissions,
            'currentFilter' => $filter,
            'currentSearch' => trim((string) $request->get('q', '')),
            'defaultCaptionTone' => AppSetting::get('caption_tone', 'formal'),
        ]);
    }

    public function submissions(Request $request)
    {
        $query = Submission::with('user');

        if ($request->user()->canSubmitPosts()) {
            $query->where('user_id', $request->user()->id);
        }

        $filter = $this->applySubmissionFilters($query, $request, 'all');
        $submissions = $query->paginate(15)->withQueryString();

        return view('Submission.SubmitForm', [
            'submissions' => $submissions,
            'currentFilter' => $filter,
            'currentSearch' => trim((string) $request->get('q', '')),
            'currentSort' => $request->get('sort', 'created_at'),
            'currentOrder' => strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc',
        ]);
    }

    public function submissionFeedback($submissionId)
    {
        $submission = Submission::with(['user', 'feedback.user'])
            ->findOrFail($submissionId);

        return view('Submission.Feedback', [
            'submission' => $submission,
            'feedback' => $submission->feedback()->with('user')->orderBy('created_at', 'desc')->get()
        ]);
    }

    public function orgDashboard(Request $request)
    {
        $userId = auth()->id();

        $stats = [
            'total' => Submission::where('user_id', $userId)->count(),
            'pending_submission' => Submission::where('user_id', $userId)->whereIn('workflow_status', SubmissionWorkflowGroups::SUBMITTED)->count(),
            'pending_pair_review' => Submission::where('user_id', $userId)->whereIn('workflow_status', array_merge(SubmissionWorkflowGroups::IN_PEER_REVIEW, SubmissionWorkflowGroups::REVISED))->count(),
            'ready_for_review' => Submission::where('user_id', $userId)
                ->whereIn('workflow_status', SubmissionWorkflowGroups::READY_FOR_ORG_REVIEW)
                ->whereNotNull('enhanced_caption')
                ->count(),
            'approved' => Submission::where('user_id', $userId)->where('workflow_status', 'approved')->count(),
        ];

        $query = Submission::where('user_id', $userId)
            ->with(['feedback.user', 'enhancer' => function ($q) {
                $q->select('id', 'name');
            }]);

        $filter = $this->applySubmissionFilters($query, $request, 'all');
        $submissions = $query->get();

        $showAwaitingSection = in_array($filter, ['all', 'ready_for_review', 'under_peer_review'], true);
        $awaitingApproval = $showAwaitingSection
            ? $submissions->filter(fn ($s) => $s->enhanced_caption
                && SubmissionWorkflowGroups::matches($s->workflow_status, SubmissionWorkflowGroups::READY_FOR_ORG_REVIEW))->values()
            : collect();

        $feedback = Feedback::whereIn('submission_id', function ($query) use ($userId) {
            $query->select('id')->from('submissions')->where('user_id', $userId);
        })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('Submission.OrgDashboard', [
            'stats' => $stats,
            'submissions' => $submissions,
            'awaitingApproval' => $awaitingApproval,
            'feedback' => $feedback,
            'user' => auth()->user(),
            'currentFilter' => $filter,
            'currentSearch' => trim((string) $request->get('q', '')),
        ]);
    }
}
