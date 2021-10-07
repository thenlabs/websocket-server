<?php

use ThenLabs\WebSocketServer\Event\OpenEvent;
use ThenLabs\WebSocketServer\WebSocketServer;
use ThenLabs\WebSocketServer\Event\CloseEvent;
use ThenLabs\WebSocketServer\Event\MessageEvent;

class MyChatServer extends WebSocketServer
{
    protected $users = [];

    public function onOpen(OpenEvent $event): void
    {
        $request = $event->getRequest();
        $nickname = explode('/', $request->getRequestUri())[1];

        // notify to the connected users previously.
        foreach ($this->users as $user) {
            $user->send("User '{$nickname}' has connected.");
        }

        // add the new user to the list.
        $this->users[$nickname] = $event->getConnection();
    }

    public function onMessage(MessageEvent $event): void
    {
        $senderUser = $event->getConnection();
        $senderNick = array_search($senderUser, $this->users);

        $message = $event->getMessage();

        // send the message to the other users.
        foreach ($this->users as $user) {
            if ($user !== $senderUser) {
                $user->send("{$senderNick}: {$message}");
            }
        }
    }

    public function onClose(CloseEvent $event): void
    {
        $user = $event->getConnection();
        $nickname = array_search($user, $this->users);

        // notify to the other users.
        foreach ($this->users as $otherUser) {
            if ($otherUser !== $user) {
                $otherUser->send("The user {$nickname} has disconnected.");
            }
        }

        unset($this->users[$nickname]);
    }
}

