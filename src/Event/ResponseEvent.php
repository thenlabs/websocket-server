<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use Symfony\Component\HttpFoundation\Response;
use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\Event\SocketServerEvent;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class ResponseEvent extends SocketServerEvent
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(SocketServer $server, Response $response, Connection $connection)
    {
        parent::__construct($server);

        $this->response = $response;
        $this->connection = $connection;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
