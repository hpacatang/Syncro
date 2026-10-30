@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Media gallery</h1>
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
                    $isVideo = in_array($asset['ext'], ['mp4', 'mov', 'webm', 'ogg', 'avi', 'mkv', 'wmv'], true);
                @endphp
                <div class="col">
                    <div class="syncro-media-tile h-100 d-flex flex-column border rounded overflow-hidden shadow-sm bg-white">
                        @if($isImage)
                            <a href="{{ $asset['url'] }}" target="_blank" rel="noopener" class="d-block position-relative bg-light" style="height: 190px; overflow: hidden;">
                                <img src="{{ $asset['url'] }}" alt="{{ $asset['name'] }}" loading="lazy" class="w-100 h-100" style="object-fit: cover;">
                            </a>
                        @elseif($isVideo)
                            <div class="bg-dark position-relative" style="height: 190px;">
                                <video controls preload="metadata" class="w-100 h-100" style="object-fit: contain;">
                                    <source src="{{ $asset['url'] }}">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        @else
                            <a href="{{ $asset['url'] }}" target="_blank" rel="noopener" class="text-decoration-none text-dark d-flex flex-column align-items-center justify-content-center p-3 bg-light" style="height: 190px;">
                                <i class="bi bi-file-earmark-text display-5 text-primary mb-2"></i>
                                <span class="small text-center text-truncate w-100 px-2">{{ $asset['name'] }}</span>
                            </a>
                        @endif
                        <div class="p-2 border-top bg-white small text-muted mt-auto">
                            <div class="d-flex align-items-center justify-content-between mb-1" style="min-width: 0;">
                                <span class="fw-semibold text-dark text-truncate me-2 d-inline-flex align-items-center" title="{{ $asset['org'] ?? '—' }}" style="min-width: 0;">
                                    <i class="bi bi-building me-1 text-secondary flex-shrink-0"></i><span class="text-truncate">{{ $asset['org'] ?? '—' }}</span>
                                </span>
                                <span class="badge bg-secondary flex-shrink-0">{{ strtoupper($asset['ext']) }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Submission #{{ $asset['submission_id'] }}</span>
                                <a href="{{ $asset['url'] }}" target="_blank" rel="noopener" class="text-decoration-none small text-primary d-inline-flex align-items-center" title="Open file in new tab">
                                    <span>View</span><i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
