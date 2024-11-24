{{-- resources/views/includes/coin-tabs.blade.php --}}
<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    @foreach ($coins as $item)
        <li class="nav-item">
            <a class="nav-link {{ $coin == $item->id ? 'active' : '' }}"
                id="{{ $item->id }}-{{ $item->iso_code ?? ($item->coin ?? $item->short_name) }}-tab"
                href="{{ route($routeWithCoin['route'], array_merge($routeWithCoin['parameters'], ['coin' => encrypt($item->id)])) }}"
                role="tab"
                aria-controls="{{ $item->id }}-{{ $item->iso_code ?? ($item->coin ?? $item->short_name) }}"
                aria-selected="{{ $coin == $item->id ? 'true' : 'false' }}">
                {{ $item->iso_code ?? ($item->coin ?? $item->short_name) }}
            </a>
        </li>
    @endforeach
</ul>
