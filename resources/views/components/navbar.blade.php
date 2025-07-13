@php
    use App\Models\Wallet;
    $wallet = Wallet::with('Coin')->where('user', Auth::id())->first();
    $user = auth()->user();
@endphp

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
            <a class="nav-link link_nav" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item" title="Monedero">
            <a href="{{ route('wallet.index') }}" title="Monedero" class="d-flex align-items-center">
                @if (isset($USDT))
                    <img src="{{ asset('imgSystem/tether.svg') }}" alt="PlayRKE" class="mb-1 brand-image"
                        style="max-height:13.5px;">
                    <span style="font-size:15px; color:#228B22; margin-left: 6px;">
                        <span class="info-box-number" style="color:#BCB9C6;">
                            {{ number_format($wallet->usdt, 2) }}
                        </span>
                        <small>USDT</small>
                    </span>
                @else
                    <span class="info-box-number" style="color:#BCB9C6;">
                        <span style="color:#228B22;">{{ $wallet->Coin->symbol }}</span>
                        {{ number_format($wallet->balance, 2, ',', '.') }}
                    </span>
                @endif
            </a>
        </li>
    </ul>

    <!-- Right side (Usuario y opciones) -->
    <ul class="navbar-nav">
        <li class="nav-item dropdown" title="Opciones">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
                id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                aria-label="Opciones de usuario">
                <img class="mr-1 user-img rounded-circle"
                    src="{{ $user->foto ? asset('imgSystem/imgUsers/' . $user->foto) : asset('imgSystem/imgUsers/rke.png') }}"
                    alt="Foto de usuario {{ $user->username }}">
                <span class="user-text-nav">{{ $user->username }}</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown" style="font-size: 13px">
                <button id="change-password" class="dropdown-item" type="button" data-type="change_pwd">
                    <i class="mr-1 fa-solid fa-lock"></i> Contraseña
                </button>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <button type="button" class="dropdown-item"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="mr-1 nav-icon fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>
        </li>
    </ul>
</nav>
