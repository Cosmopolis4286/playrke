@extends('layouts.layout-online')

@section('title', 'BingoPlay-Form')

@vite('resources/js/games/bingo/bingo-view-loader.js')
<script>
    window.currentBingoView = 'Form';
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
