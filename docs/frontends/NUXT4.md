# Frontend Nuxt 4 aislado

> **Estado:** código presente en `frontend/`, pero no es la UI principal ni forma parte del workspace pnpm o del CI principal. La interfaz principal está en `backend/resources/js` (Inertia + Vue 3).

## Stack

Nuxt 4, Vue 3, TypeScript, Tailwind CSS, Pinia y cliente Laravel Echo/Pusher. La fuente de dependencias y scripts es `frontend/package.json`.

## Ejecución local

Desde `frontend/`:

```bash
npm install
npm run dev
```

Consulta los scripts y `nuxt.config.ts` para los detalles vigentes. El script `lint` puede modificar archivos si usa `--fix`; usa ESLint sin esa opción para validar sin cambios.

## Antes de integrarlo como frontend productivo

1. Definir si sustituye o complementa la UI Inertia.
2. Confirmar rutas, funcionalidades y consumidores reales.
3. Definir autenticación cookie/CSRF e integración API.
4. Añadir instalación, typecheck, lint, tests y build a CI.
5. Definir despliegue, variables, observabilidad y estrategia de rollback.

No asumir que este frontend se despliega o comparte el ciclo de vida de la aplicación principal.
