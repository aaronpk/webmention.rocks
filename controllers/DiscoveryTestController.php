<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;

class DiscoveryTestController extends Controller {

  public function view(ServerRequestInterface $request, ResponseInterface $response, $args) {
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
    ]));
    return $response;
  }  

}
