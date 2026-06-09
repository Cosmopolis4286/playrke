{{-- resources/views/components/footer.blade.php --}}
<footer class="main-footer footer-app">
    <div class="footer-inner">
        <span class="footer-copy">
            <span class="footer-brand">
                <i class="fas fa-dice footer-brand-icon"></i>
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
