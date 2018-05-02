<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller {

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

    $state = IndieAuth\Client::generateStateParameter();
    $_SESSION['auth_state'] = $state;

    $query = [
      'client_id' => Config::$base,
      'redirect_uri' => Config::$base.'auth/callback',
      'state' => $state,
      'me' => $me,
    ];
    $authorizationURL = Config::$indieLoginServer.'auth?'.http_build_query($query);

    return $response->withHeader('Location', $authorizationURL)->withStatus(302);
  }

  public function callback(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();
    session_setup(true);

    // If there is no state in the session, start the login again
    if(empty($_SESSION['auth_state'])) {
      return $response->withHeader('Location', '/?error=missing_state')->withStatus(302);
    }

    // Check that there is a code in the response
    if(!array_key_exists('code', $params) || !$params['code']) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Missing Authorization Code',
        'error_description' => 'No authorization code was present in the request.'
      ]));
      return $response->withStatus(400);
    }

    // Verify the response included the state and that it matches the state we saved in the session
    if(!array_key_exists('state', $params) || !$params['state']) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Missing State',
        'error_description' => 'No state parameter was present in the request. Check that you have cookies enabled.'
      ]));
      return $response->withStatus(400);
    }

    if($params['state'] != $_SESSION['auth_state']) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid State',
        'error_description' => 'The state parameter provided did not match the state at the start of authorization. You will need to start the login process again.'
      ]));
      return $response->withStatus(400);
    }

    unset($_SESSION['auth_state']);
    unset($_SESSION['authorization_endpoint']);

    $auth = IndieAuth\Client::verifyIndieAuthCode(Config::$indieLoginServer,
      $params['code'], null, Config::$base.'auth/callback', Config::$base, $params['state']);

    if(!$auth || !is_array($auth) || !isset($auth['auth']['me'])) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid State',
        'error_description' => 'The login could not be validated. Please try again.'
      ]));
      return $response->withStatus(400);
    }
    $_SESSION['me'] = $auth['auth']['me'];
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
