@component('mail::message')
# Un permiso de tu cuenta ha expirado

Hola {{ $userName }},

El permiso individual **{{ $permissionName }}** que se te otorgó el {{ $grantedAt }} expiró el {{ $expiredAt }} y ya no está activo en tu cuenta.

Si todavía necesitas este acceso, contacta a un administrador para que lo renueve.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
