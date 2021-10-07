<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use ThenLabs\SocketServer\Event\SocketServerEvent;
use ThenLabs\SocketServer\SocketServer;
use ThenLabs\WebSocketServer\Frame;
use ThenLabs\WebSocketServer\WebSocketConnection;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class CloseEvent extends SocketServerEvent
{
    /**
     * @var WebSocketConnection
     */
    protected $connection;

    /**
     * @var Frame
     */
    protected $frame;

    public function __construct(SocketServer $server, WebSocketConnection $connection, Frame $frame)
    {
        parent::__construct($server);

        $this->connection = $connection;
        $this->frame = $frame;
    }

    public function getConnection(): WebSocketConnection
    {
        return $this->connection;
    }

    public function getFrame(): Frame
    {
        return $this->frame;
    }
}
