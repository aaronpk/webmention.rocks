<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\TestData;

class Webmention {

  private function _error(ResponseInterface $response, $error, $description=false) {
    $content = $error."\n";
    if($description)
      $content .= "\n".$description."\n";
    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', 'text/plain')->withStatus(400);
  }

  public function handle(ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $num = $args['num'];

    if(!TestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withHeader('Content-Type', 'text/plain')->withStatus(404);
    }

    // Check the content type of the request
    $contentType = $request->getHeaderLine('Content-type');
    if($contentType != 'application/x-www-form-urlencoded') {
      return $this->_error($response, 
        'invalid_content_type', 
        'Content type must be set to application/x-www-form-urlencoded');
    }

    $post = $request->getParsedBody();

    $sourceURL = @$post['source'];
    $response = $this->_validateSourceURL($response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;

    $targetURL = @$post['target'];
    $response = $this->_validateTargetURL($response, $targetURL, $num);
    if($response->getStatusCode() == 400)
      return $response;

    $sourceBody = $this->_fetchSourceURL($response, $sourceURL);
    if($response->getStatusCode() == 400)
      return $response;


    return $response;
  }

  private function _validateSourceURL($response, $sourceURL) {
    if(!$sourceURL) {
      return $this->_error($response, 
        'missing_source', 
        'No source parameter was specified. Provide the source URL in a POST parameter named "source".');
    }

    $source = parse_url($sourceURL);

    if(!$source) {
      return $this->_error($response,
        'invalid_source',
        'There was an error parsing the source URL.'
        );
    }

    if(!isset($source['scheme'])) {
      return $this->_error($response,
        'invalid_source',
        'The source URL was missing a scheme.'
        );
    }

    if(!in_array($source['scheme'], ['http','https'])) {
      return $this->_error($response,
        'invalid_source',
        'The source URL must have a scheme of either http or https.'
        );
    }

    if(!isset($source['host'])) {
      return $this->_error($response,
        'invalid_source',
        'The source URL was missing a hostname.'
        );
    }

    $ip=gethostbyname($source['host']);
    if(!$ip || $source['host']==$ip) {
      return $this->_error($response,
        'invalid_source',
        'No DNS entry was found for the source hostname.'
        );
    }

    if(!isPublicAddress($ip)) {
      return $this->_error($response,
        'invalid_source',
        'The source hostname resolved to a private IP address: '.$ip
        );
    }

    // TODO: add support for checking ipv6 records here

    return $response;
  }

  private function _validateTargetURL($response, $targetURL, $num) {
    if(!$targetURL) {
      return $this->_error($response, 
        'missing_target', 
        'No target parameter was specified. Provide the target URL in a POST parameter named "target".');
    }

    $target = parse_url($targetURL);

    if(!$target) {
      return $this->_error($response,
        'invalid_target',
        'There was an error parsing the target URL.'
        );
    }

    if(!isset($target['scheme'])) {
      return $this->_error($response,
        'invalid_target',
        'The target URL was missing a scheme.'
        );
    }

    $host = @$target['host'];
    $thisHost = parse_url(Config::$base, PHP_URL_HOST);
    if($host != $thisHost) {
      return $this->_error($response,
        'invalid_target',
        'This webmention endpoint does not handle webmentions for the host specified by the target parameter.'
        );
    }

    // Check that the path of the target parameter is one that accepts webmentions
    $path = @$target['path'];
    if(!$path || $path == '/') {
      return $this->_error($response,
        'invalid_target',
        'Webmentions to the home page are not accepted.'
        );
    }

    if(!preg_match('/^\/test\/\d+$/', $path)) {
      return $this->_error($response,
        'invalid_target',
        'The target provided does not accept webmentions.'
        );
    }

    // Check that the target matches the test number of the webmention endpoint
    $path = parse_url($targetURL, PHP_URL_PATH);
    preg_match('/^\/test\/(\d+)$/', $path, $match);
    if($match[1] != $num) {
      return $this->_error($response, 
        'invalid_target', 
        'This webmention endpoint ('.$num.') does not handle webmentions for the provided target.');
    }

    return $response;
  }

  private function _fetchSourceURL($response, $sourceURL) {



  }

}
