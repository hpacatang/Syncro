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
                            <h3 class="fw-bold">24</h3>
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
                            <p class="text-muted small mb-1">Under Review</p>
                            <h3 class="fw-bold text-warning">8</h3>
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
                            <h3 class="fw-bold text-success">12</h3>
                        </div>
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Posted</p>
                            <h3 class="fw-bold text-info">4</h3>
                        </div>
                        <i class="fas fa-share-alt text-info"></i>
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
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Organization</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4">Student Council</td>
                                    <td>Spring Festival 2026</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">Under Review</span>
                                    </td>
                                    <td><span class="badge bg-danger">High</span></td>
                                    <td class="text-muted small">Apr 18, 2026</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Engineering Dept</td>
                                    <td>Project Showcase Event</td>
                                    <td>
                                        <span class="badge bg-info">Caption Drafted</span>
                                    </td>
                                    <td><span class="badge bg-warning">Medium</span></td>
                                    <td class="text-muted small">Apr 17, 2026</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Marketing Club</td>
                                    <td>Social Media Campaign</td>
                                    <td>
                                        <span class="badge bg-success">Approved</span>
                                    </td>
                                    <td><span class="badge bg-success">Low</span></td>
                                    <td class="text-muted small">Apr 16, 2026</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Arts Department</td>
                                    <td>Gallery Opening</td>
                                    <td>
                                        <span class="badge bg-secondary">Submitted</span>
                                    </td>
                                    <td><span class="badge bg-info">Medium</span></td>
                                    <td class="text-muted small">Apr 19, 2026</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">HR Department</td>
                                    <td>Campus Recruitment Drive</td>
                                    <td>
                                        <span class="badge bg-success">Approved</span>
                                    </td>
                                    <td><span class="badge bg-danger">High</span></td>
                                    <td class="text-muted small">Apr 15, 2026</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Submitted</small>
                            <small class="fw-bold">3</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-secondary" style="width: 12%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Under Review</small>
                            <small class="fw-bold">8</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 33%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Caption Drafted</small>
                            <small class="fw-bold">5</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 21%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Approved</small>
                            <small class="fw-bold">12</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 50%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <small>Posted</small>
                            <small class="fw-bold">4</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 17%"></div>
                        </div>
                    </div>
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
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-0 overflow-hidden">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="text-center">
                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                <p class="text-muted small">spring_festival_01.jpg</p>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-muted">Student Council</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 overflow-hidden">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="text-center">
                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                <p class="text-muted small">project_showcase.jpg</p>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-muted">Engineering Dept</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 overflow-hidden">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="text-center">
                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                <p class="text-muted small">campaign_banner.jpg</p>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-muted">Marketing Club</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 overflow-hidden">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="text-center">
                                <i class="fas fa-image text-muted fa-3x mb-2"></i>
                                <p class="text-muted small">gallery_opening.jpg</p>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-muted">Arts Department</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endsection