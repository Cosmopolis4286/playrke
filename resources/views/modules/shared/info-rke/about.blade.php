@extends ('layouts.layout-online')
@section('title', 'Quienes Somos')
@section('content')
    <div class="col-lg-9 m-auto">
        <div class="card mb-3">
            <div class="card-body">@include('includes.info.about')</div>
        </div>
    </div>
@endsection
