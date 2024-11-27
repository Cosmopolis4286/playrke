@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($cusToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="user-token" class="form-control"
                                placeholder="Ingresa el Token del Usuario" aria-label="Ingresa el Token del Usuario:"
                                aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button id="go-route-game-route" class="btn btn-outline-success" type="button">
                                    Ir a Vista
                                </button>
                            </div>
                        </div>
                        <span></span>
                    </div>
                </div>
                <div class="row mt-3">
                    {{-- @foreach ($iframes as $item)
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
                    @endforeach --}}
                </div>
                <iframe id="all-rooms" src="{{ $iframes[0]['route'] }}" width="100%" height="500px"
                    frameborder="0"></iframe>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const userToken = $('#user-token');

        $(document).on('click', '#go-route-game-route', function() {
            if (!userToken.val()) {
                showToast('Ingrese el Token de Usuario', 'warning');
                return;
            }

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
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch exchange rate:', textStatus, errorThrown);
                }
            });
        })
    </script>
@endsection
