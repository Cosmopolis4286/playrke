@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 col-lg-7 m-auto">
        <div class="card">
            @include('includes.card-header', ['title' => $user->username])
            <div class="card-body">
                @if ($user->user_type == 1 || $user->user_type == 2)
                    @if ($wallet->coin == 1)
                        @include('modules/all/profile/forms/form_user_usdt')
                    @elseif($wallet->coin == 2)
                        @include('modules/all/profile/forms/form_user_ves')
                    @elseif($wallet->coin == 4)
                        @include('modules/all/profile/forms/form_user_chl')
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $("#name").on('input', function(evt) {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]+/g, ""));
                if (this.value.length > 25)
                    this.value = this.value.slice(0, 25);
            });
            $("#last_name").on('input', function(evt) {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]+/g, ""));
                if (this.value.length > 20)
                    this.value = this.value.slice(0, 20);
            });
        });
    </script>
@endsection
