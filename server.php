<?php

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server;
use React\Http\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use InstagramAPI\Exception\InstagramException;

require_once __DIR__.'/vendor/autoload.php';

// Loading dotenv file
\Dotenv\Dotenv::create(__DIR__)->load();

// Creating master loop and logger
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
      return new Response(200, DEFAULT_HEADERS, json_encode([
        'status' => 'OK',
        'pong' => true,
        'request_start' => $req->getServerParams()['REQUEST_TIME'],
      ]));
    default:
      // Everything else (try to call $ig->[path]->[here])

      // Sanitizing
      if (!preg_match('/^[A-Z0-9\/]*$/i', $path))
        throw new \Exception('Unacceptable character in requested URL');
      
      // Deep searching for requested method
      $callpath = explode('/', substr($path, 1));
      $cobj = $ig;
      $refl = new ReflectionClass($ig);
      foreach ($callpath as $nextnode) {
        if ($refl->hasMethod($nextnode)) {
          $ret = $refl->getMethod($nextnode)->invokeArgs($cobj, $bod->args);
          break;
        }
        if ($refl->hasProperty($nextnode)) {
          $cobj = $refl->getProperty($nextnode)->getValue($ig);
          $refl = new ReflectionClass($cobj);
          continue;
        }
      }

      // We got no return value :c
      if (!isset($ret))
        throw new \Exception('Cannot find method '.$path);

      // Everything's fine, we're ready to send response
      $log->debug('Routine executed successfully, sending response.');
      return new Response(200, DEFAULT_HEADERS, json_encode([
        'status' => 'OK',
        'data' => $ret,
      ]));
    }
  } catch (\Throwable $e) {
    // Catch all errors
    $log->error(sprintf('Error processing request %s at %.2f! Caught: %s',
      $path, $req->getServerParams()['REQUEST_TIME_FLOAT'], $e->getMessage()));
    return new Response(500, DEFAULT_HEADERS, json_encode([
      'status' => 'ERR',
      'error' => $e->getMessage(),
    ]));
  }
});

// Login
// TODO: What about challenges?
// TODO: Decide if STDIN could be used for 2FA & Challenges
$log->info('Hey there! Conveyor 1.0 is booting up!');
$log->info('Loggin\' you in...');
try {
  $loginResp = $ig->login(getenv('IG_USERNAME'), getenv('IG_PASSWORD'));
  if ($loginResp !== null && $loginResp->isTwoFactorRequired()) {
    $ig->finishTwoFactorLogin(getenv('IG_USERNAME'), getenv('IG_PASSWORD'),
      $loginResp->getTwoFactorInfo()->getTwoFactorIdentifier(), getenv('TWOFACTOR_CODE'));
    $log->info('Two-factor login completed. Yay!');
  }
  $log->info('Single-factor login completed. Yay!');
// } catch (InstagramException $e) {
//   $log->error('Instagram exception caught!');
//   print_r($e->getResponse()->getChallenge());
//   exit(1);
} catch (\Exception $e) {
  $log->error('Conveyor could not log into your IG account. Exiting. Error: '.$e->getMessage());
  exit(1);
}

// Listening
$listenon = isset($argv[1]) ? $argv[1] : '0.0.0.0:8000';
$socket = new \React\Socket\Server($listenon, $loop);
$server->listen($socket);
$log->info('Conveyor listening on '.$listenon);
$loop->run();