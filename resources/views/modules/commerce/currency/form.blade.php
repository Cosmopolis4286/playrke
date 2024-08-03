<div class="form-group">
    <label for="coin">Divisa*</label>
    <select id="coin" class="form-control input-exchange-rate" name="coin"></select>
</div>
<div class="form-group">
    <label for="val-change">Cambio*</label>
    <input type="number" readonly step="0.01" id="val-change"
        class="form-control input-exchange-rate" name="val_change" />
</div>
<div id="alert-currency" class="alert alert-danger fade show text-center hide-elem" role="alert">
    Un Dólar (USD) es igual a:
    <h6 id="text-currency-val"></h6>
</div>
