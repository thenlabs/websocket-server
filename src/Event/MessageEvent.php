<?php
declare(strict_types=1);

namespace ThenLabs\WebSocketServer\Event;

use ThenLabs\SocketServer\Event\DataEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class MessageEvent extends DataEvent
{
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
