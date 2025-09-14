# Instrucciones de Desarrollo

## Pautas Generales

### 1. Idioma
- **Comunicación**: Todos los comentarios, documentación y mensajes deben estar en español
- **Variables y funciones**: OBLIGATORIO - Usar nombres descriptivos en **INGLÉS** claro y entendible
- **Commits**: Los mensajes de commit deben estar en español
- **REGLA ESTRICTA**: Solo comentarios, PHPDoc y JSDoc van en español. Todo el código (funciones, variables, constantes, métodos) debe estar en inglés

### 2. Mejores Prácticas de Programación

#### Principios SOLID
- **S**ingle Responsibility Principle: Una clase/función debe tener una sola razón para cambiar
- **O**pen/Closed Principle: Abierto para extensión, cerrado para modificación
- **L**iskov Substitution Principle: Los objetos derivados deben poder reemplazar a los base
- **I**nterface Segregation Principle: Interfaces específicas mejor que generales
- **D**ependency Inversion Principle: Depender de abstracciones, no de concreciones

#### Nomenclatura
- Usar **camelCase** para variables y funciones en JavaScript
- Usar **PascalCase** para clases y componentes
- Usar **snake_case** para bases de datos y PHP cuando sea convención
- Nombres descriptivos y autodocumentados

### 3. Documentación

#### JSDoc para JavaScript/Vue
```javascript
/**
 * Calcula el total de un pedido incluyendo impuestos
 * @param {number} subtotal - El subtotal antes de impuestos
 * @param {number} tasaImpuesto - La tasa de impuesto (ej: 0.16 para 16%)
 * @returns {number} El total con impuestos incluidos
 * @throws {Error} Si los parámetros no son números válidos
 * @example
 * const total = calcularTotal(100, 0.16); // 116
 */
function calcularTotal(subtotal, tasaImpuesto) {
    // implementación
}
```

#### PHPDoc para PHP/Laravel
```php
/**
 * Obtiene todos los usuarios activos del sistema
 *
 * @param int $limite Número máximo de usuarios a retornar
 * @param string $ordenarPor Campo por el cual ordenar los resultados
 * @return \Illuminate\Database\Eloquent\Collection<User> Colección de usuarios activos
 * @throws \InvalidArgumentException Si el límite es menor a 1
 *
 * @example
 * $usuarios = $this->obtenerUsuariosActivos(10, 'nombre');
 */
public function obtenerUsuariosActivos($limite = 50, $ordenarPor = 'created_at')
{
    // implementación
}
```

### 4. Clean Code

#### Funciones
- **Pequeñas**: Una función debe hacer una sola cosa bien
- **Nombres descriptivos**: El nombre debe explicar qué hace
- **Pocos parámetros**: Máximo 3 parámetros, usar objetos si necesitas más
- **Sin efectos secundarios**: Si una función se llama `obtener`, no debe modificar nada

#### Variables
- **Nombres significativos**: `nombreUsuario` en lugar de `n` o `user`
- **Evitar abreviaciones**: `producto` en lugar de `prod`
- **Constantes en mayúsculas**: `MAXIMO_INTENTOS_LOGIN = 3`

#### Comentarios
- El código debe ser autoexplicativo
- Comentarios solo para explicar el "por qué", no el "qué"
- Mantener comentarios actualizados

### 5. Modularización

#### Estructura de Archivos
```
/src
  /componentes
    /comunes     # Componentes reutilizables
    /especificos # Componentes específicos de página
  /servicios     # Lógica de negocio y APIs
  /utilidades    # Funciones helper
  /constantes    # Valores constantes
```

#### Separación de Responsabilidades
- **Componentes**: Solo presentación y interacción
- **Servicios**: Lógica de negocio y comunicación con APIs
- **Utilidades**: Funciones helper reutilizables
- **Stores/Estado**: Gestión de estado global

### 6. Seguridad

#### Validación de Datos
```javascript
/**
 * Valida y sanitiza una dirección de email
 * @param {string} email - Email a validar
 * @returns {string|null} Email sanitizado o null si es inválido
 */
function validarEmail(email) {
    if (!email || typeof email !== 'string') return null;

    const emailLimpio = email.trim().toLowerCase();
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return regex.test(emailLimpio) ? emailLimpio : null;
}
```

#### Manejo de Errores
```javascript
/**
 * Maneja errores de API de forma segura
 * @param {Error} error - Error capturado
 * @param {string} contexto - Contexto donde ocurrió el error
 */
function manejarError(error, contexto) {
    // Log del error completo para desarrollo
    console.error(`Error en ${contexto}:`, error);

    // Mensaje seguro para el usuario
    const mensajeUsuario = error.esErrorDeValidacion
        ? error.message
        : 'Ha ocurrido un error inesperado. Por favor intenta de nuevo.';

    mostrarNotificacionError(mensajeUsuario);
}
```

#### Sanitización
- Siempre validar y sanitizar inputs del usuario
- Usar librerías probadas para sanitización
- Nunca confiar en datos del frontend en el backend
- Escapar datos antes de mostrar en HTML

### 7. Testing

#### Nomenclatura de Tests
```javascript
describe('Servicio de Autenticación', () => {
    test('debe retornar token válido con credenciales correctas', () => {
        // implementación
    });

    test('debe lanzar error con credenciales inválidas', () => {
        // implementación
    });
});
```

### 8. Git y Versionado

#### Mensajes de Commit
- Usar formato: `tipo(alcance): descripción`
- Ejemplos:
  - `feat(auth): agregar validación de email en login`
  - `fix(productos): corregir cálculo de descuentos`
  - `docs(readme): actualizar instrucciones de instalación`
  - `refactor(utils): simplificar función de formateo de fechas`

#### Ramas
- `master/main`: Código en producción
- `develop`: Código en desarrollo
- `feature/nombre-funcionalidad`: Nuevas características
- `fix/descripcion-bug`: Corrección de errores
- `hotfix/descripcion-urgente`: Correcciones urgentes en producción

## Herramientas Recomendadas

- **Linting**: ESLint, PHP_CodeSniffer
- **Formateo**: Prettier, PHP-CS-Fixer
- **Testing**: Jest, PHPUnit
- **Documentación**: JSDoc, PHPDoc
- **Seguridad**: SonarQube, Snyk

## Seguimiento de Tareas - OBLIGATORIO ⚠️

### Regla Fundamental
**SIEMPRE** que se inicie, progrese o complete cualquier tarea, se DEBE actualizar inmediatamente el archivo `seguimiento-tareas.md` con:

1. **Al iniciar una tarea**:
   - Agregar nueva tarea con estado 🟡 En Progreso
   - Incluir descripción, alcance, impacto y tiempo estimado
   - Actualizar métricas del proyecto

2. **Durante el progreso**:
   - Actualizar porcentaje de progreso si es relevante
   - Documentar bloqueos o cambios en el alcance
   - Registrar decisiones importantes

3. **Al completar una tarea**:
   - Cambiar estado a 🟢 Finalizada
   - Registrar tiempo real utilizado
   - Actualizar métricas y estadísticas
   - Documentar lecciones aprendidas

### Formato de Actualización
```markdown
### T00X - [Nombre de la Tarea]
- **Estado**: [🔴🟡🟢🔵⭕] [Estado]
- **Última actualización**: YYYY-MM-DD HH:MM
- **Progreso**: X%
- **Tiempo acumulado**: X horas
```

**Esta regla es NO NEGOCIABLE y debe seguirse sin excepción.**

---

## Revisión de Código

Antes de cada commit, verificar:
- [ ] Código documentado con JSDoc/PHPDoc
- [ ] Funciones pequeñas y con responsabilidad única
- [ ] Validación y sanitización de inputs
- [ ] Manejo adecuado de errores
- [ ] Tests actualizados
- [ ] Sin código duplicado
- [ ] Nomenclatura consistente en español
- [ ] **Archivo `seguimiento-tareas.md` actualizado** ⚠️