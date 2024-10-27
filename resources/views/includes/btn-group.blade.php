{{-- resources/views/includes/btn-group.blade.php --}}
<div class="btn-group" role="group" aria-label="Basic example">
    @foreach ($buttons as $btn)
        <button type="button" class="btn btn-sm {{ $btn->color }} btn-{{ $name }}"
            data-type="{{ $btn->dataType }}" data-id="{{ $dataId }}">
            <i class="{!! $btn->icon !!}"></i> {{ $btn->title ?? '' }}
        </button>
    @endforeach
</div>
