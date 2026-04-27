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

            // Log authentication info for debugging
            Log::info('Submission store() called', [
                'authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'user' => auth()->user()?->name ?? 'Not authenticated'
            ]);

            $mediaPaths = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $path = $file->store('submissions/media', 'public');
                    $mediaPaths[] = $path;
                }
            }

            $userId = auth()->id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated. Please log in first.',
                    'debug' => [
                        'authenticated' => auth()->check(),
                        'user_id' => auth()->id()
                    ]
                ], 401);
            }

            $submission = Submission::create([
                'user_id' => $userId,
                'original_caption' => $request->original_caption,
                'links' => $request->links ?? [],
                'media_paths' => $mediaPaths,
                'status' => 'pending',
                'workflow_status' => 'pending_submission'
            ]);

            Log::info('Submission created', [
                'submission_id' => $submission->id,
                'user_id' => $userId,
                'user_name' => auth()->user()?->name
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

            Log::info("Enhancing caption", [
                'submission_id' => $id,
                'provider' => $provider,
                'tone' => $tone,
                'caption_length' => strlen($submission->original_caption)
            ]);

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
                Log::error("LLM enhancement failed - null response", [
                    'submission_id' => $id,
                    'provider' => $provider
                ]);
                
                // Return a response indicating manual fallback is needed
                return response()->json([
                    'success' => false,
                    'fallback' => true,
                    'message' => "Failed to generate via {$provider}. Please manually enhance the caption below.",
                    'original_caption' => $submission->original_caption,
                    'data' => [
                        'submission_id' => $id,
                        'provider' => $provider
                    ]
                ], 202); // 202 Accepted - operation acknowledged but not completed
            }

            $submission->update([
                'enhanced_caption' => $enhancedText,
                'enhanced_by' => auth()->id(),
                'enhanced_at' => now(),
                'workflow_status' => 'pending_org_approval'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caption enhanced! Awaiting organization review.',
                'data' => [
                    'submission_id' => $submission->id,
                    'original_caption' => $submission->original_caption,
                    'enhanced_caption' => $enhancedText,
                    'provider_used' => $provider,
                    'workflow_status' => 'pending_org_approval'
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
     * Save manual caption (fallback when LLM fails)
     * POST /api/submissions/{id}/save-manual-caption
     */
    public function saveManualCaption(Request $request, $id)
    {
        try {
            $request->validate([
                'manual_caption' => 'required|string|min:10',
                'pair_feedback' => 'nullable|string'
            ]);

            $submission = Submission::findOrFail($id);
            $submission->update([
                'enhanced_caption' => $request->manual_caption,
                'enhanced_by' => auth()->id(),
                'enhanced_at' => now(),
                'pair_feedback' => $request->pair_feedback,
                'workflow_status' => 'pending_org_approval'
            ]);

            Log::info('Manual caption saved', [
                'submission_id' => $id,
                'enhanced_by' => auth()->id(),
                'caption_length' => strlen($request->manual_caption)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caption enhanced! Awaiting organization review.',
                'data' => [
                    'submission_id' => $submission->id,
                    'enhanced_caption' => $submission->enhanced_caption,
                    'workflow_status' => 'pending_org_approval'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Manual caption save failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save manual caption',
                'error' => $e->getMessage()
            ], 422);
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
     * Organization reviews and approves enhanced caption
     * POST /api/submissions/{id}/org-review/approve
     */
    public function orgApproveEnhancement(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            
            // Verify the org user owns this submission
            if ($submission->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only review your own submissions'
                ], 403);
            }

            $submission->update([
                'status' => 'approved',
                'workflow_status' => 'approved',
                'org_review_notes' => $request->notes ?? 'Approved by organization'
            ]);

            Log::info('Organization approved enhanced caption', [
                'submission_id' => $id,
                'org_user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caption approved! Ready to be posted.',
                'data' => [
                    'submission_id' => $submission->id,
                    'workflow_status' => 'approved'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Organization approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve caption',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Organization reviews and rejects enhanced caption (asks for further enhancement)
     * POST /api/submissions/{id}/org-review/reject
     */
    public function orgRejectEnhancement(Request $request, $id)
    {
        try {
            $request->validate([
                'notes' => 'required|string|min:10'
            ]);

            $submission = Submission::findOrFail($id);
            
            // Verify the org user owns this submission
            if ($submission->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only review your own submissions'
                ], 403);
            }

            $submission->update([
                'workflow_status' => 'pending_pair_review',
                'org_review_notes' => $request->notes,
                'status' => 'under_review'
            ]);

            Log::info('Organization rejected enhanced caption and requested revisions', [
                'submission_id' => $id,
                'org_user_id' => auth()->id(),
                'reason' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback sent to PAIR for further enhancements.',
                'data' => [
                    'submission_id' => $submission->id,
                    'workflow_status' => 'pending_pair_review',
                    'feedback' => $request->notes
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Organization rejection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback',
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

            Log::info('Calling OpenAI API');

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

            Log::info('OpenAI Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'failed' => $response->failed()
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json()
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');
            Log::info('OpenAI Success', ['content_length' => strlen($content)]);
            return $content;
        } catch (\Exception $e) {
            Log::error('OpenAI enhancement exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
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

            Log::info('Calling Gemini API');

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

            Log::info('Gemini Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'failed' => $response->failed()
            ]);

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json()
                ]);
                return null;
            }

            $content = $response->json('candidates.0.content.parts.0.text');
            Log::info('Gemini Success', ['content_length' => strlen($content)]);
            return $content;
        } catch (\Exception $e) {
            Log::error('Gemini enhancement exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
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

            Log::info('Calling Deepseek API');

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

            Log::info('Deepseek Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'failed' => $response->failed()
            ]);

            if ($response->failed()) {
                Log::error('Deepseek API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json()
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');
            Log::info('Deepseek Success', ['content_length' => strlen($content)]);
            return $content;
        } catch (\Exception $e) {
            Log::error('Deepseek enhancement exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return null;
        }
    }
}