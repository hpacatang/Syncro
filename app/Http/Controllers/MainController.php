<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Models\Submission;
use App\Models\Feedback;

class MainController extends Controller
{
    private const WORKFLOW_FILTERS = [
        'pending_submission',
        'pending_pair_review',
        'pending_org_approval',
        'approved',
        'rejected',
        'posted',
    ];

    /**
     * Apply workflow status, search, and sort filters to a submission query.
     */
    private function applySubmissionFilters(Builder $query, Request $request, string $defaultStatus = 'all'): string
    {
        $filter = $request->get('status', $defaultStatus);

        switch ($filter) {
            case 'all':
                break;
            case 'pending':
                $query->where(function ($q) {
                    $q->where('workflow_status', 'pending_submission')
                        ->orWhere('workflow_status', 'pending_pair_review');
                });
                break;
            default:
                if (in_array($filter, self::WORKFLOW_FILTERS, true)) {
                    $query->where('workflow_status', $filter);
                }
                break;
        }

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search, $request) {
                $q->where('original_caption', 'like', '%' . $search . '%')
                    ->orWhere('enhanced_caption', 'like', '%' . $search . '%');

                if (!$request->user()->isOrg()) {
                    $q->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
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
    /**
     * Display the main dashboard with submission statistics
     */
    public function index(Request $request)
    {
        // For PAIR staff, show submissions pending their review or pending org approval
        $stats = [
            'total' => Submission::count(),
            'pending_submission' => Submission::where('workflow_status', 'pending_submission')->count(),
            'pending_pair_review' => Submission::where('workflow_status', 'pending_pair_review')->count(),
            'pending_org_approval' => Submission::where('workflow_status', 'pending_org_approval')->count(),
            'approved' => Submission::where('workflow_status', 'approved')->count(),
        ];

        $query = Submission::with('user');
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

    /**
     * Display all submissions with filtering options
     */
    public function submissions(Request $request)
    {
        $query = Submission::with('user');

        if ($request->user()->isOrg()) {
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

    /**
     * Display feedback for a specific submission
     */
    public function submissionFeedback($submissionId)
    {
        $submission = Submission::with(['user', 'feedback.user'])
            ->findOrFail($submissionId);

        return view('Submission.Feedback', [
            'submission' => $submission,
            'feedback' => $submission->feedback()->with('user')->orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Display the organization dashboard
     */
    public function orgDashboard(Request $request)
    {
        $userId = auth()->id();

        $stats = [
            'total' => Submission::where('user_id', $userId)->count(),
            'pending_submission' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_submission')->count(),
            'pending_pair_review' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_pair_review')->count(),
            'pending_org_approval' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_org_approval')->count(),
            'approved' => Submission::where('user_id', $userId)->where('workflow_status', 'approved')->count(),
        ];

        $query = Submission::where('user_id', $userId)
            ->with(['feedback.user', 'enhancer' => function ($q) {
                $q->select('id', 'name');
            }]);

        $filter = $this->applySubmissionFilters($query, $request, 'all');
        $submissions = $query->get();

        $showAwaitingSection = in_array($filter, ['all', 'pending_org_approval'], true);
        $awaitingApproval = $showAwaitingSection
            ? $submissions->where('workflow_status', 'pending_org_approval')->values()
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
