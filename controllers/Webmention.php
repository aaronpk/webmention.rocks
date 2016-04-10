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

  public function handle(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['num'];

    if(!TestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
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

    redis()->publish('incoming', json_encode(['source'=>$sourceURL, 'target'=>$targetURL]));

    // Validate the syntax of the source URL
    $response = $this->_validateSourceURL($request, $response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;

    // Validate the syntax of the target URL
    $response = $this->_validateTargetURL($request, $response, $targetURL, $num);
    if($response->getStatusCode() == 400)
      return $response;

    // Fetch the source URL, reporting any errors that occurred
    $result = $this->_fetchSourceURL($request, $response, $sourceURL);
    if(!is_array($result) && $result->getStatusCode() == 400)
      return $result;
    $source = $result;

    // Parse the source HTML and check for a link to target
    $response = $this->_verifySourceLinksToTarget($request, $response, $source, $targetURL);
    if($response->getStatusCode() == 400) {
      if($source['code'] == 410 || $source['code'] == 200) {
        // TODO If the source URL returned 410 or 200, and delete any existing comment if we have one

      }
      return $response;
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

    // TODO: If there is an existing webmention with this source URL, remove it first

    redis()->zadd('webmention.rocks:test:'.$num.':responses', time(), json_encode($data));

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

    if(!isPublicAddress($ip)) {
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
    $thisHost = parse_url(Config::$base, PHP_URL_HOST);
    if($host != $thisHost) {
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

}
