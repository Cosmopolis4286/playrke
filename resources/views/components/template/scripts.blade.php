@props([
    'loadJquery' => true,
    'loadBootstrap' => true,
    'loadDataTables' => false,
    'loadPlugins' => true,
    'customJsFiles' => [],
])

{{-- 📦 jQuery y Validaciones --}}
@if ($loadJquery)
    <script src="{{ asset('js/jquery-3.5.1.min.js') }}" defer></script>
    <script src="{{ asset('js/jquery.validate.min.js') }}" defer></script>
    <script src="{{ asset('js/jquery.validate.unobtrusive.min.js') }}" defer></script>
    <script src="{{ asset('js/just-validate.production.min.js') }}" defer></script>
    <script src="{{ asset('js/jquery.inputmask.min.js') }}" defer></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}" defer></script>
@endif

{{-- 🚀 Bootstrap --}}
@if ($loadBootstrap)
    {{-- Carga popper antes que bootstrap --}}
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}" defer></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}" defer></script>
@endif

{{-- 📊 DataTables --}}
@if ($loadDataTables)
    <script src="{{ asset('js/DataTables/dataTables.js') }}" defer></script>
    <script src="{{ asset('js/DataTables/dataTables.bootstrap4.js') }}" defer></script>
    <script src="{{ asset('js/DataTables/dataTables.responsive.js') }}" defer></script>
    <script src="{{ asset('js/DataTables/responsive.bootstrap4.js') }}" defer></script>
@endif

{{-- 🔌 Plugins --}}
@if ($loadPlugins)
    <script src="{{ asset('js/sweetalert2.all.min.js') }}" defer></script>
    <script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}" defer></script>
    <script src="{{ asset('template/dist/js/adminlte.js') }}" defer></script>
@endif

@if (Session::has('message'))
    <script type="module" defer>
        import {
            showSessionToast
        } from '/resources/js/helpers/global.js';

        const message = {!! json_encode(Session::get('message')) !!};
        const icon = {!! json_encode(Session::get('type') ?? 'success') !!};

        showSessionToast(message, icon);
    </script>
@endif

@if ($loadDataTables)
    <script type="module" defer>
        import {
            loadDataTable
        } from '/resources/js/helpers/global.js';

        // Pasa la url del idioma para DataTables
        loadDataTable('/css/DataTables/es-ES.json');
    </script>
@endif

{{-- 🎯 Archivos JS personalizados (Vite) --}}
@foreach ($customJsFiles as $jsFile)
    @vite($jsFile)
@endforeach

@yield('scripts')
