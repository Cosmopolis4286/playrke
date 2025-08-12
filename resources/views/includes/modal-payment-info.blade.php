 {{-- resources/views/includes/modal-payment-info.blade.php --}}
 <div class="modal fade" id="modal-{{ $viewId }}" tabindex="-1" role="dialog"
     aria-labelledby="{{ $viewId }}Label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
     <div class="modal-dialog modal-sm" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="{{ $viewId }}Label">{{ $title }}</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             <div class="modal-body">
                 <form id="form-{{ $formId }}" action="{{ $formRoute }}" method="POST"
                     enctype="multipart/form-data">@csrf
                     @include('includes.info-payment-form', ['notifyAll' => true])
                 </form>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                 <button type="button" id="btn-payment-confirm" class="btn btn-primary">Notificar</button>
             </div>
         </div>
     </div>
 </div>
