{{-- resources/views/includes/btn-group.blade.php --}}
<div class="btn-group" role="group" aria-label="Basic example" data-id="{{ $dataId }}">
    @foreach ($buttons as $btn)
        <button type="button" class="btn btn-sm {{ $btn->color }} btn-{{ $viewId }}"
            data-action="{{ $btn->action }}">
            <i class="{!! $btn->icon !!}"></i> {{ $btn->title ?? '' }}
        </button>
    @endforeach
</div>
