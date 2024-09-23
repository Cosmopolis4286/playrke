<!-- Scripts -->
<script src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('js/jquery.validate.unobtrusive.min.js') }}"></script>
<script src="{{ asset('js/just-validate.production.min.js') }}"></script>
<script src="{{ asset('js/jquery.inputmask.min.js') }}"></script>
<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
<script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/DataTables/dataTables.js') }}"></script>
<script src="{{ asset('js/DataTables/dataTables.bootstrap4.js') }}"></script>
<script src="{{ asset('js/DataTables/dataTables.responsive.js') }}"></script>
<script src="{{ asset('js/DataTables/responsive.bootstrap4.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/toast-notify.js') }}"></script>
<script src="{{ asset('js/rke-functions.js') }}"></script>
<script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<script src="{{ asset('template/dist/js/adminlte.js') }}"></script>
<script>
    if ("{{ Session::has('message') }}") {
        const message = {!! json_encode(Session::get('message')) !!};
        const type = {!! json_encode(Session::get('type')) !!} || 'success';
        const html = `<div style="font-size: 15px;">${message}</div>`;
        showToast(html, type);
    }

    $(document).on('click', '#change-password', function() {
        $('#modal-change-password').modal('show');
    });

    $(document).on("click", "#btn-confirm-new-pwd[data-type='change_pwd']", function() {
        submitForm('change-password', ['current_password', 'new_password']);
    });

    $(document).on('dragstart', 'img', function(evt) {
        evt.preventDefault();
    });

    $(document).ready(function() {
        $('.table').DataTable({
            ordering: true, // Habilitar la ordenación
            order: [], // Mantener el orden inicial vacío para desactivar la ordenación inicial
            language: {
                url: "{{ asset('css/DataTables/es-ES.json') }}"
            },
            columnDefs: [{
                targets: '_all', // Aplicar a todas las columnas
                className: 'text-left' // Alinear texto a la izquierda
            }]
        });
    });

    $(document).on("input", ".input-phone", function() {
        // Eliminar caracteres no numéricos
        const phoneNumber = $(this).val().replace(/\D/g, '');
        // Limitar a un máximo de 10 dígitos
        phoneNumber = phoneNumber.substring(0, 11);
        // Actualizar el valor del campo de teléfono
        $(this).val(phoneNumber);
    });
</script>
@yield('scripts')
