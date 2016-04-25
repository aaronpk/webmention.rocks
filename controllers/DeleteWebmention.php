<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DeleteTestData;
use Rocks\HTTP;

class DeleteWebmention extends Webmention {

  public function handle(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    if(!array_key_exists('test', $args)) {
      $response->getBody()->write('Bad Request');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
    }

    $method = 'test_'.$args['test'];
    if(method_exists($this, $method)) {
      return $this->$method($request, $response, $args);
    }

    $response->getBody()->write('Bad Request');
    return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
  }

  public function test_1(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['test'];

    // $response = $this->_validateOneTimeEndpoint($request, $response);
    // if($response->getStatusCode() == 400)
    //   return $response;

    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $targetURL = @$post['target'];

    // Validate the syntax of the source URL
    $response = $this->_validateSourceURL($request, $response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, 'delete', $num);
    if($response->getStatusCode() == 400)
      return $response;

    $sourceID = Rocks\Redis::makeSourceID($sourceURL);

    $result = $this->_fetchSourceURL($request, $response, $sourceURL);
    if(!is_array($result) && $result->getStatusCode() == 400)
      return $result;
    $source = $result;

    $existing = Rocks\Redis::getSource($sourceID, true);

    // If there is not an in-progress webmention for this source, verify the webmention as normal
    if(!$existing) {

      $response = $this->_verifySourceLinksToTarget($request, $response, $source, $targetURL);
      if($response->getStatusCode() == 400)
        return $response;

      $this->_storeResponseData($sourceID, 1,
        $source, $sourceURL, $targetURL, 'source', false);

      // Store as passing test 1 part 1
      // Should show up as a single checkmark
      Rocks\Redis::setSourceHasPassedPart($sourceID, 1, 1, 'delete');
      Rocks\Redis::addInProgressResponse(1, $sourceID, 'delete');
      Rocks\Redis::extendExpiration(1, $sourceID, 'delete');

      $response->getBody()->write('Got it! You\'ve completed step one and you should see your in-progress response on ' . Config::$base . 'delete/1. You have 10 minutes to complete the next step.');
      return $response;

    } else {
    // If we've seen this before, then make sure the source has been deleted and returns HTTP 410

      $doc = $this->_HTMLtoDOMDocument($source['body']);

      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'delete/1', false)) {
        return $this->_error($request, $response,
          'not_deleted',
          'It looks like your post still has a link to this test. Make sure you delete the post and try again.', 200);
      }

      // If the post doesn't have a link, but also didn't return HTTP 410, it's an error
      if($source['code'] != 410) {
        return $this->_error($request, $response,
          'not_deleted',
          'Your post did not return an HTTP 410 Gone response. A Webmention receiver only considers your post to be deleted when the URL returns HTTP 410.');
      }

      // Store the response data on disk
      $this->_storeResponseData($sourceID, 1, 
        $source, $sourceURL, $targetURL, 'source');

      Rocks\Redis::addResponse(1, $sourceID, 'delete');
      Rocks\Redis::setSourceHasPassedPart($sourceID, 1, 2, 'delete');
      Rocks\Redis::removeInProgressResponse(1, $sourceID, 'delete');

      $response->getBody()->write('Congrats, you\'ve passed the test!');
      return $response;
    }
  }


  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['test'];

    if(!DeleteTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    }

    $response->getBody()->write(view('webmention', [
      'title' => 'Webmention Rocks!',
    ]));

    return $response;
  }

}
