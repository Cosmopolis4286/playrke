<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')

<body>
    @include('sweetalert::alert')
    <main class="container">@yield('content')</main>
    @include('layouts.sections.scripts')
</body>

</html>
