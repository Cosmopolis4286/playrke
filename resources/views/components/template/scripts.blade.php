@props([
    'loadJquery' => true,
    'loadBootstrap' => true,
    'loadDataTables' => false,
    'loadPlugins' => true,
    'loadSelect2' => false,
    'customJsFiles' => [],
])

{{-- 📦 jQuery y Validaciones --}}
@if ($loadJquery)
    {{-- jQuery sin defer para que esté disponible inmediatamente --}}
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

    {{-- Plugins de validación y máscaras, sin defer para carga inmediata --}}
    <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.unobtrusive.min.js') }}"></script>
    <script src="{{ asset('js/just-validate.production.min.js') }}"></script>
    <script src="{{ asset('js/jquery.inputmask.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>
@endif

{{-- 🎨 Select2 solo si se requiere --}}
@if ($loadSelect2)
    <script src="{{ asset('js/select2.min.js') }}"></script>
@endif

{{-- 🚀 Bootstrap --}}
@if ($loadBootstrap)
    {{-- Popper y Bootstrap sin defer para inicializar componentes UI antes de tu código --}}
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
@endif

{{-- 📊 DataTables --}}
@if ($loadDataTables)
    {{-- Scripts DataTables sin defer para inicialización inmediata --}}
    <script src="{{ asset('js/DataTables/dataTables.js') }}"></script>
    <script src="{{ asset('js/DataTables/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ asset('js/DataTables/dataTables.responsive.js') }}"></script>
    <script src="{{ asset('js/DataTables/responsive.bootstrap4.js') }}"></script>
@endif

{{-- 🔌 Plugins adicionales --}}
@if ($loadPlugins)
    {{-- Plugins como SweetAlert, overlayScrollbars y AdminLTE --}}
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/adminlte.js') }}"></script>
@endif

{{-- 🔔 Mensajes de sesión (comentados, activar si se requiere) --}}
@if (Session::has('message'))
    {{-- <script type="module" defer>
        import { showSessionToast } from '/resources/js/helpers/global.js';

        const message = {!! json_encode(Session::get('message')) !!};
        const icon = {!! json_encode(Session::get('type') ?? 'success') !!};

        showSessionToast(message, icon);
    </script> --}}
@endif

{{-- ⚙️ Inicialización de DataTables (comentado, activar si se requiere) --}}
@if ($loadDataTables)
    {{-- <script type="module" defer>
        import { loadDataTable } from '/resources/js/helpers/global.js';
        loadDataTable('/css/DataTables/es-ES.json');
    </script> --}}
@endif

{{-- 🎯 Archivos JS personalizados (Vite) --}}
@foreach ($customJsFiles as $jsFile)
    @vite($jsFile)
@endforeach

@yield('scripts')
