@extends('layouts.auth-layout')

@section('title', 'Bingo Formulario')

@vite('resources/js/games/bingo/bingo-view-loader.js')

@section('content')
<div>{{$data}}</div>
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
