<ul class="nav nav-tabs mt-n2 mb-3" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link @if ($countryCode == 'usdt') active @endif" id="1USDT-tab"
            href="{{ route('directory.index', ['countryCode' => 'usdt']) }}" role="tab" aria-controls="1USDT"
            aria-selected="true">USDT</a>
    </li>
    @foreach ($countries as $item)
        <li class="nav-item">
            <a class="nav-link @if ($countryCode == strtolower($item->iso_code_2)) active @endif"
                id="{{ $item->id }}{{ $item->iso_code_2 }}-tab"
                href="{{ route('directory.index', array_merge(['countryCode' => strtolower($item->iso_code_2)])) }}"
                role="tab" aria-controls="{{ $item->id }}{{ $item->iso_code_2 }}"
                aria-selected="true">{{ $item->iso_code_2 }}</a>
        </li>
    @endforeach
</ul>
