{{-- resources/views/components/footer.blade.php --}}
<footer class="main-footer footer-app">
    <div class="footer-inner">
        <span class="footer-copy">
            <i class="fas fa-copyright footer-copy-icon"></i>
            PlayRKE {{ date('Y') }}
            <span class="footer-version d-none d-sm-inline">· V {{ config('app.version') }}</span>
        </span>

        <span class="footer-divider d-none d-md-inline"></span>

        <nav class="footer-links">
            <a href="{{ route('info.about') }}" class="footer-link">Quiénes Somos</a>
            <a href="{{ route('info.terms') }}" class="footer-link">Términos</a>
            <a href="{{ route('info.privacy') }}" class="footer-link">Privacidad</a>
        </nav>
    </div>
</footer>
