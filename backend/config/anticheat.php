<?php

return [
    'suspension_threshold' => env('ANTICHEAT_SUSPENSION_THRESHOLD', 5),
    'window_minutes'       => env('ANTICHEAT_WINDOW_MINUTES', 1440),

    'trivia' => [
        'min_human_response_ms' => env('ANTICHEAT_TRIVIA_MIN_RESPONSE_MS', 300),
        'max_clock_drift_ms'    => env('ANTICHEAT_TRIVIA_MAX_CLOCK_DRIFT_MS', 5000),
    ],

    'bingo' => [
        'min_claim_delay_ms' => env('ANTICHEAT_BINGO_MIN_CLAIM_DELAY_MS', 400),
    ],
];
