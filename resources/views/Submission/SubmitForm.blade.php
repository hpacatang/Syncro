@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Submissions Overview</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Department/Org</th>
                            <th>Original Caption</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                        <tr>
                            <td class="ps-4">#{{ $sub->id }}</td>
                            <td>{{ $sub->user ? $sub->user->name : 'Unknown User' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($sub->original_caption, 50) }}</td>
                            <td>
                                @php
                                    $badgeColor = match($sub->status) {
                                        'approved' => 'success',
                                        'under_review' => 'warning',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                                </span>
                            </td>
                            <td>{{ $sub->created_at ? $sub->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-primary">Review Features</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                No submissions have been posted yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection