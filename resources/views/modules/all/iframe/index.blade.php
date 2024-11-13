@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($cusToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <div class="row">
                    {{-- @foreach ($iframes as $item)
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="info-box">
                                <a class="info-box-icon bg-success" href="" title="">
                                    <i class=" fas fa-wallet"></i>
                                </a>
                                <div class="info-box-content">
                                    <span class="info-box-number">
                                        <a href=""></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach --}}
                </div>
                <iframe id="all-rooms" src="{{ $iframes[0]['route'] }}" width="100%" height="500px" frameborder="0"></iframe>
            </div>
        </div>
    </div>
@endsection
