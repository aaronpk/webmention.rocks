<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\DiscoveryTestData;

class DiscoveryTestController {

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

    // Set the post's published date to 3 hours ago
    $responseTypes = [
      'like' => [],
      'repost' => [],
      'bookmark' => [],
      'reply' => [],
      'mention' => [],
      'reacji' => [],
    ];

    $numResponses = 0;
    if($responses = Rocks\Redis::getResponsesForTest($num)) {
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

    $response->getBody()->write(view('test', [
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
