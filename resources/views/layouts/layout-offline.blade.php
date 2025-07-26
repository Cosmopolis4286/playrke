<!DOCTYPE html>
<html lang="en">
<x-template.head />

<body>
    @if (empty($withoutLoader))
        <x-shared.app-loader />
    @endif

    @include('sweetalert::alert')

    <main class="container-fluid">
        @yield('content')
    </main>

    @if (empty($withoutAppJS))
        @vite('resources/js/app.js')
    @endif

    @if (empty($withoutScripts))
        <x-template.scripts />
    @else
        @yield('scripts')
    @endif
</body>

</html>
