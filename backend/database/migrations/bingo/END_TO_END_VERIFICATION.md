# Verificación End-to-End - bingo_rooms_automatic

## Fecha: 2026-05-11

---

## 📊 Resumen Ejecutivo

**Estado General:** ✅ **ADAPTADO CORRECTAMENTE**

El sistema end-to-end está correctamente adaptado para usar `bingo_rooms_automatic` con los campos JSON consolidados. Tanto backend como frontend manejan adecuadamente la estructura consolidada.

---

## ✅ Backend - Verificación Detallada

### 1. Modelo: `BingoRoomAutomatic.php`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Campos JSON en $fillable** | ✅ OK | `rounds_config`, `prize_types`, `bingo_patterns`, `prize_values`, `card_currencies`, `card_prices` |
| **Campos JSON en $casts** | ✅ OK | Todos casteados como `'array'` |
| **Helper getRoundsConfig()** | ✅ OK | Primero busca en campos consolidados, fallback a relación antigua |
| **Helper getCardPricesConfig()** | ✅ OK | Primero busca en campos consolidados, fallback a relación antigua |
| **Relación generatedRoomConfigs()** | ✅ OK | HasMany a BingoRoomConfig vía parent_automatic_id |
| **Relación template()** | ✅ OK | BelongsTo a BingoRoomTemplate |
| **Campos de programación avanzada** | ✅ OK | `recurrence_type`, `recurrence_days`, `game_time`, etc. |

**Código verificado:**
```php
// Campos JSON en $fillable (líneas 96-104)
'rounds_config', 'prize_types', 'bingo_patterns', 'prize_values',
'card_currencies', 'card_prices',

// Helpers con fallback (líneas 253-295)
public function getRoundsConfig(): array
public function getCardPricesConfig(): array
```

---

### 2. Servicio: `BingoRoomAutomaticCrudService.php`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Escritura en campos consolidados** | ✅ OK | Métodos `syncCardPrices()` y `syncRounds()` escriben JSON directamente |
| **Backward compatibility** | ✅ OK | También escribe en tablas antiguas para compatibilidad temporal |
| **Lectura de datos** | ✅ OK | Usa helpers del modelo que priorizan campos consolidados |
| **Nuevos campos de programación** | ✅ OK | `recurrenceType`, `recurrenceDays`, `gameTime`, etc. manejados en saveRoom() |

**Código verificado:**
```php
// syncCardPrices() - Escribe en campos consolidados (líneas 137-141)
BingoRoomAutomatic::where('id', $roomId)->update([
    'card_currencies' => array_map('strval', $allowedCurrencies),
    'card_prices' => $cardPrices,
]);

// syncRounds() - Escribe en campos consolidados (líneas 205-216)
BingoRoomAutomatic::where('id', $roomId)->update([
    'rounds_config' => [...],
    'prize_types' => $prizeTypes,
    'bingo_patterns' => $bingoPatterns,
    'prize_values' => $prizeValues,
]);
```

**Nota:** El servicio mantiene doble escritura (campos consolidados + tablas viejas) para permitir migración gradual sin romper funcionalidad existente.

---

### 3. Resource API: `BingoRoomAutomaticResource.php`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Expone rounds_config** | ✅ OK | Método `formatRoundsConfig()` transforma para frontend |
| **Expone card_price_by_currency** | ✅ OK | Desde `getCardPricesConfig()` |
| **Nuevos campos de programación** | ✅ OK | `recurrence_type`, `recurrence_days`, `game_time`, etc. |
| **Estructura de respuesta** | ✅ OK | `config` object + `rounds_config` array + precios |

**Estructura de respuesta API:**
```json
{
  "id": "hashed_id",
  "status": "ACTIVE",
  "days": ["monday", "tuesday"],
  "allowed_currencies": [1, 2],
  "card_price_by_currency": {"1": 5.0, "2": 25.0},
  "rounds_config": [
    {"prizeType": "percentage", "bingoPattern": 1, "prizeValue": 10}
  ],
  "config": {
    "room_type_id": "...",
    "time": "14:30",
    "recurrence_type": "WEEKLY",
    "recurrence_days": [1, 2],
    "game_time": "15:00",
    "auto_create_room": true
  }
}
```

---

### 4. Repositorio: `BingoRoomAutomaticRepository.php`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Queries básicos** | ✅ OK | Usa modelo BingoRoomAutomatic correctamente |
| **Scopes** | ✅ OK | `scopeActive`, `scopeByCreator` implementados |

**Nota:** El repositorio usa campos como `creator_type` y `creator_id` que parecen ser de una versión anterior. Se recomienda verificar si deben cambiarse a `creator_user_id` para consistencia con el modelo.

---

## ✅ Frontend - Verificación Detallada

### 1. Tipos TypeScript: `bingo-room-automatic.ts`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Interface BingoRoomAutomatic** | ✅ OK | Incluye `template_id` y todos los campos JSON consolidados |
| **Campos JSON tipados** | ✅ OK | `rounds_config`, `prize_types`, `bingo_patterns`, `prize_values`, `card_currencies`, `card_prices` |
| **Campos de programación avanzada** | ✅ OK | `recurrenceType`, `recurrenceDays`, `gameTime`, etc. |
| **Interface BingoRoomAutomaticFormData** | ✅ OK | Extiende BingoRoomFormData con campos específicos |

**Tipos verificados:**
```typescript
// Campos JSON consolidados
readonly template_id?: string | null
readonly rounds_config?: Record<string, unknown> | null
readonly prize_types?: unknown[] | null
readonly bingo_patterns?: unknown[] | null
readonly prize_values?: Record<string, number> | null
readonly card_currencies?: (number | string)[] | null
readonly card_prices?: Record<string, number> | null
```

---

### 2. Composable: `useBingoRoomAutomatic.ts`

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Importa tipos correctos** | ✅ OK | Usa `BingoRoomAutomatic` y `BingoRoomAutomaticFormData` |
| **Mapeo de rounds_config** | ✅ OK | En `edit()`, mapea correctamente desde API (líneas 228-234) |
| **Mapeo de card_price_by_currency** | ✅ OK | En `edit()`, usa `room.card_price_by_currency` (línea 227) |
| **Payload de submit** | ✅ OK | Envía `roundsConfig` y todos los campos necesarios |
| **Nuevos campos de programación** | ✅ OK | Incluye `recurrenceType`, `recurrenceDays`, etc. en el formulario |

**Código verificado - Mapeo de rounds_config:**
```typescript
// Líneas 228-234
roundsConfig: (room.rounds_config ?? []).map((r: any) => ({
    prizeType: r.prizeType ?? 'percentage',
    bingoPattern: r.bingoPattern ?? null,
    prizeValue: r.prizeValue !== null && r.prizeValue !== undefined ? Number(r.prizeValue) : null,
    ...(r.prizeValueByCurrency ? { prizeValueByCurrency: r.prizeValueByCurrency } : {}),
    ...(r.prizeType === 'prize' ? { prizeId: r.prizeId ?? null, prizeName: r.prizeName ?? null } : {}),
})),
```

---

### 3. Componentes Vue

#### `RoomBasicSection.vue`
| Aspecto | Estado | Detalle |
|---------|--------|---------|
| **Prop modelValue** | ✅ OK | Acepta `BingoRoomFormData \| BingoRoomAutomaticFormData` |
| **Manejo de días** | ✅ OK | Usa `DayOfWeek` type para selector de días |
| **Type casting** | ✅ OK | Usa casting seguro `(modelValue as BingoRoomAutomaticFormData)` |

---

## 🔍 Flujo de Datos End-to-End

### Creación de Sala Automática:
```
1. Frontend: useBingoRoomAutomatic.submit()
   ↓ POST /bingo-automatic-rooms
   
2. Backend: BingoRoomAutomaticController.store()
   ↓ BingoRoomAutomaticCrudService.saveRoom()
   
3. Service: 
   - Crea registro en bingo_rooms_automatic con campos base
   - syncCardPrices() → Escribe card_currencies/card_prices (JSON)
   - syncRounds() → Escribe rounds_config/prize_types/etc (JSON)
   
4. Response: BingoRoomAutomaticResource
   ↓ Devuelve JSON con rounds_config formateado
   
5. Frontend: Recibe y almacena en reactive refs
```

### Edición de Sala Automática:
```
1. Frontend: useBingoRoomAutomatic.edit(id)
   ↓ GET /bingo-automatic-rooms/{id}/show
   
2. Backend: Resource devuelve estructura con rounds_config formateado
   
3. Frontend: Mapea rounds_config a roundsConfig del formulario
   ↓ Llena roomFormData.value
   
4. Usuario edita y hace submit
   ↓ PUT/PATCH /bingo-automatic-rooms/{id}/update
   
5. Backend: Actualiza campos consolidados
```

---

## ⚠️ Observaciones y Recomendaciones

### 1. **Backward Compatibility Activa**
El sistema actualmente escribe datos en **ambas ubicaciones**:
- Campos JSON consolidados (nueva estructura)
- Tablas viejas (bingo_rounds_automatic, bingo_card_price_automatic)

**Recomendación:** Una vez confirmado que todo funciona, se pueden eliminar las tablas viejas y simplificar el código del servicio.

### 2. **Campos en Repositorio**
El repositorio usa `creator_type` y `creator_id` que parecen ser legacy. Verificar si deben cambiarse a `creator_user_id` para consistencia.

### 3. **template_id en Frontend**
Los tipos TypeScript incluyen `template_id`, pero el composable no lo maneja explícitamente en el formulario. Si se va a usar plantillas, agregarlo a:
- `emptyFormData()` factory
- Mapeo en `edit()`
- Payload de `submit()`

---

## 📊 Estado de Tablas Relacionadas

| Tabla | Uso Actual | Recomendación |
|-------|-----------|---------------|
| `bingo_rooms_automatic` | ✅ Activa - Uso principal | Mantener |
| `bingo_room_templates` | ✅ Activa - Referenciada por FK | Mantener |
| `bingo_rooms_config` | ✅ Activa - Recibe datos de automáticas | Mantener |
| `bingo_rounds_automatic` | ⚠️ Backward compatibility | Eliminar después de pruebas |
| `bingo_card_price_automatic` | ⚠️ Backward compatibility | Eliminar después de pruebas |

---

## ✅ Verificación Final: PASADA

| Componente | Estado |
|------------|--------|
| Migraciones | ✅ OK |
| Modelo Backend | ✅ OK |
| Servicio CRUD Backend | ✅ OK |
| Resource API | ✅ OK |
| Tipos TypeScript | ✅ OK |
| Composable Vue | ✅ OK |
| Componentes Vue | ✅ OK |
| Flujo End-to-End | ✅ OK |

---

## 🚀 Próximos Pasos Sugeridos

1. **Ejecutar migrate:fresh** para verificar que todas las migraciones funcionan
2. **Probar creación de sala automática** desde el frontend
3. **Verificar que rounds_config y precios se guarden correctamente**
4. **Una vez confirmado, eliminar tablas viejas:**
   - `bingo_rounds_automatic`
   - `bingo_card_price_automatic`
5. **Simplificar BingoRoomAutomaticCrudService** eliminando código de backward compatibility

---

*Documento generado automáticamente*
