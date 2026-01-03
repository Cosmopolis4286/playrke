{{-- resources/views/components/shared/btn-group.blade.php --}}
@props(['buttons' => collect(), 'moduleId' => '', 'useModal' => false])

<div class="btn-group" role="group" aria-label="Acciones">
    @foreach ($buttons as $btn)
        <button type="button"
            class="btn btn-sm {{ $btn->color }} btn-{{ $btnGroupId }} {{ $useModal ? 'btn-open-modal' : '' }}"
            data-action="{{ $btn->action }}"
            @if ($btn->status) data-status="{{ $btn->status }}" @endif
            @if ($btn->disabled) disabled @endif
            @if (in_array($btn->action, ['edit', 'change-password', 'delete']))
                x-bind:disabled="selected.length === 0 || hasDeletedStatus"
            @else
                x-bind:disabled="{{ $btn->disabled ? 'selected.length === 0' : 'false' }}"
            @endif
            @click="$dispatch('btn-action', ['{{ $btn->action }}', selected])">
            <i class="{{ $btn->icon }}"></i> {{ $btn->titleIcon ?? '' }}
        </button>
    @endforeach
</div>
