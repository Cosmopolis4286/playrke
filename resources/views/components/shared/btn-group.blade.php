<div class="btn-group btn-group{{ $btnGroupId }}" role="group" aria-label="Basic example" data-id="{{ $dataId }}">
    @foreach ($buttons as $btn)
        <button type="button" class="btn btn-sm {{ $btn->color }} btn-{{ $btnGroupId }}"
            data-action="{{ $btn->action }}">
            <i class="{!! $btn->icon !!}"></i> {{ $btn->title ?? '' }}
        </button>
    @endforeach
</div>
