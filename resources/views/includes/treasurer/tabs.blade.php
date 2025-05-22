<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <a id="treasurers-tab" class="nav-link @if (!$others) active @endif"
            href="{{ route('admin.treasurers') }}" role="tab" aria-controls="treasurers-tab"
            aria-selected="{{ !$others ? 'true' : 'false' }}">
            Tesoreros
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a id="other-treasurers-tab" class="nav-link @if ($others) active @endif"
            href="{{ route('admin.otherTreasurers') }}" role="tab" aria-controls="other-treasurers-tab"
            aria-selected="{{ $others ? 'true' : 'false' }}">
            Otros
        </a>
    </li>
</ul>
