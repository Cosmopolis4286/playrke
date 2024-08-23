{{-- resources/views/components/winners-ctns-tabs.blade.php  --}}
<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    <a id="only-my-winners-tab" class="nav-link @if ($isCommerce == true) active @endif"
        href="{{ route('commerce.getBingoWinnersByCommerce', ['dateFilter' => 'today']) }}" role="tab"
        aria-controls="only-my-winners-tab" aria-selected="true">Mi Comercio
        @if ($countWinnersByCommerce > 0)
            <div class="badge badge-dark pt-1">{{ $countWinnersByCommerce }}</div>
        @endif
    </a>
    <a id="all-winners-tab" class="nav-link @if ($isCommerce == false) active @endif"
        href="{{ route('commerce.getBingoWinnersByOtherCommerces', ['dateFilter' => 'today']) }}" role="tab"
        aria-controls="all-winners-tab" aria-selected="true">Otros
        @if ($countWinnersOtherCommerces > 0)
            <div class="badge badge-dark pt-1">{{ $countWinnersOtherCommerces }}</div>
        @endif
    </a>
</ul>
