<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Submission;
use App\Models\User;
use App\Support\PairUpdateFormatter;
use App\Notifications\EnhancementReadyForOrg;
use App\Notifications\SubmissionApproved;
use App\Notifications\SubmissionQueuedForReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\File;

class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', Submission::class);

        try {
            $request->validate([
                'original_caption' => 'required|string',
                'links' => 'nullable|array',
                'media' => 'nullable|array',
                'media.*' => [
                    'file',
                    File::types(['jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'pdf', 'txt'])
                        ->max(5 * 1024),
                ],
            ]);

            $mediaPaths = [];
            $mediaDisk = config('filesystems.disks.supabase.key')
                ? 'supabase'
                : 'public';

            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $path = $file->store('submissions/media', $mediaDisk);
                    $mediaPaths[] = $path;
                }
            }

            $userId = auth()->id();
            if (! $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated. Please log in first.',
                ], 401);
            }

            $submission = Submission::create([
                'user_id' => $userId,
                'original_caption' => $request->original_caption,
                'links' => $request->links ?? [],
                'media_paths' => $mediaPaths,
                'status' => 'pending',
                'workflow_status' => 'submitted'
            ]);

            User::query()
                ->whereIn('role', ['admin', 'pair'])
                ->each(function (User $staff) use ($submission) {
                    $staff->notify(new SubmissionQueuedForReview($submission));
                });

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

    public function index(Request $request)
    {
        $this->authorize('viewAny', Submission::class);

        try {
            $query = Submission::with('user');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

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

    public function show($id)
    {
        try {
            $submission = Submission::with('user')->findOrFail($id);
            $this->authorize('view', $submission);
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

    public function enhance(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('reviewAsStaff', $submission);
            $provider = $request->get('llm_provider', 'openai');
            $tone = $request->get('tone', AppSetting::get('caption_tone', 'formal'));

            $systemPrompt = "You are a professional social media manager for a university. ";
            $systemPrompt .= "Enhance the following caption to be {$tone}, engaging, professional, and grammatically correct. ";
            $systemPrompt .= "Return only the enhanced caption without any additional text.";

            $enhancedText = null;

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
                
                return response()->json([
                    'success' => false,
                    'fallback' => true,
                    'message' => "Failed to generate via {$provider}. Please manually enhance the caption below.",
                    'original_caption' => $submission->original_caption,
                    'data' => [
                        'submission_id' => $id,
                        'provider' => $provider
                    ]
                ], 202);
            }

            $actor = auth()->user();
            $submission->update([
                'enhanced_caption' => $enhancedText,
                'enhanced_by' => auth()->id(),
                'enhanced_at' => now(),
                'workflow_status' => 'under_peer_review',
                'pair_feedback' => PairUpdateFormatter::append(
                    $submission->pair_feedback,
                    PairUpdateFormatter::step($actor, 'Caption enhanced — ready for your review')
                ),
            ]);

            User::find($submission->user_id)?->notify(new EnhancementReadyForOrg($submission->fresh()));

            return response()->json([
                'success' => true,
                'message' => 'Caption enhanced! Ready for organization review.',
                'data' => [
                    'submission_id' => $submission->id,
                    'original_caption' => $submission->original_caption,
                    'enhanced_caption' => $enhancedText,
                    'provider_used' => $provider,
                    'workflow_status' => 'under_peer_review'
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

    public function approve(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('reviewAsStaff', $submission);
            $submission->update(['status' => 'approved']);

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

    public function saveManualCaption(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('reviewAsStaff', $submission);

            $request->validate([
                'manual_caption' => 'required|string|min:10',
                'pair_feedback' => 'nullable|string'
            ]);

            $actor = auth()->user();
            $feedback = $request->pair_feedback
                ? PairUpdateFormatter::append(
                    $submission->pair_feedback,
                    PairUpdateFormatter::step($actor, 'Manual caption saved', $request->pair_feedback)
                )
                : PairUpdateFormatter::append(
                    $submission->pair_feedback,
                    PairUpdateFormatter::step($actor, 'Manual caption saved — ready for your review')
                );

            $submission->update([
                'enhanced_caption' => $request->manual_caption,
                'enhanced_by' => auth()->id(),
                'enhanced_at' => now(),
                'pair_feedback' => $feedback,
                'workflow_status' => 'under_peer_review',
            ]);

            User::find($submission->user_id)?->notify(new EnhancementReadyForOrg($submission->fresh()));

            return response()->json([
                'success' => true,
                'message' => 'Caption enhanced! Ready for organization review.',
                'data' => [
                    'submission_id' => $submission->id,
                    'enhanced_caption' => $submission->enhanced_caption,
                    'workflow_status' => 'under_peer_review'
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

    public function update(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('update', $submission);

            $request->validate([
                'status' => 'in:pending,under_review,approved'
            ]);
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

    public function destroy($id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('delete', $submission);
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

    public function orgApproveEnhancement(Request $request, $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $this->authorize('reviewAsOrg', $submission);

            $submission->update([
                'status' => 'approved',
                'workflow_status' => 'approved',
                'org_review_notes' => $request->notes ?? 'Approved by organization'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caption approved! Ready to be posted.',
                'data' => [
                    'submission_id' => $submission->id,
                    'workflow_status' => 'approved',
                ],
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

    public function orgRejectEnhancement(Request $request, $id)
    {
        try {
            $request->validate([
                'notes' => 'required|string|min:10',
            ]);

            $submission = Submission::findOrFail($id);
            $this->authorize('reviewAsOrg', $submission);

            $submission->update([
                'workflow_status' => 'revised',
                'org_review_notes' => $request->notes,
                'status' => 'under_review'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback sent to PAIR for further enhancements.',
                'data' => [
                    'submission_id' => $submission->id,
                    'workflow_status' => 'revised',
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

    private function enhanceWithOpenAI($caption, $systemPrompt)
    {
        return $this->callLlm(
            env('OPEN_AI_KEY'),
            fn ($key) => Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $caption],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]),
            fn ($response) => $response->json('choices.0.message.content'),
            'OpenAI'
        );
    }

    private function enhanceWithGemini($caption, $systemPrompt)
    {
        return $this->callLlm(
            env('GEMINI_KEY'),
            fn ($key) => Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$key}",
                [
                    'contents' => [[
                        'parts' => [['text' => $systemPrompt."\n\nCaption: ".$caption]],
                    ]],
                ]
            ),
            fn ($response) => $response->json('candidates.0.content.parts.0.text'),
            'Gemini'
        );
    }

    private function enhanceWithDeepseek($caption, $systemPrompt)
    {
        return $this->callLlm(
            env('DEEPSEEK_KEY'),
            fn ($key) => Http::withToken($key)->timeout(30)->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $caption],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]),
            fn ($response) => $response->json('choices.0.message.content'),
            'Deepseek'
        );
    }

    private function callLlm(?string $apiKey, callable $request, callable $parse, string $provider): ?string
    {
        if (! $apiKey) {
            return null;
        }

        try {
            $response = $request($apiKey);
            if ($response->failed()) {
                Log::error("{$provider} API error", ['status' => $response->status()]);

                return null;
            }

            return $parse($response);
        } catch (\Exception $e) {
            Log::error("{$provider} enhancement failed", ['message' => $e->getMessage()]);

            return null;
        }
    }
}