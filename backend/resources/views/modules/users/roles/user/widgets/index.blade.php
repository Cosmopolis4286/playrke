@extends ('layouts.layout-online')
@section('title', 'Tablero')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Panel de Usuario
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Balance Widget -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>${{ number_format($balance, 2) }}</h3>
                                    <p>Balance Total</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Exchange Rate Widget -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($exchangeRate, 2) }}</h3>
                                    <p>Tasa de Cambio</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Acciones Rápidas</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <a href="#" class="btn btn-primary btn-block">
                                                <i class="fas fa-gamepad mr-2"></i>
                                                Jugar Bingo
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="#" class="btn btn-success btn-block">
                                                <i class="fas fa-plus mr-2"></i>
                                                Recargar Saldo
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="#" class="btn btn-warning btn-block">
                                                <i class="fas fa-history mr-2"></i>
                                                Historial
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="#" class="btn btn-info btn-block">
                                                <i class="fas fa-user mr-2"></i>
                                                Mi Perfil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <!-- Welcome Message -->
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> ¡Bienvenido a PlayRKE!</h5>
                                Explora nuestros juegos y comienza a ganar. Tu balance actual es de ${{ number_format($balance, 2) }}.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection