<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')
@vite('resources/js/app.js') <!-- Si usas Vite para tus activos -->

<body>
    @include('sweetalert::alert')
    <main class="container-fluid" x-data="{ open: false }">@yield('content')</main>
    @include('layouts.sections.scripts')
</body>

</html>
