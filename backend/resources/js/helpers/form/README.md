# 📋 Sistema de Formularios CRUD

Sistema modular y reutilizable para manejo de operaciones CRUD con separación clara de responsabilidades.

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    Capa de Utilidades                       │
│                   (formHelpers.ts)                          │
│  - fillFormFromObject()    [Llenar formulario desde objeto]│
│  - clearValidationErrors() [Limpiar errores visuales]      │
│  - resetFormAndContainers()[Reset completo del formulario] │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ usa
                            │
┌─────────────────────────────────────────────────────────────┐
│              Capa de Lógica CRUD Global                     │
│                 (formCrudInit.ts)                           │
│  - formCRUD()              [Orquestador principal]          │
│  - registerTableListener() [Registro dinámico de tablas]   │
│  - submitForm()            [Validación y envío]             │
│  - resetForm()             [Reset CRUD-específico]          │
│  - prepareForm()           [Preparación pre-envío]          │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ configura
                            │
┌─────────────────────────────────────────────────────────────┐
│           Capa de Carga de Módulos Dinámicos                │
│              (formCrudLoadModule.ts)                        │
│  - initFormCrud()          [Inicializador principal]        │
│  - formCrudExports         [Getters dinámicos del módulo]   │
│  - setupListeners()        [Listeners globales del módulo]  │
│  - setupTableListener()    [Registro de tabla automático]   │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ implementa
                            │
┌─────────────────────────────────────────────────────────────┐
│                  Módulos Específicos                        │
│              (commerceForm.ts, etc.)                        │
│  - insertInfo()            [Lógica de inserción específica] │
│  - initListeners()         [Listeners del módulo]           │
│  - route()                 [Rutas específicas]              │
│  - otherCasesMap           [Acciones personalizadas]        │
└─────────────────────────────────────────────────────────────┘
```

## 📦 Responsabilidades por Archivo

### `formHelpers.ts` - Utilidades Puras
**Propósito**: Funciones reutilizables sin lógica de negocio

**Funciones**:
- `fillFormFromObject<T>(data, root?)`: Llena formulario desde objeto (mapea `-` a `_`)
- `clearValidationErrors(formId)`: Remueve clases `.is-invalid` y `.invalid-feedback`
- `resetFormAndContainers(formId, containerIds?)`: Reset completo + ocultar contenedores + limpiar Select2

**Características**:
- ✅ Sin dependencias de módulos CRUD
- ✅ 100% reutilizable en cualquier contexto
- ✅ No tiene estado interno
- ✅ Tipado genérico

---

### `formCrudInit.ts` - Lógica CRUD Global
**Propósito**: Orquestación de operaciones CRUD reutilizables

**Funciones Principales**:

#### `registerTableListener(tableId: string)`
Registra dinámicamente un listener para selección de tabla.
- Evita hard-coding del nombre de tabla
- Usa importación dinámica para evitar dependencias circulares
- Mantiene estado en `tableSelectionStore.encryptId`

#### `formCRUD(elem: HTMLElement)`
Orquestador principal de acciones CRUD.
```typescript
// Acciones soportadas:
- 'create'        → Resetear form + insertInfo('create') + POST
- 'edit'          → Resetear form + getInfo() + insertInfo('edit') + PUT
- 'delete'        → handleOtherCases('delete')
- 'changeStatus' → handleOtherCases('changeStatus')
- custom          → handleOtherCases(action) desde otherCasesMap
```

#### `submitForm(formId: string, fields?: string[])`
Validación y envío de formulario.
- Auto-detecta campos `[required]` si no se pasan
- Validación visual con scroll al primer error
- Limpia validaciones antes de enviar
- Inicia loader con `ajaxStart()`

#### `resetForm(form: HTMLFormElement)`
Reset CRUD-específico que:
1. Usa `resetFormAndContainers()` (utilidad pura)
2. Quita `readonly` de inputs (específico de CRUD edit→create)

#### `prepareForm(...)`
Configura formulario antes de envío:
- Establece `action` y `method`
- Añade input hidden `_method` para PUT/PATCH/DELETE
- Inyecta CSRF token si no existe
- Actualiza título del modal

**Mejoras Implementadas**:
- ✅ Mensajes dinámicos por entidad (usa `formTitleSuffix`)
- ✅ Soporte para múltiples estados (DELETED, ACTIVE, INACTIVE, BLOCKED)
- ✅ Estado encapsulado en `tableSelectionStore`
- ✅ Sin hard-coding de nombres de tabla

---

### `formCrudLoadModule.ts` - Cargador de Módulos
**Propósito**: Carga dinámica de módulos CRUD y configuración inicial

**Funciones**:

#### `initFormCrud()`
Inicializa el módulo CRUD dinámicamente:
1. Obtiene `moduleFile` desde `appData`
2. Carga el módulo con `loadModule()`
3. Extrae configuración del módulo
4. Auto-genera `tableId` si no se provee
5. Registra listeners globales
6. Registra listener de tabla automáticamente

#### `formCrudExports`
Getters dinámicos para acceder a configuración del módulo:
```typescript
formCrudExports.moduleId        // ej: 'commerce-form'
formCrudExports.formTitleSuffix // ej: 'Comercio'
formCrudExports.insertInfo      // función del módulo
formCrudExports.route           // función de rutas
formCrudExports.requiredFields  // campos obligatorios
formCrudExports.otherCasesMap   // acciones custom
formCrudExports.modal           // referencia al modal
formCrudExports.tableId         // ID de la tabla
```

**Mejoras Implementadas**:
- ✅ Sin dependencia circular (usa importación dinámica)
- ✅ Auto-genera `tableId` desde `moduleId`
- ✅ Registra listener de tabla automáticamente
- ✅ Evita doble carga con flag `_module`

---

## 🔄 Flujo de Ejecución

### Flujo de Creación (Create)
```
Usuario click "Crear"
    ↓
formCRUD(elem) detecta action='create'
    ↓
initForm() → resetForm() → resetFormAndContainers()
    ↓
safeInsertInfo('create') → commerceForm.insertInfo('create')
    ↓
prepareForm(form, 'create', title, 'POST')
    ↓
Usuario llena formulario y click "Confirmar"
    ↓
submitForm(formId, requiredFields)
    ↓
validateRequiredFields() → Si válido: form.submit()
```

### Flujo de Edición (Edit)
```
Usuario selecciona fila → Alpine.js actualiza 'selected'
    ↓
registerTableListener() → tableSelectionStore.encryptId = selectedId
    ↓
Usuario click "Editar"
    ↓
formCRUD(elem) detecta action='edit'
    ↓
initForm() → resetForm()
    ↓
getInfo(tableSelectionStore.encryptId) → axios.get(route('show'))
    ↓
safeInsertInfo('edit', payload) → commerceForm.insertInfo('edit', data)
    ↓
fillFormFromObject(commerce) + valores preseleccionados
    ↓
prepareForm(form, 'update', title, 'PUT', encryptId)
    ↓
Usuario modifica y click "Confirmar"
    ↓
submitForm() → validateRequiredFields() → form.submit()
```

### Flujo de Eliminación (Change Status)
```
Usuario selecciona fila → tableSelectionStore.encryptId = selectedId
    ↓
Usuario click "Eliminar"
    ↓
formCRUD(elem) detecta action='changeStatus' con data-status='DELETED'
    ↓
handleOtherCases('changeStatus', encryptId, elem)
    ↓
getStatusChangeMessages(status, entityName) → mensajes dinámicos
    ↓
showToastConfirmAction("¿Deseas eliminar este comercio?")
    ↓
Si confirma: axios.put(route('changeStatus'), { status: 'DELETED' })
    ↓
showToast('Acción completada') → reload()
```

---

## 🎯 Ventajas de la Arquitectura

### ✅ Separación de Responsabilidades
- **Utilidades puras** sin dependencias (formHelpers.ts)
- **Lógica CRUD** reutilizable (formCrudInit.ts)
- **Cargador dinámico** sin circular deps (formCrudLoadModule.ts)
- **Módulos específicos** con lógica de dominio (commerceForm.ts)

### ✅ Reutilización
- `formHelpers` usado por cualquier módulo
- `formCrudInit` funciona con cualquier entidad
- Mensajes dinámicos basados en `formTitleSuffix`
- Sistema de plugins con `otherCasesMap`

### ✅ Mantenibilidad
- Una sola fuente de verdad para cada responsabilidad
- Sin duplicación de código
- Fácil testing unitario
- Tipado fuerte con TypeScript

### ✅ Extensibilidad
- Nuevos módulos solo implementan `ModuleType`
- Acciones custom via `otherCasesMap`
- Hooks para validaciones personalizadas
- Listeners de tabla configurables

---

## 🔧 Cómo Crear un Nuevo Módulo CRUD

### 1. Crear archivo del módulo
```typescript
// resources/js/modules/users/roles/admin/seller/form/sellerForm.ts

import { formCRUD } from '@/helpers/form/formCrudInit';
import { fillFormFromObject } from '@/helpers/form/formHelpers';
import { route } from '@/routes/sellerRoutes';
import { SweetAlertIcon } from 'sweetalert2';

export { route };

export const formTitleSuffix = 'Vendedor';
export const moduleId = 'seller-form';
export const requiredFields: string[] = ['name', 'email', 'phone'];

export const otherCasesMap: Record<string, {
    routeName: string;
    text: string;
    icon: SweetAlertIcon;
    confirmButtonText: string;
    method?: 'PUT' | 'DELETE';
}> = {
    '5': {
        routeName: 'resetPassword',
        text: 'Resetear contraseña de este vendedor',
        icon: 'warning',
        confirmButtonText: 'Sí, resetear',
        method: 'PUT',
    },
};

export function insertInfo(action: 'create' | 'edit', data: any = null): void {
    if (action === 'edit' && data) {
        const { seller } = data;
        fillFormFromObject(seller);
    }

    const modal = document.getElementById(`modal-${moduleId}`);
    $(modal).modal('show');
}

export function initListeners(): void {
    document.addEventListener('click', async ({ target }) => {
        const element = (target as HTMLElement | null)?.closest<HTMLElement>(`.btn-${moduleId}`);
        if (!element) return;

        try {
            await formCRUD(element);
        } catch (e) {
            console.error('Error en formCRUD:', e);
        }
    });
}
```

### 2. Configurar en Blade
```blade
{{-- resources/views/modules/users/roles/admin/seller/index.blade.php --}}
<x-layouts.layout-auth :title="$title">
    <x-shared.table-with-selection
        :title="$title"
        :rows="$pageData['sellers']"
        module-id="seller-form"
        table-id="seller-table"
    />

    <x-shared.modal id="seller-form" title="Vendedor" form-view="modules.users.roles.admin.seller.form" />

    @once
        @push('stack-scripts')
            <script type="application/json" id="page-data">
                {!! json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endpush
    @endonce
</x-layouts.layout-auth>
```

### 3. Configurar en Controller
```php
// app/Http/Controllers/User/Roles/SellerController.php

public function index()
{
    $pageData = [
        'sellers' => Seller::with('user')->get(),
        'moduleFile' => 'modules/users/roles/admin/seller/form/sellerForm',
        'tableId' => 'seller-table', // Opcional, se auto-genera si no se pasa
    ];

    return view('modules.users.roles.admin.seller.index', [
        'title' => 'Vendedores',
        'pageData' => $pageData,
    ]);
}
```

¡Listo! El sistema automáticamente:
- ✅ Cargará el módulo
- ✅ Registrará el listener de tabla
- ✅ Configurará las acciones CRUD
- ✅ Aplicará validaciones
- ✅ Manejará errores

---

## 📚 Ejemplos de Uso

### Limpiar errores de validación manualmente
```typescript
import { clearValidationErrors } from '@/helpers/form/formHelpers';

clearValidationErrors('my-form-id');
```

### Llenar formulario desde objeto
```typescript
import { fillFormFromObject } from '@/helpers/form/formHelpers';

const userData = {
    first_name: 'John',  // Llenará input con id="first-name"
    last_name: 'Doe',    // Llenará input con id="last-name"
    email: 'john@example.com'
};

fillFormFromObject(userData);
```

### Reset completo con contenedores
```typescript
import { resetFormAndContainers } from '@/helpers/form/formHelpers';

resetFormAndContainers('commerce-form', ['container-currency', 'container-time-zone']);
```

### Registrar listener de tabla personalizado
```typescript
import { registerTableListener } from '@/helpers/form/formCrudInit';

// En caso de necesitar registrar manualmente
registerTableListener('custom-table-id');
```

---

## 🐛 Debugging

### Ver estado actual del CRUD
```typescript
// En consola del navegador
import('./helpers/form/formCrudLoadModule').then(({ formCrudExports }) => {
    console.log('Module ID:', formCrudExports.moduleId);
    console.log('Table ID:', formCrudExports.tableId);
    console.log('Required Fields:', formCrudExports.requiredFields);
    console.log('Other Cases:', formCrudExports.otherCasesMap);
});
```

### Verificar campos requeridos antes de submit
Activa modo DEV en Vite para ver logs:
```typescript
// En modo DEV verás:
📋 Campos requeridos a validar: (7) ['name', 'country', 'time-zone', ...]
```

---

## 🔄 Migraciones desde sistema antiguo

Si tienes módulos antiguos, simplemente:

1. Elimina `clearValidationErrors` local y usa el de `formHelpers`
2. Elimina `resetFormAndContainers` local y usa el de `formHelpers`
3. Cambia hard-coded table listeners por `registerTableListener()`
4. Adapta mensajes hard-coded a usar `formTitleSuffix`

---

## 📝 Convenciones

- `moduleId`: siempre termina en `-form` (ej: `commerce-form`)
- `tableId`: siempre termina en `-table` (ej: `commerce-table`)
- `formTitleSuffix`: singular, capitalizado (ej: `Comercio`, `Vendedor`)
- IDs HTML: usar kebab-case (`first-name`, `time-zone`)
- Propiedades objeto: usar snake_case (`first_name`, `time_zone_id`)
- Funciones exportadas: camelCase (`insertInfo`, `initListeners`)
