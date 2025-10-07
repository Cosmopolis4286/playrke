{{-- resources/views/components/shared/table-with-selection.blade.php --}}
@props([
    'title' => '',
    'moduleId' => '',
    'columns' => [],
    'rows' => collect(),
    'rowKeys' => [],
    'context' => '',
    'tableId' => '',
    'hashedIds' => [], // Array de IDs encriptados
])

<div class="card">
    <x-shared.card-header :title="$title" />

    {{-- x-data recibe los IDs encriptados --}}
    <div class="card-body" x-data='tableSelection(@json($hashedIds), "{{ $tableId }}")'
        data-table-id="{{ $tableId }}">
        {{-- Botones de acciones --}}
        <x-shared.btn-group useModal="true" :btn-group-id="$moduleId" :buttons="[]" :context="$context"
            x-bind:data-id="selectedIds" />

        {{-- Tabla --}}
        <div class="table-responsive mt-2">
            <table class="table table-striped table-bordered my-2 w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px;">
                            <input type="checkbox" @click="toggleAll()" :checked="allSelected">
                        </th>
                        @foreach ($columns as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $item)
                        {{-- Usamos $hashedIds[$index] para cada fila --}}
                        @php $hashId = $hashedIds[$index] ?? $item->id; @endphp
                        <tr :class="selected.includes('{{ $hashId }}') ? 'table-primary' : ''"
                            @click="toggle('{{ $hashId }}')" style="cursor:pointer;">
                            <td class="text-center">
                                <input type="checkbox" :checked="selected.includes('{{ $hashId }}')"
                                    @click.stop="toggle('{{ $hashId }}')">
                            </td>
                            @foreach ($rowKeys as $key)
                                <td>{!! $key === 'status_label' ? data_get($item, $key) : e(data_get($item, $key)) !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
