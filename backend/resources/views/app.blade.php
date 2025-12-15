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
        $layout = $page['props']['layout'] ?? 'layouts.layout-online'; // Default layout
    @endphp

    @if ($layout === 'layouts.layout-auth')
        @inertia
    @else
        <x-dynamic-component :component="$layout" :title="$title ?? null" :loadDataTables="$loadDataTables ?? true" :loadSelect2="$loadSelect2 ?? false">
            @inertia
        </x-dynamic-component>
    @endif
</body>

</html>
