<?php

namespace App\Logging;

use Monolog\Level;
use Monolog\Logger;

/**
 * Factory del canal de log custom 'database' (config/logging.php).
 * Laravel invoca esto vía 'driver' => 'custom' + 'via' => self::class.
 */
class DatabaseLoggerFactory
{
    public function __invoke(array $config) : Logger
    {
        $level = Level::fromName(strtoupper($config['level'] ?? 'warning'));

        $logger = new Logger('database');
        $logger->pushHandler(new DatabaseLogHandler($level));

        return $logger;
    }
}
