@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($cusToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-auto col-lg-8 col-lx-7">
                        <div class="input-group">
                            <input type="text" id="user-token" class="form-control"
                                placeholder="Ingresa el token del usuario" aria-label="Ingresa el token del usuario"
                                aria-describedby="button-addon4">
                            <div class="input-group-append">
                                <button data-val="getId" class="btn-user-token btn btn-outline-secondary" type="button">
                                    Get ID
                                </button>
                                <button data-val="getBalance" class="btn-user-token btn btn-outline-secondary"
                                    type="button">
                                    Balance
                                </button>
                                <button data-val="goToView" class="btn-user-token btn btn-outline-primary" type="button">
                                    Go To View
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

        function getUserId() {
            $.getJSON({
                url: `{{ route('customer.getUserId', ['userToken' => ':userToken']) }}`
                    .replace(':userToken', userToken.val()),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    if (res.Error) {
                        $('#container-message').text(res.Error)
                            .addClass('alert alert-danger');
                    } else {
                        $('#container-message').text('User ID: ' + res.Data.userId)
                            .addClass('alert alert-success');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al obtener el ID del usuario:', textStatus, errorThrown);
                    $('#container-message').text('Error al realizar la solicitud.')
                        .addClass('alert alert-danger');
                }
            });
        }

        function getBalance() {
            $.getJSON({
                url: `{{ route('customer.getUserBalance', ['userToken' => ':userToken']) }}`
                    .replace(':userToken', userToken.val()),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    if (res.Error) {
                        $('#container-message').text(res.Error)
                            .addClass('alert alert-danger');
                    } else {
                        $('#container-message').text('User ID: ' + res.Data.userId)
                            .addClass('alert alert-success');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al obtener el ID del usuario:', textStatus, errorThrown);
                    $('#container-message').text('Error al realizar la solicitud.')
                        .addClass('alert alert-danger');
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
                        window.location.href = res.route;
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
            } else if (dataType === 'goToView') {
                getRouteGameRoom();
            }
        })
    </script>
@endsection
