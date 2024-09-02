<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- @vite('resources/js/app.js') --}}

<body>
    @include('sweetalert::alert')
    <div class="container">@yield('content')</div>
    @include('layouts.sections.scripts')
</body>

</html>
