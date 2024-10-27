@extends ('layouts.app_rke')
@section('title', 'Términos y Condiciones')
@section('content')
    <div class="col-12">
        <div class="card">
            <div class="card-body">@include('includes.info.terms')</div>
        </div>
    </div>
@endsection
