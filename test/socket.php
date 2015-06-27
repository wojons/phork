<?php
require '../vendor/autoload.php';

use Wojons\Phork\Socket;

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$socket = new Socket('192.168.10.10', 4444);

try {
    $socket->execute();
} catch (Exception $e) {
    print_r($e);
}

print 'done' . PHP_EOL;
