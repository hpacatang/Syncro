@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="mb-0"><i class="bi bi-bell me-2"></i> Notifications</h2>
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <button type="button" class="btn btn-outline-primary btn-sm" id="markAllReadBtn">Mark all as read</button>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $title = $data['title'] ?? class_basename($notification->type);
                    $message = $data['message'] ?? 'You have a new notification.';
                    $targetUrl = \App\Support\NotificationTargetUrl::resolve($data, auth()->user());
                    $isUnread = $notification->read_at === null;

                    // Resolve submission for full organization and date details (handles existing/older records too)
                    $submissionId = $data['submission_id'] ?? null;
                    $submission = $submissionId ? \App\Models\Submission::withoutGlobalScopes()->with(['user.department', 'reviewDepartment'])->find($submissionId) : null;

                    $fromOrg = $data['sender_org'] ?? null;
                    $fromName = $data['sender_name'] ?? $data['from'] ?? null;
                    $submittedAt = $data['submitted_at'] ?? null;
                    $scheduledPostAt = $data['scheduled_post_at'] ?? null;

                    if ($submission) {
                        $legacyOrg = $submission->user?->displayName();
                        if ($submission->user?->department) {
                            $legacyOrg .= ' (' . $submission->user->department->displayName() . ')';
                        }
                        $fromOrg ??= $legacyOrg;
                        $fromName ??= $legacyOrg;
                        if (!$submittedAt && $submission->created_at) {
                            $submittedAt = $submission->created_at->format('M d, Y h:i A');
                        }
                        if (!$scheduledPostAt && $submission->scheduled_post_at) {
                            $scheduledPostAt = $submission->scheduled_post_at->format('M d, Y h:i A');
                        }
                        if ($message === 'A new submission was added to the review queue.' && $legacyOrg) {
                            $message = "Submitted by {$legacyOrg} on {$submittedAt} for {$submission->reviewOwnerLabel()} review.";
                        }
                    }
                @endphp
                <div class="card mb-3 syncro-card-elevated border-start border-4 {{ $isUnread ? 'border-primary syncro-notif-item--unread' : 'border-secondary' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-3">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h5 class="card-title text-primary mb-2 fw-bold">{{ $title }}</h5>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    @if($fromOrg)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <i class="bi bi-building me-1"></i>From Org: <strong>{{ $fromOrg }}</strong>
                                        </span>
                                    @elseif($fromName)
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <i class="bi bi-person-circle text-primary me-1"></i>From: <strong>{{ $fromName }}</strong>
                                        </span>
                                    @endif

                                    @if($submittedAt)
                                        <span class="badge bg-light text-secondary border px-2 py-1" title="Submitted date and time">
                                            <i class="bi bi-calendar-event text-secondary me-1"></i>Submitted: {{ $submittedAt }}
                                        </span>
                                    @endif

                                    @if($scheduledPostAt)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" title="Desired posting schedule">
                                            <i class="bi bi-clock-history me-1"></i>Target Post: {{ $scheduledPostAt }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end text-muted flex-shrink-0 text-nowrap ms-auto">
                                <small class="d-block fw-semibold text-secondary"><x-formatted-date :at="$notification->created_at" relative /></small>
                                <small style="font-size: 0.78rem;"><x-formatted-date :at="$notification->created_at" format="M d, Y h:i A" /></small>
                            </div>
                        </div>
                        <p class="card-text text-secondary mb-3">{{ $message }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $targetUrl }}" class="btn btn-sm btn-primary notif-open-link" data-notif-id="{{ $notification->id }}">Open</a>
                            @if($isUnread)
                                <button type="button" class="btn btn-sm btn-outline-secondary notif-mark-read" data-notif-id="{{ $notification->id }}">Mark read</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card syncro-card-elevated bg-light text-center py-5 border-0">
                    <div class="card-body">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">You're all caught up!</h5>
                        <p class="text-muted mb-0">No notifications right now.</p>
                    </div>
                </div>
            @endforelse

            @if($notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const readBase = @json(url('/notifications'));

    async function markRead(id) {
        await fetch(readBase + '/' + encodeURIComponent(id) + '/read', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
    }

    document.getElementById('markAllReadBtn')?.addEventListener('click', async function () {
        try {
            await fetch(@json(route('notifications.read-all')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            window.location.reload();
        } catch (e) { alert('Could not mark all read'); }
    });

    document.querySelectorAll('.notif-mark-read').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = btn.getAttribute('data-notif-id');
            try {
                await markRead(id);
                window.location.reload();
            } catch (e) { alert('Could not update'); }
        });
    });

    document.querySelectorAll('.notif-open-link').forEach(function (a) {
        a.addEventListener('click', async function (e) {
            const href = a.getAttribute('href');
            if (!href || href === '#') return;

            e.preventDefault();
            e.stopPropagation();

            const id = a.getAttribute('data-notif-id');
            if (id && csrf) {
                try { await markRead(id); } catch (err) {  }
            }
            window.location.assign(href);
        });
    });
})();
</script>
@endpush
