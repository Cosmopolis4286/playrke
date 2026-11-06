# Frontend separado PlayRKE (Nuxt 4)

> **Estado:** aplicación Nuxt 4 presente en el repositorio, pero actualmente fuera del workspace pnpm activo. La interfaz principal mantenida del producto está en `backend/resources/js` (Inertia + Vue 3). No asumir que este frontend Nuxt está desplegado, conectado al CI o integrado con el flujo de autenticación de la aplicación principal.

## Stack declarado

- Nuxt 4 y Vue 3
- TypeScript en modo estricto
- Tailwind CSS
- Pinia
- Laravel Echo / Pusher client para integración de tiempo real

La fuente de dependencias y scripts es este `package.json`; el proyecto requiere Node.js 20 o superior.

## Estado del workspace

La raíz `package.json` declara `backend` y `frontend` en `workspaces`, pero el archivo `pnpm-workspace.yaml` incluye solo `backend`. Por ello, el frontend no se instala ni ejecuta mediante los comandos pnpm del workspace actual. Antes de volverlo parte del producto:
1. confirmar su alcance funcional y rutas que realmente se usan;
2. definir integración con el backend y el flujo cookie/CSRF de Laravel;
3. definir variables públicas y privadas sin exponer secretos;
4. añadir instalación, typecheck, lint, tests y build al CI;
5. definir su despliegue y observabilidad.

## Ejecución local aislada

Desde este directorio, usando npm:

```bash
npm install
npm run dev
```

El servidor de desarrollo usa el puerto 3000 por defecto. Consulta `nuxt.config.ts` y los scripts del `package.json` para los detalles vigentes. El script `setup` ejecuta `setup-env.js`; revisa el archivo y sus efectos antes de ejecutarlo.

## Scripts declarados

| Script | Acción |
|---|---|
| `dev` | Nuxt dev server |
| `build` | Build Nuxt |
| `preview` | Preview del build |
| `generate` | Generación estática |
| `type-check` | Nuxt typecheck |
| `test` | Vitest |
| `lint` | ESLint con `--fix` (modifica archivos) |
| `health` | Ejecuta `check-health.js` |

Para lint no destructivo, invoca ESLint sin `--fix`. No uses esta guía como procedimiento de despliegue productivo: ese flujo no está conectado al pipeline principal documentado.

## UI principal actual

Para la interfaz integrada activa, consulta `../backend/resources/js` y [Desarrollo](../docs/DEVELOPMENT.md).