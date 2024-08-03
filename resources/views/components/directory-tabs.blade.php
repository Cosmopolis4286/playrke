{{-- resources/views/components/directory-tabs.blade.php --}}
<ul class="nav nav-tabs mt-n2 mb-3" id="myTab" role="tablist">
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
