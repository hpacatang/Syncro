@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h1 class="h3 fw-bold mb-0">Review submission #{{ $submission->id }}</h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if(auth()->user()->isStaffReviewer() && !in_array($submission->workflow_status, ['rejected', 'posted'], true))
                    <a href="{{ route('dashboard.submissions.evaluate', $submission) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-magic me-1"></i> Evaluate &amp; edit
                    </a>
                @endif
                @if(auth()->user()->isStaffReviewer())
                    <a href="{{ route('dashboard.submissions.resolution-letter', $submission) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-text me-1"></i> Resolution letter
                    </a>
                @endif
                <a href="{{ route(auth()->user()->isOrg() ? 'org.dashboard' : 'dashboard') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
                    <i class="bi bi-arrow-left me-1"></i> Back to Queue
                </a>
            </div>
        </div>
        @if($submission->user)
            <div class="d-flex align-items-center flex-wrap gap-2 text-muted mb-3 small">
                <span class="d-inline-flex align-items-center">
                    <i class="bi bi-person-circle me-1 text-primary"></i><strong>Submitter:</strong>&nbsp;{{ $submission->user->displayName() }}
                </span>
                @if($submission->user->department)
                    <span class="text-muted">&bull;</span>
                    <span class="d-inline-flex align-items-center">
                        <i class="bi bi-diagram-3 me-1 text-secondary"></i>{{ $submission->user->department->displayName() }}
                    </span>
                @endif
            </div>
        @endif

        @php
            $hasPosted = (bool) $submission->posted_at;
            $metaColClass = $hasPosted ? 'col-lg-3 col-sm-6' : 'col-md-4 col-sm-12';
        @endphp
        <div class="card syncro-card-elevated border-0 mb-3 bg-light" style="border-radius: 10px;">
            <div class="card-body py-3 px-4">
                <div class="row g-3 align-items-center">
                    <div class="{{ $metaColClass }}">
                        <span class="text-muted small d-block mb-1">Submitted At:</span>
                        <div class="text-dark fw-bold d-flex align-items-center">
                            <i class="bi bi-calendar-event text-primary me-2"></i>
                            <x-formatted-date :at="$submission->created_at" format="M d, Y h:i A" />
                        </div>
                    </div>
                    <div class="{{ $metaColClass }} border-start-md ps-md-3">
                        <span class="text-muted small d-block mb-1">Desired Post Date:</span>
                        @if($submission->scheduled_post_at)
                            <div class="text-primary fw-bold d-flex align-items-center">
                                <i class="bi bi-clock-history me-2"></i>
                                <x-formatted-date :at="$submission->scheduled_post_at" format="M d, Y h:i A" />
                            </div>
                        @else
                            <div class="text-muted small d-flex align-items-center">
                                <i class="bi bi-dash-circle me-2"></i>Immediate / Not specified
                            </div>
                        @endif
                    </div>
                    @if($hasPosted)
                    <div class="{{ $metaColClass }} border-start-md ps-md-3">
                        <span class="text-muted small d-block mb-1">Published At:</span>
                        <div class="text-success fw-bold d-flex align-items-center">
                            <i class="bi bi-calendar2-check-fill me-2"></i>
                            <x-formatted-date :at="$submission->posted_at" format="M d, Y h:i A" />
                        </div>
                    </div>
                    @endif
                    <div class="{{ $metaColClass }} border-start-md ps-md-3">
                        <span class="text-muted small d-block mb-1">Review Owner:</span>
                        <div class="text-dark fw-bold d-flex align-items-center">
                            <i class="bi bi-building text-secondary me-2"></i>
                            {{ $submission->reviewOwnerLabel() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $similarity = \App\Services\ContentSimilarityChecker::check(
                $submission->original_caption,
                $submission->article_title,
                $submission->id
            );
        @endphp

        @if($similarity)
            @php
                $match = $similarity['matched_submission'];
                $matchReviewRoute = auth()->user()->isOrg()
                    ? route('org.submissions.review', $match)
                    : route('dashboard.submissions.review', $match);
            @endphp
            <div class="alert {{ $similarity['should_reject'] ? 'alert-danger border-danger' : 'alert-warning border-warning' }} d-flex align-items-start gap-3 mb-3 shadow-sm">
                <i class="bi {{ $similarity['should_reject'] ? 'bi-exclamation-octagon-fill text-danger' : 'bi-shield-exclamation text-warning' }} fs-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading fw-bold mb-1">
                        {{ $similarity['should_reject'] ? 'Content Checker: High Similarity / Duplicate Detected' : 'Content Checker: Similar Post Notice' }} ({{ $similarity['percentage'] }}% Match)
                    </h6>
                    <p class="mb-2 small">{{ $similarity['reason'] }}</p>
                    <div class="small">
                        <strong>Matched Submission:</strong> #{{ $match->id }} &bull;
                        <strong>Submitter:</strong> {{ $match->user?->displayName() ?? 'Unknown' }} &bull;
                        <strong>Status:</strong> <span class="badge bg-secondary">{{ $match->workflow_status }}</span>
                        <a href="{{ $matchReviewRoute }}" class="btn btn-sm btn-outline-dark ms-2 py-0" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Matched #{{ $match->id }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <x-submission-lifecycle-tracker-card :submission="$submission" class="mb-0" />

        <x-submission-lifecycle-poll :submission-ids="(string) $submission->id" />
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card syncro-card-elevated border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0 fw-bold">Submission content</h5>
                        <span class="badge bg-light text-dark border">{{ $submission->contentTypeLabel() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($submission->isWebsiteArticle())
                        <h6 class="text-muted small text-uppercase">Submitted headline</h6>
                        <h4 class="mb-3">{{ $submission->article_title }}</h4>
                        <h6 class="text-muted small text-uppercase">Original article body</h6>
                    @else
                        <h6 class="text-muted small text-uppercase">Original social media post</h6>
                    @endif
                    <p class="mb-4" style="white-space: pre-line">{{ $submission->original_caption }}</p>
                    @if($submission->enhanced_caption)
                        <h6 class="text-muted small text-uppercase">Review team revision</h6>
                        @if($submission->isWebsiteArticle())
                            <h4 class="mb-3">{{ $submission->enhanced_title ?: $submission->article_title }}</h4>
                        @endif
                        <p class="mb-0" style="white-space: pre-line">{{ $submission->enhanced_caption }}</p>
                    @else
                        <p class="text-muted mb-0">No review draft yet.</p>
                    @endif
                </div>
            </div>

            @if($submission->pair_feedback)
                <div class="card syncro-card-elevated border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Review team updates</h5>
                    </div>
                    <div class="card-body">
                        <x-pair-updates :text="$submission->pair_feedback" />
                    </div>
                </div>
            @endif

            @if($submission->media_paths && count($submission->media_paths))
                <div class="card syncro-card-elevated border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Attachments</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($submission->media_paths as $path)
                                <div class="col-sm-6 col-lg-4">
                                    <x-submission-media-tile :path="$path" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <x-submission-status-manager :submission="$submission" />
        </div>
    </div>
</div>
@endsection
