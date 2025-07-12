<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Game Info</title>
    @vite(['resources/js/app.js']) <!-- Vite carga tus componentes Vue -->
</head>

<body>
    <div id="app">
        <game-info :data='@json($data)'></game-info>
    </div>
</body>

</html>
