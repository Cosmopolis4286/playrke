@extends('layouts.auth-layout')
@section('title', 'Administradores')
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered my-2 w-100" id="administrators-table">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Nombre</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>País</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Los datos se cargarán via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para confirmar acciones --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Acción</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">¿Está seguro de realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para mostrar contraseña reseteada --}}
    <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contraseña Restablecida</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="passwordMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            loadAdministrators();

            // Función para cargar la lista de administradores
            function loadAdministrators() {
                $.get('{{ route("admin.administrators.fetchList") }}')
                    .done(function(data) {
                        let html = '';
                        data.data.forEach(function(admin) {
                            html += `
                                <tr>
                                    <td>${admin.role_badge}</td>
                                    <td>${admin.full_name}</td>
                                    <td>${admin.username}</td>
                                    <td>${admin.email}</td>
                                    <td>${admin.country_name}</td>
                                    <td>${admin.status_badge}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="toggleStatus('${admin.id}', '${admin.full_name}')" 
                                                    title="Cambiar Estado">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="resetPassword('${admin.id}', '${admin.full_name}')" 
                                                    title="Restablecer Contraseña">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteAdmin('${admin.id}', '${admin.full_name}')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#administrators-table tbody').html(html);
                    })
                    .fail(function() {
                        Swal.fire('Error', 'No se pudieron cargar los administradores', 'error');
                    });
            }

            // Función para cambiar el estado
            window.toggleStatus = function(id, name) {
                Swal.fire({
                    title: 'Cambiar Estado',
                    text: `¿Desea cambiar el estado del administrador ${name}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cambiar',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route("admin.administrators.toggleStatus") }}/${id}`,
                            method: 'PATCH',
                            success: function() {
                                Swal.fire('Éxito', 'Estado cambiado correctamente', 'success');
                                loadAdministrators();
                            },
                            error: function() {
                                Swal.fire('Error', 'No se pudo cambiar el estado', 'error');
                            }
                        });
                    }
                });
            };

            // Función para restablecer contraseña
            window.resetPassword = function(id, name) {
                Swal.fire({
                    title: 'Restablecer Contraseña',
                    text: `¿Desea generar una nueva contraseña para ${name}?`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, generar',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route("admin.administrators.resetPassword") }}/${id}`,
                            method: 'PUT',
                            success: function(response) {
                                $('#passwordMessage').text(
                                    `Nueva contraseña para ${name}: ${response.data.password}`
                                );
                                $('#passwordModal').modal('show');
                                loadAdministrators();
                            },
                            error: function() {
                                Swal.fire('Error', 'No se pudo restablecer la contraseña', 'error');
                            }
                        });
                    }
                });
            };

            // Función para eliminar administrador
            window.deleteAdmin = function(id, name) {
                Swal.fire({
                    title: 'Eliminar Administrador',
                    text: `¿Está seguro de eliminar al administrador ${name}? Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route("admin.administrators.delete") }}/${id}`,
                            method: 'DELETE',
                            success: function() {
                                Swal.fire('Eliminado', 'Administrador eliminado correctamente', 'success');
                                loadAdministrators();
                            },
                            error: function(xhr) {
                                let message = 'No se pudo eliminar el administrador';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error', message, 'error');
                            }
                        });
                    }
                });
            };
        });
    </script>
@endsection
