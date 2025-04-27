<div class="row">
    <div class="form-group col-sm-6">
        <label for="name">Nombre*</label>
        <input type="text" id="name" name="name" class="form-control"required>
    </div>
    <div class="form-group col-sm-6">
        <label for="numbers">Numeros*</label>
        <select id="numbers" name="numbers" class="form-control" required></select>
    </div>
    {{-- <div class="form-group col-sm-6">
        <label for="code">Codigo*</label>
        <input type="text" id="code" name="code" class="form-control" required>
    </div> --}}
    <div class="form-group col-12">
        <label for="schedules">Horas*</label>
        <select id="schedules" name="schedules[]" placeholder="Seleccionar Horario" data-allow-clear="1"
            class="select2 form-control" multiple required></select>
    </div>
</div>
