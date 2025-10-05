# API Endpoints Requeridos - Backend Laravel

Este documento lista todos los endpoints de API que el frontend Nuxt necesita para funcionar correctamente.

## 📋 Tabla de Contenidos

- [Autenticación](#autenticación)
- [Usuario](#usuario)
- [Bingo](#bingo)
- [Billetera](#billetera)
- [Admin - Dashboard](#admin---dashboard)
- [Admin - Customers](#admin---customers)
- [Admin - Treasurers](#admin---tesoreros)
- [Admin - Sellers](#admin---vendedores)
- [Admin - Rooms](#admin---salas)
- [Admin - Transactions](#admin---transacciones)
- [Admin - Reports](#admin---reportes)
- [Admin - Settings](#admin---configuración)
- [WebSocket Events](#websocket-events)

---

## Autenticación

### GET `/sanctum/csrf-cookie`
**Descripción**: Obtiene el token CSRF para autenticación stateful
**Auth**: No requerida
**Response**: Cookie con token CSRF

### POST `/register`
**Descripción**: Registra un nuevo usuario
**Auth**: No requerida
**Body**:
```json
{
  "first_name": "string",
  "last_name": "string",
  "username": "string",
  "email": "string",
  "password": "string",
  "password_confirmation": "string"
}
```
**Response**: Usuario creado

### POST `/login`
**Descripción**: Inicia sesión
**Auth**: No requerida
**Body**:
```json
{
  "login": "string",  // email o username
  "password": "string",
  "remember": "boolean"
}
```
**Response**: Usuario autenticado

### POST `/logout`
**Descripción**: Cierra sesión
**Auth**: Requerida
**Response**: 204 No Content

### POST `/forgot-password`
**Descripción**: Solicita reset de contraseña
**Auth**: No requerida
**Body**:
```json
{
  "email": "string"
}
```
**Response**: Mensaje de éxito

---

## Usuario

### GET `/api/user`
**Descripción**: Obtiene usuario autenticado
**Auth**: Requerida
**Response**:
```json
{
  "id": "string",
  "username": "string",
  "first_name": "string",
  "last_name": "string",
  "email": "string",
  "roles": ["string"],
  "permissions": ["string"],
  "avatar": "string|null"
}
```

### PUT `/api/user/profile`
**Descripción**: Actualiza perfil del usuario
**Auth**: Requerida
**Body**:
```json
{
  "first_name": "string",
  "last_name": "string",
  "email": "string"
}
```

### PUT `/api/user/password`
**Descripción**: Actualiza contraseña
**Auth**: Requerida
**Body**:
```json
{
  "current_password": "string",
  "password": "string",
  "password_confirmation": "string"
}
```

### POST `/api/user/avatar`
**Descripción**: Sube avatar del usuario
**Auth**: Requerida
**Body**: FormData con archivo
**Response**: URL del avatar

### PUT `/api/user/preferences`
**Descripción**: Actualiza preferencias del usuario
**Auth**: Requerida
**Body**:
```json
{
  "notifications": "boolean",
  "sound": "boolean",
  "auto_mark": "boolean",
  "language": "string"
}
```

---

## Bingo

### GET `/api/bingo/rooms`
**Descripción**: Lista salas de bingo disponibles
**Auth**: Requerida
**Query Params**:
- `currency`: string (opcional)
- `type`: string (opcional)
- `status`: string (opcional)
**Response**:
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "type": "string",
      "currency": "string",
      "card_price": "number",
      "prize_pool": "number",
      "current_players": "number",
      "max_players": "number",
      "status": "string",
      "next_game_at": "datetime|null"
    }
  ]
}
```

### GET `/api/bingo/rooms/{id}`
**Descripción**: Obtiene detalles de una sala
**Auth**: Requerida
**Response**: Objeto de sala completo

### POST `/api/bingo/rooms/{id}/purchase`
**Descripción**: Compra cartones en una sala
**Auth**: Requerida
**Body**:
```json
{
  "quantity": "number"
}
```
**Response**:
```json
{
  "cards": [
    {
      "id": "string",
      "numbers": [[...], [...], [...], [...], [...]]
    }
  ],
  "total_cost": "number"
}
```

### GET `/api/bingo/rooms/{id}/my-cards`
**Descripción**: Obtiene cartones del jugador en una sala
**Auth**: Requerida
**Response**: Array de cartones

### GET `/api/bingo/rooms/{id}/draw`
**Descripción**: Obtiene el sorteo actual
**Auth**: Requerida
**Response**:
```json
{
  "id": "string",
  "room_id": "string",
  "status": "string",
  "started_at": "datetime",
  "ended_at": "datetime|null"
}
```

### GET `/api/bingo/rooms/{id}/called-numbers`
**Descripción**: Obtiene números cantados en el juego actual
**Auth**: Requerida
**Response**:
```json
{
  "numbers": [1, 5, 12, 23, ...],
  "current_number": "number|null"
}
```

### POST `/api/bingo/rooms/{id}/check-winner`
**Descripción**: Verifica si el jugador tiene BINGO
**Auth**: Requerida
**Body**:
```json
{
  "card_id": "string"
}
```
**Response**:
```json
{
  "is_winner": "boolean",
  "pattern": "string|null",
  "prize": "number|null"
}
```

### GET `/api/bingo/rooms/{id}/winners`
**Descripción**: Obtiene ganadores de la sala
**Auth**: Requerida
**Response**: Array de ganadores

### GET `/api/bingo/history`
**Descripción**: Historial de juegos del jugador
**Auth**: Requerida
**Query Params**:
- `page`: number
- `per_page`: number
- `date_from`: date (opcional)
- `date_to`: date (opcional)
- `status`: string (opcional)
**Response**: Paginación con historial

### POST `/api/bingo/notify-prize`
**Descripción**: Notifica que se ganó un premio
**Auth**: Requerida
**Body**:
```json
{
  "draw_id": "string",
  "card_id": "string"
}
```

---

## Billetera

### GET `/api/wallets`
**Descripción**: Obtiene billeteras del usuario
**Auth**: Requerida
**Response**:
```json
{
  "data": [
    {
      "id": "string",
      "currency": "string",
      "balance": "number",
      "is_active": "boolean"
    }
  ]
}
```

### GET `/api/wallets/{currency}`
**Descripción**: Obtiene billetera específica
**Auth**: Requerida
**Response**: Objeto de billetera

### GET `/api/wallets/{currency}/balance`
**Descripción**: Obtiene balance de una billetera
**Auth**: Requerida
**Response**:
```json
{
  "balance": "number",
  "currency": "string"
}
```

### POST `/api/wallets/deposit`
**Descripción**: Solicita depósito
**Auth**: Requerida
**Body**:
```json
{
  "currency": "string",
  "amount": "number",
  "payment_method": "string",
  "reference": "string|null"
}
```

### POST `/api/wallets/withdraw`
**Descripción**: Solicita retiro
**Auth**: Requerida
**Body**:
```json
{
  "currency": "string",
  "amount": "number",
  "payment_method": "string",
  "account_info": "string"
}
```

### POST `/api/wallets/transfer`
**Descripción**: Transfiere entre billeteras
**Auth**: Requerida
**Body**:
```json
{
  "from_currency": "string",
  "to_currency": "string",
  "amount": "number"
}
```

### GET `/api/wallets/transactions`
**Descripción**: Historial de transacciones
**Auth**: Requerida
**Query Params**:
- `page`: number
- `per_page`: number
- `type`: string (opcional)
- `status`: string (opcional)
**Response**: Paginación con transacciones

---

## Admin - Dashboard

### GET `/api/admin/dashboard/stats`
**Descripción**: Estadísticas del dashboard
**Auth**: Requerida (role: admin)
**Response**:
```json
{
  "total_users": "number",
  "active_rooms": "number",
  "total_transactions": "number",
  "online_players": "number"
}
```

---

## Admin - Customers

### GET `/api/admin/customers`
**Descripción**: Lista clientes
**Auth**: Requerida (role: admin)
**Query Params**:
- `page`: number
- `per_page`: number
- `search`: string (opcional)
- `status`: string (opcional)
- `country`: string (opcional)
**Response**: Paginación con clientes

### GET `/api/admin/customers/{id}`
**Descripción**: Detalle de cliente
**Auth**: Requerida (role: admin)

### POST `/api/admin/customers`
**Descripción**: Crear cliente
**Auth**: Requerida (role: admin)
**Body**:
```json
{
  "first_name": "string",
  "last_name": "string",
  "username": "string",
  "email": "string",
  "password": "string",
  "country": "string",
  "status": "string"
}
```

### PUT `/api/admin/customers/{id}`
**Descripción**: Actualizar cliente
**Auth**: Requerida (role: admin)

### DELETE `/api/admin/customers/{id}`
**Descripción**: Eliminar cliente
**Auth**: Requerida (role: admin)

---

## Admin - Tesoreros

### GET `/api/admin/treasurers`
**Descripción**: Lista tesoreros
**Auth**: Requerida (role: admin)
**Query Params**: Similar a customers
**Response**: Paginación con tesoreros

### POST `/api/admin/treasurers`
**Body**:
```json
{
  "first_name": "string",
  "last_name": "string",
  "username": "string",
  "email": "string",
  "country": "string",
  "currency": "string",
  "commission": "number",
  "status": "string"
}
```

### PUT `/api/admin/treasurers/{id}`
### DELETE `/api/admin/treasurers/{id}`

---

## Admin - Vendedores

### GET `/api/admin/sellers`
**Query Params**: Similar a customers

### POST `/api/admin/sellers`
**Body**:
```json
{
  "first_name": "string",
  "last_name": "string",
  "username": "string",
  "email": "string",
  "commission": "number",
  "status": "string"
}
```

### PUT `/api/admin/sellers/{id}`
### DELETE `/api/admin/sellers/{id}`

---

## Admin - Salas

### GET `/api/admin/rooms`
**Query Params**:
- `page`, `per_page`, `search`
- `status`: string (active, waiting, playing, finished)
- `type`: string (normal, fast, vip, jackpot)
- `currency`: string

### POST `/api/admin/rooms`
**Body**:
```json
{
  "name": "string",
  "type": "string",
  "currency": "string",
  "card_price": "number",
  "prize_pool": "number",
  "min_players": "number",
  "max_players": "number",
  "description": "string|null"
}
```

### PUT `/api/admin/rooms/{id}`
### DELETE `/api/admin/rooms/{id}`

### POST `/api/admin/rooms/{id}/start`
**Descripción**: Inicia el juego en una sala

---

## Admin - Transacciones

### GET `/api/admin/transactions`
**Query Params**:
- `page`, `per_page`, `search`
- `type`: string (deposit, withdrawal, transfer, purchase, prize)
- `status`: string (pending, approved, rejected, completed)
- `date_from`: date
- `date_to`: date

### GET `/api/admin/transactions/{id}`

### POST `/api/admin/transactions/{id}/approve`
**Descripción**: Aprueba una transacción

### POST `/api/admin/transactions/{id}/reject`
**Body**:
```json
{
  "reason": "string"
}
```

---

## Admin - Reportes

### GET `/api/admin/reports`
**Query Params**:
- `type`: string (revenue, players, games, transactions)
- `period`: string (today, week, month, year, custom)
- `start_date`: date (si period=custom)
- `end_date`: date (si period=custom)

**Response**:
```json
{
  "totalRevenue": "number",
  "revenueChange": "number",
  "activePlayers": "number",
  "playersChange": "number",
  "totalGames": "number",
  "gamesChange": "number",
  "totalTransactions": "number",
  "transactionVolume": "number",
  "revenueChart": [12000, 15000, ...],
  "playersChart": [320, 450, ...],
  "chartLabels": ["Lun", "Mar", ...],
  "topPlayers": [...],
  "topRooms": [...]
}
```

---

## Admin - Configuración

### GET `/api/admin/settings`
**Response**: Objeto con todas las configuraciones

### PUT `/api/admin/settings/{section}`
**Descripción**: Actualiza sección de configuración
**Params**: section (general, game, payment, email, security)
**Body**: Objeto con configuración de esa sección

---

## WebSocket Events

### Canal Público: `bingo.room.{roomId}`
**Eventos**:
- `NumberCalled`: Nuevo número cantado
  ```json
  { "number": 42 }
  ```
- `GameStarted`: Juego iniciado
  ```json
  { "room": {...}, "draw_id": "string" }
  ```
- `GameEnded`: Juego finalizado
  ```json
  { "winners": [...] }
  ```
- `WinnerDeclared`: Ganador declarado
  ```json
  { "winner": {...}, "prize": 1000 }
  ```

### Canal de Presencia: `presence-bingo.room.{roomId}`
**Eventos**:
- `here`: Usuarios actualmente en sala
- `joining`: Usuario se une
- `leaving`: Usuario sale

### Canal Privado: `private-App.Models.User.{userId}`
**Eventos**:
- `.notification`: Notificación personal
  ```json
  {
    "title": "string",
    "message": "string",
    "type": "info|success|warning|error"
  }
  ```

---

## 🔒 Autenticación y Autorización

### Método de Autenticación
- **Laravel Sanctum Stateful** (cookies)
- CSRF token requerido (obtener vía `/sanctum/csrf-cookie`)
- Credenciales incluidas en todas las peticiones

### Headers Requeridos
```
Accept: application/json
Content-Type: application/json
X-CSRF-TOKEN: {token} (en mutaciones)
```

### Códigos de Respuesta
- `200`: OK
- `201`: Created
- `204`: No Content
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

### Formato de Errores de Validación
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## 📝 Notas Importantes

1. **Paginación**: Usar formato Laravel estándar con `current_page`, `last_page`, `per_page`, `total`, `from`, `to`, `data`

2. **Fechas**: Formato ISO 8601 (YYYY-MM-DD HH:mm:ss)

3. **Monedas**: Usar códigos ISO (USD, VES, BRL, USDT)

4. **CORS**: Configurar `supports_credentials: true` en Laravel

5. **Rate Limiting**: Implementar en rutas sensibles (login, register)

6. **Validación**: Siempre validar en el backend, nunca confiar solo en validación del frontend
