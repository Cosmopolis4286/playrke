<!-- Notificaciones Globales -->
@if(auth()->check())
    {{-- Las notificaciones se cargan desde la API en el navbar para garantizar datos frescos --}}
    <script nonce="{{ csp_nonce() }}">
        window.notificationsData = {
            unreadCount: {{ $navUnreadCount ?? 0 }},
            userId: {{ auth()->id() }}
        };
    </script>
@endif
