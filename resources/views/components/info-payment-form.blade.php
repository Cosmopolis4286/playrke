<!-- resources/views/components/info-payment-form.blade.php -->
@isset($notifyAll)
    <h6 id="notify-all" class="hide-elem">Toda la venta pendiente sera notificada</h6>
@endisset
<h5>¿Enviaste <span id="sent-amount"></span>?</h5>
<span id="payment-title"></span>
<h6 id="text-bank-data" class="mt-2">Datos Bancarios:</h6>
<div id="cont-payment-method" class="mt-n1 d-flex flex-column">
    <span id="commerce-name"></span>
    <h6 id="payment-subtitle"></h6>
    <span id="payment-method-1" class="payment-method"></span>
    <span id="payment-method-2" class="payment-method"></span>
    <span id="payment-method-3" class="payment-method"></span>
</div>
<div class="form-group mt-1">
    <label for="payment-proof">Cargar Comprobante:</label>
    <input type="file" id="payment-proof" class="form-control-file" name="payment_proof"
        accept="image/png, image/jpeg" required>
    <input type="hidden" id="ctn" name="ctn">
    <input type="hidden" id="lot" name="lot">
    <input type="hidden" id="notify-all-ctns" name="notify_all_ctns">
</div>
