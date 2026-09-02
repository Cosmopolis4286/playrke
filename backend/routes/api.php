<?php

/*
|--------------------------------------------------------------------------
| API Routes - PlayRKE
|--------------------------------------------------------------------------
| Autenticación real de la app: rutas web (routes/auth.php,
| AuthenticatedSessionController) y sesión de Laravel — no este archivo.
| Este archivo solo expone endpoints puramente API (sanctum/sin sesión).
|
| Las rutas de user-level (self-service add-xp/remove-xp/reset/leaderboard)
| se eliminaron: permitían a cualquier usuario autenticado (sin
| restricción de rol, sin comparar contra progreso de juego real, y en
| su mayoría sin throttle) auto-otorgarse XP arbitrario, inflando el
| leaderboard y los logros de nivel. Sin consumidor real en el frontend
| (useUserLevel.ts nunca se importaba en ningún componente). Otorgar XP
| ahora vive exclusivamente en AdminUserService::grantXp() (exige rol
| player + auditoría) y en los flujos automáticos de juego
| (BingoDrawService, GameEngineService, GameCompletionService, etc.).
|
| Nota: las rutas API de Trivia (trivia.php) son cargadas por TriviaServiceProvider
*/
