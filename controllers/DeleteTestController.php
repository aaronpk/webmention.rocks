<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DeleteTestData;

class DeleteTestController extends Controller {

  public function view(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    $num = $args['test'];

    if(!DeleteTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $head = $_SERVER['REQUEST_METHOD'] == 'HEAD';

    if($inProgress = Rocks\Redis::getInProgressResponses($num, 'delete')) {
      $inProgress = array_map(function($item){
        return Rocks\Redis::getResponse($item);
      }, $inProgress);
    } else {
      $inProgress = [];
    }

    if($responses = Rocks\Redis::getResponsesForTest($num, 'delete')) {
      $responses = array_map(function($item){
        return Rocks\Redis::getResponse($item);
      }, $responses);
    } else {
      $responses = [];
    }

    $response->getBody()->write(view('delete-test', [
      'title' => 'Webmention Rocks!',
      'num' => $num,
      'test' => DeleteTestData::data($num, $head),
      'published' => DeleteTestData::published($num),
      'responses' => $responses,
      'in_progress' => $inProgress,
      'num_responses' => (count($responses)+count($inProgress)),
    ]));
    return $response;
  }  

}
