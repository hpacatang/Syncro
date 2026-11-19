<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionPublished;
use App\Submission\Enums\SubmissionLifecycleStatus;
use App\Support\PairUpdateFormatter;
use App\Support\SubmissionMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SocialMediaPublisher
{
    private const RETRY_BASE_MINUTES = 5;

    private const MAX_RETRY_MINUTES = 60;

    private const STALE_PUBLISH_MINUTES = 10;

    public static function publish(Submission $submission, ?User $actor = null, bool $force = false): array
    {
        if ($submission->isWebsiteArticle()) {
            return self::failure(
                'Website article publishing is not available until the university CMS API is configured.',
                false
            );
        }

        $claim = self::claim($submission, $force);
        if (! $claim['claimed']) {
            return $claim['result'];
        }

        $submission = $claim['submission'];
        $token = config('services.meta.access_token');
        $pageId = config('services.meta.page_id');

        if (! is_string($token) || trim($token) === '' || ! is_string($pageId) || trim($pageId) === '') {
            return self::markFailed(
                $submission,
                'Facebook publishing is not configured. Set META_ACCESS_TOKEN and META_PAGE_ID to a Facebook Page access token and Page ID.'
            );
        }

        $caption = trim((string) ($submission->enhanced_caption ?: $submission->original_caption));
        $links = array_values(array_filter(
            is_array($submission->links) ? $submission->links : [],
            fn ($link) => is_string($link) && trim($link) !== ''
        ));
        $mediaPaths = array_values(array_filter(
            is_array($submission->media_paths) ? $submission->media_paths : [],
            fn ($path) => is_string($path) && trim($path) !== ''
        ));

        $imagePath = null;
        $imageStream = null;
        $imageMimeType = null;
        if ($mediaPaths !== []) {
            if (count($mediaPaths) !== 1 || ! SubmissionMedia::isMetaPublishableImage($mediaPaths[0])) {
                return self::markFailed(
                    $submission,
                    'Facebook publishing currently supports one JPEG, PNG, GIF, BMP, or TIFF image up to 10 MB. Video, document, and multiple attachments are not supported.'
                );
            }

            $imagePath = $mediaPaths[0];
            $resolvedImage = SubmissionMedia::resolve($imagePath, false);
            if (! $resolvedImage) {
                return self::markFailed(
                    $submission,
                    'The attached image could not be read from storage. Check the stored file and try again.'
                );
            }

            try {
                $imageDisk = Storage::disk($resolvedImage['disk']);
                $imageSize = $imageDisk->size($resolvedImage['path']);
                if ($imageSize > SubmissionMedia::MAX_META_PHOTO_SIZE_BYTES) {
                    return self::markFailed(
                        $submission,
                        'Facebook photo uploads must be 10 MB or smaller. Use a smaller image or publish without the attachment.'
                    );
                }

                if ($imageSize < 1) {
                    return self::markFailed($submission, 'The attached image is empty and cannot be published.');
                }

                $imageStream = $imageDisk->readStream($resolvedImage['path']);
            } catch (Throwable $exception) {
                Log::warning('Submission image could not be opened for Meta publishing.', [
                    'submission_id' => $submission->id,
                    'exception' => $exception::class,
                ]);

                return self::markFailed(
                    $submission,
                    'The attached image could not be read from storage. Check the stored file and try again.'
                );
            }

            if (! is_resource($imageStream)) {
                return self::markFailed(
                    $submission,
                    'The attached image could not be read from storage. Check the stored file and try again.'
                );
            }

            $imageMimeType = SubmissionMedia::metaPhotoMimeType($imagePath);
        }

        $apiVersion = (string) config('services.meta.graph_api_version', 'v23.0');
        $endpoint = 'https://graph.facebook.com/'.$apiVersion.'/'.rawurlencode(trim($pageId));
        $isPhoto = $imagePath !== null;
        $url = $endpoint.($isPhoto ? '/photos' : '/feed');
        $message = $caption;

        if ($isPhoto && $links !== []) {
            $message .= "\n\n".implode("\n", $links);
        } elseif (count($links) > 1) {
            $message .= "\n\n".implode("\n", array_slice($links, 1));
        }

        $payload = $isPhoto
            ? ['caption' => $message, 'published' => true]
            : ['message' => $message];

        if (! $isPhoto && count($links) === 1) {
            $payload['link'] = $links[0];
        }

        try {
            $request = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(60);

            if ($isPhoto) {
                $request->attach(
                    'source',
                    $imageStream,
                    basename($imagePath),
                    ['Content-Type' => $imageMimeType]
                );
            }

            $response = $request->post($url, $payload);

            if ($response->failed()) {
                $metaCode = $response->json('error.code');
                $metaSubcode = $response->json('error.error_subcode');
                $metaTraceId = $response->json('error.fbtrace_id');
                $error = $response->json('error.error_user_msg') ?: $response->json('error.message');
                $error = is_string($error) && trim($error) !== ''
                    ? mb_substr(trim($error), 0, 1000)
                    : 'Meta Graph API rejected the publishing request (HTTP '.$response->status().').';
                $diagnostics = [];
                if ($metaCode !== null) {
                    $diagnostics[] = 'Meta code '.$metaCode.($metaSubcode !== null ? '/'.$metaSubcode : '');
                }
                if (is_string($metaTraceId) && trim($metaTraceId) !== '') {
                    $diagnostics[] = 'trace '.$metaTraceId;
                }
                if ($diagnostics !== []) {
                    $error .= ' ('.implode(', ', $diagnostics).').';
                }

                Log::warning('Meta rejected a submission publish request.', [
                    'submission_id' => $submission->id,
                    'http_status' => $response->status(),
                    'meta_error_code' => $metaCode,
                    'meta_error_subcode' => $metaSubcode,
                    'meta_error_type' => $response->json('error.type'),
                    'meta_trace_id' => $metaTraceId,
                ]);

                return self::markFailed($submission, $error);
            }

            $postId = $response->json('post_id') ?: $response->json('id');
            if (! is_string($postId) || trim($postId) === '') {
                Log::warning('Meta publish response did not include a post ID.', [
                    'submission_id' => $submission->id,
                    'http_status' => $response->status(),
                ]);

                return self::markFailed(
                    $submission,
                    'Meta accepted the request but did not return a post ID. Verify the Page before retrying to avoid duplicate posts.',
                    'uncertain'
                );
            }

            $postUrl = 'https://www.facebook.com/'.rawurlencode($postId);
            $submission->forceFill([
                'meta_publish_status' => 'published',
                'meta_post_id' => $postId,
                'meta_post_url' => $postUrl,
                'meta_publish_error' => null,
            ]);
            $submission->pair_feedback = PairUpdateFormatter::append(
                $submission->pair_feedback,
                PairUpdateFormatter::step(
                    $actor ?? 'Auto-Publisher',
                    'Published to Facebook',
                    'Facebook post: '.$postUrl
                )
            );
            $submission->save();

            Log::info('Submission published to Facebook.', [
                'submission_id' => $submission->id,
                'post_id' => $postId,
            ]);

            try {
                User::find($submission->user_id)?->notify(new SubmissionPublished($submission));
            } catch (Throwable $exception) {
                Log::warning('Facebook post succeeded but submitter notification failed.', [
                    'submission_id' => $submission->id,
                    'exception' => $exception::class,
                ]);
            }

            return [
                'success' => true,
                'simulated' => false,
                'platform' => 'facebook',
                'post_id' => $postId,
                'url' => $postUrl,
                'message' => 'Successfully published to Facebook.',
            ];
        } catch (Throwable $exception) {
            Log::error('Facebook publish request failed.', [
                'submission_id' => $submission->id,
                'exception' => $exception::class,
            ]);

            return self::markFailed(
                $submission,
                'The Facebook publishing request ended without a confirmed response. Verify the Page before retrying to avoid duplicate posts.',
                'uncertain'
            );
        } finally {
            if (is_resource($imageStream)) {
                fclose($imageStream);
            }
        }
    }

    public static function publishDueSubmissions(): int
    {
        $due = Submission::withoutGlobalScopes()
            ->where('workflow_status', 'approved')
            ->where('submission_type', Submission::TYPE_SOCIAL_MEDIA)
            ->whereNotNull('scheduled_post_at')
            ->where('scheduled_post_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('meta_publish_status')
                    ->orWhereIn('meta_publish_status', ['failed', 'publishing', 'published']);
            })
            ->orderBy('scheduled_post_at')
            ->limit(100)
            ->get()
            ->filter(fn (Submission $submission) => self::retryIsDue($submission));

        $publishedCount = 0;
        $lifecycle = app(SubmissionLifecycleService::class);

        foreach ($due as $submission) {
            $result = self::publish($submission);
            if (! $result['success']) {
                continue;
            }

            $submission->refresh();
            $submission->pair_feedback = PairUpdateFormatter::append(
                $submission->pair_feedback,
                PairUpdateFormatter::step('Auto-Publisher', 'Scheduled Facebook post completed')
            );
            $lifecycle->systemTransition($submission, SubmissionLifecycleStatus::Posted, [
                'posted_at' => now(),
                'pair_feedback' => $submission->pair_feedback,
            ]);

            $publishedCount++;
        }

        return $publishedCount;
    }

    private static function claim(Submission $submission, bool $force): array
    {
        return DB::transaction(function () use ($submission, $force): array {
            $locked = Submission::withoutGlobalScopes()
                ->whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->meta_publish_status === 'published') {
                return [
                    'claimed' => false,
                    'result' => [
                        'success' => true,
                        'simulated' => false,
                        'platform' => 'facebook',
                        'post_id' => $locked->meta_post_id,
                        'url' => $locked->meta_post_url,
                        'message' => 'This submission has already been published to Facebook.',
                    ],
                ];
            }

            if ($locked->workflow_status !== 'approved') {
                return [
                    'claimed' => false,
                    'result' => self::failure('Only approved submissions can be published to Facebook.', false),
                ];
            }

            if ($locked->meta_publish_status === 'publishing'
                && $locked->meta_publish_attempted_at?->gt(now()->subMinutes(self::STALE_PUBLISH_MINUTES))) {
                return [
                    'claimed' => false,
                    'result' => self::failure('A Facebook publishing attempt is already in progress.', false),
                ];
            }

            if (! $force && ! self::retryIsDue($locked)) {
                return [
                    'claimed' => false,
                    'result' => self::failure('The previous Facebook attempt is waiting for its automatic retry window.', false),
                ];
            }

            $locked->meta_publish_status = 'publishing';
            $locked->meta_publish_attempted_at = now();
            $locked->meta_publish_attempts = ((int) $locked->meta_publish_attempts) + 1;
            $locked->meta_publish_error = null;
            $locked->save();

            return ['claimed' => true, 'submission' => $locked->fresh()];
        });
    }

    private static function markFailed(Submission $submission, string $message, string $status = 'failed'): array
    {
        $submission->forceFill([
            'meta_publish_status' => $status,
            'meta_publish_error' => mb_substr($message, 0, 2000),
        ])->save();

        return self::failure($message, false);
    }

    private static function retryIsDue(Submission $submission): bool
    {
        if ($submission->meta_publish_status === 'published') {
            return true;
        }

        if ($submission->meta_publish_status === 'publishing') {
            return ! $submission->meta_publish_attempted_at
                || $submission->meta_publish_attempted_at->lte(now()->subMinutes(self::STALE_PUBLISH_MINUTES));
        }

        if ($submission->meta_publish_status !== 'failed') {
            return true;
        }

        if (! $submission->meta_publish_attempted_at) {
            return true;
        }

        $exponent = min(max((int) $submission->meta_publish_attempts - 1, 0), 4);
        $retryMinutes = min(self::MAX_RETRY_MINUTES, self::RETRY_BASE_MINUTES * (2 ** $exponent));

        return $submission->meta_publish_attempted_at->addMinutes($retryMinutes)->isPast();
    }

    private static function failure(string $message, bool $simulated): array
    {
        return [
            'success' => false,
            'simulated' => $simulated,
            'platform' => 'facebook',
            'post_id' => null,
            'url' => null,
            'message' => $message,
        ];
    }
}
