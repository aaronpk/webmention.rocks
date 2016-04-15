<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rocks\UpdateTestData;

class UpdateTestController {

  public function view(ServerRequestInterface $request, ResponseInterface $response, $args) {
    $num = $args['test'];

    if(!UpdateTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $response->getBody()->write(view('update-test', [
      'title' => 'Webmention Rocks!',
      'num' => $num,
      'test' => UpdateTestData::data($num),
      'published' => UpdateTestData::published($num),
    ]));
    return $response;
  }

  public function step(ServerRequestInterface $request, ResponseInterface $response, $args) {
    $num = $args['test'];
    $step = $args['step'];

    if(!UpdateTestData::exists($num)) {
      $response->getBody()->write('Test not found');
      return $response->withStatus(404);
    }

    $test = UpdateTestData::data($num);

    if(!array_key_exists($step, $test['steps'])) {
      $response->getBody()->write('Step not found');
      return $response->withStatus(404);
    }

    $response->getBody()->write(view('update-step', [
      'title' => 'Webmention Rocks!',
      'num' => $num,
      'step' => $step,
      'test' => $test,
      'published' => UpdateTestData::published($num),
    ]));
    return $response;
  }

}
