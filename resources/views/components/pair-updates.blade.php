@props(['text' => ''])

@if(trim((string) $text) !== '')
    <div {{ $attributes->merge(['class' => 'pair-updates text-dark']) }}>
        @foreach(preg_split("/\n\s*\n/", trim($text)) as $block)
            @php
                $lines = preg_split("/\r\n|\n|\r/", trim($block));
                $header = $lines[0] ?? '';
                $bullets = array_slice($lines, 1);
            @endphp
            <div class="pair-update-block mb-3">
                @if(str_starts_with($header, '[PAIR'))
                    <div class="fw-bold syncro-pair-header mb-1">{{ $header }}</div>
                @else
                    <div class="mb-1">{{ $header }}</div>
                @endif
                @if(count($bullets))
                    <ul class="mb-0 ps-3">
                        @foreach($bullets as $line)
                            @if(trim($line) !== '')
                                <li>{{ ltrim($line, "• \t") }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endif
