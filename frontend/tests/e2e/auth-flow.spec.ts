/**
 * Tests E2E para Flujo de Autenticación
 *
 * Valida el flujo completo desde la perspectiva del usuario:
 * - Modal de auth en homepage
 * - Login con credenciales correctas/incorrectas
 * - Registro de nuevo usuario
 * - Logout
 * - Redirecciones según contexto
 *
 * Requiere:
 * - Backend corriendo en http://localhost:8000
 * - Frontend corriendo en http://localhost:3002
 * - Usuario de prueba: player@test.com / password123
 */

import { test, expect } from '@playwright/test'

// Configuración
const FRONTEND_URL = process.env.FRONTEND_URL || 'http://localhost:3002'
const TEST_USER = {
  email: 'player@test.com',
  password: 'password123',
  username: 'jugador1'
}

test.describe('Flujo de Autenticación de Player', () => {
  test.beforeEach(async ({ page }) => {
    // Limpiar cookies y storage antes de cada test
    await page.context().clearCookies()
    await page.goto(FRONTEND_URL)
  })

  test('debe mostrar modal de auth al visitar /player sin autenticación', async ({ page }) => {
    // Act: Navegar a /player
    await page.goto(`${FRONTEND_URL}/player`)

    // Assert: Modal de auth visible
    await expect(page.locator('text=¡Únete a la Diversión!')).toBeVisible()
    await expect(page.locator('button:has-text("Iniciar Sesión")')).toBeVisible()
    await expect(page.locator('button:has-text("Registrarse")')).toBeVisible()
  })

  test('debe redirigir a login al hacer click en "Iniciar Sesión"', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player`)

    // Act: Click en botón de login
    await page.click('button:has-text("Iniciar Sesión")')

    // Assert: Redirigido a página de login
    await page.waitForURL(`${FRONTEND_URL}/player/auth/login`)
    await expect(page.locator('text=¡Bienvenido de vuelta, Jugador!')).toBeVisible()
  })

  test('debe redirigir a registro al hacer click en "Registrarse"', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player`)

    // Act: Click en botón de registro
    await page.click('button:has-text("Registrarse")')

    // Assert: Redirigido a página de registro
    await page.waitForURL(`${FRONTEND_URL}/player/auth/register`)
    await expect(page.locator('text=¡Únete a PlayRKE!')).toBeVisible()
  })

  test('debe hacer login exitosamente con credenciales correctas', async ({ page }) => {
    // Arrange: Ir a página de login
    await page.goto(`${FRONTEND_URL}/player/auth/login`)

    // Act: Llenar formulario y enviar
    await page.fill('input[type="text"]', TEST_USER.email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')

    // Assert: Redirigido a lobby
    await page.waitForURL(`${FRONTEND_URL}/player/lobby`, { timeout: 10000 })
    await expect(page.locator(`text=${TEST_USER.username}`)).toBeVisible()
  })

  test('debe mostrar error con credenciales incorrectas', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player/auth/login`)

    // Act: Llenar formulario con credenciales incorrectas
    await page.fill('input[type="text"]', TEST_USER.email)
    await page.fill('input[type="password"]', 'wrong-password')
    await page.click('button[type="submit"]')

    // Assert: Mensaje de error visible
    await expect(page.locator('text=/Credenciales incorrectas|inválidas/i')).toBeVisible()

    // No debe estar en el lobby
    await expect(page).not.toHaveURL(`${FRONTEND_URL}/player/lobby`)
  })

  test('debe validar campos requeridos en login', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player/auth/login`)

    // Act: Intentar enviar formulario vacío
    await page.click('button[type="submit"]')

    // Assert: Validación HTML5
    const emailInput = page.locator('input[type="text"]')
    const passwordInput = page.locator('input[type="password"]')

    await expect(emailInput).toHaveAttribute('required', '')
    await expect(passwordInput).toHaveAttribute('required', '')
  })

  test('debe poder hacer logout desde el lobby', async ({ page }) => {
    // Arrange: Login primero
    await page.goto(`${FRONTEND_URL}/player/auth/login`)
    await page.fill('input[type="text"]', TEST_USER.email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(`${FRONTEND_URL}/player/lobby`)

    // Act: Click en botón de logout
    await page.click('button:has(svg):has-text("log-out")').first()

    // Assert: Redirigido a login
    await page.waitForURL(`${FRONTEND_URL}/player/auth/login`)
    await expect(page.locator('text=¡Bienvenido de vuelta, Jugador!')).toBeVisible()
  })

  test('debe validar contraseñas coincidentes en registro', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player/auth/register`)

    // Act: Llenar formulario con contraseñas diferentes
    await page.fill('input[id="first_name"]', 'Test')
    await page.fill('input[id="last_name"]', 'User')
    await page.fill('input[id="username"]', `testuser_${Date.now()}`)
    await page.fill('input[id="email"]', `test_${Date.now()}@example.com`)
    await page.fill('input[id="password"]', 'password123')
    await page.fill('input[id="password_confirmation"]', 'different-password')
    await page.check('input[type="checkbox"]')
    await page.click('button[type="submit"]')

    // Assert: Mensaje de error
    await expect(page.locator('text=/contraseñas no coinciden/i')).toBeVisible()
  })

  test('debe recordar sesión después de reload con "recordar sesión" activado', async ({ page }) => {
    // Arrange: Login con "recordar sesión"
    await page.goto(`${FRONTEND_URL}/player/auth/login`)
    await page.fill('input[type="text"]', TEST_USER.email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.check('input[type="checkbox"]') // Recordar sesión
    await page.click('button[type="submit"]')
    await page.waitForURL(`${FRONTEND_URL}/player/lobby`)

    // Act: Reload página
    await page.reload()

    // Assert: Todavía autenticado
    await expect(page).toHaveURL(`${FRONTEND_URL}/player/lobby`)
    await expect(page.locator(`text=${TEST_USER.username}`)).toBeVisible()
  })
})

test.describe('Flujo de Autenticación de Admin', () => {
  const ADMIN_USER = {
    email: 'admin@test.com',
    password: 'admin123',
    username: 'admin1'
  }

  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies()
  })

  test('debe redirigir a login de admin al acceder /admin sin auth', async ({ page }) => {
    // Act
    await page.goto(`${FRONTEND_URL}/admin`)

    // Assert: Redirigido a admin login
    await page.waitForURL(`${FRONTEND_URL}/admin/auth/login`)
    await expect(page.locator('text=Panel Administrativo')).toBeVisible()
  })

  test('debe mostrar diseño profesional en admin login', async ({ page }) => {
    // Act
    await page.goto(`${FRONTEND_URL}/admin/auth/login`)

    // Assert: Elementos del diseño admin
    await expect(page.locator('text=Panel Administrativo')).toBeVisible()
    await expect(page.locator('text=Acceso restringido')).toBeVisible()

    // No debe tener elementos gaming
    await expect(page.locator('text=/¡|🎮|🎉/i')).not.toBeVisible()
  })

  test('admin puede hacer login y acceder al dashboard', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/admin/auth/login`)

    // Act: Login como admin
    await page.fill('input[type="text"]', ADMIN_USER.email)
    await page.fill('input[type="password"]', ADMIN_USER.password)
    await page.click('button[type="submit"]')

    // Assert: Accede al dashboard
    await page.waitForURL(`${FRONTEND_URL}/admin`, { timeout: 10000 })
    await expect(page.locator('text=/Dashboard|Panel/i')).toBeVisible()
  })
})

test.describe('Seguridad y Validaciones', () => {
  test('debe prevenir XSS en inputs', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player/auth/login`)

    // Act: Intentar inyectar script
    const xssPayload = '<script>alert("XSS")</script>'
    await page.fill('input[type="text"]', xssPayload)

    // Assert: No se ejecuta el script
    const alerts = []
    page.on('dialog', dialog => {
      alerts.push(dialog.message())
      dialog.dismiss()
    })

    await page.click('button[type="submit"]')
    await page.waitForTimeout(1000)

    expect(alerts).toHaveLength(0)
  })

  test('debe implementar rate limiting en login', async ({ page }) => {
    // Arrange
    await page.goto(`${FRONTEND_URL}/player/auth/login`)

    // Act: Intentar login 6 veces seguidas con contraseña incorrecta
    for (let i = 0; i < 6; i++) {
      await page.fill('input[type="text"]', 'test@example.com')
      await page.fill('input[type="password"]', 'wrong-password')
      await page.click('button[type="submit"]')
      await page.waitForTimeout(500)
    }

    // Assert: Mensaje de rate limiting (429 o mensaje específico)
    await expect(page.locator('text=/demasiados intentos|rate limit/i')).toBeVisible()
  })

  test('debe limpiar datos sensibles del storage al logout', async ({ page }) => {
    // Arrange: Login
    await page.goto(`${FRONTEND_URL}/player/auth/login`)
    await page.fill('input[type="text"]', TEST_USER.email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(`${FRONTEND_URL}/player/lobby`)

    // Act: Logout
    await page.click('button:has(svg):has-text("log-out")').first()
    await page.waitForURL(`${FRONTEND_URL}/player/auth/login`)

    // Assert: localStorage limpio
    const storage = await page.evaluate(() => localStorage.getItem('auth'))
    expect(storage).toBeNull()
  })
})
