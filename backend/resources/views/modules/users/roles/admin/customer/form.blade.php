<form id="customer-form">
    <div class="row">
        {{-- Nombre --}}
        <x-form.input label="Nombre" name="name" required col="col-sm-12 col-md-6" />

        {{-- Email --}}
        <x-form.input label="Email" name="email" type="email" required col="col-sm-6" />

        {{-- Username --}}
        <x-form.input label="Username" name="username" minlength="5" maxlength="20" required col="col-sm-6" />

        {{-- ID Cliente --}}
        <x-form.input label="Cliente ID" id="customer-id" name="customer_id" required col="col-sm-12 col-md-6" />

        {{-- País --}}
        <x-form.select label="País" name="country" required placeholder="Seleccionar país" data-source="countries"
            data-value="id" data-label="name" col="col-sm-6" />

        {{-- Zona Horaria --}}
        <x-form.select label="Zona Horaria" id="time-zone" name="time_zone" required
            placeholder="Seleccionar zona horaria" data-source="timeZones" data-value="id" data-label="name"
            col="col-sm-6" />

        {{-- Teléfono --}}
        <x-form.input label="Teléfono" name="phone" required placeholder="Ingresa el número" col="col-sm-6" />

        {{-- Juegos Permitidos --}}
        <x-form.select label="Juegos Permitidos" id="allowed-games" name="allowed_games" multiple select2
            placeholder="Seleccionar juegos" data-source="games" data-value="id" data-label="name" col="col-sm-6" />

        <div class="form-group col-sm-12 col-md-6">
            <label for="api-route">Ruta API*</label>
            <input type="text" id="api-route" name="api_route" class="form-control" required
                value="{{ old('api_route') }}">
        </div>
        <div class="form-group col-12">
            <label for="customer-key">Llave de Conexión*</label>
            <div class="input-group">
                <input type="text" id="customer-key" name="customer_key" class="form-control" required
                    value="{{ old('customer_key') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="btn-change-customer-key">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group col-12">
            <label for="logo">Logo</label>
            <input type="file" id="logo" class="form-control-file" name="logo"
                accept="image/png, image/jpeg">
        </div>
    </div>
</form>
