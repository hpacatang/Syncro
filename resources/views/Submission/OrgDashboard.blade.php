@extends('layouts.app')

@section('page-title', auth()->user()->displayName())
@section('page-subtitle', 'Your submissions')

@section('content')
<div class="container-fluid px-0">

    @if($submissions->isNotEmpty())
        @php $trackerSubmission = $awaitingApproval->first() ?? $submissions->sortByDesc('updated_at')->first(); @endphp
        <div class="card shadow-sm border-0 mb-4 submission-lifecycle-tracker-card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3 fw-semibold">Submission progress</h6>
                <x-submission-lifecycle-progress :submission="$trackerSubmission" />
            </div>
        </div>
    @endif

    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Submissions</p>
                            <h3 class="fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-file text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Ready for Review</p>
                            <h3 class="fw-bold text-warning">{{ $stats['ready_for_review'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-hourglass-half text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">With Review Team</p>
                            <h3 class="fw-bold text-secondary">{{ ($stats['pending_submission'] ?? 0) + ($stats['pending_pair_review'] ?? 0) }}</h3>
                        </div>
                        <i class="fas fa-clock text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
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

    
    <div class="row mb-4">
        
        @if(count($awaitingApproval) > 0)
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 border-warning border-top border-5">
                <div class="card-header bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-hourglass-half text-warning"></i> Ready for Review
                            </h5>
                            <small class="text-muted">The review team has updated this content. Review and approve or request revisions.</small>
                        </div>
                        <span class="badge bg-warning text-dark fs-6">{{ count($awaitingApproval) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Submission</th>
                                    <th>Enhanced By</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($awaitingApproval as $submission)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-dark border">{{ $submission->contentTypeLabel() }}</span>
                                        @if($submission->isWebsiteArticle())
                                            <strong class="d-block mt-1">{{ Str::limit($submission->article_title, 60) }}</strong>
                                        @endif
                                        {{ Str::limit($submission->original_caption, 40) }}
                                    </td>
                                    <td>
                                        <small>{{ $submission->enhancer?->name ?? 'Review team' }}</small>
                                        <br><small class="text-muted">{{ $submission->enhanced_at?->format('M d, Y') }}</small>
                                    </td>
                                    <td class="text-muted small"><x-formatted-date :at="$submission->created_at" format="M d" /></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('org.submissions.review', $submission) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Review page</a>
                                            <button class="btn btn-sm btn-warning" 
                                                title="Review & Approve/Reject" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reviewModal"
                                                data-submission-id="{{ $submission->id }}"
                                                data-original-caption="{{ $submission->original_caption }}"
                                                data-enhanced-caption="{{ $submission->enhanced_caption }}"
                                                data-submission-type="{{ $submission->submission_type }}"
                                                data-article-title="{{ $submission->article_title }}"
                                                data-enhanced-title="{{ $submission->enhanced_title }}"
                                                onclick="loadReviewModal(this.dataset.submissionId, this.dataset.originalCaption, this.dataset.enhancedCaption, this.dataset.submissionType, this.dataset.articleTitle, this.dataset.enhancedTitle)">
                                                <i class="fas fa-eye"></i> Quick review
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        
        <div class="col-lg-{{ count($awaitingApproval) > 0 ? '8' : '8' }}">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0 fw-bold">Your Submissions</h5>
                        <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Submission
                        </a>
                    </div>
                    <x-submission-filters
                        :action="route('org.dashboard')"
                        :current-filter="$currentFilter ?? 'all'"
                        :current-search="$currentSearch ?? ''"
                        variant="org"
                        compact
                    />
                </div>
                <div class="card-body p-0">
                    @if(count($submissions) === 0)
                        <div class="text-center py-5">
                            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No submissions yet</p>
                            <p class="text-muted small">Start by submitting content to your Department or PAIR</p>
                            <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus"></i> Create First Submission
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Submission Preview</th>
                                        <th>Review team</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submissions as $submission)
                                        @php $needsReview = $submission->workflow_status === 'under_peer_review' && $submission->enhanced_caption; @endphp
                                        <tr data-submission-row="{{ $submission->id }}" data-workflow-status="{{ $submission->workflow_status }}">
                                            <td class="ps-4">
                                                <span class="badge bg-light text-dark border">{{ $submission->contentTypeLabel() }}</span>
                                                @if($submission->isWebsiteArticle())
                                                    <strong class="d-block mt-1">{{ Str::limit($submission->article_title, 60) }}</strong>
                                                @endif
                                                {{ Str::limit($submission->original_caption, 50) }}
                                            </td>
                                            <td>{{ $submission->reviewOwnerLabel() }}</td>
                                            <td style="min-width: 14rem;">
                                                <x-submission-lifecycle-progress :submission="$submission" :compact="true" />
                                            </td>
                                            <td class="text-muted small"><x-formatted-date :at="$submission->created_at" format="M d" /></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <x-view-action-link :href="route('org.submissions.review', $submission)" :title="__('View details')" />
                                                    @if($needsReview)
                                                        <button class="btn btn-sm btn-warning"
                                                            title="Quick Review"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#reviewModal"
                                                            data-submission-id="{{ $submission->id }}"
                                                            data-original-caption="{{ $submission->original_caption }}"
                                                            data-enhanced-caption="{{ $submission->enhanced_caption }}"
                                                            data-submission-type="{{ $submission->submission_type }}"
                                                            data-article-title="{{ $submission->article_title }}"
                                                            data-enhanced-title="{{ $submission->enhanced_title }}"
                                                            onclick="loadReviewModal(this.dataset.submissionId, this.dataset.originalCaption, this.dataset.enhancedCaption, this.dataset.submissionType, this.dataset.articleTitle, this.dataset.enhancedTitle)">
                                                            <i class="fas fa-check-circle"></i> Decide
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Recent feedback</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @if(count($feedback) === 0)
                        <div class="text-center py-4">
                            <i class="fas fa-comments text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted small mt-2">No feedback yet</p>
                        </div>
                    @else
                        @foreach($feedback as $comment)
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong class="text-sm">{{ $comment->user?->displayName() ?? 'Review team' }}</strong>
                                    <small class="text-muted"><x-formatted-date :at="$comment->created_at" relative /></small>
                                </div>
                                <p class="text-sm mb-0">{{ $comment->message }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('org.submit') }}" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-upload"></i> Submit Content
                    </a>
                    <a href="{{ route('org.submissions') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-list"></i> View All Submissions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold">Review Enhanced Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted" id="originalContentLabel">Original Submission</label>
                    <div class="bg-light p-3 rounded">
                        <p class="fw-semibold mb-2 d-none" id="originalArticleTitle"></p>
                        <p class="mb-0" id="originalCaption"></p>
                    </div>
                </div>

                
                <div class="mb-4">
                    <label class="form-label fw-bold text-success" id="enhancedContentLabel">Review Team Revision</label>
                    <div class="bg-light p-3 rounded">
                        <p class="fw-semibold mb-2 d-none" id="enhancedArticleTitle"></p>
                        <p class="mb-0" id="enhancedCaption"></p>
                    </div>
                </div>

                
                <div id="pairFeedbackSection" class="mb-4" style="display: none;">
                    <label class="form-label fw-bold">
                        <i class="fas fa-comments text-info"></i> Review team updates
                    </label>
                    <div class="bg-info bg-opacity-10 border border-info border-opacity-25 p-3 rounded pair-updates" id="pairFeedbackText"></div>
                </div>

                
                <div id="orgRevisionSection" class="mb-4" style="display: none;">
                    <label class="form-label fw-bold text-warning">
                        <i class="fas fa-history"></i> Your Previous Feedback
                    </label>
                    <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 p-3 rounded">
                        <p class="mb-0 text-dark" id="orgRevisionText"></p>
                    </div>
                </div>

                
                <div class="border-top pt-4">
                    <h6 class="fw-bold mb-3">Your Decision</h6>
                    <div class="mb-3">
                        <input type="radio" id="approveRadio" name="decision" value="approve" checked>
                        <label for="approveRadio" class="form-label mb-3">
                            <strong class="text-success">✓ Approve</strong><br>
                            <small class="text-muted">The reviewed draft is ready to continue through the approval workflow.</small>
                        </label>
                    </div>
                    <div class="mb-3">
                        <input type="radio" id="rejectRadio" name="decision" value="reject">
                        <label for="rejectRadio" class="form-label mb-3">
                            <strong class="text-warning">⟲ Request Revisions</strong><br>
                            <small class="text-muted">Send back to the review team with feedback for improvements.</small>
                        </label>
                    </div>

                    <div id="rejectionNotes" style="display: none;" class="mt-3">
                        <label for="revisionNotes" class="form-label" id="revisionNotesLabel">What needs improvement?</label>
                        <textarea id="revisionNotes" class="form-control" rows="3" 
                                  placeholder="Provide specific details..."></textarea>
                        <div class="invalid-feedback">
                            Feedback is required and must be at least 10 characters long.
                        </div>
                        <small class="text-muted d-block mt-1">Be specific about what changes or why it is rejected.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="approveBtn">
                    <i class="fas fa-check"></i> Approve & Proceed
                </button>
                <button type="button" class="btn btn-warning" style="display: none;" id="rejectBtn">
                    <i class="fas fa-redo"></i> Send Back for Revision
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function formatPairUpdates(text) {
    return text.split(/\n\s*\n/).map(function (block) {
        const lines = block.trim().split(/\r?\n/).filter(Boolean);
        if (!lines.length) return '';
        const header = lines[0].startsWith('[PAIR') || lines[0].startsWith('[Review team') ? '<div class="fw-bold syncro-pair-header mb-1">' + escapeHtml(lines[0]) + '</div>' : '<div class="mb-1">' + escapeHtml(lines[0]) + '</div>';
        const bullets = lines.slice(1).map(function (line) {
            return '<li>' + escapeHtml(line.replace(/^•\s*/, '')) + '</li>';
        }).join('');
        return '<div class="pair-update-block mb-3">' + header + (bullets ? '<ul class="mb-0 ps-3">' + bullets + '</ul>' : '') + '</div>';
    }).join('');
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

let currentSubmissionId = null;

async function fetchSubmissionDetails(submissionId) {
    try {
        const response = await fetch(`/api/submissions/${submissionId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();
        
        if (data.success) {
            const submission = data.data;
            
            const pairFeedbackSection = document.getElementById('pairFeedbackSection');
            const pairFeedbackText = document.getElementById('pairFeedbackText');
            
            if (submission.pair_feedback) {
                pairFeedbackText.innerHTML = formatPairUpdates(submission.pair_feedback);
                pairFeedbackSection.style.display = 'block';
            } else {
                pairFeedbackSection.style.display = 'none';
            }

            const orgRevisionSection = document.getElementById('orgRevisionSection');
            const orgRevisionText = document.getElementById('orgRevisionText');
            
            if (submission.org_review_notes) {
                orgRevisionText.textContent = submission.org_review_notes;
                orgRevisionSection.style.display = 'block';
            } else {
                orgRevisionSection.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error fetching submission details:', error);
    }
}

function loadReviewModal(submissionId, originalCaption, enhancedCaption, submissionType, articleTitle, enhancedTitle) {
    currentSubmissionId = submissionId;
    document.getElementById('originalCaption').textContent = originalCaption;
    document.getElementById('enhancedCaption').textContent = enhancedCaption;
    const isArticle = submissionType === 'website_article';
    const originalTitle = document.getElementById('originalArticleTitle');
    const enhancedArticleTitle = document.getElementById('enhancedArticleTitle');
    originalTitle.textContent = articleTitle || '';
    originalTitle.classList.toggle('d-none', !isArticle);
    enhancedArticleTitle.textContent = isArticle ? (enhancedTitle || articleTitle || '') : '';
    enhancedArticleTitle.classList.toggle('d-none', !isArticle);
    document.getElementById('originalContentLabel').textContent = isArticle ? 'Original article body' : 'Original social media post';
    document.getElementById('enhancedContentLabel').textContent = isArticle ? 'Reviewed article body' : 'Reviewed social media post';
    document.querySelector('#reviewModal .modal-title').textContent = isArticle ? 'Review website article' : 'Review social media post';
    
    fetchSubmissionDetails(submissionId);

    document.getElementById('approveRadio').checked = true;
    document.getElementById('rejectionNotes').style.display = 'none';
    const inputEl = document.getElementById('revisionNotes');
    if (inputEl) {
        inputEl.value = '';
        inputEl.classList.remove('is-invalid');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    
    if (approveBtn) {
        approveBtn.addEventListener('click', submitApproval);
    }
    
    if (rejectBtn) {
        rejectBtn.addEventListener('click', submitRejection);
    }

    const permRejectBtn = document.getElementById('permRejectBtn');
    if (permRejectBtn) {
        permRejectBtn.addEventListener('click', submitRejection);
    }

    const revisionNotesEl = document.getElementById('revisionNotes');
    if (revisionNotesEl) {
        revisionNotesEl.addEventListener('input', function() {
            if (this.value.trim().length >= 10) {
                this.classList.remove('is-invalid');
            }
        });
    }
});

document.addEventListener('change', function(e) {
    if (e.target.name === 'decision') {
        const rejectionNotesDiv = document.getElementById('rejectionNotes');
        const rejectBtn = document.getElementById('rejectBtn');
        const permRejectBtn = document.getElementById('permRejectBtn');
        const approveBtn = document.getElementById('approveBtn');
        const label = document.getElementById('revisionNotesLabel');
        
        if (e.target.value === 'reject') {
            rejectionNotesDiv.style.display = 'block';
            rejectBtn.style.display = 'block';
            if (permRejectBtn) permRejectBtn.style.display = 'none';
            approveBtn.style.display = 'none';
            if (label) label.textContent = 'What needs improvement?';
        } else if (e.target.value === 'permanent_reject') {
            rejectionNotesDiv.style.display = 'block';
            rejectBtn.style.display = 'none';
            if (permRejectBtn) permRejectBtn.style.display = 'block';
            approveBtn.style.display = 'none';
            if (label) label.textContent = 'Reason for rejection (cannot be undone):';
        } else {
            rejectionNotesDiv.style.display = 'none';
            rejectBtn.style.display = 'none';
            if (permRejectBtn) permRejectBtn.style.display = 'none';
            approveBtn.style.display = 'block';
        }
    }
});

async function submitApproval() {
    if (!currentSubmissionId) {
        alert('Error: No submission ID found');
        return;
    }
    
    const btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/submissions/${currentSubmissionId}/org-review/approve`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({})
        });

        const data = await response.json();

        if (data.success) {
            alert('Content approved and returned to the review workflow. Publishing is not automated.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Approve & Proceed';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error approving caption: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Approve & Proceed';
    }
}

async function submitRejection() {
    if (!currentSubmissionId) {
        alert('Error: No submission ID found');
        return;
    }
    
    const inputEl = document.getElementById('revisionNotes');
    if (!inputEl) return;

    const notes = inputEl.value.trim();
    if (notes.length < 10) {
        inputEl.classList.add('is-invalid');
        inputEl.focus();
        return;
    }

    inputEl.classList.remove('is-invalid');

    const isPermanent = document.getElementById('permanentRejectRadio')?.checked;
    if (isPermanent && !confirm('⚠️ WARNING: Once rejected, this submission CANNOT BE UNDONE.\n\nAre you sure you want to permanently reject this submission?')) {
        return;
    }
    
    const activeBtn = isPermanent ? document.getElementById('permRejectBtn') : document.getElementById('rejectBtn');
    if (activeBtn) {
        activeBtn.disabled = true;
        activeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/submissions/${currentSubmissionId}/org-review/reject`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                notes: notes,
                action: isPermanent ? 'reject' : 'revision'
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message || (isPermanent ? 'Submission permanently rejected.' : 'Feedback sent to the review team.'));
            location.reload();
        } else {
            alert('Error: ' + data.message);
            if (activeBtn) {
                activeBtn.disabled = false;
                activeBtn.innerHTML = isPermanent 
                    ? '<i class="fas fa-times-circle"></i> Reject (Cannot be undone)'
                    : '<i class="fas fa-redo"></i> Send Back for Revision';
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error submitting decision: ' + error.message);
        if (activeBtn) {
            activeBtn.disabled = false;
            activeBtn.innerHTML = isPermanent 
                ? '<i class="fas fa-times-circle"></i> Reject (Cannot be undone)'
                : '<i class="fas fa-redo"></i> Send Back for Revision';
        }
    }
}
</script>

<x-submission-lifecycle-poll :submission-ids="$submissions->pluck('id')->implode(',')" />

@endsection
