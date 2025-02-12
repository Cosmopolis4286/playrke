{{-- resources/views/includes/btn-group.blade.php  --}}
<div class="d-flex justify-content-between align-items-center">
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-success btn-{{ $name }}" data-type="1">
            <i class="fas fa-plus"></i> Crear
        </button>
        @if ($view === 'AUTO')
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.all-rooms', ['roomType' => 'private-rooms', 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fas fa-cogs"></i> Manual
            </a>
        @else
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.all-rooms', ['roomType' => 'automatic-rooms', 'creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fa-solid fa-rotate"></i> Auto
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
                        $icon = 'fas fa-globe';
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
    @if ($view !== 'AUTO')
        <a href="{{ $activeRooms ? $GMRoute : '#' }}"
            class="btn btn-sm btn-primary {{ $activeRooms ? '' : 'disabled' }}">
            <i class="fas fa-eye"></i> Ver Salas
        </a>
    @else
        <button type="button" id="btn-execute-job" class="btn btn-sm btn-primary {{ $rooms ? '' : 'disabled' }}">
            <i class="fa-solid fa-bolt"></i> Ejecutar Job
        </button>
    @endif
</div>
