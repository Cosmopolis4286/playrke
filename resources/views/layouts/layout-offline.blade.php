<!DOCTYPE html>
<html lang="en">
<x-head />

<body>
    @routes

    @unless (!isset($withoutLoader))
        <x-app-loader />
    @endunless

    @include('sweetalert::alert')

    <main class="container-fluid">
        <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">
        @yield('content')
    </main>

    @vite('resources/js/app.js')

    @if (!isset($withoutScripts))
        <x-scripts />
    @endif
</body>

</html>
