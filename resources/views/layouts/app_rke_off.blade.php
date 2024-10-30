<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')

<body>
    @include('sweetalert::alert')
    <main class="container">
        <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">
        @yield('content')
    </main>
    @include('layouts.sections.scripts')
</body>

</html>
