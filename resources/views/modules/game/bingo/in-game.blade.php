@extends($layout)
@section('styles')
    <style>
        body {
            background-image: url("{{ asset('img/body/space.png') }}") !important;
        }
    </style>
    @vite('resources/css/games/bingo/bingo-in-game.css')
@endsection
@section('title', $title)

@section('content')
    <div class="col-md-11 col-lg-7 col-xl-8 m-auto">
        @include('includes.games.bingo.layout.container-last-chips')
        @include('includes.games.bingo.layout.container-bingo-info')
        @if ($room->status > 2)
            @include('includes.games.bingo.layout.container-history-chips')
        @endif
        <div class="container mt-1">
            @if (!empty($cartons))
                @include('includes.games.bingo.layout.cartons')
            @endif
            <input type="hidden" id="random" value="{{ $random }}">
        </div>
        @if ($room->status == 2)
            @include('includes.games.bingo.layout.modal-info-draw')
        @elseif($room->status > 2)
            @include('includes.games.bingo.layout.modal-info-winners')
            @include('includes.games.bingo.layout.sounds')
            @if ($room->Config->types_plays)
                @include('includes.games.bingo.layout.modal-bingo-plays')
            @endif
        @endif
        @include('includes.games.bingo.layout.modal-bingo-rules')
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    @vite('resources/js/rke/time-functions.js')

    <script>
        const preRoute = '{{ asset('') }}';
        const logo = `{{ $customerLogo ?? 'img/rke.png' }}`;
        const encrypted = '{{ $room->encrypted }}';
        const roomStatus = '{{ $room->status }}';
        const coinSym = '{{ $coinSym }}';
        const valSongs = '{{ $room->val_songs }}';
        const images = [];
        const chpLast = @json($vanDraw);
        const player = @json($player);
        const encrypt = @json($encrypt);
        const restChips = @json($chipsRest);

        let arrDraw = @json($sort);
        let latestChips = @json($latestChips);
        let timeServ = @json($timeServ);
        let allChips = @json($allChips);
        let infoGame = @json($infoGame);
    </script>
    @vite('resources/js/games/bingo/includes/adjust-height-boxes.js')
    @if ($room->status == 2 && $creatorType !== 'customer')
        <script>
            const creatorType = '{{ $creatorType }}';
            const creatorID = @json($creatorID);
            const coinID = @json($coinID);
            const ctnPrice = @json($ctnPrice);
            let gameStartTime = @json($startTime);
            let amount = ctnPrice;
        </script>
        @vite(['resources/js/rke/switch-payment-method.js', 'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'])
    @endif
    @if ($room->status > 2)
        <script>
            const startTime = @json($startTime);
            const soundNext = $('#next')[0];
            const soundWin = $('#winner')[0];
            const closingRoute = @json($closingRoute);

            let loaderChip = true;
            let loadTimeServ = false;
            let actSound = 0;
            let endWin = (arrDraw.length && (roomStatus === 2 || roomStatus === 3)) ? 0 : (roomStatus >= 4 ? 1 : 0);
            let sizeCarrsel;

            soundNext.muted = true;
            soundWin.muted = true;
        </script>
        @vite(['resources/js/rke/rke-crypt.js', 'resources/js/games/bingo/includes/auto-marked.js', 'resources/js/games/bingo/in-game.js'])
        @if ($room->Config->types_plays)
            @vite('resources/js/games/bingo/includes/handle-bingo-plays.js')
        @endif
    @endif
@endsection
