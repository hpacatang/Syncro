@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold">Media gallery</h1>
        <p class="text-muted mb-0">Files uploaded with submissions.</p>
    </div>

    @if(empty($assets))
        <div class="card syncro-card-elevated border-0 text-center py-5">
            <div class="card-body text-muted">
                <i class="bi bi-images fs-1 d-block mb-3"></i>
                No uploaded assets found.
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            @foreach($assets as $asset)
                @php
                    $isImage = in_array($asset['ext'], ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                @endphp
                <div class="col">
                    <div class="syncro-media-tile h-100 d-flex flex-column">
                        @if($isImage)
                            <a href="{{ $asset['url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $asset['url'] }}" alt="{{ $asset['name'] }}">
                            </a>
                        @else
                            <a href="{{ $asset['url'] }}" target="_blank" rel="noopener" class="text-decoration-none text-dark flex-grow-1 d-flex flex-column align-items-center justify-content-center p-4" style="min-height: 200px;">
                                <i class="bi bi-file-earmark-text display-4 text-primary mb-2"></i>
                                <span class="small text-center text-truncate w-100 px-2">{{ $asset['name'] }}</span>
                            </a>
                        @endif
                        <div class="p-2 border-top bg-white small text-muted mt-auto">
                            <div class="text-truncate">{{ $asset['org'] ?? '—' }}</div>
                            <div>#{{ $asset['submission_id'] }} · {{ strtoupper($asset['ext']) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
