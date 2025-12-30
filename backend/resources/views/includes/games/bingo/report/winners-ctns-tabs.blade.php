<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    <li role="presentation" class="nav-item">
        <a id="only-my-winners-tab" class="nav-link @if ($isCommerce) active @endif"
            href="{{ route('bingo.winners', ['type' => 'private', 'dateFilter' => 'today']) }}" role="tab"
            aria-controls="only-my-winners-tab" aria-selected="{{ $isCommerce ? 'true' : 'false' }}">
            Mi Comercio
            @if ($countWinnersByCommerce > 0)
                <span class="badge badge-dark pt-1">{{ $countWinnersByCommerce }}</span>
            @endif
        </a>
    </li>
    <li role="presentation" class="nav-item">
        <a id="other-winners-tab" class="nav-link @if (!$isCommerce) active @endif"
            href="{{ route('bingo.winners', ['type' => 'public', 'dateFilter' => 'today']) }}" role="tab"
            aria-controls="other-winners-tab" aria-selected="{{ !$isCommerce ? 'true' : 'false' }}">
            Otros
            @if ($countWinnersOtherCommerces > 0)
                <span class="badge badge-dark pt-1">{{ $countWinnersOtherCommerces }}</span>
            @endif
        </a>
    </li>
</ul>
