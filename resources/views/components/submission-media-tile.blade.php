@props(['path', 'caption' => null])

@php
    use App\Support\SubmissionMedia;

    $resolved = SubmissionMedia::resolve($path);
    $mediaUrl = $resolved ? SubmissionMedia::url($path) : null;
    $isImage = $resolved && SubmissionMedia::isImage($path);
@endphp

@if($resolved && $mediaUrl)
    <div {{ $attributes->merge(['class' => 'syncro-media-tile']) }}>
        @if($isImage)
            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="d-block">
                <img
                    src="{{ $mediaUrl }}"
                    alt="{{ basename($path) }}"
                    loading="lazy"
                    class="syncro-media-tile__img"
                >
            </a>
        @else
            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="syncro-media-tile__file d-flex flex-column align-items-center justify-content-center text-decoration-none text-dark">
                <i class="bi bi-file-earmark-text display-6 text-primary mb-2"></i>
                <span class="small text-center text-truncate w-100 px-2">{{ basename($path) }}</span>
            </a>
        @endif
        @if($caption)
            <div class="p-2 border-top syncro-media-tile__caption">
                <small class="text-muted">{{ $caption }}</small>
            </div>
        @endif
    </div>
@endif
