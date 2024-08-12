@extends ('layouts.app_rke')
@section('title', 'Política de Privacidad')
@section('content')
    <div class="col-12">
        <div class="card">
            <div class="card-body">@include('components.info.priv')</div>
        </div>
    </div>
@endsection
