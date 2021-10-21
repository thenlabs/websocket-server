<?php

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
