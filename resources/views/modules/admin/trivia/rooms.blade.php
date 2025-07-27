@extends('layouts.layout-online')
<title>
    Trivias - PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <a href="{{ url('/home') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:7px; font-size:20px;">Lista de trivias</span>
                        @if (Session::has('mensaje'))
                            <div class="col-12 col-lg-5">
                                <caption>
                                    <div class="alert alert-info alert-dismissible" role="alert">
                                        <strong>{{ Session::get('mensaje') }}</strong>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                </caption>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div style="font-size:18.5px;">
                                    <a href="{{ route('trivia.create') }}">
                                        <i class="fas fa-plus-square"></i>
                                    </a>
                                    <a class="ml-3 mr-3" href="{{ route('triv_in.create_quest') }}">??</a>
                                    <a href="{{ route('trivia.edit_sound') }}">
                                        <i class="fa-solid fa-music"></i>
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>RP</th>
                                                <th>Coin</th>
                                                <th>Valor</th>
                                                <th>PJ</th>
                                                <th>prize_pool</th>
                                                <th>Tipo</th>
                                                <th>Com</th>
                                                <th>Profit</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($trivias as $triv)
                                                <tr>
                                                    <td>
                                                        @if ($triv->fecha != null)
                                                            {{ \Carbon\Carbon::parse($triv->fecha)->format('d/m/Y') }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $triv->hora }}</td>
                                                    <td>
                                                        @if ($triv->replay == 0)
                                                            No
                                                        @else
                                                            Si
                                                        @endif
                                                    </td>
                                                    <td>{{ $triv->name }}</td>
                                                    <td>{{ $triv->precio }}</td>
                                                    <td>{{ $triv->players }}/{{ $triv->min_pj }}</td>
                                                    <td>{{ $triv->prize_pool }}</td>
                                                    <td>
                                                        @if ($triv->type_boat == 1)
                                                            Aumet
                                                        @elseif($triv->type_boat == 2)
                                                            ValFJ
                                                        @endif
                                                    </td>
                                                    <td>{{ $triv->com }}%</td>
                                                    <td>{{ $triv->profit }}</td>
                                                    <td>{{ $triv->info }}</td>
                                                    <td align="center">
                                                        @if ($triv->status != 3)
                                                            <a href="{{ url('trivia/edit/' . $triv->id) }}"
                                                                class="btn bg-cyan"><i class="fas fa-edit"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-center">
                            <a href="{{ route('trivia.create') }}" title="Crear Nueva Trivia">
                                <button id="savePago" class="btn btn-primary profile-button" type="submit">
                                    <i class="fas fa-plus-square"></i>&nbspCrear Nueva Trivia
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
