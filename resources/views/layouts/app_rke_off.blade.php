<!DOCTYPE html>
<html lang="en">
@include('layouts/sections/head')

<body>
    @include('sweetalert::alert')
    <div class="container">@yield('content')</div>
    @include('layouts.sections.scripts')
</body>

</html>
