# 📊 TableFilter - Sistema de Filtrado Dinámico con Vue

Sistema modular de tablas con filtrado dinámico, búsqueda y selección múltiple construido en Vue 3, diseñado para integrarse con el sistema existente Blade/Alpine.

## 🎯 Componentes

### 1. **TableFilter.vue**
Componente de filtrado puro que maneja búsqueda y filtros dinámicos.

### 2. **TableWithFilter.vue**
Tabla completa con filtros integrados, selección múltiple y acciones.

### 3. **TableWithFilterBridge.vue**
Puente de integración entre Vue y el sistema Blade/Alpine existente.

### 4. **table-with-filter-vue.blade.php**
Componente Blade que envuelve el sistema Vue para fácil uso.

---

## 🚀 Uso Básico

### Opción 1: Usar el componente Blade (Recomendado)

```blade
<x-shared.table-with-filter-vue
    :title="'Comercializadores'"
    :rows="$pageData['commerces']"
    :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']"
    :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
    :filters="[
        [
            'key' => 'country_name',
            'label' => 'País',
            'placeholder' => 'Filtrar por país',
            'options' => $countries->map(fn($c) => ['value' => $c->name, 'label' => $c->name])->toArray()
        ],
        [
            'key' => 'status',
            'label' => 'Estado',
            'placeholder' => 'Filtrar por estado',
            'options' => [
                ['value' => 'ACTIVE', 'label' => 'Activo'],
                ['value' => 'INACTIVE', 'label' => 'Inactivo']
            ]
        ]
    ]"
    module-id="commerce-form"
    context="commerce"
    table-id="commerce-table"
    search-placeholder="Buscar por nombre, email, teléfono..."
/>
```

### Opción 2: Usar Vue directamente

```vue
<template>
    <TableWithFilter
        title="Mi Tabla"
        :columns="columns"
        :rows="rows"
        :filters="filters"
        @create="handleCreate"
        @edit="handleEdit"
        @delete="handleDelete"
    />
</template>

<script setup>
import TableWithFilter from '@/components/shared/TableWithFilter.vue'

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Email' },
    { key: 'status', label: 'Estado', html: true }
]

const rows = [
    { id: '1', name: 'Juan', email: 'juan@example.com', status: 'ACTIVE' },
    { id: '2', name: 'María', email: 'maria@example.com', status: 'INACTIVE' }
]

const filters = [
    {
        key: 'status',
        label: 'Estado',
        placeholder: 'Filtrar por estado',
        options: [
            { value: 'ACTIVE', label: 'Activo' },
            { value: 'INACTIVE', label: 'Inactivo' }
        ]
    }
]

const handleCreate = () => console.log('Crear')
const handleEdit = (id) => console.log('Editar', id)
const handleDelete = (ids) => console.log('Eliminar', ids)
</script>
```

---

## ⚙️ Props del Componente Blade

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | string | `''` | Título de la tabla |
| `columns` | array | `[]` | Array de nombres de columnas |
| `rows` | Collection | `collect()` | Colección Laravel de filas |
| `rowKeys` | array | `[]` | Keys de las propiedades a mostrar (soporta nested: `User.email`) |
| `filters` | array | `[]` | Configuración de filtros dinámicos |
| `searchPlaceholder` | string | `'Buscar...'` | Placeholder del campo de búsqueda |
| `moduleId` | string | `''` | ID del módulo para integración con formCRUD |
| `context` | string | `''` | Contexto para eventos |
| `tableId` | string | `''` | ID único de la tabla |
| `showCreateButton` | boolean | `true` | Mostrar botón crear |
| `showEditButton` | boolean | `true` | Mostrar botón editar |
| `showDeleteButton` | boolean | `true` | Mostrar botón eliminar |

---

## 🔧 Configuración de Filtros

Los filtros se definen como un array de objetos con la siguiente estructura:

```php
[
    'key' => 'nombre_campo',           // Key del campo en los datos
    'label' => 'Etiqueta',            // Etiqueta visible
    'placeholder' => 'Seleccionar...', // Placeholder del select
    'options' => [                     // Opciones del filtro
        ['value' => 'valor1', 'label' => 'Opción 1'],
        ['value' => 'valor2', 'label' => 'Opción 2']
    ]
]
```

### Ejemplo con datos del backend:

```php
:filters="[
    [
        'key' => 'country',
        'label' => 'País',
        'placeholder' => 'Todos los países',
        'options' => $countries->map(fn($country) => [
            'value' => $country->id,
            'label' => $country->name
        ])->toArray()
    ]
]"
```

---

## 🎨 Características

### ✅ Búsqueda en Tiempo Real
- Debounce configurable (300ms por defecto)
- Búsqueda en todas las columnas
- Indicador visual de búsqueda activa

### ✅ Filtros Dinámicos
- Múltiples filtros simultáneos
- Combinación de búsqueda + filtros
- Pills con filtros activos
- Botón para limpiar todos los filtros

### ✅ Selección Múltiple
- Checkbox para seleccionar todo
- Selección individual por fila
- Indicador de cantidad seleccionada
- Estado indeterminado en checkbox principal

### ✅ Acciones por Contexto
- Crear: Siempre visible
- Editar: Solo con 1 elemento seleccionado
- Eliminar: Con 1 o más elementos seleccionados
- Contador de elementos a eliminar

### ✅ Integración con Sistema Existente
- Compatible con formCRUD
- Eventos personalizados para Alpine.js
- Sincronización con selección de tabla Alpine

---

## 🔗 Integración con formCRUD

El componente emite eventos que se integran automáticamente con el sistema `formCRUD`:

```javascript
// Evento de crear
window.addEventListener('table:create', (e) => {
    if (e.detail.moduleId === 'commerce-form') {
        // Trigger botón create del sistema existente
    }
})

// Evento de editar
window.addEventListener('table:edit', (e) => {
    console.log('Editar ID:', e.detail.id)
})

// Evento de eliminar
window.addEventListener('table:delete', (e) => {
    console.log('Eliminar IDs:', e.detail.ids)
})

// Cambio de selección
window.addEventListener('table:selection-change', (e) => {
    console.log('IDs seleccionados:', e.detail.selectedIds)
})
```

---

## 📝 Ejemplo Completo

```blade
{{-- En tu vista index.blade.php --}}
<x-layouts.layout-auth :title="$title">
    <x-shared.table-with-filter-vue
        :title="$title"
        :rows="$users"
        :columns="['Nombre', 'Email', 'Rol', 'Estado']"
        :row-keys="['name', 'email', 'Role.name', 'status_label']"
        :filters="[
            [
                'key' => 'Role_name',
                'label' => 'Rol',
                'placeholder' => 'Filtrar por rol',
                'options' => $roles->map(fn($r) => [
                    'value' => $r->name,
                    'label' => $r->name
                ])->toArray()
            ],
            [
                'key' => 'status',
                'label' => 'Estado',
                'placeholder' => 'Filtrar por estado',
                'options' => [
                    ['value' => 'ACTIVE', 'label' => 'Activo'],
                    ['value' => 'INACTIVE', 'label' => 'Inactivo'],
                    ['value' => 'BLOCKED', 'label' => 'Bloqueado']
                ]
            ]
        ]"
        module-id="user-form"
        context="user"
        table-id="users-table"
        search-placeholder="Buscar usuarios por nombre o email..."
    />
</x-layouts.layout-auth>
```

---

## 🎯 Ventajas de la Migración

1. **Filtrado Dinámico**: Sin recargar la página
2. **Búsqueda Rápida**: Resultados instantáneos
3. **Mejor UX**: Indicadores visuales claros
4. **Mantenible**: Código modular y reutilizable
5. **Escalable**: Fácil agregar nuevos filtros
6. **Compatible**: Funciona con el sistema existente

---

## 🔄 Migración desde table-with-selection

### Antes (Blade/Alpine):
```blade
<x-shared.table-with-selection
    :title="$title"
    :rows="$commerces"
    :columns="['Nombre', 'País']"
    :row-keys="['name', 'Country.name']"
    module-id="commerce-form"
    context="commerce"
    table-id="commerce-table"
/>
```

### Después (Vue con filtros):
```blade
<x-shared.table-with-filter-vue
    :title="$title"
    :rows="$commerces"
    :columns="['Nombre', 'País']"
    :row-keys="['name', 'Country.name']"
    :filters="[...]"  <!-- ✨ Nueva característica -->
    module-id="commerce-form"
    context="commerce"
    table-id="commerce-table"
    search-placeholder="Buscar..."  <!-- ✨ Nueva característica -->
/>
```

---

## 📦 Estructura de Archivos

```
resources/js/components/shared/
├── TableFilter.vue                 # Componente de filtrado
├── TableWithFilter.vue             # Tabla completa
├── TableWithFilterBridge.vue       # Puente Blade/Vue
└── README-TableFilter.md           # Esta documentación

resources/views/components/shared/
└── table-with-filter-vue.blade.php # Wrapper Blade

resources/js/helpers/vue/
└── registerSharedComponents.ts     # Registro global
```

---

## 🐛 Troubleshooting

### Los filtros no aparecen
- Verifica que `filters` sea un array válido
- Asegúrate de que cada filtro tenga `key`, `label` y `options`

### La búsqueda no funciona
- Confirma que las columnas estén correctamente mapeadas en `columns`
- Verifica que `rowKeys` corresponda con los datos

### Los botones de acción no funcionan
- Asegúrate de que `moduleId` coincida con el sistema formCRUD
- Verifica que los eventos estén siendo escuchados

---

## 🚀 Próximas Mejoras

- [ ] Paginación
- [ ] Ordenamiento por columnas
- [ ] Exportar a CSV/Excel
- [ ] Filtros avanzados (rangos de fecha, etc.)
- [ ] Guardado de filtros favoritos
- [ ] Vista de columnas configurable

---

## 📄 Licencia

Parte del proyecto PlayRKE - Uso interno
