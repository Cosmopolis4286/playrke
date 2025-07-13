@extends('layouts.app_rke_off')

@section('styles')
    @vite(['resources/css/rke/styles.css', 'resources/css/games/bingo/bingo-game-rooms.css', 'resources/js/games/bingo/bingo-view-loader.js'])
@endsection

@section('scripts')
    <script>
        window.currentBingoView = 'GameInfo';
        window.bingoProps = {
            data: @json($data)
        };
    </script>
@endsection

@section('content')
    <div id="app" v-cloak></div>

    <style>
        [v-cloak] {
            display: none;
        }
    </style>
@endsection
