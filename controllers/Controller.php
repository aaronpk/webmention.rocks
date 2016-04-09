<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\TestData;

class Controller {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write(view('index', [
      'title' => 'Webmention Rocks!',
      'testData' => TestData::data()
    ]));
    return $response;
  }

  public function test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    $num = $args['num'];

    if(!TestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $head = $_SERVER['REQUEST_METHOD'] == 'HEAD';

    if($header=TestData::link_header($num, $head))
      $response = $response->withHeader('Link', $header);

    $response->getBody()->write(view('test', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num'],
      'link_tag' => TestData::link_tag($num, $head),
      'a_tag' => TestData::a_tag($num, $head)
    ]));
    return $response;
  }  

}
