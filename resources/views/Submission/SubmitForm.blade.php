@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Content Queue</h1>
            <p class="text-muted mb-0">Browse and manage submissions awaiting review, publication, or revision.</p>
        </div>
        @if(auth()->user()->canSubmitPosts())
            <a href="{{ route(auth()->user()->isOrg() ? 'org.submit' : 'department.submit') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                <i class="bi bi-plus-lg me-1"></i> New submission
            </a>
        @endif
    </div>

    <div class="syncro-panel syncro-panel--compact">
        <div class="syncro-panel__header syncro-panel__header--compact">
            <h6 class="fw-bold mb-0">Content Queue</h6>
            <div class="mt-2">
                <x-submission-filters
                    :action="auth()->user()->isOrg() ? route('org.submissions') : route('dashboard.submissions')"
                    :current-filter="$currentFilter"
                    :current-search="$currentSearch ?? ''"
                    :current-sort="$currentSort ?? 'created_at'"
                    :current-order="$currentOrder ?? 'desc'"
                    :variant="auth()->user()->isOrg() ? 'org' : 'admin'"
                    compact
                />
            </div>
        </div>
        <div class="syncro-panel__body syncro-panel__body--flush">
            @if(count($submissions) === 0)
                <div class="text-center py-5 px-3">
                    <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-1">No submissions in the queue</p>
                    <p class="text-muted small">No submissions match the selected filter criteria</p>
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

    @if(isset($submissions) && $submissions->count() > 0)
        <x-submission-lifecycle-poll :submission-ids="collect($submissions->items())->pluck('id')->implode(',')" />
    @endif
</div>
@endsection