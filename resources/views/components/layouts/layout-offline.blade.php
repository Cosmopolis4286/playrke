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

    @if (empty($withoutScripts))
        <x-template.scripts />
    @else
        @yield('scripts')
    @endif
    @stack('stack-scripts')
    @if (empty($withoutAppJS))
        @vite('resources/js/app.ts')
    @endif
</body>

</html>
