@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                {{-- @include('includes.coin-tabs') --}}
                <a href="{{ route('trivia.create-view') }}" id="btn-{{ $name }}-insert"
                    class="btn btn-sm btn-primary btn-{{ $name }}" data-type="1">
                    <i class="fas fa-plus-square mr-1"></i>Trivia
                </a>
                {{-- <a class="ml-3 mr-3" href="{{ route('trivia.create_quest') }}">??</a> --}}
                {{-- <a href="{{ route('trivia.edit_sound') }}">
                        <i class="fa-solid fa-music"></i>
                    </a> --}}
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Coin</th>
                                <th>Valor</th>
                                <th>PJ</th>
                                <th>Bote</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rooms as $room)
                                <tr>
                                    <td>
                                        @if ($room->fecha != null)
                                            {{ \Carbon\Carbon::parse($room->fecha)->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $room->hora }}</td>
                                    <td>
                                        @if ($room->replay == 0)
                                            No
                                        @else
                                            Si
                                        @endif
                                    </td>
                                    <td>{{ $room->name }}</td>
                                    <td>{{ $room->precio }}</td>
                                    <td>{{ $room->players }}/{{ $room->min_pj }}</td>
                                    <td>{{ $room->jackpot }}</td>
                                    <td>
                                        @if ($room->type_boat == 1)
                                            Aumet
                                        @elseif($room->type_boat == 2)
                                            ValFJ
                                        @endif
                                    </td>
                                    <td>{{ $room->info }}</td>
                                    <td class="text-center">
                                        @if ($room->status != 3)
                                            <a href="{{ url('trivia/edit/' . $room->id) }}" class="btn bg-cyan"><i
                                                    class="fas fa-edit"></i>
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
    </div>
@endsection
@section('scripts')

@endsection
