<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/MyChatServer.php';

$config = [
    'host' => $argv[1] ?? '127.0.0.1',
    'port' => $argv[2] ?? 9090,
];

$server = new MyChatServer($config);
$server->start();