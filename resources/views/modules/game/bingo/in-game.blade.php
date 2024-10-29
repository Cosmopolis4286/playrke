@extends($layout)
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles-bingo.css') }}">
@endsection
@section('title', $title)

@section('content')
    <div class="row">
        <div class="col-12 col-lg-9 col-xl-10 pb-2 m-auto">
            <input type="hidden" id="random" value="{{ $random }}">
            @include('includes.game.bingo.layout.container-last-chips')
            <div class="container">
                @include('includes.game.bingo.layout.container-bingo-info')
                @include('includes.game.bingo.layout.container-history-chips')
                <div class="container mt-1">
                    @if (!empty($cartons))
                        @include('includes.game.bingo.layout.cartons')
                    @endif
                </div>
            </div>
            @include('includes.game.bingo.layout.sounds')
        </div>
    </div>
    @if ($room->status == 2)
        @include('includes.game.bingo.layout.modal-info-draw')
    @elseif($room->status > 2)
        @include('includes.game.bingo.layout.modal-info-winners')
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    @vite('resources/js/rke/time-functions.js')
    <script>
        const preRoute = '{{ asset('') }}';
        const logo = `{{ $custLogo ?? 'imgSystem/imgUsers/rke.png' }}`;
        const encrypted = '{{ $room->encrypted }}';
        const roomStatus = '{{ $room->status }}';
        const coinSym = '{{ $coinSym }}';
        const valSongs = '{{ $room->val_songs }}';
        const images = [];
        const chpLast = @json($vanDraw);
        const startTime = @json($startTime);
        const player = @json($player);
        const arrDraw = @json($sort);
        const encrypt = @json($encrypt);
        const restChips = @json($chipsRest);
        const soundNext = $('#next')[0];
        const soundWin = $('#winner')[0];
        soundNext.muted = true;
        soundWin.muted = true;
        let lastSixChips = @json($lastSixChips);
        let timeServ = @json($timeServ);
        let allChips = @json($allChips);
        let allChipsSet = new Set(allChips);
        let infoGame = @json($infoGame);
    </script>
    @if ($room->status == 2)
        <script>
            const userType = '{{ $userType }}';
            const userTypeVal = @json($userTypeVal);
            const coinID = @json($coinID);
            const ctnPrice = @json($ctnPrice);
            let info = false;
            let amount = ctnPrice;
        </script>
        @vite(['resources/js/rke/switch-payment-method.js', 'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'])
    @endif
    @if ($room->status > 2)
        <script>
            let loaderChip = true;
            let loadTimeServ = false;
            let vanChange = 0;
            let actSound = 0;
            let endWin = (arrDraw.length && (status === 2 || status === 3)) ? 0 : (status >= 4 ? 1 : 0);
            let sizeCarrsel;
        </script>
        @vite(['resources/js/rke/rke-crypt.js', 'resources/js/games/bingo/auto-marked.js', 'resources/js/games/bingo/in-game.js'])
    @endif
@endsection
