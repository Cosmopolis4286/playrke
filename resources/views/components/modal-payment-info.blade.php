 {{-- resources/views/components/modal-payment-info.blade.php --}}
 <div class="modal fade" id="modal-{{ $name }}" tabindex="-1" role="dialog"
     aria-labelledby="{{ $name }}Label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
     <div class="modal-dialog modal-sm" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="{{ $name }}Label">{{ $title }}</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             <div class="modal-body">
                 <form id="form-{{ $formId }}" action="{{ $formRoute }}" method="POST"
                     enctype="multipart/form-data">@csrf
                     <h6 id="notify-all" class="hide-elem">Toda la venta pendiente sera notificada</h6>
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
                 </form>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                 <button type="button" id="btn-payment-confirm" class="btn btn-primary">Notificar</button>
             </div>
         </div>
     </div>
 </div>
