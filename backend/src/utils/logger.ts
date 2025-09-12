/**
 * Logger utilitario - Sistema de logging centralizado
 */

interface LogLevel {
  ERROR: 'error';
  WARN: 'warn';
  INFO: 'info';
  DEBUG: 'debug';
}

const LOG_LEVELS: LogLevel = {
  ERROR: 'error',
  WARN: 'warn',
  INFO: 'info',
  DEBUG: 'debug'
};

const LOG_COLORS = {
  error: '\x1b[31m',   // Rojo
  warn: '\x1b[33m',    // Amarillo
  info: '\x1b[36m',    // Cian
  debug: '\x1b[35m',   // Magenta
  reset: '\x1b[0m'     // Reset
};

class Logger {
  private isDevelopment: boolean;
  
  constructor() {
    this.isDevelopment = process.env.NODE_ENV === 'development';
  }

  private formatMessage(level: string, message: string, meta?: any): string {
    const timestamp = new Date().toISOString();
    const color = LOG_COLORS[level as keyof typeof LOG_COLORS];
    const resetColor = LOG_COLORS.reset;
    
    let logMessage = `${color}[${timestamp}] ${level.toUpperCase()}: ${message}${resetColor}`;
    
    if (meta) {
      logMessage += `\n${color}Meta: ${JSON.stringify(meta, null, 2)}${resetColor}`;
    }
    
    return logMessage;
  }

  error(message: string, meta?: any): void {
    const formattedMessage = this.formatMessage('error', message, meta);
    console.error(formattedMessage);
    
    // En producción, enviar a servicio de logging externo
    if (!this.isDevelopment) {
      // TODO: Integrar con servicio como Winston, Pino, o servicio externo
    }
  }

  warn(message: string, meta?: any): void {
    const formattedMessage = this.formatMessage('warn', message, meta);
    console.warn(formattedMessage);
  }

  info(message: string, meta?: any): void {
    const formattedMessage = this.formatMessage('info', message, meta);
    console.info(formattedMessage);
  }

  debug(message: string, meta?: any): void {
    if (this.isDevelopment) {
      const formattedMessage = this.formatMessage('debug', message, meta);
      console.debug(formattedMessage);
    }
  }

  // Métodos específicos para contextos comunes
  
  apiRequest(method: string, endpoint: string, userId?: string): void {
    this.info(`${method} ${endpoint}`, { userId });
  }

  apiResponse(method: string, endpoint: string, statusCode: number, duration: number): void {
    this.info(`${method} ${endpoint} - ${statusCode} (${duration}ms)`);
  }

  authEvent(event: string, userId: string, meta?: any): void {
    this.info(`Auth: ${event}`, { userId, ...meta });
  }

  gameEvent(event: string, gameType: string, roomId?: string, meta?: any): void {
    this.info(`Game: ${event} (${gameType})`, { roomId, ...meta });
  }

  paymentEvent(event: string, method: string, amount?: number, meta?: any): void {
    this.info(`Payment: ${event} (${method})`, { amount, ...meta });
  }

  dbQuery(query: string, duration: number): void {
    if (this.isDevelopment) {
      this.debug(`DB Query (${duration}ms): ${query}`);
    }
  }
}

// Singleton instance
export const logger = new Logger();

// Helper function para manejar errores de forma consistente
export function logAndThrowError(message: string, originalError?: any): never {
  logger.error(message, { originalError: originalError?.message, stack: originalError?.stack });
  throw new Error(message);
}