<?php

namespace App\Support;

use App\Models\User;

class PairUpdateFormatter
{
    public static function step(User|string $actor, string $stepLabel, ?string $detail = null): string
    {
        $who = is_string($actor) ? $actor : trim((string) ($actor->profile_name ?: $actor->name));
        $line = '• '.$stepLabel;
        if ($detail !== null && trim($detail) !== '') {
            $line .= ': '.trim($detail);
        }

        return "[Review team - {$who}]\n{$line}";
    }

    public static function append(?string $existing, string $block): string
    {
        $block = trim($block);
        if ($block === '') {
            return (string) $existing;
        }

        return $existing ? trim($existing)."\n\n".$block : $block;
    }
}
