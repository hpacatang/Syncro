<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    // Add a comment to a submission
    public function store(Request $request, $submission_id)
    {
        $request->validate(['message' => 'required|string']);

        $feedback = Feedback::create([
            'submission_id' => $submission_id,
            'user_id' => auth()->id() ?? 1, // The logged-in user sending the message
            'message' => $request->message
        ]);

        return response()->json(['message' => 'Feedback sent!', 'data' => $feedback]);
    }
}