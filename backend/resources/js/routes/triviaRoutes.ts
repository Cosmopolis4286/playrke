// ======================================================
// Archivo: resources/js/routes/triviaRoutes.ts
// Descripción: Rutas del módulo Trivia
// ======================================================

export const triviaRoutes = {
    // Admin routes
    roomsIndex: "/trivia/rooms",
    roomsCreate: "/trivia/rooms/create",
    roomsStore: "/trivia/rooms",
    roomsShow: "/trivia/rooms/{id}",
    roomsEdit: "/trivia/rooms/{id}/edit",
    roomsUpdate: "/trivia/rooms/{id}",
    roomsDestroy: "/trivia/rooms/{id}",

    // API routes (para referencia)
    apiRoomsIndex: "/api/trivia/rooms",
    apiRoomsStore: "/api/trivia/rooms",
    apiRoomsShow: "/api/trivia/rooms/{id}",
    apiRoomsJoin: "/api/trivia/rooms/{id}/join",
    apiRoomsLeave: "/api/trivia/rooms/{id}/leave",
    apiRoomsStart: "/api/trivia/rooms/{id}/start",
    apiGamesShow: "/api/trivia/games/{id}",
    apiGamesAnswer: "/api/trivia/games/{id}/answer",
} as const;

/**
 * Retorna la URL de una ruta específica dentro de triviaRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name: keyof typeof triviaRoutes, params?: Record<string, string | number>): string {
    let url: string = triviaRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{" + key + "\\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}

/**
 * Helper para usar en templates - alias de getRoute
 */
export function route(name: keyof typeof triviaRoutes, params?: Record<string, string | number>): string {
    return getRoute(name, params);
}
