{{-- resources/views/components/shared/table-with-selection.blade.php --}}
@props([
    'title' => '',
    'moduleId' => '',
    'columns' => [],
    'rows' => collect(),
    'rowKeys' => [],
    'context' => '',
])

<div class="card">
    <x-shared.card-header :title="$title" />
    <div class="card-body" x-data="tableSelection(@json($rows->pluck('id')))"
        @btn-action.window="(event) => { const action = event.detail[0]; const ids = event.detail[1];}">
        {{-- Botones de acciones --}}
        <x-shared.btn-group :btn-group-id="$moduleId" :buttons="[]" :context="$context" x-bind:data-id="selectedIds" />
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
                    @foreach ($rows as $item)
                        <tr :class="selected.includes({{ $item->id }}) ? 'table-primary' : ''"
                            @click="toggle({{ $item->id }})" style="cursor:pointer;">
                            <td class="text-center">
                                <input type="checkbox" :checked="selected.includes({{ $item->id }})"
                                    @click.stop="toggle({{ $item->id }})">
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
