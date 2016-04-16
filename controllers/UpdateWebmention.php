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

    $webmentionID = Rocks\Redis::makeWebmentionID($sourceURL, $targetURL);
    $sourceID = Rocks\Redis::makeSourceID($sourceURL);

    return [
      'sourceURL' => $sourceURL,
      'targetURL' => $targetURL,
      'webmentionID' => $webmentionID,
      'sourceID' => $sourceID,
      'source' => $source,
    ];
  }

  private function _sourceDocHasLinkTo($doc, $link, $strict=true) {

    if(!$doc) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source document could not be parsed as HTML.');
    }

    $xpath = new DOMXPath($doc);

    if($strict) {
      $query = '//a[@href] | //link[@href] | //area[@href]';
    } else {
      $query = '//*[@href]';
    }

    $found = false;
    foreach($xpath->query($query) as $href) {
      $url = $href->getAttribute('href');
      if($url == $link) {
        $found = true;
        break;
      }
    }

    return $found;
  }

  /*
    Test 1
  
    // this comment is wrong, fix it later

    > Write a post that links to http://wmrocks.dev/update/1/step/1, and send Webmentions for your post.

    * this should send a webmention *only* to update/1/step/1
    * if step/2 receives a webmention before source URL is already registered, the test fails

    > Update your post to include a link to this page, and send webmentions for your post again, to both URLs.

    * this should send a webmention to *both* update/1/step/1 and update/1
    * record which one got the webmention first
    * when the other one gets a webmention, verify everything and complete or reject the request
    * verification:
      * check that the post contains a link to *both* URLs

    ## States
    * P1 - webmention sent successfully to /update/1/step/1
    * P2 - webmention sent to /update/1/step/1 after P1 was received
    * P3 - webmention sent to /update/1 after P1 was received

   */

  private function test_1_step_1(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 1);
    if(!is_array($info)) {
      return $info; // is an error response
    }

    // Check that the source actually links to the target URL for this step
    $response = $this->_verifySourceLinksToTarget($request, $response,
      $info['source'], Config::$base . 'update/1');
    if($response->getStatusCode() == 400) {
      return $response;
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID']);

    // Parse to DOMDocument
    $doc = $this->_HTMLtoDOMDocument($info['source']['body']);

    // If this is a new source URL... 
    if(!$existing) {
      // Make sure the source page does not have a link to /update/1
      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/1/step/2', false)) {
        return $this->_error($request, $response,
          'test_failed',
          'It looks like your post has a link to the wrong post! Make sure the first Webmention you send is only when your post links to the /update/1 post.',
          200 // the webmention isn't invalid, but the test failed
          );
      }

      $this->_storeResponseData($info['sourceID'], 1, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source');

      // Store as passing test 1 part 1
      // Should show up as a single checkmark
      Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 1, 1);
      Rocks\Redis::addInProgressResponse(1, $info['sourceID']);

      $response->getBody()->write('Got it! You\'ve completed step one and you should see your in-progress response on ' . Config::$base . 'update/1');
      return $response;

    } else {
    // If this source URL has been seen before (this is an Update webmention)...

      $this->_storeResponseData($info['sourceID'], 1, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source');

      // Make sure their post has been updated to include a link to both posts
      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/1/step/2')) {

        // If they have already completed part 3, they pass the test!
        if(Rocks\Redis::hasSourcePassedPart($info['sourceID'], 1, 3)) {
  
          Rocks\Redis::addResponse(1, $info['sourceID'], 'update');
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 1, 2); // this is part 2
          Rocks\Redis::removeInProgressResponse(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you\'ve passed the test!');
          return $response;
        } else {

          // Otherwise, still in progress, mark as successfully completing part 2
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 1, 2);
          Rocks\Redis::addInProgressResponse(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/1. Make sure you send the update Webmention to the other URL now too!');
          return $response;
        }

      } else {
        return $this->_error($request, $response,
          'no_change',
          'You re-sent the Webmention, but your post did not add a link to the new URL. Please re-read step 3 on ' . Config::$base . 'update/1 and try again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }
    }
  }

  private function test_1_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 1);
    if(!is_array($info)) {
      return $info;
    }

    // Check that the source actually links to the target URL for this step
    $response = $this->_verifySourceLinksToTarget($request, $response, 
      $info['source'], Config::$base . 'update/1/step/2');
    if($response->getStatusCode() == 400) {
      return $response;
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID']);

    // Parse to DOMDocument
    $doc = $this->_HTMLtoDOMDocument($info['source']['body']);

    // If this source has already passed part 1...
    if(Rocks\Redis::hasSourcePassedPart($info['sourceID'], 1, 1)) {

      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/1')) {

        $this->_storeResponseData($info['sourceID'], 1, 
          $info['source'], $info['sourceURL'], $info['targetURL'], 'source');

        // If this source has already passed part 2, then the test is complete
        if(Rocks\Redis::hasSourcePassedPart($info['sourceID'], 1, 2)) {
  
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 1, 3);
          Rocks\Redis::addResponse(1, $info['sourceID'], 'update');
          Rocks\Redis::removeInProgressResponse(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you\'ve passed the test!');
          return $response;
        } else {
          // Otherwise, still in progress, mark as successfully completing part 3
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 1, 3);
          Rocks\Redis::addInProgressResponse(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/1/step/2. Make sure you send the update Webmention to the original URL now too!');
          return $response;
        }

      } else {
        return $this->_error($request, $response,
          'incomplete',
          'We got the Webmention, but it looks like you removed the link to the first post from your HTML. Please re-read step 3 on ' . Config::$base . 'update/1 and try again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }


    } else {
      // Step 2 should not receive a webmention until the source URL is already found
      // The test fails, but the webmention isn't technically invalid, so return 200 and tell them that
      return $this->_error($request, $response,
        'test_not_started',
        'It looks like you sent a Webmention to the wrong page. To start this test, send a Webmention to the ' . Config::$base . 'update/1 page first.',
        200 // the webmention isn't invalid, but the test failed
        );
    }
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
