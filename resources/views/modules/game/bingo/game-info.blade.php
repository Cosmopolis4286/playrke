@extends('layouts.layout-offline', ['withoutScripts' => false])

@section('title', 'BingoPlay')

@vite('resources/js/games/bingo/bingo-view-loader.js')

<script>
    window.currentBingoView = 'GameInfo';
    window.bingoProps = {
        data: @json($data)
    };
</script>

@section('content')
    <div id="app" v-cloak></div>
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
@endsection
