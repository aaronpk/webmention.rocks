<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller {

  public function start(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();
    session_setup(true);
    
    if(!array_key_exists('url', $params) || !($me = IndieAuth\Client::normalizeMeURL($params['url']))) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid URL',
        'error_description' => 'The URL you entered is not valid'
      ]));
      return $response->withStatus(400);
    }

    if(array_key_exists('return-to', $params)) {
      $_SESSION['return-to'] = $params['return-to'];
    }

    $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);

    if(!$authorizationEndpoint) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Missing Authorization Endpoint',
        'error_description' => 'Your website did not provide an authorization endpoint.',
        'include' => 'partials/auth-endpoint-help'
      ]));
      return $response;
    }

    // If we've seen this user recently, sign them in immediately. Otherwise, tell them what's about to happen.

    $state = IndieAuth\Client::generateStateParameter();
    $_SESSION['auth_state'] = $state;
    $_SESSION['authorization_endpoint'] = $authorizationEndpoint;
    $_SESSION['attempted_me'] = $me;
    $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, $me, Config::$base.'auth/callback', Config::$base, $state);

    if(Rocks\Redis::haveSeenUserRecently($me)) {
      return $response->withHeader('Location', $authorizationURL)->withStatus(302);
    } else {
      $response->getBody()->write(view('auth-start', [
        'title' => 'Sign In - Webmention Rocks!',
        'authorization_endpoint' => $authorizationEndpoint,
        'authorization_url' => $authorizationURL
      ]));
      return $response;
    }
  }

  public function callback(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();
    session_setup(true);

    // Verify there is a "me" parameter in the callback
    if(!array_key_exists('me', $params) || !($me = IndieAuth\Client::normalizeMeURL($params['me']))) {
      if(array_key_exists('me', $params))
        $error = 'The ID <strong>' . $params['me'] . '</strong> is not valid.';
      else
        $error = 'There was no "me" parameter in the callback.';

      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid "me" Parameter',
        'error_description' => $error
      ]));
      return $response->withStatus(400);
    }

    // If there is no state in the session, start the login again
    if(!array_key_exists('auth_state', $_SESSION)) {
      return $response->withHeader('Location', '/auth/start?url='.urlencode($params['me']))->withStatus(302);
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
        'error_description' => 'No state parameter was present in the request.'
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

    $authorizationEndpoint = $_SESSION['authorization_endpoint'];

    unset($_SESSION['auth_state']);
    unset($_SESSION['authorization_endpoint']);

    $auth = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $params['code'], $params['me'], Config::$base.'auth/callback', Config::$base, null);

    if(!$auth || !is_array($auth) || !array_key_exists('me', $auth)) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Invalid State',
        'error_description' => 'The state parameter provided did not match the state at the start of authorization. You will need to start the login process again.'
      ]));
      return $response->withStatus(400);
    }

    // Verify that the hostname of the initial user matches the hostname of the user the auth endpoint returned.
    // This allows people on multi-user sites to enter the site's home page, but use a URL with a path as their identity.
    if(!domains_are_equal($_SESSION['attempted_me'], $auth['me'])) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Webmention Rocks!',
        'error' => 'Hostname Mismatch',
        'error_description' => 'The URL returned from the authorization endpoint was on a different domain from the URL first entered.'
      ]));
      return $response->withStatus(400);
    }

    unset($_SESSION['attempted_me']);

    $_SESSION['me'] = $auth['me'];
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
