@extends('layouts.auth-layout')
@section('title', 'Buscar')
@section('content')
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-search mr-2"></i>
                    Resultados para "{{ $term }}"
                </h3>
            </div>
            <div class="card-body">
                @if ($term === '')
                    <p class="text-muted mb-0">Escribe un término para buscar.</p>
                @elseif (empty($menuResults) && empty($userResults))
                    <p class="text-muted mb-0">Sin resultados para "{{ $term }}".</p>
                @else
                    @if (!empty($menuResults))
                        <h5 class="mt-2">Secciones</h5>
                        <ul class="list-unstyled">
                            @foreach ($menuResults as $result)
                                <li class="mb-2">
                                    <a href="{{ $result['url'] }}">
                                        <i class="fas fa-arrow-right mr-2"></i>{{ $result['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (!empty($userResults))
                        <h5 class="mt-3">Usuarios</h5>
                        <ul class="list-unstyled">
                            @foreach ($userResults as $result)
                                <li class="mb-2">
                                    <a href="{{ $result['url'] }}">
                                        <i class="fas fa-user mr-2"></i>{{ $result['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
