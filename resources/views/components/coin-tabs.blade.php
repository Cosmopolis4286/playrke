{{-- resources/views/components/coin-tabs.blade.php  --}}
<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    @foreach ($coins as $item)
        <li class="nav-item">
            <a class="nav-link @if ($coin == $item->id) active @endif"
                id="{{ $item->id }}{{ $item->symbol }}-tab"
                href="{{ route(
                    $routeName,
                    array_merge(
                        isset($dateFilter) ? ['dateFilter' => $dateFilter] : [],
                        isset($room) ? ['room' => $room] : [],
                        ['user' => encrypt(Auth::id())],
                        ['coin' => encrypt($item->id)],
                    ),
                ) }}"
                role="tab" aria-controls="{{ $item->id }}{{ $item->symbol }}"
                aria-selected="true">{{ $item->short_name }}</a>
        </li>
    @endforeach
</ul>
