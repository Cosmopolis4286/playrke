<nav class="main-header navbar navbar-expand tb-center"
    style="background-image: linear-gradient(180deg, #060324 0, #030023 50%, #000022 100%);">
    <ul class="navbar-nav tb-center">
        <li class="nav-item ml-n2 mr-1" title="Menú">
            <a class="nav-link link_nav" data-widget="pushmenu" href=""><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item" title="Mi Perfil">
            <a href="{{ route('perfil.index') }}">
                <div class="cont_img_nm">
                    <img class="prke_img"
                        @if (auth()->user()->foto == '') src="{{ asset('imgSystem/imgUsers/rke.png') }}"
                            @else src="{{ asset('imgSystem/imgUsers/' . auth()->user()->foto) }}" @endif>
                    <div id="tx_username" class="nm-pf">
                        {{ Auth::user()->username }}
                    </div>
                </div>
            </a>
        </li>
    </ul>
    <ul class="navbar-nav cold tb-center ml-auto">
        <a href="{{ route('wallet.index') }}" class="d-flex" title="Monedero">
            <div style="font-size:14.5px; display: flex; align-items:center">
                @if (Route::is(
                        'wallet.index',
                        'wallet.create',
                        'trivia.rooms_usdt',
                        'trivia.show_usdt',
                        'bingo.rooms_usdt',
                        'wallet.transfer_usdt',
                        'wallet.createBlock',
                        'wallet.createBPay',
                        'wallet.retiroBlock',
                        'wallet.retiroBPay'))
                    <img src="{{ asset('imgSystem/tether.svg') }}" alt="PlayRKE" class="mr-1 mb-1 brand-image"
                        style="max-height:13.5px;">
                    <span style="font-size:15px;color:#228B22">
                        <span class="info-box-number" style="color:#BCB9C6;">
                            {{ number_format($wallet->usdt, 2) }}
                        </span>
                    </span>
                @else
                    <span class="info-box-number" style="color:#BCB9C6;">
                        <span style="color:#228B22;">{{ $wallet->Coin->symbol }}
                        </span>{{ number_format($wallet->saldo, 2, ',', '.') }}
                    </span>
                @endif
            </div>
        </a>
        <a class="ml-2 nav-link" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="nav-icon fas fa-sign-out-alt"></i>
        </a>
    </ul>
</nav>
