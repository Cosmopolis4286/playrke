<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Bingo - Game Info</title>

    @vite(['resources/css/rke/styles.css', 'resources/css/games/bingo/bingo-game-rooms.css', 'resources/js/games/bingo/bingo-view-loader.js'])

    <script>
        window.currentBingoView = 'GameInfo';
        window.bingoProps = {
            data: @json($data)
        };
    </script>

    <style>
        [v-cloak] {
            display: none;
        }
    </style>
</head>

<body>
    <div id="app" v-cloak></div>
</body>

</html>
