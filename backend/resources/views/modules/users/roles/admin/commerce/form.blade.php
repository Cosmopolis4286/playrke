<form id="commerce-form" novalidate>
    <div class="row">
        <!-- Nombre -->
        <x-form.input label="Nombre Comercio" name="name" required col="col-sm-12 col-md-6" />

        <!-- Username -->
        <x-form.input label="Username" name="username" minlength="5" maxlength="20" required col="col-sm-6" />

        <!-- Email -->
        <x-form.input label="Email" name="email" type="email" required col="col-sm-12" />

        <!-- País -->
        <x-form.select label="País" name="country" required placeholder="Seleccionar país" data-source="countries"
            data-value="id" data-label="name" col="col-sm-6" />

        <!-- Zona Horaria -->
        <x-form.select container="true" label="Zona Horaria" id="time-zone" name="time_zone" required='true'
            placeholder="Seleccionar zona horaria" col="col-sm-6" class="d-none" />

        <!-- Teléfono -->
        <x-form.input label="Teléfono" name="phone" required placeholder="Ingresa el número" col="col-sm-6" maxlength="20" />

        <!-- Moneda principal -->
        <x-form.select container="true" label="Moneda Principal" id="currency" name="currency" required='true'
            placeholder="Seleccionar moneda principal" col="col-sm-6" />

        <!-- Otras Monedas -->
        <x-form.select container="true" label="Otras Monedas" id="other-currencies" name="other_currencies" multiple
            select2 placeholder="Seleccionar otras monedas" col="col-sm-6" class="d-none" />

        <!-- Juegos Permitidos -->
        <x-form.select label="Juegos Permitidos" id="allowed-games" name="allowed_games" multiple select2
            placeholder="Seleccionar juegos" data-source="games" data-value="id" data-label="name" col="col-sm-6" />
    </div>
</form>
