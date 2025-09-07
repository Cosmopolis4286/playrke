@foreach (['yesterday' => 'Ayer', 'today' => 'Hoy', 'all' => 'Todo'] as $filter => $label)
    <a href="{{ route(
        $routeWithCurrency,
        array_merge(
            ['dateFilter' => $filter],
            isset($room) ? ['room' => $room] : [],
            isset($currency) ? ['currency' => encrypt($currency)] : [],
        ),
    ) }}"
        class="btn-date-filter btn btn-sm btn-primary {{ $dateFilter === $filter || ($dateFilter === null && $filter === 'today') ? 'active' : '' }}"
        role="button">{{ $label }}</a>
@endforeach
