@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @if (!empty($currencies) || $creator['type'] === 'customer')
                    <button type="button" class="btn-{{ $moduleId }} btn btn-sm btn-success" data-action="create"><i
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
                                            {{ $item->Currency->Country ? $item->Currency->Country->name : 'Todos' }}
                                        </td>
                                    @endif
                                    <td>
                                        {{ $creator['type'] === 'customer' ? $item->currency : $item->Currency->long_name }}
                                    </td>
                                    <td>
                                        @if ($creator['type'] === 'customer')
                                        @else
                                            {{ $item->Currency->symbol }}
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
                                                href="{{ route('directory.index', ['countryCode' => strtolower($item->Currency->Country->iso_code_2)]) }}">
                                                Sin Datos Bancarios
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-toggle">
                                            @if ($item->status == 1)
                                                <button class="btn btn-sm btn-primary btn-{{ $moduleId }}"
                                                    data-action="edit" data-id="{{ encrypt($item->id) }}"
                                                    title="Editar Tasa de Cambio">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-secondary btn-{{ $moduleId }}" data-type="4"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-repeat"></i>
                                            </button>
                                            @if ((Auth::user() && Auth::user()->user_type == 1) || $creator['type'] === 'customer')
                                                <button class="btn btn-sm btn-danger btn-{{ $moduleId }}"
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
        'moduleId' => $moduleId,
        'dataForm' => view('modules.all.exchange-rate.form', ['creator' => $creator])->render(),
    ])
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/just-validate/dist/just-validate.production.min.js"></script>
    <script>
        const name = '{{ $moduleId }}';
        const creatorType = @json($creator['type'] ?? '');
        const exchangeRate = $("#exchange-rate");
        const arrCurrencies = @json($currencies) ?? [];
        const currency = $("#currency");

        let exchangeRateMax;
        let exchangeRateMin;
        let currencyName;
        let update = false;

        $(document).ready(function() {
            setExtraInfo(`{"creatorType": "${creatorType}"}`);
        })

        function setTextExchangeRate() {
            if (currency.val() && exchange-rate.val() > 0) {
                $("#text-exchange-rate").text(`${exchange-rate.val()} ${currencyName}`);
                $("#alert-exchange-rate").removeClass('hide-elem');
            }
        }

        $(document).on("input change", "#currency", function() {
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
            submitForm(name, [currency.attr('id'), exchange-rate.attr('id')]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastConfirmAction(
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
            currency.mask('AAAAAA', {
                translation: {
                    'A': {
                        pattern: /[A-Za-z]/
                    }
                }
            });

            currency.on('input', function() {
                var valor = $(this).val();
                $(this).val(valor.toUpperCase());
            });

            $(document).on("input", currency, function() {
                currencyName = currency.val();
            });
        } else {
            function getLastValueByCoin(currency) {
                $.getJSON({
                    url: route('exchange-rate.getLastValueByCoin', currency),
                    success: function(data) {
                        const currencyValue = parseFloat(data.toFixed(2));
                        // Calcular los valores de máximo y mínimo
                        if (currencyValue > 1) {
                            exchangeRateMax = (currencyValue + 3).toFixed(2);
                            exchangeRateMin = (currencyValue - 2).toFixed(2);
                        }
                        if (!update) exchange-rate.val(currencyValue);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Manejar errores aquí si es necesario
                    }
                });
            }

            // currency.on("change", function() {
            //     if (currency.val() != '') exchange-rate.attr('readonly', true), getLastValueByCoin(currency.val());
            // });

            $(document).on("input", currency, function() {
                currencyName = currency.find("option:selected").text();
            });
        }

        function insertInfo(type, data = '') {
            update = false;

            if (type == 2) {
                $('#creator').prop('disabled', true);
                update = true;
                const currentCoin = data.currency;

                if (creatorType === 'customer') {
                    currency.val(currentCoin);
                    currencyName = currentCoin;
                } else {
                    currency.append(
                        `<option value="${currentCoin.id}" selected>${currentCoin.long_name} (${currentCoin.symbol})</option>`
                    );
                    currencyName = `${currentCoin.long_name} (${currentCoin.symbol})`;
                }

                exchange-rate.val(data.exchange_rate);

                $("#currency").trigger('change');
                $("#alert-exchange-rate").removeClass('hide-elem');
            } else {
                $('#creator').prop('disabled', false);
            }

            if (arrCurrencies.length && creatorType !== 'customer') {
                $.each(arrCurrencies, function(index, item) {
                    currency.append(`<option value="${item.id}">${item.long_name} (${item.symbol})</option>`);
                });
            }

            $(`#modal-${name}`).modal('show');
        }
    </script>
@endsection
