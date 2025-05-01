<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

require 'vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
    // 限制最大連接數
    $maxConnections = 100; // 設定最大連接數
    if (count($this->clients) >= $maxConnections) {
        $conn->close(); // 超出限制，拒絕連接
        echo "Connection rejected: max connections reached ({$maxConnections})\n";
        return;
    }
    $this->clients->attach($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "收到訊息: $msg\n";
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // 移除斷開的連接
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$chatApp = new Chat();
$loop = Factory::create();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $chatApp
        )
    ),
    8080,
    '0.0.0.0',
    $loop
);

// 使用定期計時器來清理不活動的連接
$loop->addPeriodicTimer(5, function() use ($chatApp) {
    foreach ($chatApp->clients as $client) {
        if (!$client->isConnected()) {
            $chatApp->clients->detach($client);
        }
    }
    echo "Cleanup inactive connections";
});

$server->run();