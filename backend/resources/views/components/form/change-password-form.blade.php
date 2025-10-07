<form id="change-password-form">
    <div class="row">
        {{-- Nombre --}}
        <x-form.input label="Nombre" name="name" required col="col-sm-12 col-md-6" />
        {{-- Email --}}
        <x-form.input label="Email" name="email" type="email" required col="col-sm-6" />
    </div>
</form>
