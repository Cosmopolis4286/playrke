<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')
@vite('resources/js/app.js') <!-- Si usas Vite para tus activos -->

<body>
    @include('sweetalert::alert')
    <div class="container" x-data="{ open: false }">@yield('content')</div>
    @include('layouts.sections.scripts')
</body>

</html>
