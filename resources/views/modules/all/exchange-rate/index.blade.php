@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if ($cusToken) p-2 @endif">
        <div class="card">
            @include('components.card-header', ['title' => $title, 'returnBtn' => true])
            <div class="card-body">
                @if (count($coins) > 0 || $cusToken)
                    <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                            class="fas fa-plus-square"></i> Añadir</button>
                @endif
                <div id="chat-notification"></div>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <h6>Valores en base a Un Dólar (1 USD)</h6>
                        <thead>
                            <tr>
                                @if (!isset($cusToken))
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
                                    @if (!isset($cusToken))
                                        <td>
                                            {{ $item->Coin->Country ? $item->Coin->Country->name : 'Todos' }}
                                        <td>
                                    @endif
                                    <td>
                                        {{ isset($cusToken) ? $item->coin : $item->Coin->long_name }}
                                    </td>
                                    <td>
                                        @if (isset($cusToken))
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
                                                <button class="btn btn-sm btn-primary btn-{{ $name }}"
                                                    data-type="2" data-id="{{ encrypt($item->id) }}"
                                                    title="Editar Tasa de Cambio">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-secondary btn-{{ $name }}" data-type="4"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-repeat"></i>
                                            </button>
                                            @if ((Auth::user() && Auth::user()->user_type == 1) || isset($cusToken))
                                                <button class="btn btn-sm btn-danger btn-{{ $name }}"
                                                    data-type="3" data-id="{{ encrypt($item->id) }}" title="Eliminar">
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
    @component('components.modal-form', [
        'name' => $name,
        'dataForm' => view('modules.all.exchange-rate.form', ['cusToken' => $cusToken ?? false])->render(),
    ])
    @endcomponent
    @include('components.form-other-options')
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/just-validate/dist/just-validate.production.min.js"></script>
    <script>
        const name = '<?php echo $name; ?>';
        const arrCoins = '<?php echo json_encode($coins); ?>';
        const valChange = $("#val-change");
        let coinID, customer, valChangeMax, valChangeMin, coinName, update = false;
    </script>
    @if ($cusToken)
        <script>
            customer = true;
            coinID = $("#currency");
            extraInfo = 'customer';

            coinID.mask('AAAAAA', {
                translation: {
                    'A': {
                        pattern: /[A-Za-z]/
                    }
                }
            });

            coinID.on('input', function() {
                var valor = $(this).val();
                $(this).val(valor.toUpperCase());
            });

            $(document).on("input", coinID, function() {
                coinName = coinID.val();
            });

            valChange.prop('readonly', false);

            function insertInfo(type, data = '') {
                update = false;

                if (type == 2) {
                    update = true;
                    const coin = data.coin;
                    coinID.val(coin);
                    coinName = coin;

                    valChange.val(data.exchange_rate);
                    $(".input-exchange-rate").trigger('change');
                    $("#alert-currency").removeClass('hide-elem');
                }

                $(`#modal-${name}`).modal('show');
            }
        </script>
    @else
        <script>
            const coins = $("#coin");
            coinID = coins;
            extraInfo = 'commerce';

            function insertInfo(type, data = '') {
                update = false;

                if (type == 2) {
                    update = true;
                    const coin = data.coin;
                    coins.append(
                        `<option value="${coin.id}" selected>${coin.long_name} (${coin.symbol})</option>`
                    );
                    coinName = `${coin.long_name} (${coin.symbol})`;
                    valChange.val(data.exchange_rate);
                    $(".input-exchange-rate").trigger('change');
                    $("#alert-currency").removeClass('hide-elem');
                }

                if (arrCoins.length && !customer) {
                    $.each(arrCoins, function(index, item) {
                        coins.append(`<option value="${item.id}">${item.long_name} (${item.symbol})</option>`);
                    });
                }

                $(`#modal-${name}`).modal('show');
            }

            function getLastValueByCoin(coin) {
                const url = `{{ asset('') }}exchange-rate/getLastValueByCoin/:coin`.replace(':coin', coin);
                $.getJSON({
                    url: url,
                    data: {
                        '_token': $("#token").val()
                    },
                    success: function(data) {
                        const coinValue = parseFloat(data.toFixed(2));
                        // Calcular los valores de máximo y mínimo
                        if (coinValue > 1) {
                            valChangeMax = (coinValue + 3).toFixed(2);
                            valChangeMin = (coinValue - 2).toFixed(2);
                        }
                        if (!update) valChange.val(coinValue);
                        valChange.prop('readonly', false);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Manejar errores aquí si es necesario
                    }
                });
            }

            coins.on("change", function() {
                if (coins.val() != '') valChange.attr('readonly', true), getLastValueByCoin(coins.val());
            });

            $(document).on("input", coins, function() {
                coinName = coins.find("option:selected").text();
            });
        </script>
    @endif
    <script>
        valChange.mask('00000.00', {
            reverse: true
        }); // Máscara para números con dos decimales

        $(document).on("input change", ".input-exchange-rate", function() {
            if (coinID.val() && valChange.val()) {
                console.log(900, coinName);
                $("#text-currency-val").text(`${valChange.val()} ${coinName}`);
                $("#alert-currency").removeClass('hide-elem');
            }
        });

        valChange.on('input', function() {
            let currentValue = parseFloat($(this).val());
            if (currentValue < valChangeMin) {
                $(this).val(valChangeMin);
            } else if (currentValue > valChangeMax) {
                $(this).val(valChangeMax);
            }
        });

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tasa de cambio', '{{ asset('') }}exchange-rate')
        });

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [coinID.attr('id'), valChange.attr('id')]);
        });

        function deleteInfo(encryptedId) {
            showToastOptionBtns(
                'Esta moneda será eliminada definitivamente',
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('exchange-rate.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }

        const otherCasesMap = {
            4: function() {
                showToastOptionBtns(
                    'Deseas cambiar el status de esta Tasa de Cambio',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    `{{ route('exchange-rate.change-status', ['id' => ':id']) }}`,
                    encryptedId
                );
            }
        };
    </script>
@endsection
