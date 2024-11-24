{{-- resources/views/includes/btn-group.blade.php  --}}
<div class="d-flex justify-content-between align-items-center">
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-success btn-{{ $name }}" data-type="1">
            <i class="fas fa-plus"></i> Crear
        </button>
        @if (isset($privateRoom))
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.all-rooms', ['creatorType' => $creator['type'], 'userID' => $creator['id']]) }}">
                <i class="fas fa-globe"></i> Privadas
            </a>
        @else
            <a class="btn btn-sm btn-secondary"
                href="{{ route('bingo.automatic-room.all-rooms', ['creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                <i class="fas fa-cogs"></i> Auto
            </a>
            @if ($creator['type'] !== 'customer')
                <a class="btn btn-sm btn-secondary"
                    href="{{ route('bingo.public-room.all-rooms', ['creatorType' => $creator['type'], 'creatorID' => $creator['id']]) }}">
                    <i class="fas fa-globe"></i> Publicas
                </a>
            @endif
        @endif
    </div>
    @if (isset($automaticRoom))
        <a href="{{ $activeRooms ? $GMRoute : '#' }}"
            class="btn btn-sm btn-primary {{ $activeRooms ? '' : 'disabled' }}">
            <i class="fas fa-eye"></i> Ver Salas
        </a>
    @else
        <a href="{{ $activeRooms ? $GMRoute : '#' }}"
            class="btn btn-sm btn-primary {{ $activeRooms ? '' : 'disabled' }}">
            <i class="fa-solid fa-bolt"></i> Ejecutar Job
        </a>
    @endif
</div>
