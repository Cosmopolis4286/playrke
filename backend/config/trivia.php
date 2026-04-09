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
    */
    'features' => [
        'powerups' => false, // Power-ups (50/50, doble puntos, etc.)
        'chat' => false, // Chat en sala
        'spectator_mode' => false, // Modo espectador
        'anonymous_play' => true, // Permitir juego sin login
    ],
];
