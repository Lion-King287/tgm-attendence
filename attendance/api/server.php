<?php
require '../vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

// Eigene WebSocket-Klasse
class Chat implements Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Neue Verbindung ({$conn->resourceId})\n";
    }

    public function onMessage(Ratchet\ConnectionInterface $from, $msg) {
        echo "Nachricht erhalten: $msg\n";

        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($msg);
            }
        }
    }

    public function onClose(Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Verbindung geschlossen ({$conn->resourceId})\n";
    }

    public function onError(Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Fehler: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Server starten
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

echo "WebSocket-Server läuft auf Port 8080...\n";
$server->run();
