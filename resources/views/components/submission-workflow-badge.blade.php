@props(['submission', 'size' => ''])

@php
    $lifecycle = $submission->lifecycle();
    $lightText = in_array($lifecycle->value, ['submitted', 'revised', 'under_peer_review'], true);
    $class = 'badge ' . ($lightText ? 'text-dark' : 'text-white');
    if ($size === 'lg') {
        $class .= ' fs-6 px-3 py-2';
    }
@endphp

<span
    {{ $attributes->merge([
        'class' => $class,
        'data-lifecycle-badge' => '',
        'data-submission-id' => $submission->id,
    ]) }}
    style="background-color: {{ $lifecycle->progressColor() }};"
>
    {{ $lifecycle->label() }}
</span>
