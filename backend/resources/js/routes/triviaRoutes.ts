// ======================================================
// Archivo: resources/js/routes/triviaRoutes.ts
// Descripción: Rutas del módulo Trivia
// ======================================================

export const triviaRoutes = {
    // ── Admin: Salas ────────────────────────────────────
    roomsIndex:   '/trivia-rooms',
    roomsCreate:  '/trivia-rooms/create',
    roomsStore:   '/trivia-rooms',
    roomsShow:    '/trivia-rooms/{id}',
    roomsEdit:    '/trivia-rooms/{id}/edit',
    roomsUpdate:  '/trivia-rooms/{id}',
    roomsDestroy: '/trivia-rooms/{id}',

    // ── Admin: Banco de Preguntas ────────────────────────
    questionsIndex:        '/trivia-questions/room/{roomId}',
    questionsCreate:       '/trivia-questions/room/{roomId}/create',
    questionsStore:        '/trivia-questions/room/{roomId}',
    questionsShow:         '/trivia-questions/room/{roomId}/{questionId}/show',
    questionsEdit:         '/trivia-questions/room/{roomId}/{questionId}/edit',
    questionsUpdate:       '/trivia-questions/room/{roomId}/{questionId}',
    questionsDestroy:      '/trivia-questions/room/{roomId}/{questionId}',
    questionsImport:       '/trivia-questions/room/{roomId}/import',
    questionsToggleActive: '/trivia-questions/room/{roomId}/{questionId}/toggle-active',

    // ── Usuario: páginas de juego ────────────────────────
    lobby:       '/trivia/lobby',
    roomWait:    '/trivia/room/{code}/wait',
    stats:       '/trivia/stats',
    leaderboard: '/trivia/leaderboard',
    gamesIndex:  '/trivia/games',
    gamesShow:   '/trivia/games/{id}',
    play:        '/trivia/play/{gameId}',
    playResults: '/trivia/play/{gameId}/results',
    
    // ── Rutas nuevas El Poder del Saber ───────────────────
    triviaPlay:       '/trivia/play/{gameId}',
    triviaPlayResults:'/trivia/play/{gameId}/results',

    // ── Acciones de juego ────────────────────────────────
    joinRoom:   '/trivia-game/room/{id}/join',
    startRoom:  '/trivia-game/room/{id}/start',
    gameAnswer: '/trivia-game/game/{gameId}/answer',
} as const;

/**
 * Retorna la URL de una ruta reemplazando los placeholders con los parámetros dados.
 */
export function getRoute(
    name: keyof typeof triviaRoutes,
    params?: Record<string, string | number>
): string {
    let url: string = triviaRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp(`\\{${key}\\??\\}`, 'g'), String(value));
        });
    }

    // Elimina placeholders opcionales sin valor
    url = url.replace(/\/\{[^}]+\?\}/g, '');

    return url;
}

/** Alias de getRoute para templates. */
export const route = getRoute;
