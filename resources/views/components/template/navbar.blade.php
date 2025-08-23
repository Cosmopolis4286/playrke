@php
    use App\Models\Wallet;
    $wallet = Wallet::with('Coin')->where('user', Auth::id())->first();
    $user = auth()->user();
@endphp

<nav class="main-header navbar navbar-expand navbar-bg justify-content-between">
    <ul class="navbar-nav d-flex justify-content-center align-items-center">
        <li class="nav-item ml-n2 mr-1" title="Menú">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item" title="Monedero">
            <a href="#" title="Monedero" class="d-flex align-items-center">
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
    <ul class="navbar-nav">
        <li class="nav-item dropdown" title="Opciones">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
                id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                aria-label="Opciones de usuario">
                <img class="mr-1 user-img rounded-circle"
                    src="{{ $user->foto ? asset('img/users/' . $user->foto) : asset('img/fav.ico') }}"
                    alt="Foto de usuario {{ $user->username }}">
                <span class="user-text-navbar">{{ $user->username }}</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown" style="font-size: 13px">
                <button id="change-password" class="dropdown-item" type="button">
                    <i class="mr-1 fa-solid fa-lock"></i>
                    Contraseña
                </button>
                <button type="button" class="dropdown-item logout-btn">
                    <i class="mr-1 nav-icon fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>
        </li>
    </ul>
</nav>
