                if ($lot->status !== 'ACTIVE') {
                    // Un proceso anterior pudo completar el reembolso y fallar
                    // antes de resolver una fila histórica de fallos.
                    if ($lot->status === 'REFUNDED') {
                        BingoLotRefundFailure::where('lot_id', $lot->id)
                            ->where('room_id', $room->id)
                            ->where('status', 'PENDING')
                            ->whereNull('resolved_at')
                            ->update([
                                'status' => 'RESOLVED',
                                'resolved_at' => now(),
                            ]);
                    }

                    // Idempotencia: nunca volver a acreditar un lote no activo.
                    return;
                }