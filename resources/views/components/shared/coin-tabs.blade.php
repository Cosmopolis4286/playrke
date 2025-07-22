@props([
    'coins' => [],
    'coin' => null,
    'routeWithCoin' => ['route' => '', 'parameters' => []],
])

<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    @foreach ($coins as $item)
        @php
            $label = $item->iso_code ?? ($item->coin ?? $item->short_name);
            $isActive = $coin == $item->id;
        @endphp
        <li class="nav-item">
            <a class="nav-link {{ $isActive ? 'active' : '' }}" id="{{ $item->id }}-{{ $label }}-tab"
                href="{{ route($routeWithCoin['route'], array_merge($routeWithCoin['parameters'], ['coin' => encrypt($item->id)])) }}"
                role="tab" aria-controls="{{ $item->id }}-{{ $label }}"
                aria-selected="{{ $isActive ? 'true' : 'false' }}">
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>
