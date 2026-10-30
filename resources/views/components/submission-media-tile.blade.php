@props(['path', 'caption' => null])

@php
    use App\Support\SubmissionMedia;

    $mediaUrl = SubmissionMedia::url($path);
    $isImage = SubmissionMedia::isImage($path);
    $isVideo = SubmissionMedia::isVideo($path);
    $filename = basename($path);
@endphp

@if($path && $mediaUrl)
    <div {{ $attributes->merge(['class' => 'syncro-media-tile h-100 d-flex flex-column border rounded overflow-hidden bg-white shadow-sm']) }}>
        @if($isImage)
            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="d-block position-relative bg-light" style="height: 180px; overflow: hidden;">
                <img
                    src="{{ $mediaUrl }}"
                    alt="{{ $filename }}"
                    loading="lazy"
                    class="syncro-media-tile__img w-100 h-100"
                    style="object-fit: cover;"
                >
            </a>
            <div class="px-2 py-1 bg-light border-top d-flex align-items-center justify-content-between" style="min-height: 32px;">
                <span class="small text-truncate text-muted d-inline-flex align-items-center me-2" title="{{ $filename }}" style="min-width: 0;">
                    <i class="bi bi-image text-primary me-1 flex-shrink-0"></i><span class="text-truncate">{{ $filename }}</span>
                </span>
                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary py-0 px-1 d-inline-flex align-items-center flex-shrink-0" title="Open image in new tab">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        @elseif($isVideo)
            <div class="syncro-media-tile__video-wrapper bg-dark position-relative" style="height: 180px;">
                <video controls preload="metadata" class="w-100 h-100" style="object-fit: contain;">
                    <source src="{{ $mediaUrl }}">
                    Your browser does not support the video tag.
                </video>
            </div>
            <div class="px-2 py-1 bg-light border-top d-flex align-items-center justify-content-between" style="min-height: 32px;">
                <span class="small text-truncate text-muted d-inline-flex align-items-center me-2" title="{{ $filename }}" style="min-width: 0;">
                    <i class="bi bi-camera-video text-primary me-1 flex-shrink-0"></i><span class="text-truncate">{{ $filename }}</span>
                </span>
                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary py-0 px-1 d-inline-flex align-items-center flex-shrink-0" title="Open video in new tab">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        @else
            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="syncro-media-tile__file d-flex flex-column align-items-center justify-content-center text-decoration-none text-dark p-3 bg-light" style="height: 180px;">
                <i class="bi bi-file-earmark-text display-5 text-primary mb-2"></i>
                <span class="small text-center text-truncate w-100 px-2">{{ $filename }}</span>
            </a>
            <div class="px-2 py-1 bg-light border-top d-flex align-items-center justify-content-between" style="min-height: 32px;">
                <span class="small text-truncate text-muted d-inline-flex align-items-center me-2" title="{{ $filename }}" style="min-width: 0;">
                    <i class="bi bi-file-earmark text-primary me-1 flex-shrink-0"></i><span class="text-truncate">{{ $filename }}</span>
                </span>
                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary py-0 px-1 d-inline-flex align-items-center flex-shrink-0" title="Open file in new tab">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        @endif
        @if($caption)
            <div class="px-2 py-1 border-top syncro-media-tile__caption bg-white mt-auto">
                <small class="text-muted d-block text-truncate" title="{{ $caption }}">
                    <i class="bi bi-person me-1 text-secondary"></i>{{ $caption }}
                </small>
            </div>
        @endif
    </div>
@endif
