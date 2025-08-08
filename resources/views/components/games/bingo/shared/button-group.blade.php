@props(['view', 'name' => null, 'creator' => null, 'rooms' => null, 'activeRooms' => null, 'gameRoomRoute' => null])

@php
    $isCustomer = $creator['type'] === 'customer';
    $routes = [
        'PRIVATE' => [
            'class' => 'btn-secondary',
            'icon' => 'fa-solid fa-rotate',
            'text' => 'Auto',
            'roomType' => 'automatic-rooms',
        ],
        'OTHER-PUBLIC' => [
            'class' => 'btn-secondary',
            'icon' => 'fa-solid fas fa-globe',
            'text' => 'Públicas',
            'roomType' => 'public-rooms',
        ],
    ];

    $defaultRoute = [
        'class' => 'btn-primary',
        'icon' => 'fa-solid fa-gamepad',
        'text' => 'Otras Salas',
        'roomType' => 'other-public-rooms',
    ];

    $routeData = $routes[$view] ?? $defaultRoute;
@endphp

<div class="d-flex justify-content-between align-items-center">
    <div class="btn-group">
        @if ($view === 'OTHER-PUBLIC')
            <button type="button" class="btn btn-sm btn-success btn-{{ $name }}" data-type="4">
                <i class="fa-solid fa-list-check"></i> Ver Salas
            </button>
        @else
            <a href="{{ route('bingo.handleForm') }}" class="btn btn-sm btn-success">
                <i class="fas fa-plus"></i> Crear
            </a>
        @endif

        @if ($view === 'AUTO')
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.handleGameRoomSummary', ['roomType' => 'private-rooms', 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fas fa-cogs"></i> Manual
            </a>
        @else
            <a class="btn btn-sm {{ $routeData['class'] }}"
                href="{{ route('bingo.handleGameRoomSummary', ['roomType' => $routeData['roomType'], 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="{{ $routeData['icon'] }}"></i> {{ $routeData['text'] }}
            </a>

            @if (!$isCustomer)
                @php
                    $altTitle = $view === 'PRIVATE' ? 'Públicas' : 'Privadas';
                    $altIcon = $view === 'PRIVATE' ? 'fas fa-globe' : 'fa-solid fa-shield-halved';
                    $altRoomType = $view === 'PRIVATE' ? 'public-rooms' : 'private-rooms';
                @endphp

                <a class="btn btn-sm btn-secondary"
                    href="{{ route('bingo.handleGameRoomSummary', ['roomType' => $altRoomType, 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                    <i class="{{ $altIcon }}"></i> {{ $altTitle }}
                </a>
            @else
                <button id="btn-game-id" class="btn btn-sm btn-dark">
                    <i class="fa-solid fa-gamepad"></i> Game ID
                </button>
            @endif
        @endif
    </div>

    @if ($view === 'AUTO')
        <button type="button" id="btn-execute-job" class="btn btn-sm btn-primary {{ $rooms ? '' : 'disabled' }}">
            <i class="fa-solid fa-bolt"></i> Ejecutar Job
        </button>
    @elseif($view === 'PRIVATE')
        <a href="{{ $activeRooms ? $gameRoomRoute : '#' }}"
            class="btn btn-sm btn-primary {{ $activeRooms ? '' : 'disabled' }}"
            target="{{ $activeRooms ? '_blank' : '_self' }}">
            <i class="fas fa-eye"></i> Ver Salas
        </a>
    @endif
</div>
