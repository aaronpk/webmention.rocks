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

    if($header=TestData::link_header($num, $head)) {
      $response = $response->withHeader(TestData::link_header_name($num, $head), $header);
    }

    // Set the post's published date to 3 hours ago
    $date = new DateTime();
    $date->sub(new DateInterval('PT3H'));
    $date->setTimeZone(new DateTimeZone('America/Los_Angeles'));

    $responseTypes = [
      'like' => [],
      'repost' => [],
      'bookmark' => [],
      'reply' => [],
      'mention' => [],
    ];
    $numResponses = 0;
    if($responses = Rocks\Redis::getResponsesForTest($num)) {
      $responses = array_map(function($item){
        return Rocks\Redis::getResponse($item);
      }, $responses);
      foreach($responses as $r) {
        if($r) {
          $responseTypes[$r->getMentionType()][] = $r;
          $numResponses++;
        }
      }
    }

    $response->getBody()->write(view('test', [
      'title' => 'Webmention Rocks!',
      'num' => $args['num'],
      'test' => TestData::data($num, $head),
      'date' => $date,
      'responses' => $responseTypes,
      'num_responses' => $numResponses,
    ]));
    return $response;
  }  

}
