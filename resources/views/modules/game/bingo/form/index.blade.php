@extends('layouts.layout-online')

@section('title', 'Form')

@vite('resources/js/games/bingo/bingo-view-loader.js')

@section('content')
    <div id="app" v-cloak></div>
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
@endsection

@push('scripts')
    <script>
        window.currentBingoView = 'Form';
        window.bingoProps = {
            data: @json($data)
        };
    </script>
@endpush
