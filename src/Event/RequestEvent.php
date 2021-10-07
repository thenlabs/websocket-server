<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use Symfony\Component\HttpFoundation\Request;
use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\Event\SocketServerEvent;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class RequestEvent extends SocketServerEvent
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(SocketServer $server, Request $request, Connection $connection)
    {
        parent::__construct($server);

        $this->request = $request;
        $this->connection = $connection;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
