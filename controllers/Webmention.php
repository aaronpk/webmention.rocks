<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\HTTP;

class Webmention {

  protected function _error(ServerRequestInterface $request, ResponseInterface $response, $error, $description=false) {
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

    if(!preg_match('/^\/test\/\d+$/', $path)) {
      return $this->_error($request, $response,
        'invalid_target',
        'The target provided does not accept webmentions.'
        );
    }

    // Check that the target matches the test number of the webmention endpoint
    $path = parse_url($targetURL, PHP_URL_PATH);
    preg_match('/^\/([a-z]+)\/(\d+)$/', $path, $match);
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

  protected function _verifySourceLinksToTarget($request, $response, $source, $targetURL) {

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
