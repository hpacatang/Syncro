@props([
    'href',
    'title' => __('View'),
    'small' => true,
])

@php
    $accessibleLabel = $slot->isNotEmpty() ? trim(strip_tags((string) $slot)) : $title;
    $sizeClass = $small ? 'btn-sm' : null;
@endphp

<a
    href="{{ $href }}"
    title="{{ $title }}"
    aria-label="{{ $accessibleLabel }}"
    {{ $attributes->class(array_filter(['btn', $sizeClass, 'btn-outline-primary'])) }}
>
    <i class="bi bi-eye" aria-hidden="true"></i>
    @if ($slot->isNotEmpty())
        <span class="ms-1">{{ $slot }}</span>
    @endif
</a>
