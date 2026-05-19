@props([
    'action',
    'currentFilter' => 'all',
    'currentSearch' => '',
    'currentSort' => 'created_at',
    'currentOrder' => 'desc',
    'showSort' => false,
    'compact' => false,
    'variant' => 'admin',
])

@php
    $isOrg = $variant === 'org';
    $statusOptions = [
        'all' => 'All statuses',
        'pending' => 'Pending review',
        'submitted' => 'Submitted',
        'under_peer_review' => 'Under PAIR review',
        'approved' => 'Approved',
        'posted' => 'Posted',
    ];
    if ($isOrg) {
        unset($statusOptions['posted']);
    }
@endphp

<form method="GET" action="{{ $action }}" class="submission-filters {{ $compact ? 'submission-filters--compact' : '' }}">
    <div class="row g-2 align-items-end">
        <div class="{{ $showSort ? 'col-md-3' : 'col-md-4' }} col-sm-6">
            <label for="filter-status" class="form-label small text-muted mb-1">Status</label>
            <select name="status" id="filter-status" class="form-select form-select-sm">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($currentFilter === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $showSort ? 'col-md-3' : 'col-md-5' }} col-sm-6">
            <label for="filter-search" class="form-label small text-muted mb-1">Search</label>
            <input type="search" name="q" id="filter-search" class="form-control form-control-sm"
                value="{{ $currentSearch }}" placeholder="{{ $isOrg ? 'Search captions…' : 'Org name or caption…' }}">
        </div>
        @if($showSort)
            <div class="col-md-2 col-sm-6">
                <label for="filter-sort" class="form-label small text-muted mb-1">Sort by</label>
                <select name="sort" id="filter-sort" class="form-select form-select-sm">
                    <option value="created_at" @selected($currentSort === 'created_at')>Date submitted</option>
                    <option value="updated_at" @selected($currentSort === 'updated_at')>Last updated</option>
                    <option value="id" @selected($currentSort === 'id')>ID</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="filter-order" class="form-label small text-muted mb-1">Order</label>
                <select name="order" id="filter-order" class="form-select form-select-sm">
                    <option value="desc" @selected($currentOrder === 'desc')>Newest first</option>
                    <option value="asc" @selected($currentOrder === 'asc')>Oldest first</option>
                </select>
            </div>
        @endif
        <div class="{{ $showSort ? 'col-md-2' : 'col-md-3' }} col-sm-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-funnel"></i> Apply
            </button>
            @if($currentFilter !== 'all' || $currentSearch !== '' || ($showSort && ($currentSort !== 'created_at' || $currentOrder !== 'desc')))
                <a href="{{ $action }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">Reset</a>
            @endif
        </div>
    </div>
</form>
