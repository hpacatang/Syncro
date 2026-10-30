<?php

namespace App\Support;

class TextPayloadCompressor
{
    /**
     * Compress words, collapse excess repeated letters, normalize whitespace,
     * and trim edge spaces from string input.
     */
    public static function compress(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // 1. Unicode Normalization (Form KC)
        if (class_exists(\Normalizer::class) && \Normalizer::isNormalized($text, \Normalizer::FORM_C) === false) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        }

        // 2. Normalize Windows/Mac line-endings to standard Unix \n
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 3. Compress 3+ consecutive excessive letter repetitions down to 2
        // e.g., "Greeeeaaat" -> "Greaat", "heeeelllooo" -> "heelloo"
        $text = preg_replace('/(\p{L})\1{2,}/u', '$1$1', $text);

        // 4. Collapse multiple inline whitespace characters (spaces & tabs) into a single space
        $text = preg_replace('/[^\S\n]+/', ' ', $text);

        // 5. Collapse excessive blank lines (more than 2 consecutive newlines) into 2 newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 6. Trim leading/trailing whitespace on each line and overall string
        $lines = explode("\n", $text);
        $trimmedLines = array_map('trim', $lines);
        $text = implode("\n", $trimmedLines);

        return trim($text);
    }

    /**
     * Recursively compress all string values in an array/payload.
     */
    public static function compressArray(array $payload, array $exemptKeys = ['password', 'password_confirmation', 'token']): array
    {
        foreach ($payload as $key => $value) {
            if (in_array($key, $exemptKeys, true)) {
                continue;
            }

            if (is_string($value)) {
                $payload[$key] = self::compress($value);
            } elseif (is_array($value)) {
                $payload[$key] = self::compressArray($value, $exemptKeys);
            }
        }

        return $payload;
    }
}
