import { io, Socket } from 'socket.io-client';
import { AuthService } from './authService';

// Tipos para eventos WebSocket
export interface BingoGameState {
  roomId: string;
  currentRound: {
    id: string;
    number: number;
    status: 'WAITING' | 'IN_PROGRESS' | 'FINISHED' | 'CANCELLED';
    drawnNumbers: number[];
    currentNumber?: number;
    timeElapsed: number;
  };
  players: {
    userId: string;
    username: string;
    cardsCount: number;
  }[];
  prizePool: number;
}

export interface BingoEvents {
  // Eventos del servidor
  'bingo:gameState': (state: BingoGameState) => void;
  'bingo:numberDrawn': (data: { number: number; letter: string; drawnNumbers: number[] }) => void;
  'bingo:playerJoined': (data: { userId: string; username: string }) => void;
  'bingo:playerLeft': (data: { userId: string; username: string }) => void;
  'bingo:roundStarted': (data: { roundId: string; roundNumber: number }) => void;
  'bingo:roundEnded': (data: { roundId: string; winners: any[] }) => void;
  'bingo:bingoWin': (data: { winner: string; cardId: string; prizeAmount: number }) => void;
  'notification': (data: { type: string; message: string; data?: any }) => void;
  'error': (data: { message: string; code?: string }) => void;
  
  // Eventos del cliente
  'bingo:joinRoom': (data: { roomId: string }) => void;
  'bingo:leaveRoom': (data: { roomId: string }) => void;
  'bingo:claimBingo': (data: { roundId: string; cardId: string; pattern?: string }) => void;
  'bingo:drawNumber': (data: { roomId: string; number: number }) => void;
}

/**
 * Servicio WebSocket para comunicación en tiempo real
 */
export class WebSocketService {
  private static instance: WebSocketService | null = null;
  private socket: Socket | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000;

  private constructor() {}

  /**
   * Obtener instancia singleton
   */
  static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService();
    }
    return WebSocketService.instance;
  }

  /**
   * Conectar al servidor WebSocket
   */
  connect(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (this.socket?.connected) {
        resolve();
        return;
      }

      const token = AuthService.getToken();
      if (!token) {
        reject(new Error('No hay token de autenticación'));
        return;
      }

      // Configuración del socket
      this.socket = io(process.env.NODE_ENV === 'production' 
        ? 'https://api.playrke.com' 
        : 'http://localhost:3000', {
        auth: {
          token
        },
        transports: ['websocket', 'polling'],
        upgrade: true,
        rememberUpgrade: true,
        timeout: 20000,
        forceNew: false,
        reconnection: true,
        reconnectionAttempts: this.maxReconnectAttempts,
        reconnectionDelay: this.reconnectDelay,
      });

      // Eventos de conexión
      this.socket.on('connect', () => {
        console.log('✅ WebSocket conectado:', this.socket?.id);
        this.reconnectAttempts = 0;
        resolve();
      });

      this.socket.on('connect_error', (error) => {
        console.error('❌ Error de conexión WebSocket:', error);
        reject(error);
      });

      this.socket.on('disconnect', (reason) => {
        console.warn('⚠️ WebSocket desconectado:', reason);
      });

      this.socket.on('reconnect', (attemptNumber) => {
        console.log(`🔄 WebSocket reconectado en intento ${attemptNumber}`);
        this.reconnectAttempts = 0;
      });

      this.socket.on('reconnect_error', (error) => {
        this.reconnectAttempts++;
        console.error(`❌ Error de reconexión (${this.reconnectAttempts}/${this.maxReconnectAttempts}):`, error);
      });

      this.socket.on('reconnect_failed', () => {
        console.error('❌ Falló la reconexión después de múltiples intentos');
      });

      // Manejo de errores específicos del servidor
      this.socket.on('error', (data) => {
        console.error('❌ Error del servidor:', data);
      });
    });
  }

  /**
   * Desconectar del servidor WebSocket
   */
  disconnect(): void {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
      console.log('WebSocket desconectado manualmente');
    }
  }

  /**
   * Verificar si está conectado
   */
  isConnected(): boolean {
    return this.socket?.connected || false;
  }

  /**
   * Emitir evento al servidor
   */
  emit<K extends keyof BingoEvents>(
    event: K, 
    data?: Parameters<BingoEvents[K]>[0]
  ): void {
    if (!this.socket?.connected) {
      console.warn('WebSocket no está conectado. Evento ignorado:', event);
      return;
    }

    this.socket.emit(event, data);
  }

  /**
   * Escuchar eventos del servidor
   */
  on<K extends keyof BingoEvents>(
    event: K, 
    callback: BingoEvents[K]
  ): void {
    if (!this.socket) {
      console.warn('WebSocket no está inicializado para escuchar:', event);
      return;
    }

    this.socket.on(event, callback);
  }

  /**
   * Dejar de escuchar un evento
   */
  off<K extends keyof BingoEvents>(
    event: K, 
    callback?: BingoEvents[K]
  ): void {
    if (!this.socket) return;
    
    if (callback) {
      this.socket.off(event, callback);
    } else {
      this.socket.off(event);
    }
  }

  /**
   * Unirse a una sala de bingo
   */
  joinBingoRoom(roomId: string): void {
    this.emit('bingo:joinRoom', { roomId });
  }

  /**
   * Salir de una sala de bingo
   */
  leaveBingoRoom(roomId: string): void {
    this.emit('bingo:leaveRoom', { roomId });
  }

  /**
   * Reclamar BINGO
   */
  claimBingo(roundId: string, cardId: string, pattern?: string): void {
    this.emit('bingo:claimBingo', { roundId, cardId, pattern });
  }

  /**
   * Sacar número (solo para administradores de sala)
   */
  drawNumber(roomId: string, number: number): void {
    this.emit('bingo:drawNumber', { roomId, number });
  }

  /**
   * Manejar actualización de estado del juego
   */
  onGameStateUpdate(callback: (state: BingoGameState) => void): void {
    this.on('bingo:gameState', callback);
  }

  /**
   * Manejar número cantado
   */
  onNumberDrawn(callback: (data: { number: number; letter: string; drawnNumbers: number[] }) => void): void {
    this.on('bingo:numberDrawn', callback);
  }

  /**
   * Manejar notificaciones generales
   */
  onNotification(callback: (data: { type: string; message: string; data?: any }) => void): void {
    this.on('notification', callback);
  }

  /**
   * Manejar ganadores de BINGO
   */
  onBingoWin(callback: (data: { winner: string; cardId: string; prizeAmount: number }) => void): void {
    this.on('bingo:bingoWin', callback);
  }

  /**
   * Reconectar manualmente
   */
  async reconnect(): Promise<void> {
    this.disconnect();
    await new Promise(resolve => setTimeout(resolve, 1000));
    return this.connect();
  }
}

// Exportar instancia singleton
export const websocketService = WebSocketService.getInstance();

export default websocketService;