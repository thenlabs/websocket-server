<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use Symfony\Component\HttpFoundation\Request;
use ThenLabs\SocketServer\SocketServer;
use ThenLabs\WebSocketServer\WebSocketConnection;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class OpenEvent extends RequestEvent
{
    public function __construct(SocketServer $server, Request $request, WebSocketConnection $connection)
    {
        parent::__construct($server, $request, $connection);
    }
}
