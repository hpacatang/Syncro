<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Main;
use App\Models\Submission;
use App\Models\Feedback;

class MainController extends Controller
{
    /**
     * Display the main dashboard with submission statistics
     */
    public function index()
    {
        // For PAIR staff, show submissions pending their review or pending org approval
        $stats = [
            'total' => Submission::count(),
            'pending_submission' => Submission::where('workflow_status', 'pending_submission')->count(),
            'pending_pair_review' => Submission::where('workflow_status', 'pending_pair_review')->count(),
            'pending_org_approval' => Submission::where('workflow_status', 'pending_org_approval')->count(),
            'approved' => Submission::where('workflow_status', 'approved')->count(),
        ];

        // Show submissions that PAIR needs to work on (either fresh or sent back for revision)
        $recentSubmissions = Submission::with('user')
            ->where(function($query) {
                $query->where('workflow_status', 'pending_submission')
                      ->orWhere('workflow_status', 'pending_pair_review');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('main.dashboard', [
            'stats' => $stats,
            'submissions' => $recentSubmissions
        ]);
    }

    /**
     * Display all submissions with filtering options
     */
    public function submissions(Request $request)
    {
        $query = Submission::with('user');

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Sort by
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $submissions = $query->paginate(15);

        return view('Submission.SubmitForm', [
            'submissions' => $submissions,
            'currentFilter' => $request->get('status', 'all')
        ]);
    }

    /**
     * Display notifications for the authenticated user
     */
    public function notifications()
    {
        $notifications = [];
        
        if (auth()->check()) {
            $notifications = auth()->user()->notifications()->paginate(20);
        }

        return view('main.notifications', [
            'notifications' => $notifications
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
    public function orgDashboard()
    {
        $userId = auth()->id();
        
        // Get user's submissions with statistics based on workflow status
        $stats = [
            'total' => Submission::where('user_id', $userId)->count(),
            'pending_submission' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_submission')->count(),
            'pending_pair_review' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_pair_review')->count(),
            'pending_org_approval' => Submission::where('user_id', $userId)->where('workflow_status', 'pending_org_approval')->count(),
            'approved' => Submission::where('user_id', $userId)->where('workflow_status', 'approved')->count(),
        ];

        // Get user's submissions
        $submissions = Submission::where('user_id', $userId)
            ->with(['feedback.user', 'enhancer' => function($query) {
                $query->select('id', 'name');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Separate submissions into categories for the UI
        $pendingReview = $submissions->whereIn('workflow_status', ['pending_submission', 'pending_pair_review']);
        $awaitingApproval = $submissions->where('workflow_status', 'pending_org_approval');
        $approved = $submissions->where('workflow_status', 'approved');

        // Get feedback from PAIR
        $feedback = Feedback::whereIn('submission_id', function($query) use ($userId) {
            $query->select('id')->from('submissions')->where('user_id', $userId);
        })
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return view('Submission.OrgDashboard', [
            'stats' => $stats,
            'submissions' => $submissions,
            'pendingReview' => $pendingReview,
            'awaitingApproval' => $awaitingApproval,
            'approved' => $approved,
            'feedback' => $feedback,
            'user' => auth()->user()
        ]);
    }
}
