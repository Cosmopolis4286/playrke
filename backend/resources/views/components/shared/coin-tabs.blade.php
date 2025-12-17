@props(['currencies', 'selectedCurrency', 'routeWithCurrency'])

<ul class="nav nav-tabs mt-n2 mb-2" id="myTab" role="tablist">
    @foreach ($currencies as $item)
        @php
            $label = $item->iso_code ?? ($item->currency ?? $item->name);
            $encryptedId = encrypt($item->id);
            $isActive = $selectedCurrency == $item->id;
            $tabId = "{$item->id}-{$label}";
            $url = route($routeWithCurrency['route'], [...$routeWithCurrency['parameters'], 'currency' => $encryptedId]);
        @endphp

        <li class="nav-item">
            <a class="nav-link {{ $isActive ? 'active' : '' }}" id="{{ $tabId }}-tab" href="{{ $url }}"
                role="tab" aria-controls="{{ $tabId }}" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>
