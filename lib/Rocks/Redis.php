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

  private static function filenameForResponse($responseID, $deleted=false) {
    $folder = dirname(__FILE__) . '/../../data/response/';
    preg_match('/response\/(.+)/', $responseID, $id);
    return $folder.'/'.$id[1].($deleted ? '.deleted' : '').'.json';
  }

  public static function setResponseData($responseID, $data) {
    redis()->setex($responseID, 3600*48, json_encode($data));
    // Also write to a file for archiving
    $filename = self::filenameForResponse($responseID);
    $folder = dirname($filename);
    if(!file_exists($folder))
      mkdir($folder, 0755, true);
    file_put_contents($filename, json_encode($data));
  }

  public static function addResponse($testNum, $responseID) {
    redis()->zadd(Config::$base . 'test/'.$testNum.'/responses', time(), $responseID);
    // TODO: Remove old responses from the list
  }

  public static function deleteResponse($id) {
    redis()->del($id);
    // Also delete this response ID from any response collections
    foreach(TestData::data() as $num=>$data) {
      redis()->zrem(Config::$base . 'test/'.$num.'/responses', $id);
    }
    // Rename the file to *.deleted.json
    rename(self::filenameForResponse($id), self::filenameForResponse($id, true));
  }

  public static function getResponsesForTest($testNum) {
    return redis()->zrevrangebyscore(Config::$base . 'test/' . $testNum . '/responses',
      time()+300, time()-3600*24*14);
  }

  public static function getResponse($id) {
    $data = redis()->get($id);
    if($data)
      return new Response($data);

    $filename = self::filenameForResponse($id);
    if(file_exists($filename))
      return new Response(file_get_contents($filename));

    return null;
  }

}
