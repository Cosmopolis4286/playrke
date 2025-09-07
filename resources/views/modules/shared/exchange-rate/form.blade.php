<div class="form-group">
    <label for="currency">Divisa*</label>
    @if ($creator['type'] === 'customer')
        <input type="text" id="currency" class="form-control" name="currency">
    @else
        <select id="currency" class="form-control" name="currency"></select>
    @endif
</div>
<div class="form-group">
    <label for="exchange-rate">Cambio*</label>
    <input type="number" step="0.01" min="0.01" id="exchange-rate" class="form-control" name="exchange_rate">
    <input type="hidden" id="creator" name="creator" value="{{ json_encode($creator) }}">
</div>
<div id="alert-exchange-rate" class="alert alert-danger fade show text-center hide-elem" role="alert">
    Un Dólar (USD) es igual a: <h6 id="text-exchange-rate"></h6>
</div>
