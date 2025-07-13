<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>{{ $gameName }} - {{ $viewName }}</title>

    @vite([
        'resources/css/rke/styles.css',
        'resources/css/games/' . $gameName . '/' . $gameName . '-game-rooms.css',
        'resources/css/games/' . $gameName . '/' . $gameName . '-in-game.css',
        // Helpers se cargan siempre si se necesitan
        'resources/js/games/' . $gameName . '/' . $gameName . '.js',
    ])
</head>

<body>
    <div id="app" v-cloak></div>

    <script>
        window.gameView = "{{ $viewName }}"; // Ej: "GameInfo"
        window.gameProps = @json($data ?? []);
    </script>

    <script type="module">
        (async () => {
            try {
                // Importa dinámicamente el main (montaje Vue) según juego
                const mountModule = await import(
                    /* @vite-ignore */
                    `/resources/js/games/{{ $gameName }}/{{ $gameName }}-main.js`
                );

                // Importa el componente Vue de la vista
                const views = {
                    GameInfo: () => import(
                        /* @vite-ignore */
                        `/resources/js/games/{{ $gameName }}/views/GameInfo.vue`
                    ),
                    // Más vistas aquí...
                };

                const view = window.gameView || 'GameInfo';
                const props = window.gameProps || {};

                if (!views[view]) {
                    throw new Error(`Vista "${view}" no encontrada`);
                }

                const mod = await views[view]();
                mountModule.default(mod.default, props);

            } catch (e) {
                console.error(e);
            }
        })();
    </script>
</body>

</html>
