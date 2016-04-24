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

$route->map('GET', '/discovery', 'Controller::discovery');
$route->map('GET', '/test/{num}', 'DiscoveryTestController::view');
$route->map('GET', '/test/{num}/webmention', 'DiscoveryWebmention::get');
$route->map('POST', '/test/{num}/webmention', 'DiscoveryWebmention::handle');
$route->map('POST', '/test/{num}/webmention/{mode}', 'DiscoveryWebmention::handle');
$route->map('POST', '/test/15', 'DiscoveryWebmention::handle'); // in test #15 the page is its own webmention handler
$route->map('POST', '/test/20', 'DiscoveryWebmention::handle_error'); // for #20, the webmention sent to itself is wrong

$route->map('GET', '/update/{test}', 'UpdateTestController::view');
$route->map('GET', '/update/{test}/step/{step}', 'UpdateTestController::step');
$route->map('GET', '/update/{test}/part/{step}', 'UpdateTestController::step');
$route->map('POST', '/update/{test}/step/{step}/webmention', 'UpdateWebmention::handle');
$route->map('POST', '/update/{test}/part/{step}/webmention', 'UpdateWebmention::handle');

$route->map('GET', '/image', 'ImageProxy::image');

$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

try {
  $response = $route->dispatch($container->get('request'), $container->get('response'));
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
