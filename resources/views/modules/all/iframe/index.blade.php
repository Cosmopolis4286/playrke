@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($cusToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-9 col-lg-6 col-lx-5">
                        <div class="input-group">
                            <input type="text" id="user-token" class="form-control"
                                placeholder="Ingresa el token del usuario" aria-label="Ingresa el token del usuario">
                            <div class="input-group-append">
                                <button data-val="getId" class="btn-user-token btn btn-outline-secondary" type="button"
                                    title="Obtener Info. User">
                                    <i class="fa-solid fa-circle-user"></i>
                                </button>
                                <button data-val="getBalance" class="btn-user-token btn btn-outline-secondary"
                                    type="button" title="Obtener Saldo">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                </button>
                                <button data-val="increaseBalance" class="btn-user-token btn btn-outline-secondary"
                                    type="button" title="Incrementar Saldo">
                                    <i class="fa-solid fa-arrow-up-right-dots"></i>
                                </button>
                                <button data-val="goToView" class="btn-user-token btn btn-outline-primary" type="button"
                                    title="Ir a Salas de Juego">
                                    <i class="fa-solid fa-gamepad"></i>
                                </button>
                            </div>
                        </div>
                        <div id="container-message" class="mt-2" role="alert"></div>
                    </div>
                </div>
                {{-- <div class="row mt-3">
                    @foreach ($iframes as $item)
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="info-box">
                                <a class="info-box-icon bg-success" href="" title="">
                                    <i class=" fas fa-wallet"></i>
                                </a>
                                <div class="info-box-content">
                                    <span class="info-box-number">
                                        <a href=""></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div> --}}
                <iframe id="all-rooms" src="{{ $iframes[0]['route'] }}" width="100%" height="500px"
                    frameborder="0"></iframe>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const userToken = $('#user-token');
        const containerMessage = $('#container-message');

        function getUserId() {
            $.getJSON({
                url: `{{ route('customer.getUserId', ['userToken' => ':userToken']) }}`
                    .replace(':userToken', userToken.val()),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    // Limpia clases previas
                    containerMessage.removeClass('alert-danger alert-success');

                    if (res.Error) {
                        // Muestra el error si existe
                        containerMessage
                            .addClass('alert alert-danger').text(res.Error);
                    } else if (res.Data) {
                        // Estructura y muestra la respuesta recibida desde Data
                        const data = res.Data; // Extraer el objeto Data
                        const responseHtml = `
                    <h5>Datos del usuario:</h5>
                    <ul>
                        <li><strong>ID:</strong> ${data.ID}</li>
                        <li><strong>Brand:</strong> ${data.Brand ?? 'No especificado'}</li>
                        <li><strong>Nombre de Usuario:</strong> ${data.UserName}</li>
                        <li><strong>País:</strong> ${data.Country}</li>
                        <li><strong>Moneda Predeterminada:</strong> ${data.DefaultCurrencyCode}</li>
                    </ul>`;
                        containerMessage.addClass('alert alert-success').html(responseHtml);
                    } else {
                        containerMessage
                            .addClass('alert alert-danger').text('No se encontraron datos en la respuesta.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al obtener el ID del usuario:', jqXHR.status, textStatus, errorThrown);
                    containerMessage.removeClass('alert-success').addClass('alert alert-danger')
                        .text('Error al realizar la solicitud. Por favor, inténtelo de nuevo más tarde.');
                }
            });
        }

        function getBalance() {

            $.ajax({
                url: `{{ route('customer.getUserBalance', ['userToken' => ':userToken']) }}`
                    .replace(':userToken', userToken.val()),
                type: 'GET',
                dataType: 'json',
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    // Limpia clases previas
                    containerMessage.removeClass('alert-danger alert-success');
                    if (res.Error) {
                        containerMessage.text(res.Error).addClass('alert alert-danger');
                    } else if (res.Data && Array.isArray(res.Data) && res.Data.length > 0) {
                        const balanceInfo = res.Data[0];
                        const message = `
                            <span><strong>Moneda:</strong> ${balanceInfo.CurrencyCode}</span><br>
                            <span><strong>Balance:</strong> ${balanceInfo.Amount}</span><br>
                            <span><strong>Predeterminado:</strong> ${balanceInfo.IsDefault}</span>`;
                        containerMessage.html(message).addClass('alert alert-success');
                    } else {
                        containerMessage.text('Datos no disponibles.').addClass('alert alert-danger');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al obtener el balance del usuario:', textStatus, errorThrown);
                    containerMessage.text('Error al realizar la solicitud.').addClass('alert alert-danger');
                }
            });
        }

        function increaseBalance() {
            const balance = 2500;

            $.getJSON({
                url: `{{ route('customer.increaseBalance', ['userToken' => ':userToken', 'balance' => ':balance']) }}`
                    .replace(':userToken', userToken.val()).replace(':balance', balance),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    showToast(res.message, res.type);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch exchange rate:', textStatus, errorThrown);
                }
            });
        }

        function getRouteGameRoom() {
            $.getJSON({
                url: `{{ route('iframe.get-route-game-rooms', ['userToken' => ':userToken']) }}`
                    .replace(':userToken', userToken.val()),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    if (res.route) {
                        window.open(res.route, '_blank');
                    } else {
                        showToast(res.error, 'warning');
                        $('#container-message').text(res.error).addClass('alert alert-danger');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch exchange rate:', textStatus, errorThrown);
                }
            });
        }

        $(document).on('click', '.btn-user-token', function() {

            $('#container-message').text('').removeClass('alert alert-danger alert-success');

            if (!userToken.val()) {
                userToken.focus();
                showToast('Ingrese el Token de Usuario', 'warning');
                return;
            }

            const dataType = $(this).data('val');

            if (dataType === 'getId') {
                getUserId();
            } else if (dataType === 'getBalance') {
                getBalance();
            } else if (dataType === 'increaseBalance') {
                increaseBalance();
            } else if (dataType === 'goToView') {
                getRouteGameRoom();
            }
        })
    </script>
@endsection
