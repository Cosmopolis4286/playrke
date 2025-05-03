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
<script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<script src="{{ asset('template/dist/js/adminlte.js') }}"></script>

<script>
    $(document).ready(function() {
        if ("{{ Session::has('message') }}") {
            const message = {!! json_encode(Session::get('message')) !!};
            const icon = {!! json_encode(Session::get('type')) !!} || 'success';
            const html = `<div style="font-size: 15px;">${message}</div>`;
            showToast(html, icon);
        }
    });

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
        let phoneNumber = $(this).val().replace(/\D/g, '');
        phoneNumber = phoneNumber.substring(0, 11);
        $(this).val(phoneNumber);
    });
</script>

@vite(['resources/js/rke/rke-functions.js', 'resources/js/rke/toast-notify.js'])

@yield('scripts')
