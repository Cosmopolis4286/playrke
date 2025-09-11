# PlayRKE Frontend - Sistema de Diseño Moderno

## 🎨 Descripción

Este directorio contiene el nuevo sistema frontend para PlayRKE, diseñado para reemplazar AdminLTE y Bootstrap con una experiencia moderna, interactiva y premium.

## 🚀 Tecnologías Utilizadas

- **Vue 3** con Composition API
- **TypeScript** para tipado estático
- **Inertia.js** para migración gradual de Blade a Vue
- **TailwindCSS** como base del sistema de diseño
- **Vite** para bundling y desarrollo
- **Heroicons** para iconografía
- **CSS Custom Properties** para temas dinámicos

## 📁 Estructura del Proyecto

```
frontend/
├── components/          # Componentes reutilizables
│   ├── ui/             # Componentes base del sistema de diseño
│   │   ├── Button.vue  # Botón interactivo con efectos
│   │   ├── Card.vue    # Tarjetas con variantes
│   │   ├── Input.vue   # Campos de entrada avanzados
│   │   └── Modal.vue   # Modales con transiciones
│   ├── layout/         # Componentes de layout
│   │   ├── NavItem.vue
│   │   └── NotificationPanel.vue
│   └── dashboard/      # Componentes específicos del dashboard
├── layouts/            # Layouts de página
│   └── AppLayout.vue   # Layout principal de la aplicación
├── pages/              # Páginas Vue para Inertia.js
│   ├── Dashboard.vue   # Dashboard principal
│   └── Auth/
│       └── Login.vue   # Página de login moderna
├── assets/             # Assets estáticos
│   ├── styles/         # Estilos globales
│   │   └── design-system.css  # Sistema de diseño
│   └── animations/     # Animaciones y transiciones
│       └── transitions.css
├── composables/        # Composables Vue reutilizables
├── types/             # Definiciones de tipos TypeScript
└── app.js             # Punto de entrada de la aplicación
```

## 🎨 Sistema de Diseño

### Paleta de Colores
- **Primary**: Azul (#0ea5e9) a Púrpura (#d946ef)
- **Secondary**: Gris neutro con variaciones
- **Accent**: Naranja (#f97316) a Rosa (#f472b6)
- **Semantic**: Verde (éxito), Amarillo (advertencia), Rojo (error)

### Componentes UI

#### Button
- **Variantes**: primary, secondary, accent, outline, ghost, danger
- **Tamaños**: xs, sm, md, lg, xl
- **Características**: Efectos ripple, estados de carga, iconos
- **Animaciones**: Hover, press, loading spinner

#### Card
- **Variantes**: default, elevated, outlined, glass, gradient
- **Características**: Header, footer, acciones, contenido flexible
- **Efectos**: Hover lift, sombras dinámicas

#### Input
- **Tipos**: text, email, password, number, tel, url, search
- **Características**: Iconos, validación, limpieza, toggle password
- **Estados**: Normal, focused, error, success, disabled

#### Modal
- **Tamaños**: xs, sm, md, lg, xl, full
- **Características**: Backdrop blur, animaciones, scroll lock
- **Funciones**: Persistente, closable, loading overlay

### Layouts

#### AppLayout
- **Sidebar**: Navegación colapsible con animaciones
- **Header**: Breadcrumbs, notificaciones, tema, perfil
- **Main**: Contenido principal con transiciones de página
- **Footer**: Links y información de copyright
- **Características**: Responsive, tema oscuro/claro, notificaciones en tiempo real

## 🔄 Migración Gradual

El sistema está diseñado para permitir migración gradual:

1. **Coexistencia**: Los componentes Vue pueden coexistir con las vistas Blade existentes
2. **Inertia.js**: Permite renderizar páginas Vue desde Laravel sin APIs
3. **Fallback**: Si una página Vue no existe, se usa el sistema Blade existente
4. **Layouts**: AppLayout se aplica automáticamente a páginas Vue

## 🎯 Características Premium

### Animaciones
- **Micro-interacciones**: Botones con efectos press y ripple
- **Transiciones de página**: Slide, fade, scale effects
- **Loading states**: Skeletons, spinners, shimmer effects
- **Scroll animations**: Reveal on scroll, parallax

### Efectos Visuales
- **Glassmorphism**: Fondos con blur y transparencia
- **Gradientes**: Colores dinámicos y atractivos
- **Sombras**: Elevation system con sombras realistas
- **Hover effects**: Lift, glow, scale transformations

### UX Avanzada
- **Notificaciones**: Toast messages y panel de notificaciones
- **Temas**: Modo oscuro/claro con transiciones suaves
- **Responsive**: Diseño mobile-first completamente adaptativo
- **Accesibilidad**: Focus states, keyboard navigation, ARIA labels

## 🚀 Cómo Usar

### 1. Instalar Dependencias
```bash
# Actualizar package.json con las nuevas dependencias
cp package-new.json package.json
npm install
```

### 2. Configurar Vite
El proyecto ya incluye configuración de Vite optimizada para:
- Hot Module Replacement (HMR)
- Tree shaking
- Code splitting por rutas
- TypeScript support

### 3. Crear Nueva Página Vue
```vue
<!-- frontend/pages/MiPagina.vue -->
<template>
  <AppLayout page-title="Mi Página" page-subtitle="Descripción">
    <Card>
      <h1>¡Hola PlayRKE!</h1>
    </Card>
  </AppLayout>
</template>
```

### 4. Usar Componentes
```vue
<template>
  <div>
    <Button variant="primary" size="lg" @click="handleClick">
      Jugar Ahora
    </Button>
    
    <Input
      v-model="email"
      type="email"
      label="Email"
      :error="errors.email"
    />
    
    <Modal v-model:show="showModal" title="Confirmación">
      <p>¿Estás seguro?</p>
      <template #footer>
        <Button @click="confirm">Sí</Button>
        <Button variant="outline" @click="showModal = false">No</Button>
      </template>
    </Modal>
  </div>
</template>
```

## 🎨 Personalización

### CSS Custom Properties
```css
:root {
  --primary-500: #0ea5e9;
  --primary-600: #0284c7;
  /* Personalizar colores aquí */
}
```

### Temas
```javascript
// Cambiar tema programáticamente
document.documentElement.setAttribute('data-theme', 'dark')
```

## 📱 Responsive Design

El sistema utiliza un approach mobile-first:
- **xs**: < 640px (móviles)
- **sm**: ≥ 640px (móviles grandes)
- **md**: ≥ 768px (tablets)
- **lg**: ≥ 1024px (desktop)
- **xl**: ≥ 1280px (desktop grande)

## 🔧 Desarrollo

### Scripts Disponibles
```bash
npm run dev     # Desarrollo con HMR
npm run build   # Build para producción
npm run preview # Preview del build
```

### Hot Module Replacement
Todos los cambios en componentes Vue se reflejan instantáneamente sin reload.

## 🎯 Próximos Pasos

1. **Migrar páginas existentes** una por una a Vue
2. **Crear más componentes** según las necesidades específicas
3. **Implementar PWA** features para experiencia app-like
4. **Añadir tests** unitarios y de integración
5. **Optimizar performance** con lazy loading y caching

## 🤝 Contribución

Para añadir nuevos componentes o funcionalidades:

1. Seguir la estructura de carpetas existente
2. Usar TypeScript para tipado
3. Incluir animaciones y estados interactivos
4. Documentar props y eventos
5. Mantener consistencia con el sistema de diseño

¡El nuevo frontend de PlayRKE está listo para ofrecer una experiencia de usuario excepcional! 🎮✨