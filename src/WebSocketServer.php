<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ThenLabs\HttpServer\Utils;
use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\SocketServer;
use ThenLabs\SocketServer\Task\ConnectionTask;
use ThenLabs\WebSocketServer\Event\CloseEvent;
use ThenLabs\WebSocketServer\Event\MessageEvent;
use ThenLabs\WebSocketServer\Event\OpenEvent;
use ThenLabs\WebSocketServer\Event\RequestEvent;
use ThenLabs\WebSocketServer\Event\ResponseEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class WebSocketServer extends SocketServer
{
    public const PROTOCOL_VERSION = 13;
    public const HANDSHAKE_MAGIC_STRING = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public function __construct(array $config = [])
    {
        $config['fread_length'] = $config['fread_length'] ?? 1500;
        $config['socket'] = "tcp://{$config['host']}:{$config['port']}";

        parent::__construct($config);

        $callback = [$this, 'onOpen'];
        if (is_callable($callback)) {
            $this->dispatcher->addListener(OpenEvent::class, $callback);
        }

        $callback = [$this, 'onMessage'];
        if (is_callable($callback)) {
            $this->dispatcher->addListener(MessageEvent::class, $callback);
        }

        $callback = [$this, 'onClose'];
        if (is_callable($callback)) {
            $this->dispatcher->addListener(CloseEvent::class, $callback);
        }
    }

    public function onConnection(ConnectionEvent $event): void
    {
        $connection = $event->getConnection();

        if (! $httpRequestMessage = $connection->read($this->config['fread_length'])) {
            return;
        }

        $request = Utils::createRequestFromHttpMessage($httpRequestMessage);

        if (! $request instanceof Request) {
            return;
        }

        $requestEvent = new RequestEvent($this, $request, $connection);
        $this->dispatcher->dispatch($requestEvent);

        if (self::PROTOCOL_VERSION != $request->headers->get('Sec-WebSocket-Version')) {
            $connection->close();
            $this->logger->error('Unsupported version of the WebSocket protocol. The connection has been closed.');
            return;
        }

        $key = $request->headers->get('Sec-WebSocket-Key');
        $handshake = base64_encode(sha1($key.self::HANDSHAKE_MAGIC_STRING, true));

        $response = new Response('', 101);
        $response->setProtocolVersion($request->getProtocolVersion());
        $response->headers->remove('Cache-Control');
        $response->headers->set('Upgrade', 'websocket');
        $response->headers->set('Connection', 'Upgrade');
        $response->headers->set('Sec-WebSocket-Accept', $handshake);
        $response->headers->set('X-Powered-By', 'ThenLabs\WebSocketServer');

        $responseEvent = new ResponseEvent($this, $response, $connection);
        $this->dispatcher->dispatch($responseEvent);

        $connection->write((string) $response);

        $webSocketConnection = new WebSocketConnection(
            $connection->getServer(),
            $connection->getSocket()
        );

        foreach ($this->getLoop()->getTasks() as $task) {
            if ($task instanceof ConnectionTask &&
                $connection === $task->getConnection()
            ) {
                (function ($webSocketConnection) {
                    $this->connection = $webSocketConnection;
                })->call($task, $webSocketConnection);

                break;
            }
        }

        $openEvent = new OpenEvent($this, $request, $webSocketConnection);
        $this->dispatcher->dispatch($openEvent);
    }

    public function onData(DataEvent $event): void
    {
        $data = $event->getData();
        $webSocketConnection = $event->getConnection();

        $frame = Frame::createFromString($data);

        if (! $frame instanceof Frame) {
            return;
        }

        // Client frames must always be masked.
        if (! $frame->getMask()) {
            $this->logger->error('The connection was closed because we receive a client frame without masking.');
            fclose($webSocketConnection->getSocket());
            return;
        }

        if (1 == $frame->getFin()) {
            switch ($frame->getOpode()) {
                case Frame::OPCODE_CONTINUATION:
                case Frame::OPCODE_TEXT:
                case Frame::OPCODE_BINARY:
                    $payload = $frame->getPayload();

                    if (Frame::OPCODE_CONTINUATION === $frame->getOpode()) {
                        $previousPayload = '';

                        foreach ($webSocketConnection->getBuffer() as $previousFrame) {
                            $previousPayload .= $previousFrame->getPayload();
                        }
                        $webSocketConnection->clearBuffer();

                        $payload = $previousPayload.$payload;
                    }

                    $frames = $webSocketConnection->getBuffer();
                    $frames[] = $frame;

                    $messageEvent = new MessageEvent($this, $webSocketConnection, $payload);
                    $messageEvent->setFrames($frames);

                    $this->dispatcher->dispatch($messageEvent);
                    break;

                case Frame::OPCODE_PING:
                    $this->logger->debug('we receive a ping...we will respond with a pong.');

                    $pongFrame = new Frame();
                    $pongFrame->setFin(true);
                    $pongFrame->setOpcode(Frame::OPCODE_PONG);
                    $pongFrame->setPayload($frame->getPayload());

                    $webSocketConnection->sendFrame($pongFrame);
                    break;

                case Frame::OPCODE_CLOSE:
                    $this->logger->debug('The client did request close the connection.');

                    $closeEvent = new CloseEvent($this, $webSocketConnection, $frame);
                    $this->dispatcher->dispatch($closeEvent);

                    $webSocketConnection->sendFrame($frame);
                    fclose($webSocketConnection->getSocket());
                    break;
            }
        } else {
            $webSocketConnection->addToBuffer($frame);
        }
    }
}
