{{-- resources/views/includes/coin-tabs.blade.php  --}}
<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    @foreach ($coins as $item)
        <li class="nav-item">
            <a class="nav-link @if ($coin == $item->id) active @endif"
                id="{{ $item->id }}{{ $item->symbol ?? $item->coin }}-tab"
                href="{{ route(
                    $routeName,
                    array_merge(
                        isset($dateFilter) ? ['dateFilter' => $dateFilter] : [],
                        isset($room) ? ['room' => $room] : [],
                        ['user' => !isset($userToken) || !$userToken ? encrypt(Auth::id()) : $userToken],
                        ['coin' => encrypt($item->id)],
                    ),
                ) }}"
                role="tab" aria-controls="{{ $item->id }}{{ $item->symbol ?? $item->coin }}"
                aria-selected="true">{{ $item->short_name ?? $item->coin }}</a>
        </li>
    @endforeach
</ul>
