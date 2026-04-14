<!-- Notificaciones Globales -->
@if(auth()->check())
    {{-- Las notificaciones se cargan desde la API en el navbar para garantizar datos frescos --}}
    <script>
        window.notificationsData = {
            unreadCount: {{ $unreadCount ?? 0 }},
            userId: {{ auth()->id() }}
        };
    </script>
@endif
