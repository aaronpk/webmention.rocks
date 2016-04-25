<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\UpdateTestData;
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

    $response = $this->_validateOneTimeEndpoint($request, $response);
    if($response->getStatusCode() == 400)
      return $response;

    // Initial Webmention verification, check source & target syntax
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

  /*
    Test 1
  
    // this comment is wrong, fix it later

    > Write a post that links to http://wmrocks.dev/update/1/part/1, and send Webmentions for your post.

    * this should send a webmention *only* to update/1/part/1
    * if part/2 receives a webmention before source URL is already registered, the test fails

    > Update your post to include a link to this page, and send webmentions for your post again, to both URLs.

    * this should send a webmention to *both* update/1/part/1 and update/1
    * record which one got the webmention first
    * when the other one gets a webmention, verify everything and complete or reject the request
    * verification:
      * check that the post contains a link to *both* URLs

    ## States
    * P1 - webmention sent successfully to /update/1/part/1
    * P2 - webmention sent to /update/1/part/1 after P1 was received
    * P3 - webmention sent to /update/1 after P1 was received

   */


  // TODO:
  // if the test has passed for the source URL, do not let it start a new test

  private function test_1_step_1(ServerRequestInterface $request, ResponseInterface $response) {

    $info = $this->_basic_verification($request, $response, 1);
    if(!is_array($info)) {
      return $info; // is an error response
    }

    // Only allow this endpoint to accept mentions for /update/1
    if($info['targetURL'] != Config::$base . 'update/1') {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint does not handle webmentions for the provided target.');
    }

    // Check that the source actually links to the target URL for this step
    $response = $this->_verifySourceLinksToTarget($request, $response,
      $info['source'], Config::$base . 'update/1');
    if($response->getStatusCode() == 400) {
      return $response;
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID'], true);

    // Parse to DOMDocument
    $doc = $this->_HTMLtoDOMDocument($info['source']['body']);

    // If this is a new source URL... 
    if(!$existing) {
      // Make sure the source page does not have a link to /update/1
      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/1/part/2', false)) {
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
      Rocks\Redis::extendExpiration(1, $info['sourceID']);

      $response->getBody()->write('Got it! You\'ve completed step one and you should see your in-progress response on ' . Config::$base . 'update/1. You have 10 minutes to complete the next step.');
      return $response;

    } else {
    // If this source URL has been seen before (this is an Update webmention)...

      $this->_storeResponseData($info['sourceID'], 1, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source');

      // Make sure their post has been updated to include a link to both posts
      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/1/part/2')) {

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
          Rocks\Redis::extendExpiration(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/1. Make sure you send the update Webmention to the other URL now too! You have 10 minutes to complete the next step.');
          return $response;
        }

      } else {
        return $this->_error($request, $response,
          'incomplete',
          'You re-sent the Webmention, but your post did not add a link to the new URL. Please re-read step 2 on ' . Config::$base . 'update/1 and try again.',
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

    // Only allow this endpoint to accept mentions for /update/1
    if($info['targetURL'] != Config::$base . 'update/1/part/2') {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint does not handle webmentions for the provided target.');
    }

    // Check that the source actually links to the target URL for this step
    $response = $this->_verifySourceLinksToTarget($request, $response, 
      $info['source'], Config::$base . 'update/1/part/2');
    if($response->getStatusCode() == 400) {
      return $response;
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID'], true);

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
          Rocks\Redis::extendExpiration(1, $info['sourceID']);

          $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/1/part/2. Make sure you send the update Webmention to the original URL now too! You have 10 minutes to complete the next step.');
          return $response;
        }

      } else {
        return $this->_error($request, $response,
          'incomplete',
          'We got the Webmention, but it looks like you removed the link to the first post from your HTML. Please re-read step 2 on ' . Config::$base . 'update/1 and try again.',
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

    // Only allow this endpoint to accept mentions for /update/2
    if($info['targetURL'] != Config::$base . 'update/2') {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint does not handle webmentions for the provided target.');
    }

    // Check that the source actually links to the target URL for this step
    $response = $this->_verifySourceLinksToTarget($request, $response,
      $info['source'], Config::$base . 'update/2');
    if($response->getStatusCode() == 400) {
      return $response;
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID'], true);

    // Parse to DOMDocument
    $doc = $this->_HTMLtoDOMDocument($info['source']['body']);

    // If this is the first time they sent the first webmention...
    if(!Rocks\Redis::hasSourcePassedPart($info['sourceID'], 2, 1)) {

      // Make sure the source page also has a link to /update/2/part/2
      if(!$this->_sourceDocHasLinkTo($doc, Config::$base . 'update/2/part/2', false)) {
        return $this->_error($request, $response,
          '',
          'In the first step of this test, your post needs to also have a link to ' . Config::$base . 'update/2/part/2! Add that link and try again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }

      $this->_storeResponseData($info['sourceID'], 2, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source', false);

      // Store as passing test 2 part 1
      Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 1);
      Rocks\Redis::addInProgressResponse(2, $info['sourceID']);
      Rocks\Redis::extendExpiration(2, $info['sourceID']);

      $response->getBody()->write('Got it! You\'ve completed this part of step one and you should see your in-progress response on ' . Config::$base . 'update/2. You have 10 minutes to complete the next step.');
      return $response;

    } else {
    // If they have already sent the first webmention (this is an Update webmention)...
      
      $this->_storeResponseData($info['sourceID'], 2, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source', false);

      // Make sure their post has been updated to remove the link to the other post
      if(!$this->_sourceDocHasLinkTo($doc, Config::$base . 'update/2/part/2')) {

        // If they have already completed part 4, they pass the test!
        if(Rocks\Redis::hasSourcePassedPart($info['sourceID'], 2, 4)) {
  
          Rocks\Redis::addResponse(2, $info['sourceID'], 'update');
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 3);
          Rocks\Redis::removeInProgressResponse(2, $info['sourceID']);
          // Run setResponseData which archives the post to disk as well
          Rocks\Redis::setResponseData($info['sourceID'], Rocks\Redis::getResponseData($info['sourceID']), 'source');
    
          $response->getBody()->write('Congrats, you\'ve passed the test!');
          return $response;
        } else {

          // Otherwise, still in progress, mark as successfully completing part 3
          Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 3);
          Rocks\Redis::addInProgressResponse(2, $info['sourceID']);
          Rocks\Redis::extendExpiration(2, $info['sourceID']);

          $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/2. Make sure you send the update Webmention to the other URL now too! You have 10 minutes to complete the next step.');
          return $response;
        }

      } else {
        return $this->_error($request, $response,
          'incomplete',
          'You re-sent the Webmention, but your post still has a link to both URLs. Please re-read step 3 on ' . Config::$base . 'update/2 and try again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }

    }

  }

  private function test_2_step_2(ServerRequestInterface $request, ResponseInterface $response) {
    $info = $this->_basic_verification($request, $response, 2);
    if(!is_array($info)) {
      return $info;
    }

    // Only allow this endpoint to accept mentions for /update/2/part/2
    if($info['targetURL'] != Config::$base . 'update/2/part/2') {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint does not handle webmentions for the provided target.');
    }

    // Load the existing source URL if present
    $existing = Rocks\Redis::getSource($info['sourceID'], true);

    // Parse to DOMDocument
    $doc = $this->_HTMLtoDOMDocument($info['source']['body']);

    // If this is the first time they sent the second webmention...
    if(!Rocks\Redis::hasSourcePassedPart($info['sourceID'], 2, 2)) {

      // Check that the source actually links to the target URL for this step
      $response = $this->_verifySourceLinksToTarget($request, $response,
        $info['source'], Config::$base . 'update/2/part/2');
      if($response->getStatusCode() == 400) {
        return $response;
      }

      // Make sure the source page also has a link to /update/2
      if(!$this->_sourceDocHasLinkTo($doc, Config::$base . 'update/2', false)) {
        return $this->_error($request, $response,
          'test_failed',
          'In the first step of this test, your post needs to also have a link to ' . Config::$base . 'update/2! Add that link and try again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }

      $this->_storeResponseData($info['sourceID'], 2, 
        $info['source'], $info['sourceURL'], $info['targetURL'], 'source');

      // Store as passing test 2 part 2
      Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 2);
      Rocks\Redis::addInProgressResponse(2, $info['sourceID']);
      Rocks\Redis::extendExpiration(2, $info['sourceID']);

      $response->getBody()->write('Got it! You\'ve completed this part of step one and you should see your in-progress response on ' . Config::$base . 'update/2. You have 10 minutes to complete the next step.');
      return $response;

    } else {
      // If we've seen this URL before, make sure the link to this post has been removed

      if($this->_sourceDocHasLinkTo($doc, Config::$base . 'update/2/part/2', false)) {
        return $this->_error($request, $response,
          'test_failed',
          'Your post still links to ' . Config::$base . 'update/2/step/2! Remove that link and send the Webmention again.',
          200 // the webmention isn't invalid, but the test failed
          );
      }

      // If they have already completed part 3, they pass the test!
      if(Rocks\Redis::hasSourcePassedPart($info['sourceID'], 2, 3)) {

        Rocks\Redis::addResponse(2, $info['sourceID'], 'update');
        Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 4);
        Rocks\Redis::removeInProgressResponse(2, $info['sourceID']);
        // Run setResponseData which archives the post to disk as well
        Rocks\Redis::setResponseData($info['sourceID'], Rocks\Redis::getResponseData($info['sourceID']), 'source');

        $response->getBody()->write('Congrats, you\'ve passed the test!');
        return $response;
      } else {

        // Otherwise, still in progress, mark as successfully completing part 4
        Rocks\Redis::setSourceHasPassedPart($info['sourceID'], 2, 4);
        Rocks\Redis::addInProgressResponse(2, $info['sourceID']);
        Rocks\Redis::extendExpiration(2, $info['sourceID']);

        $response->getBody()->write('Congrats, you sent an update Webmention to ' . Config::$base . 'update/2/part/2. Make sure you send the update Webmention to the other URL now too! You have 10 minutes to complete the next step.');
        return $response;
      }

    }
    
  }

  public function get(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['test'];

    if(!UpdateTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    }

    $response->getBody()->write(view('webmention', [
      'title' => 'Webmention Rocks!',
    ]));

    return $response;
  }

}
