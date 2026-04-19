<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubmissionController extends Controller
{
    /**
     * Store a new submission (Org/Department)
     * POST /api/submissions
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'original_caption' => 'required|string',
                'links' => 'nullable|array',
                'media.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $mediaPaths = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $path = $file->store('submissions/media', 'public');
                    $mediaPaths[] = $path;
                }
            }

            $submission = Submission::create([
                'user_id' => auth()->id() ?? 1,
                'original_caption' => $request->original_caption,
                'links' => $request->links ?? [],
                'media_paths' => $mediaPaths,
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submission created successfully!',
                'data' => $submission->load('user')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Submission creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create submission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get all submissions with optional filtering (PAIR/Dashboard)
     * GET /api/submissions/pending
     * GET /api/submissions?status=pending&sort=created_at
     */
    public function index(Request $request)
    {
        try {
            $query = Submission::with('user');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $sortOrder = $request->get('order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $submissions = $query->get();

            return response()->json([
                'success' => true,
                'count' => $submissions->count(),
                'data' => $submissions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch submissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch submissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific submission
     * GET /api/submissions/{id}
     */
    public function show($id)
    {
        try {
            $submission = Submission::with('user')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Submission not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Enhance caption via LLM (OpenAI, Gemini, or Deepseek)
     * POST /api/submissions/{id}/enhance
     * Body: { "llm_provider": "openai" } (optional, defaults to openai)
     */
    public function enhance(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $provider = $request->get('llm_provider', 'openai');
            $tone = $request->get('tone', 'formal');

            $systemPrompt = "You are a professional social media manager for a university. ";
            $systemPrompt .= "Enhance the following caption to be {$tone}, engaging, professional, and grammatically correct. ";
            $systemPrompt .= "Return only the enhanced caption without any additional text.";

            $enhancedText = null;

            // Try the selected LLM provider
            switch ($provider) {
                case 'gemini':
                    $enhancedText = $this->enhanceWithGemini($submission->original_caption, $systemPrompt);
                    break;
                case 'deepseek':
                    $enhancedText = $this->enhanceWithDeepseek($submission->original_caption, $systemPrompt);
                    break;
                case 'openai':
                default:
                    $enhancedText = $this->enhanceWithOpenAI($submission->original_caption, $systemPrompt);
            }

            if (!$enhancedText) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to enhance caption with LLM provider'
                ], 500);
            }

            $submission->update(['enhanced_caption' => $enhancedText]);

            return response()->json([
                'success' => true,
                'message' => 'Caption enhanced successfully!',
                'data' => [
                    'submission_id' => $submission->id,
                    'original_caption' => $submission->original_caption,
                    'enhanced_caption' => $enhancedText,
                    'provider_used' => $provider
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Caption enhancement failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to enhance caption',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a submission and notify the organization
     * PUT /api/submissions/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $submission->update(['status' => 'approved']);

            // Notify the organization
            $user = User::find($submission->user_id);
            if ($user) {
                $user->notify(new SubmissionApproved($submission));
            }

            return response()->json([
                'success' => true,
                'message' => 'Submission approved and organization notified!',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            Log::error('Submission approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update submission status
     * PUT /api/submissions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'in:pending,under_review,approved'
            ]);

            $submission = Submission::findOrFail($id);
            $submission->update($request->only('status'));

            return response()->json([
                'success' => true,
                'message' => 'Submission updated successfully!',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update submission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a submission
     * DELETE /api/submissions/{id}
     */
    public function destroy($id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $submission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Submission deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhance caption using OpenAI API
     */
    private function enhanceWithOpenAI($caption, $systemPrompt)
    {
        try {
            $apiKey = env('OPEN_AI_KEY');
            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                return null;
            }

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $caption]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API error: ' . $response->body());
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('OpenAI enhancement error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enhance caption using Google Gemini API
     */
    private function enhanceWithGemini($caption, $systemPrompt)
    {
        try {
            $apiKey = env('GEMINI_KEY');
            if (!$apiKey) {
                Log::warning('Gemini API key not configured');
                return null;
            }

            $response = Http::timeout(30)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt . "\n\nCaption: " . $caption]
                            ]
                        ]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Gemini API error: ' . $response->body());
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Exception $e) {
            Log::error('Gemini enhancement error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enhance caption using Deepseek API
     */
    private function enhanceWithDeepseek($caption, $systemPrompt)
    {
        try {
            $apiKey = env('DEEPSEEK_KEY');
            if (!$apiKey) {
                Log::warning('Deepseek API key not configured');
                return null;
            }

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $caption]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]);

            if ($response->failed()) {
                Log::error('Deepseek API error: ' . $response->body());
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('Deepseek enhancement error: ' . $e->getMessage());
            return null;
        }
    }
}