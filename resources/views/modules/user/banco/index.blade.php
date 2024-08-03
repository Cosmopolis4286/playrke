@extends('layouts.app_rke')
<title>PlayRKE - Mi Cuenta Bancaria</title>
@section('content')
    <div class="row">
        <div class="col-12" style="max-width:590px; margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:7px; font-size:20px;">Información Bancaria</span>
                    @if (Session::has('mensaje'))
                        <div class="alert alert-alert-info justify-content-start text-start" role="alert">
                            {!! Session::get('mensaje') !!}
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                           <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th>Tipo</th>
                                            <th>N° Cuenta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($accounts as $bank)
                                            <tr>
                                                <td>{{ $bank->banco->name }}</td>
                                                <td>
                                                    @if ($bank->type == 1)
                                                        Cuenta Corriente
                                                    @elseif($bank->type == 2)
                                                        Cuenta Visa
                                                    @elseif($bank->type == 3)
                                                        Cuenta de Ahorro
                                                    @elseif($bank->type == 4)
                                                        Cuenta Rut
                                                    @endif
                                                </td>
                                                <td>{{ $bank->num_account }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        @if (count($accounts) != 2)
                            <a href="{{ route('banco.create') }}">
                                <button id="savePago" class="btn btn-primary profile-button" type="submit"><i
                                        class="fas fa-plus-square"></i>&nbspCuenta Bancaria</button></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
