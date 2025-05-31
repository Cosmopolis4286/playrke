@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Moneda</th>
                                <th>Cambio</th>
                                <th>%</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- @foreach ($exchangeRate as $item)
                               <tr></tr>
                            @endforeach --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'dataForm' => view('modules.all.awards.form', ['creator' => $creator])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    {{-- <script>
        const name = '{{ $name }}';
        const creatorType = @json($creator['type'] ?? '');
        const exchangeRate = $("#exchange-rate");
        const arrCoins = @json($coins) ?? [];
        const coin = $("#coin");

        let selectedCoin = 'all';
        let exchangeRateMax;
        let exchangeRateMin;
        let coinName;
        let update = false;

        $(document).ready(function() {
            setExtraInfo(`{"creatorType": "${creatorType}"}`);
        })

        function setTextExchangeRate() {
            const selectedCoin = coin.find("option:selected");
            const coinSymbol = selectedCoin.length ? selectedCoin.text() : coinName;
            const rateValue = parseFloat(exchangeRate.val());

            if (coin.val() && rateValue > 0) {
                $("#text-exchange-rate").text(`${rateValue} ${coinSymbol}`);
                $("#alert-exchange-rate").removeClass('hide-elem');
            } else {
                // Opcional: ocultar el mensaje si no hay datos válidos
                $("#alert-exchange-rate").addClass('hide-elem');
                $("#text-exchange-rate").text('');
            }
        }

        $(document).on("input change", "#coin", function() {
            setTextExchangeRate();
        });

        exchangeRate.on('input', function() {
            let currentValue = parseFloat($(this).val());
            if (currentValue < exchangeRateMin) {
                $(this).val(exchangeRateMin);
            } else if (currentValue > exchangeRateMax) {
                $(this).val(exchangeRateMax);
            }

            setTextExchangeRate();
        });

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tasa de cambio', '{{ asset('') }}exchange-rate', otherCasesMap)
        });

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [coin.attr('id'), exchangeRate.attr('id')]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Cambiar el status de esta Tasa de Cambio',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    route(`${name}.change-status`, {
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
                    data: {
                        '_token': $("#5K3_70K").val()
                    },
                    success: function(data) {
                        const coinValue = parseFloat(data.toFixed(2));
                        // Calcular los valores de máximo y mínimo
                        if (coinValue > 1) {
                            exchangeRateMax = (coinValue + 3).toFixed(2);
                            exchangeRateMin = (coinValue - 2).toFixed(2);
                        }
                        if (!update) exchangeRate.val(coinValue);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Manejar errores aquí si es necesario
                    }
                });
            }

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

                exchangeRate.val(data.exchange_rate);

                $("#coin").trigger('change');
                $("#alert-exchange-rate").removeClass('hide-elem');
            } else {
                $("#alert-exchange-rate").addClass('hide-elem');
                $('#creator').prop('disabled', false);
            }

            if (arrCoins.length && creatorType !== 'customer') {
                $.each(arrCoins, function(index, item) {
                    coin.append(`<option value="${item.id}">${item.long_name} (${item.symbol})</option>`);
                });
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-historical", function() {
            return;
            // Obten la URL
            const urlTemplate = $(this).data("url-template");
            // Reemplaza los placeholders en la URL
            const finalUrl = urlTemplate.replace(':coin', selectedCoin)
            // Redirige a la URL final
            window.location.href = finalUrl;
        });

        $(document).on("click", "#btn-update-exchange-rates", function() {
            $.getJSON({
                url: route('exchange-rate.getExchangeRates'),
                data: {
                    '_token': $("#5K3_70K").val()
                },
                success: function(res) {
                    showToast(res.message, res.type);
                    if (res.type === 'success') {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Manejar errores aquí si es necesario
                }
            });
        });
    </script> --}}
@endsection
