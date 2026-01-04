<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @vite('resources/js/renderApp.ts')
    @inertiaHead
</head>

<body class="antialiased">
    @php
        $layout = $page['props']['bladeLayout'] ?? 'layouts.layout-online';
    @endphp

    @include($layout)
</body>

</html>
