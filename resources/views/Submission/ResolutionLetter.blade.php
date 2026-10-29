@extends('layouts.app')

@section('page-title', 'Resolution letter')
@section('page-subtitle', 'Initial resolution addressed to PAIR for interview and Meta posting billing.')

@section('content')
@php
    $deptName = $submission->user?->department?->displayName() ?? $submission->reviewOwnerLabel();
    $title = $submission->isWebsiteArticle()
        ? ($submission->enhanced_title ?: $submission->article_title)
        : \Illuminate\Support\Str::limit($submission->enhanced_caption ?: $submission->original_caption, 80);
@endphp
<div class="container py-4" style="max-width: 820px;">
    <div class="d-flex justify-content-between mb-3 d-print-none">
        <a href="{{ route('dashboard.submissions') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print letter
        </button>
    </div>

    <article class="card shadow-sm" id="resolutionLetter">
        <div class="card-body p-5">
            <p class="text-muted mb-4">{{ $issuedOn->format('F j, Y') }}</p>

            <p class="mb-0 fw-semibold">The Director</p>
            <p class="mb-4">Public Affairs and Institutional Relations (PAIR)</p>

            <p class="fw-semibold">RE: Resolution on Posting Request #{{ $submission->id }} — {{ $title }}</p>

            <p>Dear PAIR,</p>

            <p>
                The {{ $deptName }} has reviewed and approved the {{ strtolower($submission->contentTypeLabel()) }}
                submitted by {{ $submission->user?->displayName() ?? 'the submitter' }}, and respectfully endorses it
                for your action.
            </p>

            <ol class="mb-4">
                <li class="mb-2">
                    <strong>Interview coordination.</strong> Should the posting require an interview or
                    additional source material, we request PAIR to coordinate scheduling with the
                    {{ $deptName }} point of contact.
                </li>
                <li class="mb-2">
                    <strong>Meta posting billing.</strong> Where this posting is boosted or sponsored on Meta
                    platforms, we request PAIR to process billing against the {{ $deptName }}. Please
                    confirm the approved budget before the posting goes live.
                </li>
            </ol>

            <p>Thank you for your continued support.</p>

            <p class="mt-5 mb-0">Respectfully,</p>
            <p class="fw-semibold mt-4 mb-0">{{ $issuedBy->displayName() }}</p>
            <p class="text-muted">{{ $deptName }}</p>
        </div>
    </article>
</div>
@endsection
