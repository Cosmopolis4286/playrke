@switch(Auth::user()->user_type)
    @case(1)
        @include('layouts.sections.sidebar.user.index')
    @break

    @case(2)
        @include('layouts.sections.sidebar.admin.index')
    @break

    @case(3)
        @include('layouts.sections.sidebar.commerce.index')
    @break

    @case(4)
        @include('layouts.sections.sidebar.seller.index')
    @break

    @case(5)
        @include('layouts.sections.sidebar.provider.index')
    @break

    @default
        {{-- Código a ejecutar si Auth::user()->user_type no coincide con ningún caso --}}
@endswitch
