<?php

// WebSocketHandler.php

namespace App\Http\Controllers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class WebSocketHandler implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    // Método para obtener los clientes
    public function getClients()
    {
        return $this->clients;
    }   

    public function onOpen(ConnectionInterface $conn)
    {
        // Almacenar la nueva conexión para enviar mensajes más tarde
        $this->clients->attach($conn);
        echo "Nueva conexión! ({$conn->resourceId})\n";

        // Aquí podrías implementar la lógica de autenticación del usuario si fuera necesario
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Manejar los mensajes recibidos, por ejemplo, puedes verificar si es un evento específico
        // y enviar un mensaje a todos los clientes conectados

        // Por ahora, no necesitamos lógica específica de mensaje
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Eliminar la conexión cuando se cierre
        $this->clients->detach($conn);
        echo "Conexión {$conn->resourceId} ha sido desconectada\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Manejar errores de conexión
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

   public function sendRoomInfo(ConnectionInterface $conn, $roomId)
{
    // Obtén la información de la sala desde la base de datos
    $data = DB::table("bingo_room")->where('id', $roomId)->first();

    // Verifica si se obtuvieron datos
    if ($data) {
        // Codifica los datos como JSON
        $jsonData = json_encode($data);

        // Envía los datos a través del canal
        $conn->send($jsonData);

        // Mensaje de depuración
        echo "Información de la sala enviada a través del canal WebSocket.\n";
    } else {
        // Mensaje de depuración si no se obtuvieron datos
        echo "No se pudo obtener información de la sala para el ID {$roomId}.\n";
    }
}

    
}
