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

    $comments = redis()->zrevrangebyscore('webmention.rocks:test:'.$num.':responses', 
      time()+300, time()-3600*48); // load the past 48 hours of mentions
    if($comments) {
      $comments = array_map(function($item){
        return new Rocks\Response($item);
      }, $comments);
    }

    $response->getBody()->write(view('test', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num'],
      'test' => TestData::data($num, $head),
      'date' => $date,
      'comments' => $comments
    ]));
    return $response;
  }  

}
