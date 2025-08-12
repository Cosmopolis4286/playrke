<div class="row">
    <div class="form-group col-sm-12 col-md-6">
        <label for="name">Nombre*</label>
        <input type="text" id="name" name="name" class="form-control" required value="{{ old('viewId') }}">
    </div>
    <div class="form-group col-sm-6">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
            required value="{{ old('email') }}">
        @error('email')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-6">
        <label for="username">Username*</label>
        <input type="text" id="username" name="username" minlength="5" maxlength="20"
            class="form-control @error('username') is-invalid @enderror" required value="{{ old('username') }}">
        @error('username')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-6">
        <label for="customer-id">Cliente ID*</label>
        <input type="text" id="customer-id" name="customer_id" class="form-control" required value="">
    </div>
    <div class="form-group col-sm-6">
        <label for="country">País*</label>
        <select id="country" name="country" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6">
        <label for="time-zone">Zona Horaria*</label>
        <select id="time-zone" name="time_zone" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6">
        <label for="phone">Teléfono</label>
        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
            required value="{{ old('username') }}" placeholder="Ingresa el número" value="{{ old('phone') }}">
        @error('phone')
            <span class="invalid-feedback">
                <small>{{ $message }}</small>
            </span>
        @enderror
    </div>
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
        <input type="file" id="logo" class="form-control-file" name="logo" accept="image/png, image/jpeg">
    </div>
</div>
