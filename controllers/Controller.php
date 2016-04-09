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

    $date = new DateTime();
    $date->sub(new DateInterval('PT3H'));
    $date->setTimeZone(new DateTimeZone('America/Los_Angeles'));

    $response->getBody()->write(view('test', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num'],
      'test' => TestData::data($num, $head),
      'date' => $date,
      'responses' => [1,2,3]
    ]));
    return $response;
  }  

}
