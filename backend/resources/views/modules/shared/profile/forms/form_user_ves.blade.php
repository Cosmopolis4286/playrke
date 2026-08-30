<form id="store_perfil" action="{{ route('profile.update', $user->id) }}" method="post" enctype="multipart/form-data">
    {{ method_field('PUT') }}
    {{ csrf_field() }}
    <div class="sp_body card-body mt-n3">
        @if ($user->country == 234)
            @if ($user->cedula == null)
                <div class="col-12 mb-1 alert alert-success text-center">
                    <strong>CARGUE TODA LA INFORMACION</strong>
                </div>
            @endif
        @endif
        <div class="row">
            <div class="col-sm-6">
                <label class="lb_in">Nombres:</label>
                @if ($user->name == null)
                    <input type="text" id="name" name="name" class="name form-control" placeholder="Nombre">
                @else
                    <div>{{ $user->name }}</div>
                @endif
            </div>
            <div class="col-sm-6">
                <label class="lb_in">Apellidos:</label>
                @if ($user->apellido == null)
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Apellido">
                @else
                    <div>{{ $user->apellido }}</div>
                @endif
            </div>
            <div class="col-6">
                <label class="lb_in">País:</label>
                <div>{{ $wallet->name }}</div>
            </div>
            @if ($user->country == 234)
                <div class="col-6">
                    <label class="lb_in">Cédula:</label>
                    <div style="display: flex">
                        @if ($user->cedula == null || $user->cedula == '')
                            <select id="type_id" class="sl_in form-control"name="type_id">
                                <option value="">-</option>
                                @foreach ($type_ident as $ident)
                                    <option value="{{ $ident->id }}">{{ $ident->info_ident }}</option>
                                @endforeach
                            </select>
                            <div style="display: grid; width:100%;">
                                <input id="cedula" type="text" name="cedula"
                                    class="form-control @error('cedula') is-invalid @enderror">
                                @error('cedula')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        @else
                            <div>{{ $user->info_ident }} {{ $user->cedula }}</div>
                        @endif
                    </div>
                </div>
            @endif
            <div class="col-sm-6">
                <label class="lb_in">E-mail:</label>
                <div>{{ $user->email }}</div>
            </div>
            <div class="col-sm-6">
                <label class="lb_in">Imagen Perfil</label>
                <input type="hidden" id="van_img" @if ($user->cedula != '') value="1" @else value="0" @endif>
                <div style="display:flex;">
                    <div class="cont_img_pf">
                        <img class="img_pf"
                            @if ($user->foto == '') src="{{ asset('img/fav.ico') }}" 
                    @else src="imgSystem/imgUsers/{{ $user->foto }}" @endif>
                    </div>
                    <input id="foto" type="file" name="foto" class="ml-1 form-control"
                        value="{{ $user->foto }}">
                </div>
            </div>
            <div class="save_pf col-sm-6" style="margin:0 auto;">
                <button id="save_inf_ves" style="width:100%; font-size:15px;"
                    class="mt-3 form-control btn btn-primary profile-button" type="button">
                    <i class="fa-solid fa-floppy-disk"></i>
                </button>
            </div>
        </div>
    </div>
</form>
@section('scripts')
    <script nonce="{{ csp_nonce() }}">
        $("#save_inf_ves").click(function() {
            if ($("#van_img").val() == 0) {
                if ($("#name").val() == undefined || $("#last_name").val() == undefined ||
                    $("#type_id").val() == undefined || $("#cedula").val() == undefined) {} else {
                    $('#store_perfil').submit();
                }
            } else {
                if ($("#foto").val() != '') {
                    $('#store_perfil').submit();
                }
            }
        })
        $("#cedula").on('input', function() {
            $(this).val($(this).val().replace(/[^0-9.]/g, ''));
            if (this.value.length > 9)
                this.value = this.value.slice(0, 9);
        });
    </script>
@endsection
