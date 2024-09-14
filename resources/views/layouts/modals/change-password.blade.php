<div class="modal fade" id="modal-change-password" tabindex="-1" role="dialog"
    aria-labelledby="modal-change-password-label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-change-password-label">
                    Cambiar Contraseña
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-change-password" action="{{ route('profile.change-password', Auth::id()) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_password">Contraseña Actual*</label>
                        <input type="password" id="current_password" name="current_password"
                            class="form-control form-info" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña*</label>
                        <input type="password" id="new_password" name="new_password" class="form-control form-info"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirmation">Confirmar Nueva Contraseña*</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                            class="form-control form-info" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="btn-confirm-new-pwd" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
