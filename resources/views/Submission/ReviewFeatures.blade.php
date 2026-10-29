@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
            <h1 class="h3 fw-bold mb-0">Review submission #{{ $submission->id }}</h1>
            <a href="{{ route(auth()->user()->isOrg() ? 'org.dashboard' : 'dashboard') }}" class="btn btn-outline-secondary btn-sm">
                ← Back to Queue
            </a>
        </div>
        @if($submission->user)
            <p class="text-muted mb-3">
                {{ $submission->user->displayName() }}
                @if($submission->user->department)
                    <span class="text-muted"> · {{ $submission->user->department->displayName() }}</span>
                @endif
            </p>
        @endif
        <p class="text-muted small mb-3">Review owner: <strong>{{ $submission->reviewOwnerLabel() }}</strong></p>
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
