@props([
    'loadJquery' => true,
    'loadBootstrap' => true,
    'loadDataTables' => true,
    'loadPlugins' => true,
    'customJsFiles' => [],
])

@if ($loadJquery)
    <!-- jQuery y Plugins necesarios -->
    <script src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.unobtrusive.min.js') }}"></script>
    <script src="{{ asset('js/just-validate.production.min.js') }}"></script>
    <script src="{{ asset('js/jquery.inputmask.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>
@endif

@if ($loadBootstrap)
    <!-- Bootstrap -->
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
@endif

@if ($loadDataTables)
    <!-- DataTables -->
    <script src="{{ asset('js/DataTables/dataTables.js') }}"></script>
    <script src="{{ asset('js/DataTables/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ asset('js/DataTables/dataTables.responsive.js') }}"></script>
    <script src="{{ asset('js/DataTables/responsive.bootstrap4.js') }}"></script>
@endif

@if ($loadPlugins)
    <!-- Otros Plugins -->
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/adminlte.js') }}"></script>
@endif

<!-- Código personalizado jQuery -->
<script>
    $(function() {
        @if (Session::has('message'))
            const message = {!! json_encode(Session::get('message')) !!};
            const icon = {!! json_encode(Session::get('type')) !!} || 'success';
            const html = `<div style="font-size: 15px;">${message}</div>`;
            showToast(html, icon);
        @endif

        @if ($loadDataTables)
            // Inicializar DataTables solo si está cargado
            $('.table').DataTable({
                ordering: true,
                order: [],
                language: {
                    url: "{{ asset('css/DataTables/es-ES.json') }}"
                },
                columnDefs: [{
                    targets: '_all',
                    className: 'text-left'
                }]
            });
        @endif
    });

    // Eventos delegados
    $(document).on('click', '#change-password', function() {
        $('#modal-change-password').modal('show');
    });

    $(document).on('click', "#btn-confirm-new-pwd[data-type='change_pwd']", function() {
        submitForm('change-password', ['current_password', 'new_password']);
    });

    $(document).on('dragstart', 'img', function(evt) {
        evt.preventDefault();
    });

    $(document).on('input', '.input-phone', function() {
        let phoneNumber = $(this).val().replace(/\D/g, '').substring(0, 11);
        $(this).val(phoneNumber);
    });

    // Selecciona todos los elementos con clase 'logout-btn'
    document.querySelectorAll('.logout-btn').forEach(button => {
        button.addEventListener('click', function() {
            fetch("{{ route('logout') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        window.location.href = '{{ route('login') }}';
                    } else {
                        alert('Error al cerrar sesión');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                });
        });
    });
</script>

<!-- Archivos JS personalizados compilados con Vite -->
@foreach ($customJsFiles as $jsFile)
    @vite($jsFile)
@endforeach

@yield('scripts')
