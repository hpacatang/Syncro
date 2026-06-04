@extends('layouts.app')

@section('page-title', 'PAIR Office Dashboard')
@section('page-subtitle', 'Content queue & submission management')

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
                            <p class="text-muted small mb-1">To Review</p>
                            <h3 class="fw-bold text-secondary">{{ ($stats['pending_submission'] ?? 0) + ($stats['pending_pair_review'] ?? 0) }}</h3>
                        </div>
                        <i class="fas fa-inbox text-secondary"></i>
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
                            <h3 class="fw-bold text-warning">{{ $stats['pending_pair_review'] ?? 0 }}</h3>
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
                                <span>Organization</span>
                                <span>Title / Caption</span>
                                <span>Status</span>
                                <span>Date</span>
                                <span class="text-end">Actions</span>
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
                                        </div>
                                        <p class="syncro-queue__caption" title="{{ $submission->original_caption }}">
                                            {{ $submission->original_caption }}
                                        </p>
                                        <div class="syncro-queue__status">
                                            <x-submission-workflow-badge :submission="$submission" size="sm" />
                                        </div>
                                        <time class="syncro-queue__date" datetime="{{ $submission->created_at->toIso8601String() }}">
                                            {{ $submission->created_at->format('M d, Y') }}
                                        </time>
                                        <div class="syncro-queue__actions">
                                            <x-submission-queue-actions :submission="$submission" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
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
                <div class="row g-3">
                    @foreach($submissions->whereNotNull('media_paths')->take(4) as $submission)
                        @if(is_array($submission->media_paths) && count($submission->media_paths) > 0)
                            @foreach($submission->media_paths as $media)
                                <div class="col-md-3">
                                    <div class="syncro-media-tile">
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <div class="text-center">
                                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                                <p class="text-muted small">{{ basename($media) }}</p>
                                            </div>
                                        </div>
                                        <div class="p-2 border-top">
                                            <small class="text-muted">{{ $submission->user?->displayName() ?? 'Unknown' }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg" style="border: 1px solid #dee2e6;">
            <div class="modal-header bg-primary text-white" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                <div>
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-pen-fancy"></i> Enhance Caption
                    </h5>
                    <small class="text-white-50">Choose AI-assisted or manual enhancement</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Original Caption</label>
                        <div class="alert alert-light border">
                            <p id="originalCaption" class="mb-0" style="max-height: 100px; overflow-y: auto;"></p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-wand-magic-sparkles text-primary"></i> AI-Assisted
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">AI Provider</label>
                                <div class="btn-group d-flex gap-1" role="group" style="width: 100%;">
                                    <input type="radio" class="btn-check" name="llm_provider" value="openai" id="openai" checked>
                                    <label class="btn btn-outline-primary btn-sm flex-grow-1" for="openai" style="font-size: 0.85rem;">
                                        <i class="fas fa-brain"></i> OpenAI
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="llm_provider" value="gemini" id="gemini">
                                    <label class="btn btn-outline-primary btn-sm flex-grow-1" for="gemini" style="font-size: 0.85rem;">
                                        <i class="fas fa-sparkles"></i> Gemini
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="llm_provider" value="deepseek" id="deepseek">
                                    <label class="btn btn-outline-primary btn-sm flex-grow-1" for="deepseek" style="font-size: 0.85rem;">
                                        <i class="fas fa-zap"></i> Deepseek
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Tone</label>
                                <select class="form-select form-select-sm" name="tone" id="toneSelect">
                                    @php $defTone = $defaultCaptionTone ?? 'formal'; @endphp
                                    <option value="formal" {{ $defTone === 'formal' ? 'selected' : '' }}>📋 Formal</option>
                                    <option value="friendly" {{ $defTone === 'friendly' ? 'selected' : '' }}>😊 Friendly</option>
                                    <option value="enthusiastic" {{ $defTone === 'enthusiastic' ? 'selected' : '' }}>🎉 Enthusiastic</option>
                                    <option value="urgent" {{ $defTone === 'urgent' ? 'selected' : '' }}>⚡ Urgent</option>
                                    <option value="professional" {{ $defTone === 'professional' ? 'selected' : '' }}>💼 Academic</option>
                                </select>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm w-100 mb-3" id="generateBtn" onclick="generateCaption()">
                                <i class="fas fa-wand-magic-sparkles"></i> Generate with AI
                            </button>

                            <div id="generatingAlert" class="alert alert-info d-none mb-0 py-2" role="alert" style="font-size: 0.85rem;">
                                <div class="spinner-border spinner-border-sm me-2" role="status" style="width: 1rem; height: 1rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong>Generating...</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-pen-fancy text-info"></i> Manual Input
                            </h6>
                            
                            <label class="form-label fw-bold small">Enhanced Caption</label>
                            <textarea 
                                id="manualCaption" 
                                class="form-control form-control-sm" 
                                rows="8" 
                                placeholder="Type your enhanced caption here. Make it engaging, professional, and grammatically correct."
                                minlength="10">
                            </textarea>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Minimum 10 characters
                            </small>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="border-top pt-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-comments text-secondary"></i> Your Comments/Feedback
                                </h6>
                                <label class="form-label small text-muted">Optional: Add internal notes about this enhancement</label>
                                <textarea 
                                    id="pairFeedback" 
                                    class="form-control form-control-sm" 
                                    rows="3" 
                                    placeholder="e.g., 'Adjusted tone to be more professional. Consider emphasizing the event date more.' - These notes will be visible to the organization during review.">
                                </textarea>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> These comments will be shared with the organization during their review
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                
                <button type="button" class="btn btn-success" id="approveBtn" onclick="approveFinalCaption()">
                    <i class="fas fa-check"></i> Approve & Update
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSubmissionId = null;
const dashboardUrl = @json(route('dashboard'));

function setupEnhanceModal(submissionId, caption, enhancedCaption) {
    currentSubmissionId = submissionId;
    document.getElementById('originalCaption').textContent = caption || 'No caption provided';
    document.getElementById('manualCaption').value = enhancedCaption || '';
    document.getElementById('pairFeedback').value = '';
    document.getElementById('generateBtn').disabled = false;
    document.getElementById('approveBtn').disabled = false;
    document.getElementById('generatingAlert').classList.add('d-none');
}

function openEnhanceModal(submissionId, caption, enhancedCaption) {
    setupEnhanceModal(submissionId, caption, enhancedCaption);
    const el = document.getElementById('generateModal');
    bootstrap.Modal.getOrCreateInstance(el).show();
}

document.getElementById('generateModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button && button.classList.contains('generate-btn')) {
        setupEnhanceModal(
            button.getAttribute('data-submission-id'),
            button.getAttribute('data-caption'),
            ''
        );
    }
});

document.addEventListener('DOMContentLoaded', async function () {
    const params = new URLSearchParams(window.location.search);
    const enhanceId = params.get('enhance');
    if (!enhanceId) return;

    try {
        const response = await fetch('/api/submissions/' + encodeURIComponent(enhanceId), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        const data = await response.json();
        if (data.success && data.data) {
            openEnhanceModal(
                enhanceId,
                data.data.original_caption,
                data.data.enhanced_caption || ''
            );
        }
    } catch (err) {
        console.error('Could not load submission for enhance:', err);
    }

    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('enhance');
    window.history.replaceState({}, '', cleanUrl);
});

async function generateCaption() {
    if (!currentSubmissionId) {
        alert('Error: Submission ID not found. Please close and try again.');
        return;
    }
    
    const provider = document.querySelector('input[name="llm_provider"]:checked').value;
    const tone = document.getElementById('toneSelect').value;
    const generateBtn = document.getElementById('generateBtn');
    const generatingAlert = document.getElementById('generatingAlert');

    generateBtn.disabled = true;
    generatingAlert.classList.remove('d-none');
    generatingAlert.className = 'alert alert-info';
    generatingAlert.innerHTML = '<div class=\"spinner-border spinner-border-sm me-2\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div><strong>Generating with ' + provider + '...</strong>';

    try {
        const response = await fetch(`/api/submissions/${currentSubmissionId}/enhance`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                llm_provider: provider,
                tone: tone
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('manualCaption').value = data.data.enhanced_caption;
            generatingAlert.className = 'alert alert-success';
            generatingAlert.innerHTML = '<i class=\"fas fa-check-circle\"></i> <strong>Success!</strong> AI-generated caption loaded. Review and click Approve to finalize.';
        } else if (data.fallback) {
            generatingAlert.className = 'alert alert-warning';
            generatingAlert.innerHTML = '<i class=\"fas fa-lightbulb\"></i> <strong>AI Service Unavailable</strong> - No problem! Manually enhance the caption below and click Approve.';
        } else {
            generatingAlert.className = 'alert alert-danger';
            generatingAlert.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> <strong>Error:</strong> ${data.message || 'Failed to generate caption'}`;
        }
    } catch (error) {
        console.error('Error:', error);
        generatingAlert.className = 'alert alert-danger';
        generatingAlert.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> <strong>Error:</strong> ${error.message}`;
    }

    generateBtn.disabled = false;
}

async function approveFinalCaption() {
    const finalCaption = document.getElementById('manualCaption').value.trim();
    const pairFeedback = document.getElementById('pairFeedback').value.trim();
    
    if (!finalCaption) {
        alert('Please enter or generate a caption before approving.');
        document.getElementById('manualCaption').focus();
        return;
    }

    if (finalCaption.length < 10) {
        alert('Caption must be at least 10 characters long.');
        return;
    }

    if (!currentSubmissionId) {
        alert('Error: Submission ID not found. Please close and try again.');
        return;
    }

    const approveBtn = document.getElementById('approveBtn');
    const generatingAlert = document.getElementById('generatingAlert');

    approveBtn.disabled = true;
    generatingAlert.classList.remove('d-none');
    generatingAlert.className = 'alert alert-info';
    generatingAlert.innerHTML = '<div class=\"spinner-border spinner-border-sm me-2\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div><strong>Finalizing...</strong>';

    try {
        const response = await fetch(`/api/submissions/${currentSubmissionId}/save-manual-caption`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                manual_caption: finalCaption,
                pair_feedback: pairFeedback
            })
        });

        const data = await response.json();

        if (data.success) {
            generatingAlert.className = 'alert alert-success';
            generatingAlert.innerHTML = '<i class=\"fas fa-check-circle\"></i> <strong>Success!</strong> Caption saved. Returning to Enhance Caption…';
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('generateModal'));
                if (modal) modal.hide();
                window.location.assign(
                    dashboardUrl + '?enhance=' + encodeURIComponent(currentSubmissionId)
                );
            }, 800);
        } else {
            generatingAlert.className = 'alert alert-danger';
            generatingAlert.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> <strong>Error:</strong> ${data.message || 'Failed to save caption'}`;
            approveBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        generatingAlert.className = 'alert alert-danger';
        generatingAlert.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> <strong>Error:</strong> ${error.message}`;
        approveBtn.disabled = false;
    }
}

</script>

@if(count($submissions ?? []) > 0)
<x-submission-lifecycle-poll :submission-ids="collect($submissions)->pluck('id')->implode(',')" />
@endif

@endsection