<!-- Notificaciones Globales -->
@if(auth()->check())
    <!-- Datos de notificaciones disponibles globalmente -->
    <script>
        window.notificationsData = {
            unreadCount: {{ $unreadCount ?? 0 }},
            notifications: @json($formattedNotifications ?? []),
            userId: {{ auth()->id() }}
        };
        
        // Disparar evento para notificar al frontend
        document.addEventListener('DOMContentLoaded', function() {
            window.dispatchEvent(new CustomEvent('notifications-loaded', {
                detail: window.notificationsData
            }));
        });
    </script>
@endif
