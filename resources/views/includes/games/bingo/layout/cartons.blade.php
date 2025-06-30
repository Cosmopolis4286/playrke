{{-- components.game.bingo.layout.cartons --}}
<div class="row">
    @foreach ($cartons as $ctn)
        <div class="col-6 col-sm-4 col-xl-3 my-1 notranslate" translate="no">
            <div class="card">
                <div class="container-carton">
                    <div class="section-letter">
                        <div class="number-box-letter"><b>B</b></div>
                        <div class="number-box" data-box="{{ $ctn->b1 }}">
                            <span class="number-box-number">{{ $ctn->b1 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->b2 }}">
                            <span class="number-box-number">{{ $ctn->b2 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->b3 }}">
                            <span class="number-box-number">{{ $ctn->b3 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->b4 }}">
                            <span class="number-box-number">{{ $ctn->b4 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->b5 }}">
                            <span class="number-box-number">{{ $ctn->b5 }}</span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>I</b></div>
                        <div class="number-box" data-box="{{ $ctn->i1 }}">
                            <span class="number-box-number">{{ $ctn->i1 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->i2 }}">
                            <span class="number-box-number">{{ $ctn->i2 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->i3 }}">
                            <span class="number-box-number">{{ $ctn->i3 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->i4 }}">
                            <span class="number-box-number">{{ $ctn->i4 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->i5 }}">
                            <span class="number-box-number">{{ $ctn->i5 }}</span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>N</b></div>
                        <div class="number-box" data-box="{{ $ctn->n1 }}">
                            <span class="number-box-number">{{ $ctn->n1 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->n2 }}">
                            <span class="number-box-number">{{ $ctn->n2 }}</span>
                        </div>
                        <div class="number-box">
                            <img class="img-fluid img-logo-ctn"
                                src="{{ isset($customerLogo) ? asset($customerLogo) : asset('img/rke.png') }}">
                        </div>
                        <div class="number-box" data-box="{{ $ctn->n4 }}">
                            <span class="number-box-number">{{ $ctn->n4 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->n5 }}">
                            <span class="number-box-number">{{ $ctn->n5 }}</span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>G</b></div>
                        <div class="number-box" data-box="{{ $ctn->g1 }}">
                            <span class="number-box-number">{{ $ctn->g1 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->g2 }}">
                            <span class="number-box-number">{{ $ctn->g2 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->g3 }}">
                            <span class="number-box-number">{{ $ctn->g3 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->g4 }}">
                            <span class="number-box-number">{{ $ctn->g4 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->g5 }}">
                            <span class="number-box-number">{{ $ctn->g5 }}</span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>O</b></div>
                        <div class="number-box" data-box="{{ $ctn->o1 }}">
                            <span class="number-box-number">{{ $ctn->o1 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->o2 }}">
                            <span class="number-box-number">{{ $ctn->o2 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->o3 }}">
                            <span class="number-box-number">{{ $ctn->o3 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->o4 }}">
                            <span class="number-box-number">{{ $ctn->o4 }}</span>
                        </div>
                        <div class="number-box" data-box="{{ $ctn->o5 }}">
                            <span class="number-box-number">{{ $ctn->o5 }}</span>
                        </div>
                    </div>
                </div>
                <div class="section-serial">
                    <small>{{ $ctn->serial }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>
