@php
    $filters = ['yesterday' => 'Ayer', 'today' => 'Hoy', 'all' => 'Todo'];
@endphp

@foreach ($filters as $filter => $label)
    @php
        $params = $routeWithCoin['parameters'] ?? [];
    @endphp

    <a href="{{ route($routeWithCoin['route'], $params) }}"
        class="btn-date-filter btn btn-sm btn-primary {{ $dateFilter === $filter || ($dateFilter === null && $filter === 'today') ? 'active' : '' }}"
        role="button">
        {{ $label }}
    </a>
@endforeach
