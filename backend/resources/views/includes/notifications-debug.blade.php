<!-- Debug de notificaciones -->
@if(auth()->check())
    <script>
        // Debug: Verificar que los datos estén disponibles
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DEBUG NOTIFICATIONS ===');
            console.log('window.notificationsData:', window.notificationsData);
            
            // Verificar si hay datos
            if (window.notificationsData) {
                console.log('✅ Datos de notificaciones disponibles:');
                console.log('- Unread count:', window.notificationsData.unreadCount);
                console.log('- Notifications count:', window.notificationsData.notifications.length);
                console.log('- User ID:', window.notificationsData.userId);
                
                // Mostrar notificaciones individuales
                window.notificationsData.notifications.forEach((notif, index) => {
                    console.log(`- Notificación ${index + 1}:`, notif);
                });
            } else {
                console.log('❌ No se encontraron datos de notificaciones');
            }
            
            // Escuchar evento
            window.addEventListener('notifications-loaded', function(event) {
                console.log('🎉 Evento notifications-loaded recibido:', event.detail);
            });
            
            console.log('=== END DEBUG ===');
        });
    </script>
@endif
