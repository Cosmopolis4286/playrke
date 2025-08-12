@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @if (!empty($coins) || $creator['type'] === 'customer')
                    <button type="button" class="btn-{{ $viewId }} btn btn-sm btn-success" data-action="create"><i
                            class="fas fa-plus-square"></i> Añadir</button>
                @endif
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <h6>Valores en base a Un Dólar (1 USD)</h6>
                        <thead>

                            <tr>
                                @if ($creator['type'] !== 'customer')
                                    <th>Pais</th>
                                @endif
                                <th>Moneda</th>
                                <th>Cambio</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exchangeRate as $item)
                                <tr>
                                    @if ($creator['type'] !== 'customer')
                                        <td>
                                            {{ $item->Coin->Country ? $item->Coin->Country->name : 'Todos' }}
                                        </td>
                                    @endif
                                    <td>
                                        {{ $creator['type'] === 'customer' ? $item->coin : $item->Coin->long_name }}
                                    </td>
                                    <td>
                                        @if ($creator['type'] === 'customer')
                                        @else
                                            {{ $item->Coin->symbol }}
                                        @endif
                                        {{ $item->exchange_rate }}
                                    </td>
                                    <td>
                                        @if ($item->status == 0)
                                            No/Disp
                                        @elseif($item->status == 1)
                                            Activo
                                        @elseif($item->status == 2)
                                            <a class="badge badge-danger" title="Registrar Datos Bancarios"
                                                href="{{ route('directory.index', ['countryCode' => strtolower($item->Coin->Country->iso_code_2)]) }}">
                                                Sin Datos Bancarios
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-toggle">
                                            @if ($item->status == 1)
                                                <button class="btn btn-sm btn-primary btn-{{ $viewId }}"
                                                    data-action="edit" data-id="{{ encrypt($item->id) }}"
                                                    title="Editar Tasa de Cambio">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-secondary btn-{{ $viewId }}" data-type="4"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-repeat"></i>
                                            </button>
                                            @if ((Auth::user() && Auth::user()->user_type == 1) || $creator['type'] === 'customer')
                                                <button class="btn btn-sm btn-danger btn-{{ $viewId }}"
                                                    data-action="delete" data-id="{{ encrypt($item->id) }}" title="Eliminar">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'viewId' => $viewId,
        'dataForm' => view('modules.all.exchange-rate.form', ['creator' => $creator])->render(),
    ])
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/just-validate/dist/just-validate.production.min.js"></script>
    <script>
        const name = '{{ $viewId }}';
        const creatorType = @json($creator['type'] ?? '');
        const exchangeRate = $("#exchange-rate");
        const arrCoins = @json($coins) ?? [];
        const coin = $("#coin");

        let exchangeRateMax;
        let exchangeRateMin;
        let coinName;
        let update = false;

        $(document).ready(function() {
            setExtraInfo(`{"creatorType": "${creatorType}"}`);
        })

        function setTextExchangeRate() {
            if (coin.val() && exchange-rate.val() > 0) {
                $("#text-exchange-rate").text(`${exchange-rate.val()} ${coinName}`);
                $("#alert-exchange-rate").removeClass('hide-elem');
            }
        }

        $(document).on("input change", "#coin", function() {
            setTextExchangeRate();
        });

        exchange-rate.on('input', function() {
            let currentValue = parseFloat($(this).val());
            if (currentValue < exchangeRateMin) {
                $(this).val(exchangeRateMin);
            } else if (currentValue > exchangeRateMax) {
                $(this).val(exchangeRateMax);
            }

            setTextExchangeRate();
        });

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tasa de cambio', otherCasesMap)
        });

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [coin.attr('id'), exchange-rate.attr('id')]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Cambiar el status de esta Tasa de Cambio',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.changeStatus`, {
                        id: encryptedId
                    })
                );
            }
        };

        if (creatorType === 'customer') {
            coin.mask('AAAAAA', {
                translation: {
                    'A': {
                        pattern: /[A-Za-z]/
                    }
                }
            });

            coin.on('input', function() {
                var valor = $(this).val();
                $(this).val(valor.toUpperCase());
            });

            $(document).on("input", coin, function() {
                coinName = coin.val();
            });
        } else {
            function getLastValueByCoin(coin) {
                $.getJSON({
                    url: route('exchange-rate.getLastValueByCoin', coin),
                    success: function(data) {
                        const coinValue = parseFloat(data.toFixed(2));
                        // Calcular los valores de máximo y mínimo
                        if (coinValue > 1) {
                            exchangeRateMax = (coinValue + 3).toFixed(2);
                            exchangeRateMin = (coinValue - 2).toFixed(2);
                        }
                        if (!update) exchange-rate.val(coinValue);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Manejar errores aquí si es necesario
                    }
                });
            }

            // coin.on("change", function() {
            //     if (coin.val() != '') exchange-rate.attr('readonly', true), getLastValueByCoin(coin.val());
            // });

            $(document).on("input", coin, function() {
                coinName = coin.find("option:selected").text();
            });
        }

        function insertInfo(type, data = '') {
            update = false;

            if (type == 2) {
                $('#creator').prop('disabled', true);
                update = true;
                const currentCoin = data.coin;

                if (creatorType === 'customer') {
                    coin.val(currentCoin);
                    coinName = currentCoin;
                } else {
                    coin.append(
                        `<option value="${currentCoin.id}" selected>${currentCoin.long_name} (${currentCoin.symbol})</option>`
                    );
                    coinName = `${currentCoin.long_name} (${currentCoin.symbol})`;
                }

                exchange-rate.val(data.exchange_rate);

                $("#coin").trigger('change');
                $("#alert-exchange-rate").removeClass('hide-elem');
            } else {
                $('#creator').prop('disabled', false);
            }

            if (arrCoins.length && creatorType !== 'customer') {
                $.each(arrCoins, function(index, item) {
                    coin.append(`<option value="${item.id}">${item.long_name} (${item.symbol})</option>`);
                });
            }

            $(`#modal-${name}`).modal('show');
        }
    </script>
@endsection
