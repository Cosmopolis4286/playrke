@php
    $passwordFields = [
        'current_password' => 'Contraseña Actual',
        'new_password' => 'Nueva Contraseña',
        'new_password_confirmation' => 'Confirmar Nueva Contraseña',
    ];
@endphp

<form id="change-password-form" action="{{ route('profile.changePassword') }}" method="POST">
    @csrf
    @method('PATCH')

    <div class="row">
        @foreach ($passwordFields as $name => $label)
            <x-form.input :label="$label" :name="$name" type="password" inputGroup required col="col-12">
                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="{{ $name }}"
                    tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
            </x-form.input>
        @endforeach
    </div>
</form>
