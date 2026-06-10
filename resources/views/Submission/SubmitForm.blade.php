@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="mb-0">Submissions Overview</h2>
        @if(auth()->user()->canSubmitPosts())
            <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New submission
            </a>
        @endif
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <x-submission-filters
                :action="auth()->user()->canSubmitPosts() ? route('org.submissions') : route('dashboard.submissions')"
                :current-filter="$currentFilter"
                :current-search="$currentSearch ?? ''"
                :current-sort="$currentSort ?? 'created_at'"
                :current-order="$currentOrder ?? 'desc'"
                :variant="auth()->user()->canSubmitPosts() ? 'org' : 'admin'"
                :show-sort="true"
            />
        </div>
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
                            <th class="pe-4 text-end">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                        <tr>
                            <td class="ps-4">#{{ $sub->id }}</td>
                            <td>{{ $sub->user ? $sub->user->displayName() : 'Unknown' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($sub->original_caption, 50) }}</td>
                            <td style="min-width: 14rem;">
                                <x-submission-lifecycle-progress :submission="$sub" :compact="true" />
                            </td>
                            <td><x-formatted-date :at="$sub->created_at" format="M d, Y h:i A" /></td>
                            <td class="pe-4 text-end">
                                @if(auth()->user()->canSubmitPosts())
                                    <a href="{{ route('org.submissions.review', $sub) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye me-1"></i> Review</a>
                                @else
                                    <a href="{{ route('dashboard.submissions.review', $sub) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye me-1"></i> Review</a>
                                @endif
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
    @if(isset($submissions) && $submissions instanceof \Illuminate\Contracts\Pagination\Paginator && $submissions->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $submissions->links() }}
        </div>
    @endif

    @if(isset($submissions) && $submissions->count() > 0)
        <x-submission-lifecycle-poll :submission-ids="collect($submissions->items())->pluck('id')->implode(',')" />
    @endif
</div>
@endsection