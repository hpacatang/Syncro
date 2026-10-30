<?php

namespace App\Http\Middleware;

use App\Services\ContentSimilarityChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateNoDuplicateSubmission
{
    /**
     * Intercepts pre-submission requests to detect duplicate or similar content.
     * If identical or highly similar content exists (active or rejected), blocks the submission.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->has('original_caption')) {
            return $next($request);
        }

        $caption = (string) $request->input('original_caption');
        $title = (string) $request->input('article_title');

        if (trim($caption) === '') {
            return $next($request);
        }

        $similarity = ContentSimilarityChecker::check($caption, $title);

        if ($similarity && $similarity['should_reject']) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'                 => false,
                    'error'                   => 'DuplicateContentDetected',
                    'message'                 => $similarity['reason'],
                    'duplicate_submission_id' => $similarity['matched_submission_id'],
                    'duplicate_status'        => $similarity['matched_status'],
                    'similarity_percent'      => $similarity['percentage'],
                    'reason'                  => $similarity['reason'],
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['original_caption' => $similarity['reason']]);
        }

        return $next($request);
    }
}
