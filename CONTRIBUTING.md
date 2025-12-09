# Guía de Contribución - PlayRKE Bingo

¡Gracias por tu interés en contribuir al proyecto PlayRKE Bingo! Esta guía te ayudará a entender cómo puedes contribuir efectivamente.

## 📋 Tabla de Contenidos

- [Código de Conducta](#código-de-conducta)
- [Cómo Contribuir](#cómo-contribuir)
- [Configuración del Entorno](#configuración-del-entorno)
- [Estándares de Código](#estándares-de-código)
- [Proceso de Pull Request](#proceso-de-pull-request)
- [Reportar Bugs](#reportar-bugs)
- [Sugerir Mejoras](#sugerir-mejoras)

## 📜 Código de Conducta

Este proyecto se adhiere a un código de conducta profesional:

- Ser respetuoso con todos los colaboradores
- Aceptar críticas constructivas
- Enfocarse en lo mejor para el proyecto
- Mostrar empatía hacia otros miembros

## 🚀 Cómo Contribuir

### Tipos de Contribuciones

Aceptamos varios tipos de contribuciones:

1. **Reportar bugs** - Encontrar y reportar problemas
2. **Sugerir mejoras** - Proponer nuevas funcionalidades
3. **Escribir código** - Implementar features o fixes
4. **Mejorar documentación** - Actualizar o crear docs
5. **Revisar código** - Code reviews en PRs

## 🛠️ Configuración del Entorno

### 1. Fork y Clone

```bash
# Fork el repositorio en GitHub
# Luego clona tu fork
git clone https://github.com/tu-usuario/playrke.git
cd playrke

# Agregar remote upstream
git remote add upstream https://github.com/playrke/playrke.git
```

### 2. Configurar Backend

```bash
# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Migrar base de datos
php artisan migrate

# Iniciar servidor
php artisan serve
```

### 3. Configurar Frontend

```bash
# Navegar al frontend
cd frontend

# Instalar dependencias
npm install

# Configurar entorno
npm run setup

# Iniciar servidor
npm run dev
```

### 4. Configurar WebSockets

```bash
# En una nueva terminal
php artisan reverb:start
```

## 📝 Estándares de Código

### Backend (Laravel/PHP)

#### PSR-12 Coding Standard

```php
<?php

namespace App\Services;

use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
```

#### Reglas

- Usar type hints en parámetros y return types
- Documentar métodos con PHPDoc
- Usar camelCase para métodos
- Usar snake_case para columnas de DB
- Usar facades de Laravel cuando sea apropiado
- Validar inputs con Form Requests

### Frontend (Nuxt/TypeScript)

#### TypeScript Strict

```typescript
// ✅ Correcto
interface User {
  id: string
  name: string
  email: string
}

const getUser = async (id: string): Promise<User> => {
  const { data } = await useFetch<User>(`/api/users/${id}`)
  return data.value!
}

// ❌ Incorrecto
const getUser = async (id) => {
  const data = await useFetch(`/api/users/${id}`)
  return data
}
```

#### Reglas

- Siempre tipar variables, parámetros y retornos
- Usar interfaces para objetos complejos
- Usar Composition API (no Options API)
- Usar composables para lógica reutilizable
- Componentes en PascalCase
- Props y emits tipados
- Evitar `any`, usar `unknown` si es necesario

### Vue Components

```vue
<script setup lang="ts">
interface Props {
  title: string
  count?: number
}

interface Emits {
  (e: 'update', value: number): void
}

const props = withDefaults(defineProps<Props>(), {
  count: 0,
})

const emit = defineEmits<Emits>()

const handleClick = () => {
  emit('update', props.count + 1)
}
</script>

<template>
  <div class="card">
    <h2>{{ title }}</h2>
    <p>Count: {{ count }}</p>
    <Button @click="handleClick">Increment</Button>
  </div>
</template>

<style scoped>
.card {
  @apply rounded-lg border bg-white p-6 shadow-sm dark:bg-slate-800;
}
</style>
```

### Nombres de Archivos

#### Backend
```
UserController.php          # Controllers en PascalCase
user.php                    # Routes en lowercase
CreateUsersTable.php        # Migrations en PascalCase
UserFactory.php             # Factories en PascalCase
User.php                    # Models en PascalCase singular
```

#### Frontend
```
UserProfile.vue             # Components en PascalCase
index.vue                   # Pages en lowercase
useAuth.ts                  # Composables con 'use' prefix
user.ts                     # Stores en lowercase
formatters.ts               # Utils en lowercase
```

### Commits

Seguir [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# Features
git commit -m "feat: agregar autenticación de dos factores"
git commit -m "feat(admin): agregar filtros en tabla de usuarios"

# Fixes
git commit -m "fix: corregir validación de email"
git commit -m "fix(player): resolver error en compra de cartones"

# Docs
git commit -m "docs: actualizar README con instrucciones de deployment"

# Style
git commit -m "style: formatear código con Prettier"

# Refactor
git commit -m "refactor: simplificar lógica de autenticación"

# Test
git commit -m "test: agregar tests para UserService"

# Chore
git commit -m "chore: actualizar dependencias"
```

**Formato**:
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat` - Nueva funcionalidad
- `fix` - Bug fix
- `docs` - Documentación
- `style` - Formateo, no afecta lógica
- `refactor` - Refactorización
- `test` - Agregar o modificar tests
- `chore` - Mantenimiento, deps, config

## 🔄 Proceso de Pull Request

### 1. Crear Rama

```bash
# Actualizar main
git checkout master
git pull upstream master

# Crear rama feature
git checkout -b feature/nombre-descriptivo

# O para bug fix
git checkout -b fix/nombre-del-bug
```

### 2. Desarrollar

```bash
# Hacer cambios
# Commit frecuentemente con mensajes descriptivos
git add .
git commit -m "feat: agregar nueva funcionalidad"

# Mantener actualizado con main
git fetch upstream
git rebase upstream/master
```

### 3. Tests

```bash
# Backend
php artisan test

# Frontend
cd frontend
npm run lint
npm run type-check
```

### 4. Push

```bash
git push origin feature/nombre-descriptivo
```

### 5. Crear Pull Request

1. Ve a GitHub y crea un Pull Request
2. Usa el template de PR (ver abajo)
3. Asigna reviewers
4. Espera feedback

### Template de Pull Request

```markdown
## Descripción

Breve descripción de los cambios realizados.

## Tipo de cambio

- [ ] Bug fix
- [ ] Nueva funcionalidad
- [ ] Breaking change
- [ ] Documentación

## ¿Cómo se ha probado?

Describe cómo probaste los cambios:
- [ ] Tests unitarios
- [ ] Tests manuales
- [ ] Tests E2E

## Checklist

- [ ] Mi código sigue los estándares del proyecto
- [ ] He realizado self-review de mi código
- [ ] He comentado código complejo
- [ ] He actualizado la documentación
- [ ] Mis cambios no generan warnings
- [ ] He agregado tests
- [ ] Tests nuevos y existentes pasan
- [ ] He verificado en diferentes navegadores (frontend)

## Screenshots (si aplica)

Agregar screenshots para cambios visuales.

## Notas adicionales

Cualquier información adicional relevante.
```

## 🐛 Reportar Bugs

### Template de Issue para Bugs

```markdown
**Descripción del bug**
Descripción clara y concisa del problema.

**Pasos para reproducir**
1. Ir a '...'
2. Click en '...'
3. Ver error

**Comportamiento esperado**
Qué esperabas que sucediera.

**Screenshots**
Si aplica, agregar screenshots.

**Entorno:**
- OS: [ej. Windows 10]
- Navegador: [ej. Chrome 120]
- Versión: [ej. 1.0.0]

**Logs relevantes**
```
Agregar logs aquí
```

**Contexto adicional**
Cualquier otra información relevante.
```

## 💡 Sugerir Mejoras

### Template de Issue para Features

```markdown
**¿Tu feature request está relacionado con un problema?**
Descripción clara del problema. Ej. "Me frustra que..."

**Solución propuesta**
Descripción clara de lo que quieres que suceda.

**Alternativas consideradas**
Describe alternativas que hayas considerado.

**Contexto adicional**
Cualquier otra información, screenshots, mockups, etc.

**Beneficio esperado**
Cómo esta funcionalidad beneficiaría al proyecto.
```

## 🧪 Testing

### Backend Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
```

### Frontend Tests (cuando esté configurado)

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '@/components/ui/Button.vue'

describe('Button', () => {
  it('renders correctly', () => {
    const wrapper = mount(Button, {
      props: {
        variant: 'primary',
      },
      slots: {
        default: 'Click me',
      },
    })

    expect(wrapper.text()).toBe('Click me')
    expect(wrapper.classes()).toContain('btn-primary')
  })

  it('emits click event', async () => {
    const wrapper = mount(Button)
    await wrapper.trigger('click')

    expect(wrapper.emitted('click')).toBeTruthy()
  })
})
```

## 📚 Documentación

Al agregar nuevas funcionalidades, actualizar:

1. **README.md** - Si cambia el setup o arquitectura
2. **docs/** - Documentación técnica detallada
3. **Code comments** - Comentarios en código complejo
4. **PHPDoc/TSDoc** - Documentación de funciones/métodos
5. **CHANGELOG.md** - Registro de cambios

## 🎨 Estándares de UI/UX

### Diseño

- Usar sistema de diseño existente (Tailwind)
- Seguir paleta de colores del proyecto
- Responsive design (mobile-first)
- Soportar dark mode
- Accesibilidad (ARIA labels, keyboard navigation)

### Componentes

```vue
<!-- ✅ Correcto -->
<Button
  variant="primary"
  size="lg"
  :loading="isLoading"
  :disabled="!isValid"
  @click="handleSubmit"
>
  Guardar Cambios
</Button>

<!-- ❌ Incorrecto -->
<button
  class="bg-blue-500 px-4 py-2"
  @click="handleSubmit"
>
  Guardar
</button>
```

## 🔍 Code Review

### Para Reviewers

- Ser constructivo y respetuoso
- Explicar el "por qué" de los cambios sugeridos
- Aprobar si el código cumple estándares
- Pedir cambios si hay problemas críticos
- Comentar si hay mejoras opcionales

### Para Contributors

- Responder a todos los comentarios
- Hacer cambios solicitados
- Explicar decisiones si difieren
- Re-solicitar review después de cambios
- Agradecer feedback

## 📞 Comunicación

- **Issues**: Para bugs y features
- **Pull Requests**: Para revisión de código
- **Discussions**: Para preguntas generales
- **Email**: Para asuntos privados

## ✅ Checklist de Contribución

Antes de crear un PR, verifica:

- [ ] Código sigue estándares del proyecto
- [ ] Tests agregados/actualizados
- [ ] Documentación actualizada
- [ ] Commits siguen Conventional Commits
- [ ] Branch actualizada con main/master
- [ ] Sin conflictos de merge
- [ ] CI/CD pasa (si está configurado)
- [ ] Self-review realizado

## 🙏 Agradecimientos

¡Gracias por contribuir a PlayRKE Bingo! Tus contribuciones hacen que este proyecto sea mejor para todos.

---

**Última actualización**: 2025-10-05
