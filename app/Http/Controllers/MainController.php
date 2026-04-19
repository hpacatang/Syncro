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
        $stats = [
            'total' => Submission::count(),
            'pending' => Submission::where('status', 'pending')->count(),
            'under_review' => Submission::where('status', 'under_review')->count(),
            'approved' => Submission::where('status', 'approved')->count(),
        ];

        $recentSubmissions = Submission::with('user')
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
}
