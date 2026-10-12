@extends('layouts.app')

@section('page-title', 'Account requests')
@section('page-subtitle', 'Review access requests from students')

@section('content')
<div class="container-fluid px-0 py-1">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>School / Department</th>
                        <th>Requested</th>
                        <th class="text-end">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->displayName() }}</td>
                            <td><code>{{ $user->name }}</code></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department?->displayName() ?? '—' }}</td>
                            <td><x-formatted-date :at="$user->created_at" /></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('account-requests.approve', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('account-requests.reject', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">There are no pending account requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
