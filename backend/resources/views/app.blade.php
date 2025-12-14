<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @vite('resources/js/renderApp.ts')
    @inertiaHead
</head>
<body class="antialiased">
    @isset($layout)
        <x-dynamic-component :component="$layout" :title="$title ?? null" :loadDataTables="$loadDataTables ?? true" :loadSelect2="$loadSelect2 ?? false">
            @inertia
        </x-dynamic-component>
    @else
        <x-layouts.layout-online :title="$title ?? null" :loadDataTables="$loadDataTables ?? true" :loadSelect2="$loadSelect2 ?? false">
            @inertia
        </x-layouts.layout-online>
    @endisset
</body>
</html>
