<?php
class Config {
  public static $base = 'http://webmention-rocks.dev/';
  public static $redis = 'tcp://127.0.0.1:6379';

  // List one or more hostnames that this application is listening on.
  // Only webmentions with a target matching a hostname in this list will be accepted.
  public static $hostnames = ['webmention-rocks.dev'];

  // You might need to allow source URLs to resolve to localhost for development
  // In production, there is almost never a valid reason to allow localhost sources
  public static $allowLocalhostSource = false;

  // Used when an encryption key is needed. Set to something random.
  public static $secret = 'xxxx';
}
