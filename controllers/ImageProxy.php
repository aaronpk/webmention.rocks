<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ImageProxy {

  public static function url($url) {
    $signature = hash_hmac('sha256', $url, Config::$secret);
    if(preg_match('/^http/', $url))
      $path = '?url=' . urlencode($url) . '&';
    else
      $path = '/' . $url . '?';
    return '/image'.$path.'sig=' . $signature;
  }

  public function image(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();

    if(!array_key_exists('url', $params) || !$params['url'])
      return self::_404($response);

    if(!array_key_exists('sig', $params) || !$params['sig'])
      return self::_400($response);

    $url = $params['url'];
    $signature = $params['sig'];

    // Check the signature
    $expectedSignature = hash_hmac('sha256', $url, Config::$secret);
    if($signature != $expectedSignature)
      return self::_400($response);

    if(preg_match('/https?:\/\//', $url)) {
      $client = new GuzzleHttp\Client();
      try {
        $img = $client->request('GET', $url);
        $contentType = $img->getHeader('Content-type');
        $response->getBody()->write($img->getBody());
        return $response->withHeader('Content-type', $contentType);
      } catch(GuzzleHttp\Exception\ClientException $e) {
        return self::_400($response);
      }
    } else {
      return self::_400($response);
    }
  }

  private static function _404($response) {
    $filename = dirname(__FILE__).'/../public/assets/image-404.png';
    $response->getBody()->write(file_get_contents($filename));
    return $response->withHeader('Content-Type', 'image/png')->withStatus(404);
  }

  private static function _400($response) {
    $filename = dirname(__FILE__).'/../public/assets/image-400.png';
    $response->getBody()->write(file_get_contents($filename));
    return $response->withHeader('Content-Type', 'image/png')->withStatus(400);
  }

}
