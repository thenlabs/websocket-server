<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer;

use ThenLabs\SocketServer\Connection;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class WebSocketConnection extends Connection
{
    /**
     * @var Frame[]
     */
    protected $buffer = [];

    public function send(string $message)
    {
        $frame = new Frame();
        $frame->setPayload($message);

        return $this->sendFrame($frame);
    }

    public function sendFrame(Frame $frame)
    {
        return $this->write((string) $frame);
    }

    public function getBuffer(): array
    {
        return $this->buffer;
    }

    public function clearBuffer(): void
    {
        $this->buffer = [];
    }

    public function addToBuffer(Frame $frame): void
    {
        $this->buffer[] = $frame;
    }
}
