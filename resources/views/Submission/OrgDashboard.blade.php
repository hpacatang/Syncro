@extends('layouts.app')

@section('page-title', auth()->user()->displayName())
@section('page-subtitle', 'Organization submissions')

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

    <!-- Statistics Cards -->
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
                            <p class="text-muted small mb-1">With PAIR</p>
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

    <!-- Main Content -->
    <div class="row mb-4">
        <!-- Ready for review -->
        @if(count($awaitingApproval) > 0)
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 border-warning border-top border-5">
                <div class="card-header bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-hourglass-half text-warning"></i> Ready for Review
                            </h5>
                            <small class="text-muted">PAIR has enhanced these captions. Review and approve or request revisions.</small>
                        </div>
                        <span class="badge bg-warning text-dark fs-6">{{ count($awaitingApproval) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Original Caption</th>
                                    <th>Enhanced By</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($awaitingApproval as $submission)
                                <tr>
                                    <td class="ps-4">{{ Str::limit($submission->original_caption, 40) }}</td>
                                    <td>
                                        <small>{{ $submission->enhancer?->name ?? 'PAIR Staff' }}</small>
                                        <br><small class="text-muted">{{ $submission->enhanced_at?->format('M d, Y') }}</small>
                                    </td>
                                    <td class="text-muted small">{{ $submission->created_at->format('M d') }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('org.submissions.review', $submission) }}" class="btn btn-sm btn-outline-primary">Review page</a>
                                            <button class="btn btn-sm btn-warning" 
                                                title="Review & Approve/Reject" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reviewModal"
                                                onclick="loadReviewModal({{ $submission->id }}, '{{ htmlspecialchars($submission->original_caption, ENT_QUOTES) }}', '{{ htmlspecialchars($submission->enhanced_caption, ENT_QUOTES) }}')">
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

        <!-- Your Submissions -->
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
                            <p class="text-muted small">Start by submitting your first content for PAIR review</p>
                            <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus"></i> Create First Submission
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Caption Preview</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        @foreach($submissions as $submission)
                                            @php $needsReview = $submission->workflow_status === 'under_peer_review' && $submission->enhanced_caption; @endphp
                                            <tr data-submission-row="{{ $submission->id }}" data-workflow-status="{{ $submission->workflow_status }}">
                                                <td class="ps-4">{{ Str::limit($submission->original_caption, 50) }}</td>
                                                <td style="min-width: 14rem;">
                                                    <x-submission-lifecycle-progress :submission="$submission" :compact="true" />
                                                </td>
                                                <td class="text-muted small">{{ $submission->created_at->format('M d') }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <x-view-action-link :href="route('org.submissions.review', $submission)" :title="__('View details')" />
                                                        @if($needsReview)
                                                            <button class="btn btn-sm btn-warning"
                                                                title="Quick Review"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#reviewModal"
                                                                onclick="loadReviewModal({{ $submission->id }}, '{{ htmlspecialchars($submission->original_caption, ENT_QUOTES) }}', '{{ htmlspecialchars($submission->enhanced_caption, ENT_QUOTES) }}')">
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

        <!-- Recent Feedback -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Recent Feedback from PAIR</h5>
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
                                    <strong class="text-sm">{{ $comment->user->displayName() }}</strong>
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="text-sm mb-0">{{ $comment->message }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
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

<!-- Review & Approval Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold">Review Enhanced Caption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Original Caption -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted">Original Caption</label>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-0" id="originalCaption"></p>
                    </div>
                </div>

                <!-- Enhanced Caption -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-success">Enhanced Caption</label>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-0" id="enhancedCaption"></p>
                    </div>
                </div>

                <!-- PAIR Feedback Section -->
                <div id="pairFeedbackSection" class="mb-4" style="display: none;">
                    <label class="form-label fw-bold">
                        <i class="fas fa-comments text-info"></i> PAIR updates
                    </label>
                    <div class="bg-info bg-opacity-10 border border-info border-opacity-25 p-3 rounded pair-updates" id="pairFeedbackText"></div>
                </div>

                <!-- Org Revision History -->
                <div id="orgRevisionSection" class="mb-4" style="display: none;">
                    <label class="form-label fw-bold text-warning">
                        <i class="fas fa-history"></i> Your Previous Feedback
                    </label>
                    <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 p-3 rounded">
                        <p class="mb-0 text-dark" id="orgRevisionText"></p>
                    </div>
                </div>

                <!-- Decision Section -->
                <div class="border-top pt-4">
                    <h6 class="fw-bold mb-3">Your Decision</h6>
                    <div class="mb-3">
                        <input type="radio" id="approveRadio" name="decision" value="approve" checked>
                        <label for="approveRadio" class="form-label mb-3">
                            <strong class="text-success">✓ Approve</strong><br>
                            <small class="text-muted">Caption is good. Ready to post.</small>
                        </label>
                    </div>
                    <div class="mb-3">
                        <input type="radio" id="rejectRadio" name="decision" value="reject">
                        <label for="rejectRadio" class="form-label">
                            <strong class="text-warning">⟲ Request Revisions</strong><br>
                            <small class="text-muted">Send back to PAIR with feedback for improvements.</small>
                        </label>
                    </div>

                    <!-- Feedback for Rejection -->
                    <div id="rejectionNotes" style="display: none;" class="mt-3">
                        <label for="revisionNotes" class="form-label">What needs improvement?</label>
                        <textarea id="revisionNotes" class="form-control" rows="3" 
                                  placeholder="Provide specific feedback for PAIR staff..."></textarea>
                        <small class="text-muted">Be specific about what changes you'd like.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
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
        const header = lines[0].startsWith('[PAIR') ? '<div class="fw-bold syncro-pair-header mb-1">' + escapeHtml(lines[0]) + '</div>' : '<div class="mb-1">' + escapeHtml(lines[0]) + '</div>';
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
            
            // Display PAIR feedback if it exists
            const pairFeedbackSection = document.getElementById('pairFeedbackSection');
            const pairFeedbackText = document.getElementById('pairFeedbackText');
            
            if (submission.pair_feedback) {
                pairFeedbackText.innerHTML = formatPairUpdates(submission.pair_feedback);
                pairFeedbackSection.style.display = 'block';
            } else {
                pairFeedbackSection.style.display = 'none';
            }

            // Display org revision history if it exists
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

function loadReviewModal(submissionId, originalCaption, enhancedCaption) {
    currentSubmissionId = submissionId;
    document.getElementById('originalCaption').textContent = originalCaption;
    document.getElementById('enhancedCaption').textContent = enhancedCaption;
    
    // Fetch submission details to get feedback
    fetchSubmissionDetails(submissionId);
    
    // Reset form
    document.getElementById('approveRadio').checked = true;
    document.getElementById('rejectionNotes').style.display = 'none';
    document.getElementById('revisionNotes').value = '';
}

// Attach event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    
    if (approveBtn) {
        approveBtn.addEventListener('click', submitApproval);
    }
    
    if (rejectBtn) {
        rejectBtn.addEventListener('click', submitRejection);
    }
});

// Show/hide revision notes based on radio selection
document.addEventListener('change', function(e) {
    if (e.target.name === 'decision') {
        const rejectionNotesDiv = document.getElementById('rejectionNotes');
        const rejectBtn = document.getElementById('rejectBtn');
        const approveBtn = document.getElementById('approveBtn');
        
        if (e.target.value === 'reject') {
            rejectionNotesDiv.style.display = 'block';
            rejectBtn.style.display = 'block';
            approveBtn.style.display = 'none';
        } else {
            rejectionNotesDiv.style.display = 'none';
            rejectBtn.style.display = 'none';
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
            alert('Caption approved! Ready to be posted.');
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
    
    const notes = document.getElementById('revisionNotes').value.trim();
    if (notes.length < 10) {
        alert('Please provide specific feedback of at least 10 characters for PAIR staff.');
        return;
    }
    
    const btn = document.getElementById('rejectBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
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
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Feedback sent to PAIR for further enhancements.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo"></i> Send Back for Revision';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error submitting feedback: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo"></i> Send Back for Revision';
    }
}
</script>

<x-submission-lifecycle-poll :submission-ids="$submissions->pluck('id')->implode(',')" />

@endsection
