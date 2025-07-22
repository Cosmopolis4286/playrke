<!DOCTYPE html>
<html lang="en">
<x-head />

<body>
    @routes

    @unless (!isset($withoutLoader))
        <x-shared.app-loader />
    @endunless

    @include('sweetalert::alert')

    <main class="container-fluid">
        <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">
        @yield('content')
    </main>

    @vite('resources/js/app.js')

    @if (!isset($withoutScripts))
        <x-template.scripts />
    @endif
</body>

</html>
