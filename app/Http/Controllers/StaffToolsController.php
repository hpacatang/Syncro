<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Submission;
use App\Support\SubmissionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\Rules\File;

class StaffToolsController extends Controller
{
    public function mediaGallery(): View
    {
        $assets = [];

        $submissions = Submission::query()
            ->with('user:id,name')
            ->whereNotNull('media_paths')
            ->orderByDesc('created_at')
            ->get();

        foreach ($submissions as $submission) {
            $paths = $submission->media_paths ?? [];
            if (! is_array($paths)) {
                continue;
            }
            foreach ($paths as $path) {
                if (! $path || SubmissionMedia::resolve($path) === null) {
                    continue;
                }
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $assets[] = [
                    'url' => SubmissionMedia::url($path),
                    'name' => basename($path),
                    'ext' => $ext,
                    'submission_id' => $submission->id,
                    'org' => $submission->user?->displayName(),
                    'created_at' => $submission->created_at,
                ];
            }
        }

        return view('main.media-gallery', ['assets' => $assets]);
    }

    public function captionAssist(): View
    {
        return view('main.caption-assist');
    }

    public function captionFromMedia(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:5000',
            'llm_provider' => 'nullable|string|in:openai,gemini',
            'media' => 'nullable|array',
            'media.*' => [
                'file',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'pdf', 'txt'])
                    ->max(5 * 1024),
            ],
        ]);

        $tone = AppSetting::get('caption_tone', 'formal');
        $provider = $request->input('llm_provider', 'openai');
        $notes = (string) $request->input('notes', '');

        $fileSummaries = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $fileSummaries[] = sprintf(
                    '- %s (%s, %s bytes)',
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    $file->getSize()
                );
            }
        }

        $system = 'You are a concise university social media editor. '
            ."Write one ready-to-post caption. Tone: {$tone}. "
            .'Use file names and user notes as context. Max 280 words. No hashtags unless notes ask.';

        $userContent = "User notes:\n{$notes}\n\nAttached files (metadata only):\n"
            .(count($fileSummaries) ? implode("\n", $fileSummaries) : '(none)');

        $caption = $provider === 'gemini'
            ? $this->callGemini($system, $userContent)
            : $this->callOpenAI($system, $userContent);

        if (! $caption) {
            return response()->json([
                'success' => false,
                'message' => 'AI caption could not be generated. Check OPEN_AI_KEY or GEMINI_KEY in .env.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ['caption' => trim($caption), 'tone' => $tone, 'provider' => $provider],
        ]);
    }

    private function callOpenAI(string $system, string $user): ?string
    {
        $key = config('services.openai.key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::withToken($key)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI caption-from-media failed', ['body' => $response->body()]);

                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::error('OpenAI caption-from-media', ['e' => $e->getMessage()]);

            return null;
        }
    }

    private function callGemini(string $system, string $user): ?string
    {
        $key = config('services.gemini.key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key='.urlencode($key),
                    [
                        'contents' => [[
                            'parts' => [['text' => $system."\n\n".$user]],
                        ]],
                    ]
                );

            if ($response->failed()) {
                Log::warning('Gemini caption-from-media failed', ['body' => $response->body()]);

                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Throwable $e) {
            Log::error('Gemini caption-from-media', ['e' => $e->getMessage()]);

            return null;
        }
    }
}
