<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller {

  private function _indieLoginSetup() {
    IndieLogin\Client::$server = Config::$indieLoginServer;
    IndieLogin\Client::$clientID = Config::$base;
    IndieLogin\Client::$redirectURL = Config::$base.'auth/callback';
  }

  public function start(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();
    session_setup(true);

    if(empty($params['url']) || !($me = IndieAuth\Client::normalizeMeURL($params['url']))) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid URL',
        'error_description' => 'The URL you entered is not valid'
      ]));
      return $response->withStatus(400);
    }

    if(isset($params['return-to'])) {
      $_SESSION['return-to'] = $params['return-to'];
    }

    $this->_indieLoginSetup();
    list($authorizationURL, $error) = IndieLogin\Client::begin($params['url']);

    return $response->withHeader('Location', $authorizationURL)->withStatus(302);
  }

  public function callback(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();
    session_setup(true);

    $this->_indieLoginSetup();

    list($user, $error) = IndieLogin\Client::complete($_GET);

    if($error) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => $error['error'],
        'error_description' => $error['error_description'],
      ]));
      return $response->withStatus(400);
    }

    $_SESSION['me'] = $user['me'];
    Rocks\Redis::haveSeenUserRecently($_SESSION['me'], true);

    if(array_key_exists('return-to', $_SESSION)) {
      $returnTo = $_SESSION['return-to'];
      unset($_SESSION['return-to']);
      return $response->withHeader('Location', $returnTo)->withStatus(302);
    } else {
      return $response->withHeader('Location', '/')->withStatus(302);
    }
  }

  public function signout(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup(true);
    unset($_SESSION['me']);
    $_SESSION = [];
    session_destroy();
    return $response->withHeader('Location', '/')->withStatus(302);
  }

}
