<form id="form-seller">
    <div class="row">
        <!-- Nombre -->
        <x-form.input label="Nombre" name="name" required col="col-sm-12 col-md-6" value="{{ old('name') }}" />

        <!-- Email -->
        <x-form.input label="Email" name="email" type="email" required col="col-sm-6" />

        <!-- Username -->
        <x-form.input label="Username" name="username" minlength="5" maxlength="20" required col="col-sm-5" />

        <!-- Comisión -->
        <x-form.input label="%Comisión" name="commission" type="number" placeholder="%Com" required step="0.25"
            min="3.00" max="35" value="{{ old('commission', '3.00') }}" col="col-sm-6 col-md-3" />

        <!-- País -->
        <x-form.select label="País" name="country" required placeholder="Seleccionar país" data-source="countries"
            data-value="id" data-label="name" col="col-sm-4" />

        <!-- Zona Horaria -->
        <x-form.select label="Zona Horaria" id="time-zone" name="time_zone" required
            placeholder="Seleccionar zona horaria" data-source="timeZones" data-value="id" data-label="name"
            col="col-sm-6 d-none" id="container-time-zone" />

        <!-- Teléfono -->
        <x-form.input label="Teléfono" name="phone" required placeholder="Ingresa el número" col="col-sm-6" />

        <!-- Moneda principal -->
        <div id="container-currency" class="form-group col-sm-6 d-none">
            <label for="currency">Moneda*</label>
            <div id="currency-text" class="form-control"></div>
            <input type="hidden" id="currency" name="currency" value="{{ old('currency') }}">
        </div>

        <!-- Otras Monedas -->
        <x-form.select container="true" label="Otras Monedas" id="other-currencies" name="other_currencies" multiple
            select2 placeholder="Seleccionar otras monedas" data-source="currencies" data-value="id" data-label="description"
            col="col-sm d-none" />

        <!-- Juegos Permitidos -->
        <x-form.select label="Juegos Permitidos" id="allowed-games" name="allowed_games" multiple select2
            placeholder="Seleccionar juegos" data-source="games" data-value="id" data-label="name" col="col-sm-6" />
    </div>
</form>
