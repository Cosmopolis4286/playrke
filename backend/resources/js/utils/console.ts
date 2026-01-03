type LogLevel = 'log' | 'info' | 'warn' | 'error';

const levelStyles: Record<LogLevel, string> = {
    log: 'color: #000',
    info: 'color: #0af',
    warn: 'color: #fa0; font-weight: bold',
    error: 'color: #f00; font-weight: bold; background: #fee',
};

function isLogLevel(level: string): level is LogLevel {
    return ['log', 'info', 'warn', 'error'].includes(level);
}

export function devConsole(
    levelOrMessage?: LogLevel | any,
    messageOrParams?: any,
    ...optionalParams: any[]
): void {
    if (!import.meta.env.DEV) return;

    let level: LogLevel = 'log';
    let message: any;
    let params: any[];

    if (typeof levelOrMessage === 'string' && isLogLevel(levelOrMessage)) {
        level = levelOrMessage;
        message = messageOrParams;
        params = optionalParams;
    } else {
        message = levelOrMessage;
        params = messageOrParams ? [messageOrParams, ...optionalParams] : [];
    }

    if (typeof message === 'string') {
        console[level](`%c${message}`, levelStyles[level], ...params);
    } else {
        console[level](message, ...params);
    }
}
