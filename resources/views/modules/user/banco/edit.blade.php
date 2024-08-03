@extends('layouts.app_rke')
<title>Editar Pago Móvil - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-md-5" style="max-width:420px; margin:0 auto;">
            <form action="{{ route('pagomovil.update', $pagomovil->id) }}" method="POST">
                @csrf 
                <div class="card bg-rke">
                    <div class="card-header mb-n3" id="headingOne">
                        <div class="sub_ti-tle">
                            <a href="{{ route('pagomovil.index') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Editar Pago Móvil</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5 col-sm-4">
                                <label class="lb_in">Cédula</label>
                                <input id="cedula" type="text" name="cedula" class="form-control_d"
                                    value="{{ $pagomovil->cedula }}" disabled>
                            </div>
                            <div class="col-7 col-sm-8">
                                <label class="lb_in">Banco</label>
                                <select name="banco" class="form-control">
                                    <option value="{{ $pagomovil->banco->id }}">{{ $pagomovil->banco->codigo }} -
                                        {{ $pagomovil->banco->nombre }}</option>
                                    @foreach ($bancos as $banco)
                                        @if ($pagomovil->banco->codigo != $banco->codigo)
                                            <option value="{{ $banco->id }}">{{ $banco->codigo }} -
                                                {{ $banco->nombre }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-8 col-sm-7 mb-n3">
                                <label class="lb_in">Teléfono</label>
                                <div style="display: flex">
                                    <select class="form-control col-md-6" style="max-width:71px;" name="codigo_telefono">
                                        <option value="{{ $pagomovil->codigo }}">{{ $pagomovil->codigo }}</option>
                                        @foreach ($cod_tef as $code)
                                            @if ($pagomovil->codigo != $code->codigo)
                                                <option value="{{ $code->id }}">{{ $code->codigo }}
                                            @endif
                                        @endforeach
                                    </select>
                                    <input id="telefono" type="text" name="telefono" class="form-control"
                                        value="{{ $pagomovil->telefono }}">
                                </div>
                            </div>
                            <div class="save_pf col-4 col-sm-5">
                                <label class="lb_in">&nbsp</label>
                                <div>
                                    <button id="guardar" class="form-control btn btn-primary profile-button"
                                        type="submit">Editar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="{{ asset('js/vali_pm.js') }}"></script>
@endsection
