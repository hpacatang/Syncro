<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubmissionController extends Controller
{
    // 1. FOR THE ORG: Submit a new post
    public function store(Request $request)
    {
        $request->validate([
            'original_caption' => 'required|string',
            'media.*' => 'image|mimes:jpeg,png,jpg|max:2048' // Validate pictures
        ]);

        $mediaPaths = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $mediaPaths[] = $file->store('submissions/media', 'public');
            }
        }

        $submission = Submission::create([
            'user_id' => auth()->id() ?? 1, // Assume logged-in user (hardcoded to 1 for testing)
            'original_caption' => $request->original_caption,
            'links' => $request->links ?? [],
            'media_paths' => $mediaPaths,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Submission successful!', 'data' => $submission]);
    }

    // 2. FOR PAIR: View all pending submissions
    public function index()
    {
        $submissions = Submission::with('user')->where('status', 'pending')->get();
        return response()->json($submissions);
    }

    // 3. FOR PAIR: Enhance Caption via LLM (OpenAI example)
    public function enhance(Request $request, $id)
    {
        $submission = Submission::findOrFail($id);
        
        // Call to OpenAI API (Make sure you add OPENAI_API_KEY to your .env file!)
        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional social media manager for a university. Enhance the following caption to be engaging, professional, and grammatically correct.'],
                ['role' => 'user', 'content' => $submission->original_caption]
            ]
        ]);

        $enhancedText = $response->json('choices.0.message.content');

        $submission->update(['enhanced_caption' => $enhancedText]);

        return response()->json(['message' => 'Caption enhanced!', 'enhanced_caption' => $enhancedText]);
    }

    // 4. FOR PAIR: Approve the submission
    public function approve($id)
    {
        $submission = Submission::findOrFail($id);
        $submission->update(['status' => 'approved']);

        // Trigger Laravel Notification here to alert the Org!
        $user = User::find($submission->user_id);
        if ($user) {
            $user->notify(new SubmissionApproved($submission));
        }
        
        return response()->json(['message' => 'Submission approved and ready to post!']);
    }
}