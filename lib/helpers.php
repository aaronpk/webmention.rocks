<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/../config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/../config.php');
}

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
}

function redis() {
  static $client = false;
  if(!$client)
    $client = new Predis\Client(Config::$redis);
  return $client;
}

function random_string($len) {
  $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  $c = strlen($charset)-1;
  for($i=0; $i<$len; $i++) {
    $str .= $charset[mt_rand(0, $c)];
  }
  return $str;
}

function isPublicAddress($ip) {
  // http://stackoverflow.com/a/30143143

  //Private ranges...
  //http://www.iana.org/assignments/iana-ipv4-special-registry/
  $networks = array('10.0.0.0'        =>  '255.0.0.0',        //LAN.
                    '172.16.0.0'      =>  '255.240.0.0',      //LAN.
                    '192.168.0.0'     =>  '255.255.0.0',      //LAN.
                    '127.0.0.0'       =>  '255.0.0.0',        //Loopback.
                    '169.254.0.0'     =>  '255.255.0.0',      //Link-local.
                    '100.64.0.0'      =>  '255.192.0.0',      //Carrier.
                    '192.0.2.0'       =>  '255.255.255.0',    //Testing.
                    '198.18.0.0'      =>  '255.254.0.0',      //Testing.
                    '198.51.100.0'    =>  '255.255.255.0',    //Testing.
                    '203.0.113.0'     =>  '255.255.255.0',    //Testing.
                    '192.0.0.0'       =>  '255.255.255.0',    //Reserved.
                    '224.0.0.0'       =>  '224.0.0.0',        //Reserved.
                    '0.0.0.0'         =>  '255.0.0.0');       //Reserved.

  $ip = @inet_pton($ip);
  if (strlen($ip) !== 4) { return false; }

  //Is the IP in a private range?
  foreach($networks as $network_address => $network_mask) {
    $network_address   = inet_pton($network_address);
    $network_mask      = inet_pton($network_mask);
    assert(strlen($network_address)    === 4);
    assert(strlen($network_mask)       === 4);
    if (($ip & $network_mask) === $network_address)
      return false;
  }

  return true;
}
