@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 fw-bold">PAIR Office Dashboard</h1>
        <p class="text-muted">Content Queue & Submission Management</p>
    </div>

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
                        <span class="badge bg-primary">+3</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pending</p>
                            <h3 class="fw-bold text-secondary">{{ $stats['pending'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-file-alt text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Under Review</p>
                            <h3 class="fw-bold text-warning">{{ $stats['under_review'] ?? 0 }}</h3>
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
                            <p class="text-muted small mb-1">Approved</p>
                            <h3 class="fw-bold text-success">{{ $stats['approved'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Queue Section -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Content Queue</h5>
                        <div>
                            <select class="form-select form-select-sm d-inline-block w-auto">
                                <option>All Status</option>
                                <option>Submitted</option>
                                <option>Under Review</option>
                                <option>Caption Drafted</option>
                                <option>Approved</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count($submissions) === 0)
                        <div class="text-center py-5">
                            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No submissions in the queue</p>
                            <p class="text-muted small">Waiting for organizations to submit content for review</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Organization</th>
                                        <th>Title/Caption</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($submissions as $submission)
                                        <tr>
                                            <td class="ps-4">{{ $submission->user->name ?? 'Unknown' }}</td>
                                            <td>{{ Str::limit($submission->original_caption, 40) }}</td>
                                            <td>
                                                @if($submission->status === 'pending')
                                                    <span class="badge bg-secondary">Pending</span>
                                                @elseif($submission->status === 'under_review')
                                                    <span class="badge bg-warning text-dark">Under Review</span>
                                                @elseif($submission->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small">{{ $submission->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary generate-btn" title="Generate Caption" data-bs-toggle="modal" data-bs-target="#generateModal" data-submission-id="{{ $submission->id }}" data-caption="{{ htmlspecialchars($submission->original_caption, ENT_QUOTES) }}">
                                                    <i class="fas fa-wand-magic-sparkles"></i> Generate
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                No submissions found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Status Overview</h5>
                </div>
                <div class="card-body">
                    @php
                        $total = $stats['total'] ?? 0;
                        $pending = $stats['pending'] ?? 0;
                        $underReview = $stats['under_review'] ?? 0;
                        $approved = $stats['approved'] ?? 0;
                        
                        // Prevent division by zero
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

            <!-- Quick Actions -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-magic"></i> Generate Caption
                    </button>
                    <button class="btn btn-outline-secondary w-100 mb-2">
                        <i class="fas fa-images"></i> View Media Gallery
                    </button>
                    <button class="btn btn-outline-secondary w-100">
                        <i class="fas fa-cogs"></i> Configure Tones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Gallery Preview -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-bold">Recent Media Uploads</h5>
        </div>
        <div class="card-body">
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
                                    <div class="card border-0 overflow-hidden">
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <div class="text-center">
                                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                                <p class="text-muted small">{{ basename($media) }}</p>
                                            </div>
                                        </div>
                                        <div class="card-body p-2">
                                            <small class="text-muted">{{ $submission->user->name ?? 'Unknown' }}</small>
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

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Caption Generation Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
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
                    <!-- Original Caption Display -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Original Caption</label>
                        <div class="alert alert-light border">
                            <p id="originalCaption" class="mb-0" style="max-height: 150px; overflow-y: auto;"></p>
                        </div>
                    </div>

                    <!-- LLM Provider Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-cube text-primary"></i> AI Provider
                        </label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="llm_provider" value="openai" id="openai" checked>
                                <label class="btn btn-outline-primary w-100" for="openai">
                                    <i class="fas fa-brain"></i> OpenAI
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="llm_provider" value="gemini" id="gemini">
                                <label class="btn btn-outline-primary w-100" for="gemini">
                                    <i class="fas fa-sparkles"></i> Gemini
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="llm_provider" value="deepseek" id="deepseek">
                                <label class="btn btn-outline-primary w-100" for="deepseek">
                                    <i class="fas fa-zap"></i> Deepseek
                                </label>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Choose your preferred AI model
                        </small>
                    </div>

                    <!-- Tone Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-palette text-primary"></i> Tone
                        </label>
                        <select class="form-select" name="tone" id="toneSelect">
                            <option value="formal">📋 Formal & Professional</option>
                            <option value="friendly">😊 Friendly & Casual</option>
                            <option value="enthusiastic">🎉 Enthusiastic & Energetic</option>
                            <option value="urgent">⚡ Urgent & Action-Oriented</option>
                            <option value="professional">💼 Professional & Academic</option>
                        </select>
                    </div>

                    <!-- Loading State -->
                    <div id="generatingAlert" class="alert alert-info d-none" role="alert">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <strong>Generating enhanced caption...</strong> This may take a moment.
                    </div>

                    <!-- Enhanced Caption Display -->
                    <div id="enhancedCaptionContainer" class="d-none">
                        <label class="form-label fw-bold">
                            <i class="fas fa-check-circle text-success"></i> Enhanced Caption
                        </label>
                        <div class="alert alert-success border-0 bg-light-success">
                            <p id="enhancedCaption" class="mb-0" style="max-height: 150px; overflow-y: auto;"></p>
                        </div>
                    </div>

                    <!-- Manual Caption Fallback (when AI fails) -->
                    <div id="manualCaptionContainer" class="d-none">
                        <div class="alert alert-warning border-2 border-warning mb-3">
                            <i class="fas fa-lightbulb"></i> <strong>AI Generation Failed</strong>
                            <p class="mb-2">The AI service is temporarily unavailable. No problem! You can manually enhance the caption below:</p>
                        </div>
                        <label class="form-label fw-bold">
                            <i class="fas fa-pen-fancy text-info"></i> Enhanced Caption (Manual)
                        </label>
                        <textarea 
                            id="manualCaption" 
                            class="form-control form-control-lg" 
                            rows="5" 
                            placeholder="Type your enhanced caption here. Be engaging, professional, and grammatically correct."
                            minlength="10">
                        </textarea>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Minimum 10 characters required
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                
                <!-- AI Success Path -->
                <button type="button" class="btn btn-primary" id="generateBtn" onclick="generateCaption()">
                    <i class="fas fa-wand-magic-sparkles"></i> Generate with AI
                </button>
                <button type="button" class="btn btn-success" id="approveBtn" onclick="approveFinalCaption()">
                    <i class="fas fa-check"></i> Approve & Update
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSubmissionId = null;

// Handle modal show event to capture button data
document.getElementById('generateModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button && button.classList.contains('generate-btn')) {
        currentSubmissionId = button.getAttribute('data-submission-id');
        const caption = button.getAttribute('data-caption');
        
        document.getElementById('originalCaption').textContent = caption || 'No caption provided';
        document.getElementById('manualCaption').value = '';
        document.getElementById('generateBtn').disabled = false;
        document.getElementById('approveBtn').disabled = false;
        document.getElementById('generatingAlert').classList.add('d-none');
        
        console.log('Modal setup - Submission ID:', currentSubmissionId, 'Caption:', caption);
    }
});

async function generateCaption() {
    console.log('Generating caption for submission:', currentSubmissionId);
    
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
            // AI Generation Successful - Fill the manual textarea with AI result
            document.getElementById('manualCaption').value = data.data.enhanced_caption;
            generatingAlert.className = 'alert alert-success';
            generatingAlert.innerHTML = '<i class=\"fas fa-check-circle\"></i> <strong>Success!</strong> AI-generated caption loaded. Review and click Approve to finalize.';
        } else if (data.fallback) {
            // AI Generation Failed - Show helpful message but keep manual input visible
            generatingAlert.className = 'alert alert-warning';
            generatingAlert.innerHTML = '<i class=\"fas fa-lightbulb\"></i> <strong>AI Service Unavailable</strong> - No problem! Manually enhance the caption below and click Approve.';
        } else {
            // Other error
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
                manual_caption: finalCaption
            })
        });

        const data = await response.json();

        if (data.success) {
            generatingAlert.className = 'alert alert-success';
            generatingAlert.innerHTML = '<i class=\"fas fa-check-circle\"></i> <strong>Success!</strong> Caption approved and updated.';
            
            // Close modal after brief delay
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('generateModal'));
                if (modal) modal.hide();
                location.reload(); // Refresh to show updated data
            }, 1500);
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
    
    // Show a success toast/alert
    const successAlert = document.createElement('div');
    successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    successAlert.style.zIndex = '9999';
    successAlert.innerHTML = `
        <i class="fas fa-check-circle"></i> <strong>Perfect!</strong> Caption has been enhanced and saved.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(successAlert);
    
    // Close modal after 1 second
    setTimeout(() => {
        modal.hide();
        // Reload page to show updated submission
        setTimeout(() => location.reload(), 500);
    }, 1500);
}
</script>

@endsection