@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="mb-0">Submissions Overview</h2>
        @if(auth()->user()->canSubmitPosts())
            <a href="{{ route(auth()->user()->isOrg() ? 'org.submit' : 'department.submit') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New submission
            </a>
        @endif
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <x-submission-filters
                :action="auth()->user()->isOrg() ? route('org.submissions') : route('dashboard.submissions')"
                :current-filter="$currentFilter"
                :current-search="$currentSearch ?? ''"
                :current-sort="$currentSort ?? 'created_at'"
                :current-order="$currentOrder ?? 'desc'"
                :variant="auth()->user()->isOrg() ? 'org' : 'admin'"
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
                            <th>Submission</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th class="pe-4 text-center" style="width: 1%;">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                        <tr>
                            <td class="ps-4">#{{ $sub->id }}</td>
                            <td>{{ $sub->user ? $sub->user->displayName() : 'Unknown' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $sub->contentTypeLabel() }}</span>
                                @if($sub->isWebsiteArticle())
                                    <strong class="d-block mt-1">{{ \Illuminate\Support\Str::limit($sub->article_title, 60) }}</strong>
                                @endif
                                {{ \Illuminate\Support\Str::limit($sub->original_caption, 50) }}
                            </td>
                            <td style="min-width: 14rem;">
                                <x-submission-lifecycle-progress :submission="$sub" :compact="true" />
                            </td>
                            <td><x-formatted-date :at="$sub->created_at" format="M d, Y h:i A" /></td>
                            <td class="pe-4 text-center text-nowrap">
                                @php
                                    $isRejected = $sub->workflow_status === 'rejected';
                                    $reviewRoute = auth()->user()->isOrg()
                                        ? route('org.submissions.review', $sub)
                                        : route('dashboard.submissions.review', $sub);
                                @endphp
                                <a href="{{ $reviewRoute }}" class="btn btn-sm btn-primary syncro-btn-compact text-nowrap" title="{{ $isRejected ? 'View submission' : 'Review submission' }}">
                                    <i class="bi bi-eye"></i>
                                    <span>{{ $isRejected ? 'View' : 'Review' }}</span>
                                </a>
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
        @if(isset($submissions) && $submissions instanceof \Illuminate\Contracts\Pagination\Paginator && $submissions->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>

    @if(isset($submissions) && $submissions->count() > 0)
        <x-submission-lifecycle-poll :submission-ids="collect($submissions->items())->pluck('id')->implode(',')" />
    @endif
</div>
@endsection