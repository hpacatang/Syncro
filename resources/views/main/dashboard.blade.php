@extends('layouts.app')

@section('page-title', auth()->user()->isDepartment() ? (auth()->user()->department?->displayName() ?? 'Department').' Dashboard' : 'PAIR Office Dashboard')
@section('page-subtitle', auth()->user()->isDepartment() ? 'Department content queue & submission management' : 'School-wide content queue & submission management')

@section('content')
<div class="container-fluid px-0">

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card syncro-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Submissions</p>
                            <h3 class="fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-file-alt text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card syncro-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">In Review</p>
                            <h3 class="fw-bold text-warning">{{ ($stats['pending_submission'] ?? 0) + ($stats['pending_pair_review'] ?? 0) }}</h3>
                        </div>
                        <i class="fas fa-hourglass-half text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card syncro-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Approved</p>
                            <h3 class="fw-bold text-success">{{ $stats['approved'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card syncro-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Rejected</p>
                            <h3 class="fw-bold text-danger">{{ $stats['rejected'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-times-circle text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-4">
        <div class="col-xl-9 col-lg-8">
            <div class="syncro-panel syncro-panel--compact">
                <div class="syncro-panel__header syncro-panel__header--compact">
                    <h6 class="fw-bold mb-0">Content Queue</h6>
                    <div class="mt-2">
                        <x-submission-filters
                            :action="route('dashboard')"
                            :current-filter="$currentFilter"
                            :current-search="$currentSearch ?? ''"
                            variant="admin"
                            compact
                        />
                    </div>
                </div>
                <div class="syncro-panel__body syncro-panel__body--flush">
                    @if(count($submissions) === 0)
                        <div class="text-center py-5 px-3">
                            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-1">No submissions in the queue</p>
                            <p class="text-muted small">Waiting for organizations to submit content for review</p>
                        </div>
                    @else
                        <div class="syncro-queue">
                            <div class="syncro-queue__head" aria-hidden="true">
                                <span>ORGANIZATION</span>
                                <span>CONTENT PREVIEW</span>
                                <span>STATUS</span>
                                <span>DATE</span>
                                <span>ACTIONS</span>
                            </div>
                            <ul class="syncro-queue__list list-unstyled mb-0">
                                @foreach($submissions as $submission)
                                    <li class="syncro-queue__item">
                                        <div class="syncro-queue__org">
                                            <span class="syncro-queue__org-name" title="{{ $submission->user?->displayName() }}">
                                                {{ $submission->user?->displayName() ?? 'Unknown' }}
                                            </span>
                                            @if($submission->user?->department)
                                                <span class="syncro-queue__org-dept" title="{{ $submission->user->department->displayName() }}">
                                                    {{ $submission->user->department->displayName() }}
                                                </span>
                                            @endif
                                            <span class="syncro-queue__org-dept">For {{ $submission->reviewOwnerLabel() }}</span>
                                        </div>
                                        <p class="syncro-queue__caption" title="{{ Str::limit($submission->original_caption, 500) }}">
                                            <span class="badge bg-light text-dark border">{{ $submission->contentTypeLabel() }}</span>
                                            @if($submission->isWebsiteArticle())
                                                <strong>{{ $submission->enhanced_title ?: $submission->article_title }}</strong><br>
                                            @endif
                                            {{ Str::limit($submission->original_caption, 180) }}
                                        </p>
                                        <div class="syncro-queue__status">
                                            <x-submission-workflow-badge :submission="$submission" size="sm" />
                                        </div>
                                        <div class="syncro-queue__date" @if($submission->created_at) title="Submitted: {{ $submission->created_at->format('M d, Y h:i A') }}" @endif>
                                            <span class="d-block small text-muted">Submitted:</span>
                                            <span class="fw-semibold text-dark"><x-formatted-date :at="$submission->created_at" format="M d, Y" /></span>
                                            <small class="text-muted d-block"><i class="bi bi-clock me-1"></i><x-formatted-date :at="$submission->created_at" format="h:i A" /></small>
                                            @if($submission->workflow_status === 'posted' && $submission->posted_at)
                                                <div class="mt-1 small text-success fw-semibold" title="Published time">
                                                    <i class="bi bi-check2-circle me-1"></i>Posted: <strong><x-formatted-date :at="$submission->posted_at" format="M d, h:i A" /></strong>
                                                </div>
                                            @elseif($submission->scheduled_post_at)
                                                <div class="mt-1 small text-primary" title="Target post time">
                                                    <i class="bi bi-calendar-check me-1"></i>Post: <strong><x-formatted-date :at="$submission->scheduled_post_at" format="M d, h:i A" /></strong>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="syncro-queue__actions">
                                            <x-submission-queue-actions :submission="$submission" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                @if(isset($submissions) && $submissions instanceof \Illuminate\Contracts\Pagination\Paginator && $submissions->hasPages())
                    <div class="card-footer bg-white border-top py-3 px-3">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-3 col-lg-4">
            <div class="syncro-sidebar-card card mb-3">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Status Overview</h5>
                </div>
                <div class="card-body">
                    @php
                        $total = $stats['total'] ?? 0;
                        $pending = $stats['pending'] ?? 0;
                        $underReview = $stats['under_review'] ?? 0;
                        $approved = $stats['approved'] ?? 0;
                        
                        if ($total <= 0) {
                            $pendingPercent = 0;
                            $underReviewPercent = 0;
                            $approvedPercent = 0;
                        } else {
                            $pendingPercent = ($pending / $total) * 100;
                            $underReviewPercent = ($underReview / $total) * 100;
                            $approvedPercent = ($approved / $total) * 100;
                        }
                    @endphp
                    
                    @if($total == 0)
                        <div class="alert alert-info mb-0" role="alert">
                            <i class="fas fa-info-circle"></i> No submissions yet. Waiting for organizations to submit content.
                        </div>
                    @else
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Pending</small>
                                <small class="fw-bold">{{ $pending }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-secondary" style="width: {{ $pendingPercent }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Under Review</small>
                                <small class="fw-bold">{{ $underReview }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: {{ $underReviewPercent }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <small>Approved</small>
                                <small class="fw-bold">{{ $approved }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $approvedPercent }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="syncro-sidebar-card card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('staff.caption-assist') }}" class="btn btn-primary w-100 mb-2 text-decoration-none">
                        <i class="fas fa-magic"></i> Generate captions
                    </a>
                    <a href="{{ route('staff.media-gallery') }}" class="btn btn-outline-secondary w-100 mb-2 text-decoration-none">
                        <i class="fas fa-images"></i> View media gallery
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="syncro-panel">
        <div class="syncro-panel__header">
            <h5 class="mb-0 fw-bold">Recent Media Uploads</h5>
        </div>
        <div class="syncro-panel__body">
            @if(count($submissions) === 0 || !$submissions->whereNotNull('media_paths')->count())
                <div class="text-center py-5">
                    <i class="fas fa-images text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No media uploads yet</p>
                    <p class="text-muted small">Media from submissions will appear here</p>
                </div>
            @else
                @php $mediaShown = 0; @endphp
                <div class="row g-3">
                    @foreach($submissions->whereNotNull('media_paths') as $submission)
                        @if($mediaShown >= 8)
                            @break
                        @endif
                        @if(is_array($submission->media_paths))
                            @foreach($submission->media_paths as $media)
                                @if($mediaShown >= 8)
                                    @break
                                @endif
                                <div class="col-md-3">
                                    <x-submission-media-tile
                                        :path="$media"
                                        :caption="$submission->user?->displayName() ?? 'Unknown'"
                                    />
                                </div>
                                @php $mediaShown++; @endphp
                            @endforeach
                        @endif
                    @endforeach
                </div>
                @if($mediaShown === 0)
                    <div class="text-center py-4 text-muted small">
                        Media filenames are recorded but files were not found in storage. Re-upload attachments or check Supabase / storage configuration.
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>



@if(count($submissions ?? []) > 0)
<x-submission-lifecycle-poll :submission-ids="collect($submissions)->pluck('id')->implode(',')" />
@endif

@endsection