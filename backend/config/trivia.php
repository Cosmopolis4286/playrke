<?php

/**
 * Configuración del módulo Trivia.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Juego
    |--------------------------------------------------------------------------
    */
    'game' => [
        'default_question_count' => 10,
        'default_time_per_question' => 15, // segundos
        'min_players' => 1,
        'max_players' => 1000,
        'max_response_time' => 30, // segundos
        
        // Puntuación
        'base_points_per_question' => 100,
        'time_bonus_multiplier' => 0.5, // 50% extra por rapidez
        'streak_bonus' => [ // Puntos extra por racha
            'enabled' => true,
            'multiplier' => 0.1, // 10% extra por cada respuesta correcta consecutiva
            'max_multiplier' => 2.0, // Máximo 2x
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Redis
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'prefix' => 'trivia:',
        'ttl' => [
            'game_state' => 3600, // 1 hora
            'room' => 7200, // 2 horas
            'score' => 3600,
            'leaderboard' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout de sesión Postgres (conexiones 'pgsql'/'pgsql_trivia')
    |--------------------------------------------------------------------------
    |
    | PDO_pgsql no acota queries en ejecución ni conexiones ESTABLISHED cuyo
    | socket dejó de responder (PDO::ATTR_TIMEOUT solo limita el connect()
    | inicial) — sin este límite, un socket "zombie" (ej. tras reiniciar los
    | contenedores Docker con un test en curso) bloquea el proceso PHP en
    | poll() indefinidamente. Ver TriviaServiceProvider::guardPgsqlTriviaAgainstHungQueries().
    */
    'pgsql_statement_timeout_ms' => env('DB_PGSQL_STATEMENT_TIMEOUT_MS', 15000),

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de WebSocket
    |--------------------------------------------------------------------------
    */
    'websocket' => [
        'channels' => [
            'room' => 'trivia.room.{roomId}',
            'game' => 'trivia.game.{gameId}',
            'player' => 'trivia.player.{playerId}',
        ],
        'events' => [
            'game_started' => 'trivia.game.started',
            'question_sent' => 'trivia.question.sent',
            'answer_submitted' => 'trivia.answer.submitted',
            'score_updated' => 'trivia.score.updated',
            'game_finished' => 'trivia.game.finished',
            'player_joined' => 'trivia.player.joined',
            'player_left' => 'trivia.player.left',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'submit_answer' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'join_room' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Microservicios
    |--------------------------------------------------------------------------
    |
    | Preparación para extracción a microservicios.
    | Cuando se migre, estos endpoints se usarán para comunicación
    | entre servicios.
    |
    */
    'microservice' => [
        'enabled' => false,
        'base_url' => env('TRIVIA_SERVICE_URL', 'http://localhost:3000'),
        'api_key' => env('TRIVIA_SERVICE_API_KEY'),
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | 'chat' y 'spectator_mode' se retiraron el 2026-08-31 — no tenían
    | ningún código consumidor ni especificación de reglas de negocio (ver
    | CHANGELOG_AUDIT_2026_08.md en app/Domains/Games/Trivia/). Revisitar si
    | hay un diseño concreto que implementar.
    */
    'features' => [
        // Power-ups implementados (fifty_fifty, double_points, skip,
        // extra_time) — pero el on/off real es por sala, vía
        // TriviaRoom.settings.allow_powerups, no este flag global.
        'powerups' => false,
        'anonymous_play' => true, // Permitir juego sin login
    ],
];
