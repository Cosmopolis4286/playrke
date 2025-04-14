<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')

<body>
    @routes
    @include('sweetalert::alert')
    <main class="container-fuild">
        <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">
        @yield('content')
    </main>
    @isset($footer)
        <footer>
            <aside> Copyrights © 2024 Todos los derechos reservado por PremierPluss </aside>
        </footer>
    @endisset
    @include('layouts.sections.scripts')
</body>

</html>
