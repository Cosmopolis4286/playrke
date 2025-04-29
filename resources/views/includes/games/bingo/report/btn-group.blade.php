{{-- resources/views/includes/btn-group.blade.php  --}}
<div class="d-flex justify-content-between align-items-center">
    <div class="btn-group">
        @if ($view === 'OTHER-PUBLIC')
            <button type="button" class="btn btn-sm btn-success btn-{{ $name }}" data-type="4">
                <i class="fa-solid fa-list-check"></i> Ver Salas
            </button>
        @else
            <button type="button" class="btn btn-sm btn-success btn-{{ $name }}" data-type="1">
                <i class="fas fa-plus"></i> Crear
            </button>
        @endif
        @if ($view === 'AUTO')
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.all-rooms', ['roomType' => 'private-rooms', 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fas fa-cogs"></i> Manual
            </a>
        @else
            @php
                $buttons = [
                    'PRIVATE' => [
                        'class' => 'btn-secondary',
                        'icon' => 'fa-rotate',
                        'text' => 'Auto',
                        'roomType' => 'automatic-rooms',
                    ],
                    'OTHER-PUBLIC' => [
                        'class' => 'btn-secondary',
                        'icon' => 'fas fa-globe',
                        'text' => 'Publicas',
                        'roomType' => 'public-rooms',
                    ],
                ];

                $default = [
                    'class' => 'btn-primary',
                    'icon' => 'fa-gamepad',
                    'text' => 'Otras Salas',
                    'roomType' => 'other-public-rooms',
                ];

                $data = $buttons[$view] ?? $default;
            @endphp

            <a class="btn btn-sm {{ $data['class'] }}"
                href="{{ route('bingo.all-rooms', ['roomType' => $data['roomType'], 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fa-solid {{ $data['icon'] }}"></i> {{ $data['text'] }}
            </a>
            @if ($creator['type'] !== 'customer')
                @if ($view === 'PRIVATE')
                    @php
                        $title = 'Publicas';
                        $icon = 'fas fa-globe';
                        $roomType = 'public-rooms';
                    @endphp
                @else
                    @php
                        $title = 'Privadas';
                        $icon = 'fa-solid fa-shield-halved';
                        $roomType = 'private-rooms';
                    @endphp
                @endif

                <a class="btn btn-sm btn-secondary"
                    href="{{ route('bingo.all-rooms', ['roomType' => $roomType, 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                    <i class="{{ $icon }}"></i> {{ $title }}
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
        <a href="{{ $activeRooms ? $GMRoute : '#' }}"
            class="btn btn-sm btn-primary {{ $activeRooms ? '' : 'disabled' }}">
            <i class="fas fa-eye"></i> Ver salas
        </a>
    @endif
</div>
