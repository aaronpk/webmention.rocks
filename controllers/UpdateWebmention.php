<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;
use Rocks\HTTP;

class UpdateWebmention {

  public function handle(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    if(!array_key_exists('test', $args) || !array_key_exists('step', $args)) {
      $response->getBody()->write('Bad Request');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
    }

    $method = 'test_'.$args['test'].'_step_'.$args['step'];
    if(method_exists($this, $method)) {
      return $this->$method($request, $response);
    }

    $response->getBody()->write('Bad Request');
    return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
  }

  private function test_1_step_1(ServerRequestInterface $request, ResponseInterface $response) {

  }

  private function test_1_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    
  }

  private function test_2_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    
  }

  private function test_2_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    
  }

}
