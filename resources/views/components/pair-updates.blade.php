@props(['text' => '', 'submission' => null])

@php
    $rawBlocks = array_values(array_filter(preg_split("/\n\s*\n/", trim((string) $text))));

    // If text is empty or missing lifecycle steps, build timeline events from submission
    if ($submission) {
        $existingText = implode("\n", $rawBlocks);
        $wf = $submission->workflow_status;
        $actorName = $submission->enhancer?->displayName() ?? 'PAIR Reviewer';
        $createdTime = $submission->created_at?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A');
        $updatedTime = $submission->updated_at?->format('M d, Y h:i A') ?? $createdTime;
        $postedTime = $submission->posted_at?->format('M d, Y h:i A') ?? $updatedTime;

        if (empty($rawBlocks)) {
            // Build full chronological timeline matching PAIR
            if ($submission->enhanced_caption || $submission->enhanced_at) {
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Manual content saved — ready for your review";
            }
            if ($wf === 'revised' || $submission->org_review_notes) {
                $notes = $submission->org_review_notes ? ": {$submission->org_review_notes}" : '';
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Send back for revision{$notes}";
            }
            if (in_array($wf, ['approved', 'posted'], true)) {
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Approve post";
            }
            if ($wf === 'posted') {
                $rawBlocks[] = "[Review team - {$actorName} • {$postedTime}]\n• Mark as posted";
            }
            if ($wf === 'rejected') {
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Reject post";
            }
            if ($wf === 'under_peer_review' && empty($rawBlocks)) {
                $rawBlocks[] = "[Review team - {$actorName} • {$createdTime}]\n• Under review";
            }
            if (empty($rawBlocks)) {
                $rawBlocks[] = "[Review team - {$actorName} • {$createdTime}]\n• Submitted for review";
            }
        } else {
            // Ensure completion steps are present if missing
            if (in_array($wf, ['approved', 'posted'], true) && !str_contains(strtolower($existingText), 'approve')) {
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Approve post";
            }
            if ($wf === 'posted' && !str_contains(strtolower($existingText), 'post')) {
                $rawBlocks[] = "[Review team - {$actorName} • {$postedTime}]\n• Mark as posted";
            }
            if ($wf === 'rejected' && !str_contains(strtolower($existingText), 'reject')) {
                $rawBlocks[] = "[Review team - {$actorName} • {$updatedTime}]\n• Reject post";
            }
        }
    }
@endphp

@if(count($rawBlocks) > 0)
    <div {{ $attributes->merge(['class' => 'pair-updates text-dark']) }}>
        @foreach($rawBlocks as $block)
            @php
                $lines = preg_split("/\r\n|\n|\r/", trim($block));
                $header = trim($lines[0] ?? '');
                $bullets = array_slice($lines, 1);

                // If block has no separate bullets, treat first line as bullet
                if (empty($bullets) && !str_starts_with($header, '[')) {
                    $bullets = [$header];
                    $defaultActor = $submission?->enhancer?->displayName() ?? 'PAIR Reviewer';
                    $header = "[Review team - {$defaultActor}]";
                }

                // Parse actor and timestamp from header: e.g. "[Review team - PAIR Reviewer • Nov 18, 2026 10:45 PM]" or "[PAIR - PAIR Reviewer]"
                $cleanHeader = trim($header, "[]");
                $parts = explode('•', $cleanHeader);
                $rawActor = trim($parts[0] ?? '');
                $time = isset($parts[1]) ? trim($parts[1]) : null;

                // Normalize actor name across all account types (Admin, PAIR, Department, etc.)
                if (str_starts_with($rawActor, 'Review team - ')) {
                    $actorName = trim(substr($rawActor, 14));
                } elseif (str_starts_with($rawActor, 'PAIR - ')) {
                    $actorName = trim(substr($rawActor, 7));
                } elseif ($rawActor === 'PAIR' || $rawActor === 'Review team' || $rawActor === '') {
                    $actorName = $submission?->enhancer?->displayName() ?? 'PAIR Reviewer';
                } else {
                    $actorName = $rawActor;
                }

                $actor = "Review team - {$actorName}";

                if (! $time && $submission) {
                    $firstBullet = strtolower(trim($bullets[0] ?? ''));
                    if (str_contains($firstBullet, 'post') || str_contains($firstBullet, 'publish')) {
                        $time = $submission->posted_at?->format('M d, Y h:i A');
                    } elseif (str_contains($firstBullet, 'approve')) {
                        $time = $submission->updated_at?->format('M d, Y h:i A');
                    } elseif (str_contains($firstBullet, 'revision') || str_contains($firstBullet, 'reject')) {
                        $time = $submission->updated_at?->format('M d, Y h:i A');
                    } elseif (str_contains($firstBullet, 'save') || str_contains($firstBullet, 'manual')) {
                        $time = $submission->enhanced_at?->format('M d, Y h:i A') ?? $submission->updated_at?->format('M d, Y h:i A');
                    }
                    $time = $time ?? $submission->updated_at?->format('M d, Y h:i A') ?? $submission->created_at?->format('M d, Y h:i A');
                }

                $formattedHeader = $time ? "[{$actor} • {$time}]" : "[{$actor}]";
            @endphp
            <div class="pair-update-block mb-3">
                <div class="fw-bold syncro-pair-header mb-1">{{ $formattedHeader }}</div>
                @if(count($bullets))
                    <ul class="mb-0 ps-3">
                        @foreach($bullets as $line)
                            @if(trim($line) !== '')
                                <li>{{ ltrim(trim($line), "• \t") }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endif
