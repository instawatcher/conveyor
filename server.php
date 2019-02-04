<?php

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server;
use React\Http\Response;

require_once __DIR__.'/vendor/autoload.php';

$loop = Factory::create();

// Creating IG instance
$ig = new \InstagramAPI\Instagram();

// Default headers
define('DEFAULT_HEADERS', array(
  'Content-Type' => 'application/json',
  'Pragma' => 'no-cache',
  'Server' => 'iw-conveyor/1.0',
  'X-Powered-By' => 'mgp25\'s Instagram API',
));

// Instantiating server
$server = new Server(function (ServerRequestInterface $req) use (&$ig) {
  $path = $req->getUri()->getPath();
  if ($path === '/login') {
    // TODO: Login logic here
  }
});

// Listening
$socket = new \React\Socket\Server(isset($argv[1]) ? $argv[1] : '0.0.0.0:8000', $loop);
$server->listen($socket);
echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
$loop->run();