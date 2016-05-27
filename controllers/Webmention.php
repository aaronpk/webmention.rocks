<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\HTTP;

class Webmention {

  protected function _error(ServerRequestInterface $request, ResponseInterface $response, $error, $description=false, $code=400) {
    $post = $request->getParsedBody();
    if(@$post['via'] == 'browser') {
      $response->getBody()->write(view('webmention-error', [
        'title' => 'Webmention Rocks!',
        'error' => $error,
        'description' => $description
      ]));
      return $response->withStatus($code);
    } else {
      $content = $error."\n";
      if($description)
        $content .= "\n".$description."\n";
      $response->getBody()->write($content);
      return $response->withHeader('Content-Type', 'text/plain')->withStatus($code);
    }
  }

  protected function _validateOneTimeEndpoint(ServerRequestInterface $request, ResponseInterface $response) {

    if(!Config::$oneTimeEndpoints)
      return $response;

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

    return $response;
  }

  protected function _validateSourceURL(ServerRequestInterface $request, ResponseInterface $response, $sourceURL) {
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

  protected function _validateTargetURL(ServerRequestInterface $request, ResponseInterface $response, $targetURL, $type, $num) {
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

    if(!preg_match('/^\/(test|update|delete)\/\d+(\/(?:step|part)\/\d+)?$/', $path)) {
      return $this->_error($request, $response,
        'invalid_target',
        'The target provided does not accept webmentions.'
        );
    }

    // Check that the target matches the test number of the webmention endpoint
    $path = parse_url($targetURL, PHP_URL_PATH);
    preg_match('/^\/([a-z]+)\/(\d+)(?:\/(?:step|part)\/\d+)?$/', $path, $match);
    if($match[1] != $type || $match[2] != $num) {
      return $this->_error($request, $response, 
        'invalid_target', 
        'This webmention endpoint ('.$type.'/'.$num.') does not handle webmentions for the provided target.');
    }

    return $response;
  }

  protected function _fetchSourceURL($request, $response, $sourceURL) {
    $http = new HTTP();
    $result = $http->get($sourceURL);

    if($result['error_code'] != 0) {
      return $this->_error($request, $response,
        $result['error'],
        'There was an error fetching the source URL:' ."\n" . $result['error_description']);
    }

    return $result;
  }

  protected function _HTMLtoDOMDocument($html) {
    // Parse the source body as HTML
    $doc = new DOMDocument();
    libxml_use_internal_errors(true); # suppress parse errors and warnings
    $body = mb_convert_encoding($html, 'HTML-ENTITIES', mb_detect_encoding($html));
    @$doc->loadHTML($body, LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();
    return $doc;
  }

  protected function _sourceDocHasLinkTo(DOMDocument $doc, $link, $strict=true) {
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

  protected function _getMetaHTTPStatus(DOMDocument $doc) {
    if(!$doc) {
      return $this->_error($request, $response,
        'invalid_source',
        'The source document could not be parsed as HTML.');
    }

    $xpath = new DOMXPath($doc);

    $status = null;
    foreach($xpath->query('//meta[@http-equiv]') as $meta) {
      $equiv = $meta->getAttribute('http-equiv');
      if(strtolower($equiv) == 'status') {
        $content = $meta->getAttribute('content');
        if(is_string($content) && preg_match('/^(\d+)/', $content, $match)) {
          $status = $match[1];
          break;
        }
      }
    }

    return $status;
  }

  protected function _verifySourceLinksToTarget($request, $response, $source, $targetURL) {

    $doc = $this->_HTMLtoDOMDocument($source['body']);

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

  protected function _storeResponseData($responseID, $num, $source, $sourceURL, $targetURL, $type='response', $longLifetime=true) {
    // Parse the Microformats on the source URL to extract post/author information
    $mf2 = mf2\Parse($source['body'], $source['url']);

    $comment = false;
    if($mf2 && count($mf2['items']) > 0) {
      $http = new HTTP();
      $comment = Rocks\Formats\Mf2::parse($mf2, $source['url'], $http);
    }

    $data = [
      'source' => $sourceURL,
      'target' => $targetURL,
      'date' => date('c'),
      'comment' => $comment,
    ];

    // Store the response data and set the expiration date
    if($type == 'response' || $longLifetime) {
      Rocks\Redis::setResponseData($responseID, $data, $type);
    } else {
      Rocks\Redis::setInProgressSourceData($responseID, $data);
    }

    return $data;
  }

}
