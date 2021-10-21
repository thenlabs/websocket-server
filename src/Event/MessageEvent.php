<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\Frame;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class MessageEvent extends DataEvent
{
    /**
     * @var Frame[]
     */
    protected $frames = [];

    public function setFrames(array $frames): void
    {
        $this->frames = $frames;
    }

    public function getFrames(): array
    {
        return $this->frames;
    }

    /**
     * Is alias of getData()
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->data;
    }
}
