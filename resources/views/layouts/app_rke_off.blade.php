<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- @vite('resources/js/app.js') --}}

<body>
    @include('sweetalert::alert')
    <main class="container">@yield('content')</main>
    @include('layouts.sections.scripts')
</body>

</html>
