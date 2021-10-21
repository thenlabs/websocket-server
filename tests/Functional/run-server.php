<?php

require_once __DIR__.'/../../bootstrap.php';
require_once __DIR__.'/MyWebSocketServer.php';

$config = [
    'host'       => $argv[1] ?? '127.0.0.1',
    'port'       => $argv[2] ?? 9090,
    'loop_delay' => 100000, // the default value is 1 but causes conflicts with xdebug.
];

$server = new MyWebSocketServer($config);
$server->start();
