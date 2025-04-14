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
    @include('layouts.sections.scripts')
</body>

</html>
