@extends('layouts.app_rke')
<title>Mis Referidos - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-md-9" style="margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <h6>Mi link de referencia</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" id="referralLink" readonly value="{{ $referralLink }}">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-primary" onclick="copyReferralLink()">
                                <i class="fa fa-copy"></i>&nbspCopiar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-header" align="center" style="font-size:20px;">Mis Referidos</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="t_1" class="table table-striped table-bordered nowrap"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>País</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($referidos as $refe)
                                            <tr>
                                                <td>{{ $refe->username }}</td>
                                                @foreach ($pais as $pai)
                                                    @if ($pai->id == $refe->country)
                                                        <td>{{ $pai->name }}</td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><br>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function copyReferralLink() {
            var input = document.getElementById("referralLink");
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Su enlace de referencia ha sido copiado: " + input.value);
        }
    </script>
@endsection
