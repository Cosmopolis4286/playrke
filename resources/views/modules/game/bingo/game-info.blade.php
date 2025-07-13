@extends('layouts.layout-online_off')

@section('styles')
    @vite(['resources/css/games/bingo/bingo-game-rooms.css'])
@endsection

@section('title', 'BingoPlay')

@section('scripts')
    @vite('resources/js/games/bingo/bingo-view-loader.js')
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
