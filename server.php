<?php

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server;
use React\Http\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__.'/vendor/autoload.php';

$loop = Factory::create();
$log = new Logger('conveyor');
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

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
$server = new Server(function (ServerRequestInterface $req) use (&$ig, $log) {
  // Only allow POSTs
  if ($req->getMethod() !== 'POST')
    return new Response(405, DEFAULT_HEADERS, json_encode(['error' => 'Only POST requests are allowed']));

  // Get request path
  $path = $req->getUri()->getPath();

  $log->debug('Processing POST '.$path.'...');
  try {
    // Get JSON body
    $bod = json_decode($req->getBody()->getContents());
    switch ($path) {
    case '/ping':
      // General ping
      $log->debug('Ping request, doing nothing.');
      return new Response(200, DEFAULT_HEADERS, json_encode(['pong' => true, 'request_start' => $req->getServerParams()['REQUEST_TIME']]));
    default:
      // Everything else (try to call $ig->[path]->[here])
      // TODO: Sanitize!
      $ret = call_user_func_array([$ig, substr($path, 1)], $bod->args);
      $log->debug('Routine executed successfully, sending response.');
      return new Response(200, DEFAULT_HEADERS, [
        'status' => 'OK',
        'data' => $ret,
      ]);
    }
  } catch (\Throwable $e) {
    // Catch all errors
    $log->error(sprintf('Error processing request %s at %.2f! Caught: %s',
      $path, $req->getServerParams()['REQUEST_TIME_FLOAT'], $e->getMessage()));
    return new Response(500, DEFAULT_HEADERS, json_encode([
      'error' => $e->getMessage(),
    ]));
  }
});

// Listening
$log->info('Hey there! Conveyor 1.0 is booting up!');
$socket = new \React\Socket\Server(isset($argv[1]) ? $argv[1] : '0.0.0.0:8000', $loop);
$server->listen($socket);
$log->info('Listening on *:8000');
$loop->run();