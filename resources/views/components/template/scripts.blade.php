@props([
    'loadJquery' => true,
    'loadBootstrap' => true,
    'loadDataTables' => false,
    'loadPlugins' => true,
    'loadSelect2' => false,
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

@php
    $laravelErrors = $errors->all();
    $message = Session::pull('message');
@endphp

{{-- 🔔 Mensajes de sesión --}}
@if ($message)
    <script>
        window.Session = @json(
            ['message' => $message, 'type' => Session::pull('type', 'success')],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    </script>
@endif

@if (!empty($laravelErrors) || !empty($loadDataTables))
    <script>
        @if (!empty($laravelErrors))
            window.LaravelErrors = @json($laravelErrors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        @endif

        @if (!empty($loadDataTables))
            window.loadDataTables = true;
        @endif
    </script>
@endif

@yield('scripts')
