<?php

require_once __DIR__.'/../../bootstrap.php';

use Monolog\Handler\StreamHandler;
use ThenLabs\WebSocketServer\Event\MessageEvent;
use ThenLabs\WebSocketServer\Event\OpenEvent;
use ThenLabs\WebSocketServer\WebSocketServer;

class MyWebSocketServer extends WebSocketServer
{
    public function onOpen(OpenEvent $event): void
    {
        sleep(1);

        $connection = $event->getConnection();
        $request = $event->getRequest();

        $connection->send('New WebSocketConnection to the path: '.$request->getRequestUri());
    }

    public function onMessage(MessageEvent $event): void
    {
        $connection = $event->getConnection();

        // responds with the same message.
        $connection->send($event->getData());
    }
}

$config = [
    'host'       => $argv[1] ?? '127.0.0.1',
    'port'       => $argv[2] ?? 9090,
    'loop_delay' => 100000, // the default value is 1 but causes conflicts with xdebug.
];

$server = new MyWebSocketServer($config);
$server->getLogger()->pushHandler(new StreamHandler(LOGS_FILE));

$server->start();
