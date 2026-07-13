{{-- resources/views/components/footer.blade.php --}}
<style>
.main-footer.footer-app {
    height: var(--footer-h, 44px) !important;
    min-height: 0 !important;
    padding: 0 !important;
    background: var(--footer-bg, linear-gradient(90deg, rgba(42,245,152,0.10) 0%, rgba(7,3,40,0.97) 20%, rgba(7,3,40,0.97) 80%, rgba(123,79,255,0.14) 100%), #070120) !important;
    border-top: 1px solid var(--footer-border, rgba(123,79,255,0.35)) !important;
    box-shadow: var(--footer-shadow, 0 -8px 24px rgba(0,0,0,0.35)) !important;
    display: flex !important;
    align-items: center !important;
    color: var(--footer-color, rgba(255,255,255,0.55)) !important;
}
.main-footer.footer-app .footer-inner {
    display: flex !important;
    width: 100% !important;
}
@media (max-width: 576px) {
    .main-footer.footer-app { min-height: 0 !important; }
}
</style>

<footer class="main-footer footer-app">
    <div class="footer-inner">
        <span class="footer-copy">
            <span class="footer-brand">
                <img src="{{ asset('img/rke.png') }}" alt="" class="footer-brand-icon" />
                <span class="footer-brand-name">PlayRKE</span>
            </span>
            <span class="footer-copy-sep">·</span>
            <span class="footer-year">{{ date('Y') }}</span>
            <span class="footer-version d-none d-sm-inline">· v{{ config('app.version') }}</span>
        </span>

        <nav class="footer-links">
            <a href="{{ route('info.about') }}" class="footer-link">Quiénes Somos</a>
            <a href="{{ route('info.terms') }}" class="footer-link">Términos</a>
            <a href="{{ route('info.privacy') }}" class="footer-link">Privacidad</a>
        </nav>
    </div>
</footer>
