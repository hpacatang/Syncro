@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h3 fw-bold mb-2">Tone configuration</h1>
    <p class="text-muted">Default tone for AI caption enhancement and caption-from-media.</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card syncro-card-elevated border-0 mt-4">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('settings.tone.update') }}">
                @csrf
                <label for="tone" class="form-label fw-semibold">Caption tone</label>
                <select name="tone" id="tone" class="form-select mb-3" required>
                    @foreach([
                        'formal' => 'Formal',
                        'friendly' => 'Friendly',
                        'enthusiastic' => 'Enthusiastic',
                        'urgent' => 'Urgent',
                        'professional' => 'Academic / professional',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($tone ?? 'formal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
