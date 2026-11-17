<?php

namespace App\Jobs;

use App\Services\SubmissionDraftGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSubmissionDraft implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 360;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $userId,
        public string $requestId,
        public string $prompt,
        public string $submissionType,
        public string $provider,
        public string $tone
    ) {}

    public function handle(SubmissionDraftGenerator $generator): void
    {
        Cache::put(
            self::cacheKey($this->userId, $this->requestId),
            ['status' => 'running'],
            now()->addMinutes(15)
        );

        try {
            $result = $generator->generate(
                $this->prompt,
                $this->submissionType,
                $this->provider,
                $this->tone
            );
            $this->storeResult($result);
        } catch (Throwable $exception) {
            Log::error('Queued submission draft generation failed.', [
                'request_id' => $this->requestId,
                'provider' => $this->provider,
                'exception' => $exception::class,
            ]);
            $this->storeResult([
                'success' => false,
                'fallback' => true,
                'message' => 'AI drafting is unavailable right now. You can write your submission manually.',
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queued submission draft generation did not complete.', [
            'request_id' => $this->requestId,
            'provider' => $this->provider,
            'exception' => $exception ? $exception::class : null,
        ]);
        $this->storeResult([
            'success' => false,
            'fallback' => true,
            'message' => 'AI drafting is unavailable right now. You can write your submission manually.',
        ]);
    }

    public static function cacheKey(int $userId, string $requestId): string
    {
        return "submission-draft:{$userId}:{$requestId}";
    }

    private function storeResult(array $result): void
    {
        Cache::put(
            self::cacheKey($this->userId, $this->requestId),
            ['status' => $result['success'] ? 'completed' : 'failed', 'result' => $result],
            now()->addMinutes(15)
        );
    }
}
