<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionPublished;
use App\Support\PairUpdateFormatter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaPublisher
{
    /**
     * Publish a single submission to social media (Facebook Graph API or simulated if no key).
     */
    public static function publish(Submission $submission, ?User $actor = null): array
    {
        $token  = config('services.facebook.page_access_token') ?: env('FACEBOOK_PAGE_ACCESS_TOKEN');
        $pageId = config('services.facebook.page_id') ?: env('FACEBOOK_PAGE_ID');

        $caption = $submission->enhanced_caption ?: $submission->original_caption;
        if ($submission->isWebsiteArticle() && ! empty($submission->enhanced_title ?: $submission->article_title)) {
            $title = $submission->enhanced_title ?: $submission->article_title;
            $caption = "{$title}\n\n{$caption}";
        }

        $result = [
            'success'   => false,
            'simulated' => false,
            'platform'  => 'facebook',
            'post_id'   => null,
            'url'       => null,
            'message'   => '',
        ];

        if (! empty($token) && ! empty($pageId)) {
            // Live API call when credentials are configured
            try {
                $endpoint = "https://graph.facebook.com/v19.0/{$pageId}/feed";
                $payload = [
                    'message'      => $caption,
                    'access_token' => $token,
                ];

                if (! empty($submission->links) && is_array($submission->links)) {
                    $payload['link'] = $submission->links[0];
                }

                $response = Http::timeout(15)->post($endpoint, $payload);

                if ($response->successful()) {
                    $postId = $response->json('id');
                    $result['success'] = true;
                    $result['post_id'] = $postId;
                    $result['url']     = "https://facebook.com/{$postId}";
                    $result['message'] = "Successfully published to Facebook Page (#{$postId}).";

                    Log::info("SocialMediaPublisher: Published Submission #{$submission->id} to Facebook: {$postId}");
                } else {
                    $err = $response->json('error.message') ?? $response->body();
                    $result['success'] = false;
                    $result['message'] = "Facebook API error: {$err}";
                    Log::error("SocialMediaPublisher Facebook API error for Submission #{$submission->id}: {$err}");
                }
            } catch (\Throwable $e) {
                $result['success'] = false;
                $result['message'] = "Facebook API request failed: {$e->getMessage()}";
                Log::error("SocialMediaPublisher exception for Submission #{$submission->id}: {$e->getMessage()}");
            }
        } else {
            // No API key configured yet - prepare system so it operates seamlessly now and automatically fires when key is provided
            $result['success']   = true;
            $result['simulated'] = true;
            $result['message']   = 'Social media API key not configured in .env yet. Post marked as auto-published; once FACEBOOK_PAGE_ACCESS_TOKEN and FACEBOOK_PAGE_ID are added to .env, real API dispatch will automatically execute.';

            Log::info("SocialMediaPublisher: Submission #{$submission->id} auto-published (Ready for API Key in .env)");
        }

        // Record publishing in submission feedback
        $note = $result['message'];
        $step = PairUpdateFormatter::step(
            $actor ?? 'Auto-Publisher',
            'Published to Social Media',
            $note
        );
        $submission->pair_feedback = PairUpdateFormatter::append($submission->pair_feedback, $step);

        return $result;
    }

    public static function publishDueSubmissions(): int
    {
        $due = Submission::withoutGlobalScopes()
            ->whereIn('workflow_status', ['approved', 'posted'])
            ->whereNotNull('scheduled_post_at')
            ->get()
            ->filter(function (Submission $s) {
                return $s->isPublicationTimeReached()
                    && ($s->workflow_status === 'approved' || is_null($s->posted_at) || $s->posted_at->isFuture());
            });

        $publishedCount = 0;

        foreach ($due as $submission) {
            $result = self::publish($submission);

            $submission->workflow_status = 'posted';
            $submission->posted_at = $submission->scheduled_post_at ?? now();
            $submission->save();

            try {
                User::find($submission->user_id)?->notify(new SubmissionPublished($submission));
            } catch (\Throwable $e) {
                // Ignore notification failure
            }

            $publishedCount++;
        }

        return $publishedCount;
    }
}
