<!DOCTYPE html>
<html lang="en">
@include('layouts.sections.head')

<body>
    <div id="app"> {{-- Contenedor raíz para Vue --}}
        @routes
        @include('sweetalert::alert')

        <main class="container-fluid">
            <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">
            @yield('content') {{-- Aquí puedes usar componentes Vue --}}
        </main>
    </div>

    {{-- Carga el bundle Vue generado por Vite --}}
    @vite('resources/js/app.js')

    {{-- Scripts adicionales --}}
    @include('layouts.sections.scripts')
</body>

</html>
