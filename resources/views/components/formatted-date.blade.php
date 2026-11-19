@props([
    'at' => null,
    'format' => 'M d, Y',
    'empty' => 'N/A',
    'relative' => false,
])

@php
    $value = $at instanceof \DateTimeInterface ? \App\Support\AppTime::toLocal($at) : null;
@endphp

@if($relative && $value)
    {{ $value->diffForHumans() }}
@elseif($value)
    {{ $value->format($format) }}
@else
    {{ $empty }}
@endif
