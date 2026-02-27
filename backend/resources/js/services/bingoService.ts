import { backendClient as api } from '@js/plugins/axios';
import type { AxiosResponse } from 'axios';

// Tipos para el servicio de Bingo
export interface BingoRoom {
  id: string;
  encrypted: string;
  uuidCode: string;
  status: number;
  roomType: number;
  roomTypeDescription: string;
  creator: {
    id: string;
    username: string;
    email?: string;
  };
  paidWin: boolean;
  publicationDate: Date;
  cardPrice: number;
  players: number;
  ctns: number;
  allSale: boolean;
  rkeCommn: number;
  roomCommn: number;
  commission: number;
  prizePool: number;
  fullHousePrize: number;
  serialPrize: number;
  closeRoom: boolean;
  currentPlayers: number;
  currentCards: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface BingoCard {
  id: string;
  roomId: string;
  userId: string;
  serialNumber: string;
  cardMatrix: {
    B: [number, number, number, number, number];
    I: [number, number, number, number, number];
    N: [number, number, number, number, number];
    G: [number, number, number, number, number];
    O: [number, number, number, number, number];
  };
  isActive: boolean;
  purchaseDate: Date;
  price: number;
  currencyId: string;
  lot: {
    id: string;
    serial: string;
  };
}

export interface BingoRound {
  id: string;
  roomId: string;
  round: number;
  startTime: Date | null;
  lastChip: number | null;
  bingoPatternId: string | null;
  prizeType: 'FULL_HOUSE' | 'SERIAL' | 'PATTERN';
  prizeValue: number;
  status: 'WAITING' | 'IN_PROGRESS' | 'FINISHED' | 'CANCELLED';
  drawnNumbers: number[];
  winners: BingoWinner[];
  createdAt: Date;
  updatedAt: Date;
}

export interface BingoWinner {
  id: string;
  userId: string;
  username: string;
  cardId: string;
  cardSerial: string;
  prizeType: 'FULL_HOUSE' | 'SERIAL' | 'PATTERN';
  prizeAmount: number;
  winTime: Date;
  pattern?: string;
}

export interface BingoGameStats {
  room: {
    id: string;
    name: string;
    totalPlayers: number;
    totalCards: number;
    prizePool: number;
  };
  currentRound: {
    id: string;
    number: number;
    status: string;
    drawnNumbers: number[];
    timeElapsed: number;
  };
  nextDraw: {
    scheduled: boolean;
    timeRemaining: number;
  };
}

export interface CreateRoomRequest {
  roomType: number;
  paidWin?: boolean;
  publicationDate?: Date;
  cardPrice: number;
  maxPlayers?: number;
  maxCards?: number;
  allSale?: boolean;
  rkeCommission?: number;
  roomCommission?: number;
  sellerCommission?: number;
  fullHousePrize?: number;
  serialPrize?: number;
}

export interface BuyCardsRequest {
  roomId: string;
  quantity: number;
  currencyId: string;
}

export interface CreateRoundRequest {
  roomId: string;
  round: number;
  bingoPatternId?: string;
  prizeType: 'FULL_HOUSE' | 'SERIAL' | 'PATTERN';
  prizeValue: number;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message: string;
  errors?: Array<{
    field: string;
    message: string;
    code: string;
  }>;
}

export interface RoomListResponse {
  rooms: BingoRoom[];
  total: number;
  page: number;
  totalPages: number;
}

/**
 * Servicio para gestión de Bingo
 */
export class BingoService {

  // === SALAS DE BINGO ===

  /**
   * Obtener tipos de sala disponibles
   */
  static async getRoomTypes(): Promise<ApiResponse<any[]>> {
    try {
      const response: AxiosResponse<ApiResponse<any[]>> = await api.get('/bingo/room-types');
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Listar salas disponibles
   */
  static async listRooms(params?: {
    roomType?: number;
    creatorId?: string;
    limit?: number;
    offset?: number;
  }): Promise<ApiResponse<RoomListResponse>> {
    try {
      const response: AxiosResponse<ApiResponse<RoomListResponse>> = await api.get('/bingo/rooms', {
        params
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener mis salas creadas
   */
  static async getMyRooms(limit = 20, offset = 0): Promise<ApiResponse<RoomListResponse>> {
    try {
      const response: AxiosResponse<ApiResponse<RoomListResponse>> = await api.get('/bingo/rooms/my', {
        params: { limit, offset }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Crear nueva sala
   */
  static async createRoom(roomData: CreateRoomRequest): Promise<ApiResponse<BingoRoom>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRoom>> = await api.post('/bingo/rooms', roomData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener sala por ID
   */
  static async getRoomById(roomId: string): Promise<ApiResponse<BingoRoom>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRoom>> = await api.get(`/bingo/rooms/${roomId}`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener sala por código
   */
  static async getRoomByCode(encrypted: string): Promise<ApiResponse<BingoRoom>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRoom>> = await api.get(`/bingo/rooms/code/${encrypted}`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Actualizar sala
   */
  static async updateRoom(roomId: string, updateData: Partial<CreateRoomRequest>): Promise<ApiResponse<BingoRoom>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRoom>> = await api.put(`/bingo/rooms/${roomId}`, updateData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Unirse a sala
   */
  static async joinRoom(roomId: string): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/bingo/rooms/${roomId}/join`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Cerrar sala
   */
  static async closeRoom(roomId: string): Promise<ApiResponse<BingoRoom>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRoom>> = await api.delete(`/bingo/rooms/${roomId}`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  // === CARTONES ===

  /**
   * Comprar cartones
   */
  static async buyCards(cardsData: BuyCardsRequest): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post('/bingo/cards/buy', cardsData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener mis cartones en una sala
   */
  static async getMyCardsInRoom(roomId: string): Promise<ApiResponse<BingoCard[]>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoCard[]>> = await api.get(`/bingo/rooms/${roomId}/cards`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener cartón por ID
   */
  static async getCardById(cardId: string): Promise<ApiResponse<BingoCard>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoCard>> = await api.get(`/bingo/cards/${cardId}`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Validar BINGO
   */
  static async validateBingo(cardId: string, drawnNumbers: number[], claimedPattern?: string): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/bingo/cards/${cardId}/validate`, {
        drawnNumbers,
        claimedPattern
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  // === RONDAS ===

  /**
   * Crear ronda
   */
  static async createRound(roundData: CreateRoundRequest): Promise<ApiResponse<BingoRound>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRound>> = await api.post('/bingo/rounds', roundData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Iniciar ronda
   */
  static async startRound(roundId: string): Promise<ApiResponse<BingoRound>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRound>> = await api.put(`/bingo/rounds/${roundId}/start`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Sacar número
   */
  static async drawNumber(roundId: string, roomId: string, number: number): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/bingo/rounds/${roundId}/draw`, {
        roomId,
        number
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Reclamar BINGO
   */
  static async claimBingo(roundId: string, cardId: string, claimedPattern?: string): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/bingo/rounds/${roundId}/claim`, {
        cardId,
        claimedPattern
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener ronda actual de una sala
   */
  static async getCurrentRound(roomId: string): Promise<ApiResponse<BingoRound>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRound>> = await api.get(`/bingo/rooms/${roomId}/current-round`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener historial de rondas
   */
  static async getRoundsHistory(roomId: string, limit = 10): Promise<ApiResponse<BingoRound[]>> {
    try {
      const response: AxiosResponse<ApiResponse<BingoRound[]>> = await api.get(`/bingo/rooms/${roomId}/rounds/history`, {
        params: { limit }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  // === UTILIDADES ===

  /**
   * Obtener letra de un número de BINGO
   */
  static getNumberLetter(number: number): string {
    if (number >= 1 && number <= 15) return 'B';
    if (number >= 16 && number <= 30) return 'I';
    if (number >= 31 && number <= 45) return 'N';
    if (number >= 46 && number <= 60) return 'G';
    if (number >= 61 && number <= 75) return 'O';
    return '';
  }

  /**
   * Validar número de BINGO
   */
  static isValidBingoNumber(number: number): boolean {
    return number >= 1 && number <= 75;
  }

  /**
   * Formatear precio con moneda
   */
  static formatPrice(price: number, currency = 'USD'): string {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency,
      minimumFractionDigits: 2
    }).format(price);
  }

  /**
   * Obtener estado de la sala
   */
  static getRoomStatusText(status: number): string {
    const statuses: { [key: number]: string } = {
      1: 'Abierta',
      2: 'En Juego',
      3: 'Pausada',
      4: 'Finalizada',
      5: 'Cancelada'
    };
    return statuses[status] || 'Desconocido';
  }

  /**
   * Manejar errores de la API
   */
  private static handleError(error: any): ApiResponse {
    if (error.response?.data) {
      return error.response.data;
    }

    return {
      success: false,
      message: 'Error de conexión con el servidor',
      errors: [{
        field: 'network',
        message: 'No se pudo conectar con el servidor',
        code: 'NETWORK_ERROR'
      }]
    };
  }
}

export default BingoService;
