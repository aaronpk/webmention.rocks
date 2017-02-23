<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;
use Rocks\Redis;

class DiscoveryTestController extends Controller {

  public function view(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $params = $request->getQueryParams();

    $num = $args['num'];

    if(!DiscoveryTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $head = $_SERVER['REQUEST_METHOD'] == 'HEAD';

    if($headers=DiscoveryTestData::link_header($num, $head)) {
      if(!is_array($headers))
        $headers = [$headers];
      foreach($headers as $header)
        $response = $response->withAddedHeader(DiscoveryTestData::link_header_name($num, $head), $header);
    }

    list($responseTypes, $numResponses) = $this->_gatherResponseTypes($num, 'test');

    $response->getBody()->write(view('discovery-test', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num'],
      'test' => DiscoveryTestData::data($num, $head),
      'published' => DiscoveryTestData::published($num),
      'responses' => $responseTypes,
      'num_responses' => $numResponses,
      'expired' => isset($params['expired'])
    ]));
    return $response;
  }

  public function redirect23(ServerRequestInterface $request, ResponseInterface $response, $args) {
    $key = $args['key'];

    // if(Redis::useOneTimeKey($key)) {
      $newKey = Redis::createOneTimeKey();
      return $response->withHeader('Location', 'page/'.$newKey)->withStatus(302);
    // } else {
    //   return $response->withHeader('Location', '/test/23?expired')->withStatus(302);
    // }
  }

  public function page23(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    $key = $args['key'];
    $head = $_SERVER['REQUEST_METHOD'] == 'HEAD';
    $num = 23;

    if(Redis::useOneTimeKey($key)) {
      $newKey = Redis::createOneTimeKey();
      $response->getBody()->write('<div class="h-entry"><h2 class="p-name">Discovery Test #23</h2><p><a href="/test/23/page" class="u-url">permalink</a> <a href="/" class="p-author h-card"><img src="/assets/webmention-rocks-icon.png" alt="Webmention Rocks!"></a></p><p><a rel="webmention" href="webmention-endpoint/'.$newKey.'">webmention endpoint</a></p></div>');
      return $response->withHeader('Link', '<webmention-endpoint/'.$newKey.'>; rel=webmention');
    } else {
      $response->getBody()->write('Code expired'."\n");
      return $response->withStatus(410);
    }
  }

}
