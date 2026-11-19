<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

final class AppTime
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Manila');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function parseLocal(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value, self::timezone())->utc();
    }

    public static function parseLogTimestamp(string $value): Carbon
    {
        $timezone = preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/', trim($value)) ? null : 'UTC';

        return Carbon::parse($value, $timezone)->utc();
    }

    public static function toLocal(?DateTimeInterface $value): ?Carbon
    {
        return $value === null
            ? null
            : Carbon::instance($value)->setTimezone(self::timezone());
    }

    public static function format(?DateTimeInterface $value, string $format, string $fallback = ''): string
    {
        return self::toLocal($value)?->format($format) ?? $fallback;
    }
}
