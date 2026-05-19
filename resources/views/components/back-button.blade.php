@props([
    'href' => null,
    'label' => null,
])

@php
    $resolved = \App\Support\BackNavigation::resolve();
    $href = $href ?? $resolved['href'];
    $label = $label ?? $resolved['label'];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary btn-sm syncro-back-btn']) }}>
    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ $label }}
</a>
