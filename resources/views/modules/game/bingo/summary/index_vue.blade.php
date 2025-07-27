@extends($layout)
@section('title', $title)

@section('scripts')
    @vite('resources/js/games/bingo/bingo-view-loader.js')
    <script>
        window.currentBingoView = 'Summary';
        window.bingoProps = {
            layout: @json($layout),
            rooms: @json($rooms)
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
