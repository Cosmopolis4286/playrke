<!DOCTYPE html>
<html lang="en">
<x-template.head />

<body>
    @empty($withoutLoader)
        <x-shared.app-loader />
    @endempty

    @include('sweetalert::alert')

    <main class="container-fluid">
        @yield('content')
    </main>

    @vite('resources/js/app.js')

    @empty($withoutScripts)
        <x-template.scripts />
    @endempty
</body>

</html>