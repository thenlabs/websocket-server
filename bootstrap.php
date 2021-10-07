<?php

require_once __DIR__.'/vendor/autoload.php';

define('LOGS_FILE', __DIR__.'/tests/Functional/.logs/test.logs');

if (! is_dir(dirname(LOGS_FILE))) {
    mkdir(dirname(LOGS_FILE));
}