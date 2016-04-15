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

  private function _basic_verification(ServerRequestInterface $request, ResponseInterface $response, $num) {

    $contentType = $request->getHeaderLine('Content-type');
    if(strpos($contentType, 'application/x-www-form-urlencoded') === false) {
      return $this->_error($request, $response, 
        'invalid_content_type', 
        'Content type must be set to application/x-www-form-urlencoded');
    }

    // These endpoints are always single-use, so reject completely if it has been used before
    $query = $request->getQueryParams();
    if(!array_key_exists('key', $query)) {
      return $this->_error($request, $response,
        'invalid_request',
        'The request was missing a key. Most likely this means you dropped the query string parameters or sent them as POST body parameters.'
        );
    }

    // This key must exist in the cache, otherwise the endpoint expired or has already been used
    if(Rocks\Redis::useOneTimeKey($query['key']) == false) {
      return $this->_error($request, $response,
        'invalid_request',
        'This Webmention endpoint has expired. You need to re-discover the endpoint for the post you are replying to.'
        );
    }

    // Initial Webmention verification, check source & target syntax, check for link to this page
    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $targetURL = @$post['target'];

    // Validate the syntax of the source URL
    $response = $this->_validateSourceURL($request, $response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, 'update', $num);
    if($response->getStatusCode() == 400)
      return $response;

    // Fetch the source URL and save the HTTP response
    $source = $this->_fetchSourceURL($request, $response, $sourceURL);
    if(!is_array($source) && $source->getStatusCode() == 400)
      return $source;

    return [
      'sourceURL' => $sourceURL,
      'targetURL' => $targetURL,
      'source' => $source,
    ];
  }

  /*
    Test 1

    > Write a post that links to http://wmrocks.dev/update/1/step/1, and send Webmentions for your post.

    * this should send a webmention *only* to step/1
    * if step/2 receives a webmention before source URL is already registered, the test fails


   */

  private function test_1_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 1);
    if(!is_array($info)) {
      return $info; // is an error response
    }

    // If this is a new source URL...

      // Make sure the source page does not have a link to /update/1


    // If this source URL has been seen before (this is an Update webmention)...


  }

  private function test_1_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 1);
    if(!is_array($info)) {
      return $info;
    }

    // If this is a new source URL...

      // The test fails, delete all progress for this source URL


    // If this source URL has been seen before (this is an Update webmention)...

    
  }


  /* 
    Test 2


  */

  private function test_2_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 2);
    if(!is_array($info)) {
      return $info;
    }

  }

  private function test_2_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 2);
    if(!is_array($info)) {
      return $info;
    }

    
  }

}
