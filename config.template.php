<?php
class Config {
  public static $base = 'http://webmention-rocks.dev/';

  // List one or more hostnames that this application is listening on.
  // Only webmentions with a target matching a hostname in this list will be accepted.
  public static $hostnames = ['webmention-rocks.dev'];

  // You might need to allow source URLs to resolve to localhost for development
  // In production, there is almost never a valid reason to allow localhost sources
  public static $allowLocalhostSource = false;

  public static $ssl = false;
  public static $secretKey = '';

  public static $clientID = 'https://webmention-rocks.dev/';
  public static $defaultAuthorizationEndpoint = 'https://indieauth.com/auth';

  public static $relMeEmail = false;
}
