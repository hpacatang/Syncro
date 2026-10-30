<?php

namespace App\Services;

use App\Models\Submission;

class ContentSimilarityChecker
{
    /**
     * Threshold percentage above which content is flagged as a duplicate.
     */
    public const REJECT_THRESHOLD = 80.0;

    /**
     * Check whether the given caption/title matches or is highly similar to existing submissions.
     *
     * @param string $caption
     * @param string|null $title
     * @param int|null $excludeId
     * @return array|null Returns details if match or warning, or null if unique.
     */
    public static function check(string $caption, ?string $title = null, ?int $excludeId = null): ?array
    {
        $newFingerprint = self::fingerprint($caption);
        if (mb_strlen($newFingerprint) < 10) {
            return null;
        }

        $query = Submission::withoutGlobalScopes()
            ->select(['id', 'user_id', 'original_caption', 'article_title', 'status', 'workflow_status', 'created_at'])
            ->latest('id');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $candidates = $query->limit(150)->get();

        $maxSimilarity = 0.0;
        $matchedSubmission = null;
        $isExact = false;

        foreach ($candidates as $candidate) {
            $existingFingerprint = self::fingerprint((string) $candidate->original_caption);
            if (empty($existingFingerprint)) {
                continue;
            }

            // Exact match
            if ($newFingerprint === $existingFingerprint) {
                $maxSimilarity = 100.0;
                $matchedSubmission = $candidate;
                $isExact = true;
                break;
            }

            // High similarity check using PHP's native similar_text
            similar_text($newFingerprint, $existingFingerprint, $percent);

            if ($percent > $maxSimilarity) {
                $maxSimilarity = $percent;
                $matchedSubmission = $candidate;
            }
        }

        if ($matchedSubmission && $maxSimilarity >= self::REJECT_THRESHOLD) {
            $isRejected = strtolower((string) $matchedSubmission->workflow_status) === 'rejected';
            $rounded = round($maxSimilarity, 1);
            $reason = $isExact
                ? ($isRejected
                    ? "This post matches previously rejected submission #{$matchedSubmission->id} due to identical content."
                    : "Identical content was already submitted in submission #{$matchedSubmission->id} (Status: {$matchedSubmission->workflow_status}).")
                : ($isRejected
                    ? "Content has {$rounded}% similarity to previously rejected submission #{$matchedSubmission->id}. Duplicate or repeated submissions are rejected."
                    : "Content has {$rounded}% similarity to submission #{$matchedSubmission->id} (Status: {$matchedSubmission->workflow_status}). Duplicate or repeated submissions are rejected.");

            return [
                'should_reject' => true,
                'percentage' => $rounded,
                'matched_submission_id' => $matchedSubmission->id,
                'matched_status' => $matchedSubmission->workflow_status,
                'is_exact' => $isExact,
                'reason' => $reason,
                'matched_submission' => $matchedSubmission,
            ];
        }

        // If similarity is between 50% and 80%, flag as a warning for reviewers
        if ($matchedSubmission && $maxSimilarity >= 50.0) {
            return [
                'should_reject' => false,
                'percentage' => round($maxSimilarity, 1),
                'matched_submission_id' => $matchedSubmission->id,
                'matched_status' => $matchedSubmission->workflow_status,
                'is_exact' => false,
                'reason' => "Warning: Content has " . round($maxSimilarity, 1) . "% similarity to submission #{$matchedSubmission->id}.",
                'matched_submission' => $matchedSubmission,
            ];
        }

        return null;
    }

    /**
     * Generate canonical fingerprint: lowercased, stripped of punctuation and compressed whitespace.
     */
    public static function fingerprint(string $text): string
    {
        $clean = mb_strtolower($text, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean);
        return preg_replace('/\s+/', ' ', trim((string) $clean));
    }
}
