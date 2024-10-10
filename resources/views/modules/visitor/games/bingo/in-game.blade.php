@extends('layouts.app_rke_off')
@section('title', $title)
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles-bingo.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-lg-9 col-xl-10 pb-2 m-auto">
            @include('components.game.bingo.layout.container-last-chips')
            <div class="container">
                @include('components.game.bingo.layout.container-bingo-info')
                @include('components.game.bingo.layout.container-history-chips')
                <div class="container mt-1">
                    @if (!empty($cartons))
                        @include('components.game.bingo.layout.cartons')
                    @endif
                </div>
            </div>
            @include('components.game.bingo.layout.sounds')
        </div>
    </div>
    @if ($room->status == 2)
        @include('components.game.bingo.layout.modal-info-draw', ['userType' => $userType ?? null])
    @elseif($room->status > 2)
        @include('components.game.bingo.layout.modal-info-winners')
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    @vite('resources/js/rke/time-functions.js')
    <script>
        const preRoute = '{{ asset('') }}';
        const encrypted = '<?php echo $room->encrypted; ?>';
        const status = '<?php echo $room->status; ?>';
        const coinSym = '<?php echo $coinSym; ?>';
        const valSongs = '<?php echo $room->val_songs; ?>';
        const arrDraw = JSON.parse('<?php echo json_encode($sort); ?>');
        const chpLast = Number('<?php echo $vanDraw; ?>');
        const player = '<?php echo json_encode($player); ?>';
        const startTime = Number('<?php echo $startTime; ?>');
        const encrypt = JSON.parse('<?php echo json_encode($encrypt); ?>');
        const soundNext = $('#next')[0];
        const soundWin = $('#winner')[0];
        const images = [];
        const restChips = JSON.parse('<?php echo json_encode($chipsRest); ?>');
        let lastSixChips = JSON.parse('<?php echo json_encode($lastSixChips); ?>');
        let timeServ = Number('<?php echo $timeServ; ?>');
        let allChips = JSON.parse('<?php echo json_encode($allChips); ?>');
        let allChipsSet = new Set(allChips);
        let infoGame = JSON.parse('<?php echo json_encode($infoGame); ?>');

        soundNext.muted = true;
        soundWin.muted = true;
    </script>

    @if ($room->status == 2)
        <script>
            const userType = '<?php echo $userType; ?>';
            const userTypeVal = '<?php echo json_encode($userTypeVal); ?>';
            const coinID = '<?php echo json_encode($coinID); ?>';
            const ctnPrice = Number('<?php echo $ctnPrice; ?>');
            let info = false;
            let amount = ctnPrice;
        </script>
        @vite(['resources/js/rke/components/switch-payment-method.js', 'resources/js/games/bingo/components/bingo-off-buy-ctns.js'])
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