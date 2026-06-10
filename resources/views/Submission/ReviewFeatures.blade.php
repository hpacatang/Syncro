@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
            <h1 class="h3 fw-bold mb-0">Review submission #{{ $submission->id }}</h1>
<<<<<<< Updated upstream
            @php
                if (auth()->user()->isAdmin()) {
                    $backRoute = route('dashboard');
                } elseif (auth()->user()->isOrg() || auth()->user()->isDepartment()) {
                    $backRoute = route('org.dashboard');
                } else {
                    $backRoute = route('dashboard');
                }
            @endphp
            <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm">
=======
            <a href="{{ route(auth()->user()->canSubmitPosts() ? 'org.dashboard' : 'dashboard') }}" class="btn btn-outline-secondary btn-sm">
>>>>>>> Stashed changes
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
        <x-submission-lifecycle-tracker-card :submission="$submission" class="mb-0" />

        <x-submission-lifecycle-poll :submission-ids="(string) $submission->id" />
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card syncro-card-elevated border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Captions</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase">Original</h6>
                    <p class="mb-4">{{ $submission->original_caption }}</p>
                    @if($submission->enhanced_caption)
                        <h6 class="text-muted small text-uppercase">PAIR enhanced</h6>
                        <p class="mb-0">{{ $submission->enhanced_caption }}</p>
                    @else
                        <p class="text-muted mb-0">No enhanced caption yet.</p>
                    @endif
                </div>
            </div>

            @if($submission->pair_feedback)
                <div class="card syncro-card-elevated border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">PAIR updates</h5>
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

