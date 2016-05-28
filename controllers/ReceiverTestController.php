<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\ReceiverTestData;

class ReceiverTestController extends Controller {

  public function view(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    $num = $args['test'];

    if(!ReceiverTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $response->getBody()->write(view('receiver-test', [
      'title' => 'Webmention Rocks!',
      'num' => $num,
      'test' => ReceiverTestData::data($num),
      'published' => ReceiverTestData::published($num),
      'num_responses' => 0
    ]));
    return $response;
  }  

  public function start(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $params = $request->getQueryParams();
    
    $num = $args['test'];

    if(!ReceiverTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    // Check that the URL entered is on the same domain as the user's website
    if(!domains_are_equal($_SESSION['me'], $params['url'])) {
      $_SESSION['error'] = 'host-mismatch';
      return $response->withHeader('Location', '/receive/'.$num)->withStatus(302);
    }

    // Store the URL and generate a hash for it
    $code = Rocks\Redis::generateCodeForTarget($params['url'], $num);

    return $response->withHeader('Location', '/receive/'.$num.'/'.$code);
  }

  public function process(ServerRequestInterface $request, ResponseInterface $response, $args) {

  }

}
