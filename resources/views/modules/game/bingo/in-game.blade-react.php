@extends($layout)

@section('title', $title)

@section('styles')
    @vite('resources/css/games/bingo/bingo-in-game.css')
@endsection

@section('content')
    <div id="bingo-in-game" data-props='@json([
        'room' => $room,
        'cartons' => $cartons,
    ])'>
    </div>
@endsection

@section('scripts')
    @viteReactRefresh
    @vite('resources/js/games/bingo/bingo-in-game.tsx')
@endsection
