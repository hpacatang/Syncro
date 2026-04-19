@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 fw-bold">{{ auth()->user()->name }} - Submission Dashboard</h1>
        <p class="text-muted">Manage your content submissions and track approval status</p>
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
                            <p class="text-muted small mb-1">Pending</p>
                            <h3 class="fw-bold text-secondary">{{ $stats['pending'] ?? 0 }}</h3>
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

    <!-- Main Content -->
    <div class="row mb-4">
        <!-- Your Submissions -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Your Submissions</h5>
                        <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Submission
                        </a>
                    </div>
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
                                        <tr>
                                            <td class="ps-4">{{ Str::limit($submission->original_caption, 50) }}</td>
                                            <td>
                                                @if($submission->status === 'pending')
                                                    <span class="badge bg-secondary">Pending</span>
                                                @elseif($submission->status === 'under_review')
                                                    <span class="badge bg-warning text-dark">Under Review</span>
                                                @elseif($submission->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small">{{ $submission->created_at->format('M d') }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
                                    <strong class="text-sm">{{ $comment->user->name }}</strong>
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



<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endsection
