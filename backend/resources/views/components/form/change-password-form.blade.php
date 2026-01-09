<form id="change-password-form" action="{{ route('profile.changePassword', ['id' => auth()->id()]) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="row">
        {{-- Contraseña Actual --}}
        <x-form.input label="Contraseña Actual" name="current_password" type="password" required col="col-12" />
        {{-- Nueva Contraseña --}}
        <x-form.input label="Nueva Contraseña" name="new_password" type="password" required col="col-12" />
        {{-- Confirmar Nueva Contraseña --}}
        <x-form.input label="Confirmar Nueva Contraseña" name="new_password_confirmation" type="password" required
            col="col-12" />
    </div>
</form>
