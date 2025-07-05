<div class="row">
    @foreach ($cartons as $index => $ctn)
        <div class="col-6 col-sm-4 col-xl-3 my-1 notranslate" translate="no">
            <div class="card" data-carton-index="{{ $index }}">
                <div class="container-carton">
                    @foreach (['B' => 'b', 'I' => 'i', 'N' => 'n', 'G' => 'g', 'O' => 'o'] as $letter => $prefix)
                        <div class="section-letter">
                            <div class="number-box-letter"><b>{{ $letter }}</b></div>
                            @for ($i = 1; $i <= 5; $i++)
                                @php $key = $prefix . $i; @endphp
                                @if ($letter === 'N' && $i === 3)
                                    <div class="number-box is-logo">
                                        <img class="img-fluid img-logo-ctn"
                                            src="{{ asset($customerLogo ?? 'img/rke.png') }}">
                                    </div>
                                @else
                                    <div class="number-box" data-pos="{{ $key }}">
                                        <span class="number-box-number">{{ $ctn->$key }}</span>
                                    </div>
                                @endif
                            @endfor
                        </div>
                    @endforeach
                </div>
                <div class="section-serial">
                    <small>{{ $ctn->serial }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>
