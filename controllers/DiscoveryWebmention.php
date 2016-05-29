<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;
use Rocks\HTTP;

class DiscoveryWebmention extends Webmention {

  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    session_setup();

    $num = $args['num'];

    if(!DiscoveryTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    }

    $response->getBody()->write(view('webmention', [
      'title' => 'Webmention Rocks!',
    ]));

    return $response;
  }

  private function _test_num(ServerRequestInterface $request, array $args) {
    if(preg_match('/^\/test\/(\d+)$/', $request->getUri()->getPath(), $match))
      return $match[1];
    else
      return $args['num'];
  }

  private function _test_exists(ResponseInterface $response, $num) {
    if(!DiscoveryTestData::exists($num)) {
      $response->getBody()->write('Test not found!');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    } else {
      return true;
    }
  }

  public function handle_error(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $this->_test_num($request, $args);

    if(($r=$this->_test_exists($response, $num)) !== true) {
      return $r;
    }

    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $targetURL = @$post['target'];

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, 'test', $num);
    if($response->getStatusCode() == 400)
      return $response;

    $responseID = Rocks\Redis::makeResponseID($sourceURL, $targetURL);

    // Delete the existing comment for this source URL if there is one
    $this->_deleteResponse($num, $responseID);

    $testData = DiscoveryTestData::data($num);

    return $this->_error($request, $response, 
      'error', 
      array_key_exists('error_description', $testData) ? $testData['error_description'] : 'There was an error.');
  }

  public function handle(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $this->_test_num($request, $args);

    // The path might be /test/15/webmention/error which sets mode=error
    if(array_key_exists('mode', $args))
      $mode = $args['mode'];
    else
      $mode = false;

    if(($r=$this->_test_exists($response, $num)) !== true) {
      return $r;
    }

    // Check the content type of the request
    $contentType = $request->getHeaderLine('Content-type');
    if(strpos($contentType, 'application/x-www-form-urlencoded') === false) {
      return $this->_error($request, $response, 
        'invalid_content_type', 
        'Content type must be set to application/x-www-form-urlencoded');
    }

    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $targetURL = @$post['target'];

    $responseID = Rocks\Redis::makeResponseID($sourceURL, $targetURL);
    $webmentionID = Rocks\Redis::makeWebmentionID($sourceURL, $targetURL);

    // TODO: figure out how to handle publishing the streaming data
    redis()->publish(Config::$base . 'test/'.$num.'/stream', json_encode([
      'id' => $webmentionID, 'source'=>$sourceURL, 'target'=>$targetURL
    ]));

    // Validate the syntax of the source URL
    $response = $this->_validateSourceURL($request, $response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, 'test', $num);
    if($response->getStatusCode() == 400)
      return $response;

    // Test 21 requires the query string be maintained, so double check it exists
    if($num == 21) {
      $params = $request->getQueryParams();
      if(!array_key_exists('query', $params)
        || $params['query'] != 'yes'
        || array_key_exists('query', $post)) {
        $mode = 'error';
      }
    }

    // For any of the tests that include a false endpoint, check if the webmention was
    // sent to that endpoint and delete the comment if present
    if($mode == 'error') {
      $this->_deleteResponse($num, $responseID);

      $testData = DiscoveryTestData::data($num);

      return $this->_error($request, $response, 
        'error', 
        array_key_exists('error_description', $testData) ? $testData['error_description'] : 'There was an error.');
    }

    // Fetch the source URL, reporting any errors that occurred
    $result = $this->_fetchSourceURL($request, $response, $sourceURL);
    if(!is_array($result) && $result->getStatusCode() == 400)
      return $result;
    $source = $result;

    // Parse the source HTML and check for a link to target
    $response = $this->_verifySourceLinksToTarget($request, $response, $source, $targetURL);
    if($response->getStatusCode() == 400) {
      if($source['code'] == 410 || $source['code'] == 200) {
        // If we've seen this before, and if the source URL returned 410 or 200, delete the existing comment
        if($this->_deleteResponse($num, $responseID) == true) {
          $body = "";
          if($source['code'] == 410) {
            $body .= "The source URL returned HTTP 410, and we have previously seen this source URL, so the response has been deleted.";
          } else {
            $body .= "The source URL returned HTTP 200, and we have previously seen this source URL, so the response has been deleted.";
          }
          // This is the worst hack ever. Why can't I just replace the response body??
          $stream = new Zend\Diactoros\Stream('php://temp', 'wb+');
          $stream->write($body);
          $stream->rewind();

          return $response->withBody($stream)->withStatus(200);
        } else {
          return $response;
        }
      } else {
        return $response;
      }
    }

    $data = $this->_storeResponseData($responseID, $num, $source, $sourceURL, $targetURL);

    // Add the response ID to the list of responses for this post
    Rocks\Redis::addResponse($num, $responseID, 'test');

    // Publish to anyone listening on the EventSource channel
    $this->_publish($num, new Rocks\Response($data, $responseID));

    $response->getBody()->write('Got it! Your response should be visible on the website now!');

    return $response;
  }

  private function _publish($num, $comment) {
    if(in_array($comment->getMentionType(), ['reply','mention']))
      $html = view('partials/comment', ['comment' => $comment, 'type' => $comment->getMentionType()]);
    else
      $html = view('partials/facepile-icon', ['res' => $comment, 'type' => $comment->getMentionType()]);

    if($comment->getMentionType() == 'reacji')
      $reacji_html = view('partials/reacji', ['emoji'=>$comment->text_content, 'reacjis'=>[$comment]]);
    else
      $reacji_html = false;

    $ch = curl_init(Config::$base . 'streaming/pub?id=test-'.$num);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
      'type' => $comment->getMentionType(),
      'hash' => $comment->hash(),
      'html' => $html,
      'emoji' => $comment->getMentionType() == 'reacji' ? $comment->text_content() : false,
      'reacji_html' => $reacji_html
    ]));
    curl_exec($ch);
  }

  private function _publishDelete($num, $responseID, $comment) {
    $ch = curl_init(Config::$base . 'streaming/pub?id=test-'.$num);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
      'action' => 'delete',
      'hash' => $comment->hash(),
      'emoji' => $comment->getMentionType() == 'reacji' ? $comment->text_content() : false,
    ]));
    curl_exec($ch);
  }

  private function _deleteResponse($num, $responseID) {
    // Delete the existing comment for this source URL if there is one
    if($existing = Rocks\Redis::getResponse($responseID)) {
      Rocks\Redis::deleteResponse($responseID);
      $this->_publishDelete($num, $responseID, $existing);
      return true;
    } else {
      return false;
    }
  }

}
