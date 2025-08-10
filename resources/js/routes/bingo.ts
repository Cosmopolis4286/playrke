// resources/js/routes/bingo.ts
import { buildRoute } from './helper';

export const bingoRoutes = {
  form: (): string => '/bingo/form',

  callNumber: (roomId: string | number): string =>
    `/bingo/room/${encodeURIComponent(roomId.toString())}/callNumber`,

  showGameInfo: (): string => '/bingo/showGameInfo',

  bingoPatterns: (userToken?: string): string =>
    userToken
      ? `/bingo/configuration/patterns/${encodeURIComponent(userToken)}`
      : `/bingo/configuration/patterns`,

  configuration: (userToken?: string): string =>
    userToken
      ? `/bingo/configuration/${encodeURIComponent(userToken)}`
      : `/bingo/configuration`,

  getInfo: (id: string | number, starTime?: string | number): string =>
    starTime !== undefined
      ? `/bingo/getInfo/${encodeURIComponent(id.toString())}/${encodeURIComponent(starTime.toString())}`
      : `/bingo/getInfo/${encodeURIComponent(id.toString())}`,

  getInfoByEncrypt: (id: string | number, creatorUser: string | number, coin: string | number): string =>
    `/bingo/getInfoByEncrypt/${encodeURIComponent(id.toString())}/${encodeURIComponent(creatorUser.toString())}/${encodeURIComponent(coin.toString())}`,

  getInfoPublicRoom: (): string => '/bingo/getInfoPublicRoom',

  getInfoPublicRoomPendingPayments: (): string => '/bingo/getInfoPublicRoomPendingPayments',

  getRoundsConfig: (room: string | number, type: string | number): string =>
    `/bingo/getRoundsConfig/${encodeURIComponent(room.toString())}/${encodeURIComponent(type.toString())}`,

  getDateTimeRooms: (creatorUser: string | number, player: string | number): string =>
    `/bingo/getDateTimeRooms/${encodeURIComponent(creatorUser.toString())}/${encodeURIComponent(player.toString())}`,

  getCtnPriceForCoin: (
    creation: string | number,
    creatorType: string | number,
    creatorID: string | number,
    room?: string | number
  ): string =>
    room !== undefined
      ? `/bingo/getCtnPriceForCoin/${encodeURIComponent(creation.toString())}/${encodeURIComponent(creatorType.toString())}/${encodeURIComponent(creatorID.toString())}/${encodeURIComponent(room.toString())}`
      : `/bingo/getCtnPriceForCoin/${encodeURIComponent(creation.toString())}/${encodeURIComponent(creatorType.toString())}/${encodeURIComponent(creatorID.toString())}`,

  participateInPublicRoom: (id?: string | number, getOut?: string | number): string => {
    if (id !== undefined && getOut !== undefined) {
      return `/bingo/participate/public-room/${encodeURIComponent(id.toString())}/${encodeURIComponent(getOut.toString())}`;
    }
    if (id !== undefined) {
      return `/bingo/participate/public-room/${encodeURIComponent(id.toString())}`;
    }
    return `/bingo/participate/public-room`;
  },

  notifyPaymentPrizeWinningCtn: (): string => '/bingo/carton/notify/payment/win-ctn',

  handleBingoRules: (id?: string | number): string =>
    id !== undefined
      ? `/bingo/handle-bingo-rules/${encodeURIComponent(id.toString())}`
      : '/bingo/handle-bingo-rules',

  reportSalesRooms: (coin?: string | number, dateFilter?: string | number): string => {
    let url = '/bingo/sales-report';
    if (coin !== undefined) url += `/${encodeURIComponent(coin.toString())}`;
    if (dateFilter !== undefined) url += `/${encodeURIComponent(dateFilter.toString())}`;
    return url;
  },

  reportSalesByRoom: (room: string | number, coin: string | number): string =>
    `/bingo/sales-report-by-room/${encodeURIComponent(room.toString())}/${encodeURIComponent(coin.toString())}`,

  reportSalesByRoomWinners: (room: string | number, coin: string | number): string =>
    `/bingo/sales-report-by-room-winners/${encodeURIComponent(room.toString())}/${encodeURIComponent(coin.toString())}`,

  reportSalesByRoomPlayer: (room: string | number, playerLink: string | number): string =>
    `/bingo/sales-report-by-room/${encodeURIComponent(room.toString())}/player/${encodeURIComponent(playerLink.toString())}`,

  ctnsAwarded: (type: string | number, dateFilter: string | number, coin?: string | number): string => {
    let url = `/bingo/winners/${encodeURIComponent(type.toString())}/${encodeURIComponent(dateFilter.toString())}`;
    if (coin !== undefined) url += `/${encodeURIComponent(coin.toString())}`;
    return url;
  },

  ctnsSold: (coin?: string | number, dateFilter?: string | number): string => {
    let url = '/bingo/cartons-sold';
    if (coin !== undefined) url += `/${encodeURIComponent(coin.toString())}`;
    if (dateFilter !== undefined) url += `/${encodeURIComponent(dateFilter.toString())}`;
    return url;
  },

  create: (): string => '/bingo/create',

  update: (id: string | number): string => `/bingo/update/${encodeURIComponent(id.toString())}`,

  delete: (id: string | number): string => `/bingo/delete/${encodeURIComponent(id.toString())}`,

  changeStatus: (id: string | number): string => `/bingo/status/${encodeURIComponent(id.toString())}`,

  replay: (id: string | number): string => `/bingo/replay/${encodeURIComponent(id.toString())}`,

  publish: (id: string | number): string => `/bingo/publish/${encodeURIComponent(id.toString())}`,

  refund: (id: string | number): string => `/bingo/refund/${encodeURIComponent(id.toString())}`,

  copy: (id: string | number): string => `/bingo/copy/${encodeURIComponent(id.toString())}`,

  testRegenerateDraw: (): string => '/bingo/test/regenerate-draw',

  gameRoomsAdmin: (user: string | number, coin?: string | number): string =>
    coin !== undefined
      ? `/bingo/game-rooms/admin/${encodeURIComponent(user.toString())}/${encodeURIComponent(coin.toString())}`
      : `/bingo/game-rooms/admin/${encodeURIComponent(user.toString())}`,

  gameRoomsCommerce: (roomType: string | number, coin?: string | number): string =>
    coin !== undefined
      ? `/bingo/${encodeURIComponent(roomType.toString())}/game-rooms/${encodeURIComponent(coin.toString())}`
      : `/bingo/${encodeURIComponent(roomType.toString())}/game-rooms`,

  actWn: (): string => '/bingo/actWn',
} as const;

type BingoRouteName = keyof typeof bingoRoutes;

/**
 * Función de ayuda para construir rutas de Bingo.
 */
export function route<K extends BingoRouteName>(
  name: K,
  ...params: typeof bingoRoutes[K] extends (...args: infer P) => string ? P : []
): string {
  return buildRoute(bingoRoutes, name, ...params);
}