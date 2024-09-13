<style>
    .nav-bar-bg {
        background-image: linear-gradient(180deg, #060324 0, #030023 50%, #000022 100%);
    }

    .user-img {
        max-width: 25px;
    }

    .user-text-nav {
        color: #00eef7;
        font-size: 13px;
        font-weight: 400;
    }
</style>
<nav class="main-header navbar navbar-expand nav-bar-bg justify-content-between">
    <!-- Left side (Menú) -->
    <ul class="navbar-nav d-flex justify-content-center align-items-center">
        <li class="nav-item ml-n2 mr-1" title="Menú">
            <a class="nav-link link_nav" data-widget="pushmenu" href="">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item" title="Mi Perfil">
            <a href="{{ route('wallet.index') }}" title="Monedero">
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
            </a>
        </li>
    </ul>

    <ul class="navbar-nav">
        <li class="nav-item" title="Opciones">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenu2"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="mr-1 user-img"
                        @if (auth()->user()->foto == '') src="{{ asset('imgSystem/imgUsers/rke.png') }}"
                        @else src="{{ asset('imgSystem/imgUsers/' . auth()->user()->foto) }}" @endif>
                    <span class="user-text-nav">{{ Auth::user()->username }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2" style="font-size: 13px">
                    <button class="dropdown-item" type="button">
                        <i class="mr-1 fa-solid fa-lock"></i>Contraseña
                    </button>
                    <button type="button" class="dropdown-item" href="#"
                        onclick="document.getElementById('logout-form').submit();">
                        <i class="mr-1 nav-icon fas fa-sign-out-alt"></i>Cerrar sesión
                    </button>
                </div>
            </div>
        </li>
    </ul>
</nav>
