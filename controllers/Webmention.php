<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\TestData;
use Rocks\HTTP;

class Webmention {

  private function _error(ServerRequestInterface $request, ResponseInterface $response, $error, $description=false) {

    $post = $request->getParsedBody();
    if(@$post['via'] == 'browser') {
      $response->getBody()->write(view('webmention-error', [
        'title' => 'Webmention Rocks!',
        'error' => $error,
        'description' => $description
      ]));
      return $response->withStatus(400);
    } else {
      $content = $error."\n";
      if($description)
        $content .= "\n".$description."\n";
      $response->getBody()->write($content);
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
    }
  }

  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['num'];

    if(!TestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    }

    $response->getBody()->write(view('webmention', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num']
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
    if(!TestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    } else {
      return true;
    }
  }

  public function handle_error(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $this->_test_num($request, $args);

    if($this->_test_exists($response, $num) !== true) {
      return $response;
    }

    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $targetURL = @$post['target'];

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, $num);
    if($response->getStatusCode() == 400)
      return $response;

    $responseID = Rocks\Redis::makeResponseID($sourceURL, $targetURL);

    // Delete the existing comment for this source URL if there is one
    $this->_deleteResponse($num, $responseID);

    $testData = TestData::data($num);

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

    if($this->_test_exists($response, $num) !== true) {
      return $response;
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
    $response = $this->_validateTargetURL($request, $response, $targetURL, $num);
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

      $testData = TestData::data($num);

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

    // Parse the Microformats on the source URL to extract post/author information
    $mf2 = mf2\Parse($source['body'], $source['url']);

    $comment = false;
    if($mf2 && count($mf2['items']) > 0) {
      $http = new HTTP();
      $comment = Rocks\Formats\Mf2::parse($mf2, $source['url'], $http);
    }

    // Store the source URL and comment data in Redis

    $data = [
      'source' => $sourceURL,
      'target' => $targetURL,
      'date' => date('c'),
      'comment' => $comment,
    ];

    // Store the response data and set the expiration date
    Rocks\Redis::setResponseData($responseID, $data);
    // Add the response ID to the list of responses for this post
    Rocks\Redis::addResponse($num, $responseID);

    // Publish to anyone listening on the EventSource channel
    $this->_publish($num, new Rocks\Response($data, $responseID));

    return $response;
  }

  private function _validateSourceURL($request, $response, $sourceURL) {
    if(!$sourceURL) {
      return $this->_error($request, $response, 
        'missing_source', 
        'No source parameter was specified. Provide the source URL in a POST parameter named "source".');
    }

    $source = parse_url($sourceURL);

    if(!$source) {
      return $this->_error($request, $response,
        'invalid_source',
        'There was an error parsing the source URL.'
        );
    }

    if(!isset($source['scheme'])) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source URL was missing a scheme.'
        );
    }

    if(!in_array($source['scheme'], ['http','https'])) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source URL must have a scheme of either http or https.'
        );
    }

    if(!isset($source['host'])) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source URL was missing a hostname.'
        );
    }

    $ip=gethostbyname($source['host']);
    if(!$ip || $source['host']==$ip) {
      return $this->_error($request, $response,
        'invalid_source',
        'No DNS entry was found for the source hostname.'
        );
    }

    if(Config::$allowLocalhostSource == false && !isPublicAddress($ip)) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source hostname resolved to a private IP address: '.$ip
        );
    }

    // TODO: add support for checking ipv6 records here

    return $response;
  }

  private function _validateTargetURL($request, $response, $targetURL, $num) {
    if(!$targetURL) {
      return $this->_error($request, $response, 
        'missing_target', 
        'No target parameter was specified. Provide the target URL in a POST parameter named "target".');
    }

    $target = parse_url($targetURL);

    if(!$target) {
      return $this->_error($request, $response,
        'invalid_target',
        'There was an error parsing the target URL.'
        );
    }

    if(!isset($target['scheme'])) {
      return $this->_error($request, $response,
        'invalid_target',
        'The target URL was missing a scheme.'
        );
    }

    $host = @$target['host'];
    if(!in_array($host, Config::$hostnames)) {
      return $this->_error($request, $response,
        'invalid_target',
        'This webmention endpoint only handles webmentions for '.$thisHost.'.'
        );
    }

    // Check that the path of the target parameter is one that accepts webmentions
    $path = @$target['path'];
    if(!$path || $path == '/') {
      return $this->_error($request, $response,
        'invalid_target',
        'Webmentions to the home page are not accepted.'
        );
    }

    if(!preg_match('/^\/test\/\d+$/', $path)) {
      return $this->_error($request, $response,
        'invalid_target',
        'The target provided does not accept webmentions.'
        );
    }

    // Check that the target matches the test number of the webmention endpoint
    $path = parse_url($targetURL, PHP_URL_PATH);
    preg_match('/^\/test\/(\d+)$/', $path, $match);
    if($match[1] != $num) {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint ('.$num.') does not handle webmentions for the provided target.');
    }

    return $response;
  }

  private function _fetchSourceURL($request, $response, $sourceURL) {
    $http = new HTTP();
    $result = $http->get($sourceURL);

    if($result['error_code'] != 0) {
      return $this->_error($request, $response,
        $result['error'],
        'There was an error fetching the source URL:' ."\n" . $result['error_description']);
    }

    return $result;
  }

  private function _verifySourceLinksToTarget($request, $response, $source, $targetURL) {

    // Parse the source body as HTML
    $doc = new DOMDocument();
    libxml_use_internal_errors(true); # suppress parse errors and warnings
    $body = mb_convert_encoding($source['body'], 'HTML-ENTITIES', mb_detect_encoding($source['body']));
    @$doc->loadHTML($body, LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();

    if(!$doc) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source document could not be parsed as HTML.');
    }

    $xpath = new DOMXPath($doc);

    $found = false;
    $matchingDomain = [];

    // Check all the <a> tags on the page
    foreach($xpath->query('//a[@href]') as $href) {
      $url = $href->getAttribute('href');
      if($url == $targetURL) {
        $found = true;
        break;
      }
      if(parse_url($url, PHP_URL_HOST) == parse_url($targetURL, PHP_URL_HOST)) {
        $matchingDomain[] = $url;
      }
    }

    if(!$found) {
      $description = '';

      $description .= 'The source document does not contain a link to the target URL.';

      if($source['code'] != 200) {
        $description .= "\n\n".'The source URL returned an HTTP '.$source['code'].' response.';
      }

      $description .= "\n\nThe source document must contain an <a> tag with an href attribute matching the target URL specified.";

      if(count($matchingDomain)) {
        $description .= "\n\nThe following links to this website were found. Keep in mind that the source document must contain an exact match for the target URL you are specifying, even if the target page is a redirect.\n\n";
        $description .= implode("\n", array_map(function($item){ return '* '.$item; }, $matchingDomain));
        $description .= "\n";
      }

      return $this->_error($request, $response,
        'no_link_found',
        $description);
    }

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
