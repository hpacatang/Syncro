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
                @endphp
                <div class="card mb-3 syncro-card-elevated border-start border-4 {{ $isUnread ? 'border-primary syncro-notif-item--unread' : 'border-secondary' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-primary mb-0">{{ $title }}</h5>
                            <small class="text-muted"><x-formatted-date :at="$notification->created_at" relative /></small>
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
