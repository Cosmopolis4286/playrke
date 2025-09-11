<?php

namespace App\Repositories\Games\Bingo\Cards;

use App\Models\Games\Bingo\Cards\BingoCtnsPayment;

class BingoCtnsPaymentRepository
{
    /**
     * Obtener un pago por ID.
     *
     * @param int $paymentId
     * @param array $fields
     * @return BingoCtnsPayment|null
     */
    public function getById(int $paymentId, array $fields = ['*'])
    {
        return BingoCtnsPayment::select($fields)->find($paymentId);
    }

    /**
     * Listar pagos de una sala de bingo.
     *
     * @param int $roomId
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRoom(int $roomId, array $fields = ['*'])
    {
        return BingoCtnsPayment::select($fields)->where('room_id', $roomId)->get();
    }

    /**
     * Listar pagos de un vendedor.
     *
     * @param int $sellerId
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySeller(int $sellerId, array $fields = ['*'])
    {
        return BingoCtnsPayment::select($fields)->where('seller_id', $sellerId)->get();
    }

    /**
     * Listar pagos de un jugador vinculado.
     *
     * @param int $playerLinkId
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByExternalPlayers(int $playerLinkId, array $fields = ['*'])
    {
        return BingoCtnsPayment::select($fields)->where('external_player_id_id', $playerLinkId)->get();
    }

    /**
     * Listar pagos en una moneda específica.
     *
     * @param int $currencyId
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCurrency(int $currencyId, array $fields = ['*'])
    {
        return BingoCtnsPayment::select($fields)->where('currency_id', $currencyId)->get();
    }

    /**
     * Crear un nuevo pago de cartón.
     *
     * @param array $data
     * @return BingoCtnsPayment
     */
    public function create(array $data)
    {
        return BingoCtnsPayment::create($data);
    }

    /**
     * Actualizar un pago existente.
     *
     * @param int $paymentId
     * @param array $data
     * @return bool
     */
    public function update(int $paymentId, array $data)
    {
        $payment = BingoCtnsPayment::find($paymentId);
        return $payment ? $payment->update($data) : false;
    }

    /**
     * Eliminar un pago.
     *
     * @param int $paymentId
     * @return bool|null
     */
    public function delete(int $paymentId)
    {
        return BingoCtnsPayment::find($paymentId)?->delete();
    }
}
