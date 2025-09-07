{{-- resources/views/includes/table-info-bingo-automatic-rooms.blade.php --}}
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Modo</th>
                <th>Hora</th>
                <th>CTN</th>
                <th>Bote</th>
                <th>%Com</th>
                <th>%Bote Serial</th>
                <th>Máx Serial</th>
                <th>Status</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rooms as $room)
                <tr>
                    <td>
                        {{ $room->room_type == 2 ? 'Publica' : 'Privada' }}
                    </td>
                    <td>{{ $room->GameMode->description }}</td>
                    <td>{{ $room->time }} H</td>
                    <td>{{ $room->card_price }}&nbsp<small>USDT</small></td>
                    <td>{{ $room->PrizePoolType->description }}</td>
                    <td>{{ $room->room_commn }}%</td>
                    <td>{{ $room->serials_pct }}%</td>
                    <td>{{ $room->num_serials }}</td>
                    <td class="align-middle">
                        @if ($room->status == 1)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-gray">Inactivo</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <div class="btn-group" role="group" aria-label="Acciones">
                            <button title="Editar" class="btn-{{ $moduleId }} btn btn-sm btn-primary" data-action="edit"
                                data-id="{{ encrypt($room->id) }}">
                                <i class="fa-solid fa-pen-to-square"></i> </button>
                            <button class="btn-{{ $moduleId }} btn btn-sm btn-secondary" data-type="4"
                                data-id="{{ encrypt($room->id) }}" title="Publicar">
                                <i class="fa-solid fa-repeat"></i>
                            </button>
                            @if ($room->status == 0)
                                <button class="btn-{{ $moduleId }} btn btn-sm bg-danger" data-action="delete"
                                    data-id="{{ encrypt($room->id) }}" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i></button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
