@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Review submission #{{ $submission->id }}</h1>
        <p class="text-muted mb-0">
            <span class="badge bg-secondary">{{ $submission->workflow_status }}</span>
            @if($submission->user)
                <span class="ms-2">{{ $submission->user->name }}</span>
            @endif
        </p>
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
                        <h5 class="mb-0 fw-bold">PAIR notes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $submission->pair_feedback }}</p>
                    </div>
                </div>
            @endif

            @if($submission->media_paths && count($submission->media_paths))
                <div class="card syncro-card-elevated border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Attachments</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($submission->media_paths as $path)
                                <li class="mb-2">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" target="_blank" rel="noopener">{{ basename($path) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <x-submission-action :submission="$submission" />
        </div>
    </div>
</div>
@endsection
