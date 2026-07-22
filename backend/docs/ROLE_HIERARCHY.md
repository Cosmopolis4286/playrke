# Jerarquía de roles y entidades de negocio

Mapa de referencia de cómo se relacionan realmente los 9 roles del sistema
(Spatie `roles`) con las entidades de negocio (`commerces`, `sellers`,
`customers`, jugadores) y el flujo de venta hacia el **player** como cliente
final. Verificado leyendo modelos, migraciones y servicios de dominio — no es
un diseño aspiracional, describe el código tal como existe.

## 1. Cadena real de entidades

```
Commerce (commerces.user_id)
   └── Seller.manager_user_id → Commerce.user_id      [único vínculo padre-hijo en BD]
          └── ExternalPlayer.seller_user_id  → User.id (del seller)
          └── ExternalPlayer.creator_user_id → User.id (del commerce, resuelto vía manager_user_id)
          └── ExternalPlayer.room_user_id    → BingoRoomUser.id (compartido: todos los
                                                jugadores externos de un mismo commerce/seller
                                                en una sala acumulan cartones bajo el MISMO
                                                BingoRoomUser, el del commerce)
```

- `Commerce::sellers()` (`app/Models/Roles/Commerce.php`) y
  `Seller::managerCommerce()` (`app/Models/Roles/Seller.php`) son la misma
  relación en ambos sentidos, vía `sellers.manager_user_id = commerces.user_id`.
  **No existe** columna `commerce_id` en `sellers`.

`Player` (usuario con cuenta propia) y `Customer` (integración API B2B) **no
cuelgan de esta cadena** — son ramas paralelas e independientes, ver §2 y §3.

## 2. Player vs ExternalPlayer — no son intercambiables

Dos flujos de negocio distintos, diferenciados por `creator.type` en
`BingoCardGenerateService`:

- **Player interno** (`User` + rol `player` + `Wallet` propia): compra vía
  `BingoCardGenerateService::playerBuyFromGameRooms()`. Es dueño único de su
  `BingoRoomUser`. No crea ni toca `ExternalPlayer`.
- **Player externo** (sin cuenta, vendido por un seller/commerce): compra vía
  `BingoCardGenerateService::generate()`. El sistema crea/reutiliza un
  `ExternalPlayer` (ficha comercial: nombre, teléfono, `link` de acceso
  temporal de 7 días) vinculado al `BingoRoomUser` del **commerce**, no del
  jugador — la venta se contabiliza bajo el commerce/seller que la hizo.

## 3. Customer

Entidad de integración B2B vía API (`customer_key`, `api_route` en
`app/Models/Roles/Customer.php`), sin `commerce_id` ni `seller_id`, sin
manager. No se encontró un flujo de compra de bingo equivalente al de
`player`/`ExternalPlayer` en el dominio `Domains/Games/Bingo` — sus permisos
de juego existen en el seeder, pero no hay servicio que lo use como comprador
directo. Se documenta como está, sin inventar funcionalidad.

## 4. Subsidiary

No tiene modelo ni tabla propia. Es únicamente un rol Spatie
(`RoleSeeder.php`) con los mismos permisos que `commerce`
(`RolePermissionSeeder.php`) — funciona como un alias de permisos, no como un
nodo de la jerarquía de datos. No crear una tabla `subsidiaries` a menos que
el negocio decida modelar sucursales como entidad real.

## 5. Tabla de roles

| Rol | Guard | Entidad de negocio | Notas |
|---|---|---|---|
| `superadmin` | web | ninguna | acceso total, todos los permisos |
| `admin` | web | ninguna | administración de plataforma |
| `commerce` | web | `Commerce` | dueño de salas, gestiona sellers |
| `subsidiary` | web | ninguna (alias de `commerce`) | ver §4 |
| `seller` | web | `Seller` | vende cartones, `manager_user_id → Commerce` |
| `customer` | web | `Customer` | integración API B2B, ver §3 |
| `player` | web | `User` + `Wallet` | jugador con cuenta propia, cliente final |
| `treasurer` | web | `Treasurer` | finanzas a nivel de plataforma, sin commerce asociado |
| `provider` | api | ninguna | integración externa vía API, sin jerarquía de negocio |

## 6. Comisiones — fuente de verdad

El reparto real de una venta de cartón ocurre en
`RoomMetricsService::updateRoomMetricsWithCommissions()`
(`app/Domains/Games/Bingo/Services/Room/Metrics/RoomMetricsService.php`),
con tasas configurables por sala en `bingo_rooms_config`:

```
saleAmount (precio del cartón × cantidad)
 ├─ seller_commission → Seller.commission o SellerRoomLimits override
 │                       (CommissionCalculationService::calculateSellerCommission)
 ├─ room_commission   → % de bingo_rooms_config, va al commerce dueño de la sala
 ├─ rke_commission    → % de bingo_rooms_config, acreditado a PlatformWalletService
 └─ resto             → prize_pool / full_house_prize de la sala
```

`BingoCreator.creator_commission` es una copia de solo-sincronización de
`bingo_rooms_config.room_commission` (ambos se escriben al crear/editar una
sala en `BingoRoomCrudService`), útil para listar en UI, pero **no** es lo
que se lee en el momento del cálculo de venta.

`CommissionCalculationService` solo implementa `calculateSellerCommission()`;
los métodos que antes calculaban comisión de sala/RKE con tasas hardcodeadas
(5%/10%) fueron eliminados por ser código muerto que nunca se invocaba desde
el flujo real y podía confundir sobre cuál es la fuente de verdad.

## 7. Inconsistencias corregidas

- `Commerce::sellers()` usaba `hasMany(Seller::class, 'commerce_id')`, columna
  inexistente. Corregido a `hasMany(Seller::class, 'manager_user_id', 'user_id')`.
  Sin consumidores previos (relación inalcanzable), corrección sin riesgo.
- `CommissionCalculationService::calculateRoomCommission()`,
  `calculateRkeCommission()`, `calculateAllCommissions()` y
  `getCommissionRates()` eliminados (código muerto, tasas hardcodeadas
  divergentes de `RoomMetricsService`, sin consumidores ni presencia en la
  interfaz del contrato).
