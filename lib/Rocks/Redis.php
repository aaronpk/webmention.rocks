<?php
namespace Rocks;
use Config;

class Redis {

  public static function makeResponseID($source, $target) {
    return Config::$base . 'response/' . md5($source . '::' . $target);
  }

  public static function makeWebmentionID($source, $target) {
    return Config::$base . 'webmention/' . md5($source . '::' . $target . '::' . date('c'));
  }

  public static function setResponseData($responseID, $data) {
    redis()->setex($responseID, 3600*48, json_encode($data));
  }

  public static function addResponse($testNum, $responseID) {
    redis()->zadd(Config::$base . 'test/'.$testNum.'/responses', time(), $responseID);
  }

  public static function deleteResponse($id) {
    redis()->del($id);
    // Also delete this response ID from any response collections
    foreach(TestData::data() as $num=>$data) {
      redis()->zrem(Config::$base . 'test/'.$num.'/responses', $id);
    }
  }

  public static function getResponsesForTest($testNum) {
    return redis()->zrevrangebyscore(Config::$base . 'test/' . $testNum . '/responses',
      time()+300, time()-3600*48);
  }

  public static function getResponse($id) {
    $data = redis()->get($id);
    if($data)
      return new Response($data);
    else
      return null;
  }

}
