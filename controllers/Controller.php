<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write(view('index', [
      'title' => 'Webmention Rocks!',
      'discoveryTestData' => DiscoveryTestData::data(),
    ]));
    return $response;
  }

}
