<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;

class FeedbackController extends Controller
{
    /**
     * Get all feedback for a submission
     * GET /api/submissions/{submission_id}/feedback
     */
    public function index($submission_id)
    {
        try {
            $submission = Submission::findOrFail($submission_id);
            $feedback = Feedback::where('submission_id', $submission_id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'submission_id' => $submission_id,
                'count' => $feedback->count(),
                'data' => $feedback
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Submission not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Add feedback/comment to a submission
     * POST /api/submissions/{submission_id}/feedback
     */
    public function store(Request $request, $submission_id)
    {
        try {
            $request->validate([
                'message' => 'required|string|min:3|max:1000'
            ]);

            // Verify submission exists
            Submission::findOrFail($submission_id);

            $feedback = Feedback::create([
                'submission_id' => $submission_id,
                'user_id' => auth()->id() ?? 1,
                'message' => $request->message
            ]);

            $feedback->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Feedback submitted successfully!',
                'data' => $feedback
            ], 201);
        } catch (Exception $e) {
            Log::error('Feedback creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback',
                'error' => $e->getMessage()
            ], $e instanceof ModelNotFoundException ? 404 : 422);
        }
    }

    /**
     * Get a specific feedback record
     * GET /api/feedback/{id}
     */
    public function show($id)
    {
        try {
            $feedback = Feedback::with(['user', 'submission'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $feedback
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update feedback
     * PUT /api/feedback/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'message' => 'required|string|min:3|max:1000'
            ]);

            $feedback = Feedback::findOrFail($id);

            // Check if user is the feedback author or admin
            $authId = auth()->id();
            $isAuthor = $feedback->user_id === $authId;
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
            
            if (!$isAuthor && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $feedback->update(['message' => $request->message]);
            $feedback->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully!',
                'data' => $feedback
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update feedback',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete feedback
     * DELETE /api/feedback/{id}
     */
    public function destroy($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);

            // Check if user is the feedback author or admin
            $authId = auth()->id();
            $isAuthor = $feedback->user_id === $authId;
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
            
            if (!$isAuthor && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $feedback->delete();

            return response()->json([
                'success' => true,
                'message' => 'Feedback deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}