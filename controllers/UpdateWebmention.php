<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;
use Rocks\HTTP;

class UpdateWebmention extends Webmention {

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

  /*
    Test 1

    > Write a post that links to http://wmrocks.dev/update/1/step/1, and send Webmentions for your post.

    


   */

  private function test_1_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    // If this is a new source URL...

      // Make sure the source page does not have 


    // If this source URL has been seen before (this is an Update webmention)...


  }

  private function test_1_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    
  }


  /* 
    Test 2


  */

  private function test_2_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    
  }

  private function test_2_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    
  }

}
