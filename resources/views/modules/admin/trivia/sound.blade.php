@extends('layouts.layout-online')
<title>Edit Sounds Trivia - PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-4" style="margin:0 auto;">
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne" style="">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="sub_ti-tle">
                            <a href="{{ route('trivia.index') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Edit Sounds Trivia</span>
                        </div>
                    </div>
                    <form action="{{ route('trivia.store_sound') }}" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="card-body mb-n4">
                            <div class="mb-4 col-12" style="margin-bottom:16px;">
                                Pista Track
                                <input type="file" name="track" required>
                            </div>
                            <div class="mb-4 col-12"style="margin-bottom:16px;">
                                Sound Correct
                                <input type="file" name="s_correct" required>
                            </div>
                            <div class="mb-4 col-12"style="margin-bottom:16px;">
                                Sound Incorrect
                                <input type="file" name="s_incorrect" required>
                            </div>
                            <div>
                                <button class="mt-1 btn btn-primary form-control">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection