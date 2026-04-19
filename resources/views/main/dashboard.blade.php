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
                                                <a href="#" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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

@endsection