@extends('layouts.guest-layout', ['withoutAppJS' => true, 'withoutScripts' => true])

@section('title', 'BingoPlay')

@section('content')
    <div id="app" v-cloak></div>
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
@endsection

@section('scripts')
    @vite('resources/js/games/bingo/bingo-view-loader.js')

   <script>
    window.currentBingoView = 'GameInfo';
    window.bingoProps = {
        data: @json($data)
    };
</script>
@endsection
