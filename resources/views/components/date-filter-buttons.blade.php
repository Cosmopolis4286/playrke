@foreach (['yesterday' => 'Ayer', 'today' => 'Hoy', 'all' => 'Todo'] as $filter => $label)
    <a href="{{ route(
        $routeName,
        array_merge(
            ['dateFilter' => $filter],
            isset($room) ? ['room' => $room] : [],
            isset($coin) ? ['coin' => encrypt($coin)] : [],
        ),
    ) }}"
        class="btn-date-filter btn btn-sm btn-primary {{ $dateFilter === $filter || ($dateFilter === null && $filter === 'today') ? 'active' : '' }}"
        role="button">{{ $label }}</a>
@endforeach
