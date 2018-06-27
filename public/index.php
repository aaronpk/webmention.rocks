<?php
chdir('..');
include('vendor/autoload.php');

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$container = new League\Container\Container;
$container->share('response', Zend\Diactoros\Response::class);
$container->share('request', function () {
  return Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
  );
});
$container->share('emitter', Zend\Diactoros\Response\SapiEmitter::class);

$route = new League\Route\RouteCollection($container);

$route->map('GET', '/', 'Controller::index');

$route->map('GET', '/auth/start', 'AuthController::start');
$route->map('GET', '/auth/callback', 'AuthController::callback');
$route->map('GET', '/auth/signout', 'AuthController::signout');

$route->map('GET', '/discovery', 'Controller::discovery');
$route->map('GET', '/test/{num}', 'DiscoveryTestController::view');
$route->map('GET', '/test/{num}/webmention', 'DiscoveryWebmention::get');
$route->map('POST', '/test/{num}/webmention', 'DiscoveryWebmention::handle');
$route->map('POST', '/test/{num}/webmention/{mode}', 'DiscoveryWebmention::handle');
// in test #15 the page is its own webmention handler
$route->map('POST', '/test/15', 'DiscoveryWebmention::handle'); 
// for #20, the webmention sent to itself is wrong
$route->map('POST', '/test/20', 'DiscoveryWebmention::handle_error'); 
// for #23, the webmention target redirects to the page that advertises the endpoint
$route->map('GET', '/test/23/{key}', 'DiscoveryTestController::redirect23');
$route->map('GET', '/test/23/page/{key}', 'DiscoveryTestController::page23');
$route->map('GET', '/test/23/page/webmention-endpoint/{key}', 'DiscoveryWebmention::test23_get');
$route->map('POST', '/test/23/page/webmention-endpoint/{key}', 'DiscoveryWebmention::test23');


$route->map('GET', '/update/{test}', 'UpdateTestController::view');
$route->map('GET', '/update/{test}/step/{step}', 'UpdateTestController::step');
$route->map('GET', '/update/{test}/part/{step}', 'UpdateTestController::step');
$route->map('POST', '/update/{test}/step/{step}/webmention', 'UpdateWebmention::handle');
$route->map('GET', '/update/{test}/part/{step}/webmention', 'UpdateWebmention::get');
$route->map('POST', '/update/{test}/part/{step}/webmention', 'UpdateWebmention::handle');

$route->map('GET', '/delete/{test}', 'DeleteTestController::view');
$route->map('GET', '/delete/{test}/webmention', 'DeleteWebmention::get');
$route->map('POST', '/delete/{test}/webmention', 'DeleteWebmention::handle');

$route->map('POST', '/receive/discover', 'ReceiverTestController::discover');
$route->map('POST', '/receive/send-webmention', 'ReceiverTestController::send_webmention');
$route->map('GET', '/receive/{test}', 'ReceiverTestController::view');
$route->map('GET', '/receive/{test}/start', 'ReceiverTestController::start');
$route->map('GET', '/receive/{test}/{code}', 'ReceiverTestController::process');

$route->map('GET', '/image', 'ImageProxy::image');

$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

try {
  $response = $route->dispatch($container->get('request'), $container->get('response'));
  $response = $response->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST');
  $container->get('emitter')->emit($response);
} catch(League\Route\Http\Exception\NotFoundException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Not Found\n");
  $container->get('emitter')->emit($response->withStatus(404));
} catch(League\Route\Http\Exception\MethodNotAllowedException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Method not allowed\n");
  $container->get('emitter')->emit($response->withStatus(405));
}
