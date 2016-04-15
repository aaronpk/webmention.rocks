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
$route->map('GET', '/test/{num}', 'Controller::test');
$route->map('GET', '/test/{num}/webmention', 'Webmention::get');
$route->map('POST', '/test/{num}/webmention', 'Webmention::handle');
$route->map('POST', '/test/{num}/webmention/{mode}', 'Webmention::handle');
$route->map('POST', '/test/15', 'Webmention::handle'); // in test #15 the page is its own webmention handler
$route->map('POST', '/test/20', 'Webmention::handle_error'); // for #20, the webmention sent to itself is wrong
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
