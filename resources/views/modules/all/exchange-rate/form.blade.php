<div class="form-group">
    <label for="coin">Divisa*</label>
    @if (!$cusToken)
        <select id="coin" class="form-control input-exchange-rate" name="coin"></select>
    @else
        <input type="text" id="currency" class="form-control input-exchange-rate" name="currency" />
        <input type="hidden" name="cust_token" value="{{ $cusToken }}">
    @endif
</div>
<div class="form-group">
    <label for="val-change">Cambio*</label>
    <input type="number" readonly step="0.01" id="val-change" class="form-control input-exchange-rate"
        name="val_change" />
</div>
<div id="alert-currency" class="alert alert-danger fade show text-center hide-elem" role="alert">
    Un Dólar (USD) es igual a:
    <h6 id="text-currency-val"></h6>
</div>
