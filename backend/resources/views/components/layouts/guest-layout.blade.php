<!DOCTYPE html>
<html lang="en" class="is-loading">
<x-template.head />

<body>
    @if (empty($withoutLoader))
        <x-shared.app-loader />
    @endif

    @include('includes.notifications')

    <main class="container-fluid">
        {{ $slot }}
    </main>

    @if (empty($withoutScripts))
        <x-template.scripts />
    @endif
    @stack('stack-scripts')
    @if (empty($withoutAppJS))
        @vite('resources/js/app.ts')
    @endif
</body>

</html>
