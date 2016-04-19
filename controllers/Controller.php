<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;
use Rocks\UpdateTestData;

class Controller {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write(view('index', [
      'title' => 'Webmention Rocks!',
      'discoveryTestData' => DiscoveryTestData::data(),
      'updateTestData' => UpdateTestData::data(),
    ]));
    return $response;
  }

  public function discovery(ServerRequestInterface $request, ResponseInterface $response) {

    $ids = Rocks\Redis::getAllResponses();
    $responses = [];
    $sources = [];
    foreach($ids as $id) {
      if(!preg_match('/deleted/', $id)) {
        $r = Rocks\Redis::getResponse($id);
      }
      if(array_key_exists($r->source, $sources)) {
        if($r->created->format('U') > $sources[$r->source]->format('U')) {
          $sources[$r->source] = $r->created;
          $responses[$r->source] = $r;
        }
      } else {
        $responses[$r->source] = $r;
        $sources[$r->source] = $r->created;
      }
    }

    $response->getBody()->write(view('discovery-results', [
      'title' => 'Webmention Rocks!',
      'responses' => $responses,
      'num_responses' => count($responses)
    ]));
    return $response;
  }

  protected function _gatherResponseTypes($num, $testKey) {
    $responseTypes = [
      'like' => [],
      'repost' => [],
      'bookmark' => [],
      'reply' => [],
      'mention' => [],
      'reacji' => [],
    ];

    $numResponses = 0;
    if($responses = Rocks\Redis::getResponsesForTest($num, $testKey)) {
      $responses = array_map(function($item){
        return Rocks\Redis::getResponse($item);
      }, $responses);
      foreach($responses as $r) {
        if($r) {
          // Group reacji another level based on the emoji used
          if($type=$r->getMentionType() == 'reacji') {
            $emoji = $r->text_content();
            if(!array_key_exists($emoji, $responseTypes['reacji']))
              $responseTypes['reacji'][$emoji] = [];
            $responseTypes['reacji'][$emoji][] = $r;
          } else {
            $responseTypes[$r->getMentionType()][] = $r;
          }
          $numResponses++;
        }
      }
    }

    // Sort reacji by number descending
    uksort($responseTypes['reacji'], function($a, $b) use($responseTypes){
      return count($responseTypes['reacji'][$a]) < count($responseTypes['reacji'][$b]);
    });

    return [$responseTypes, $numResponses]; 
  }

}
