<?php
namespace Rocks;
use Config;

class Redis {

  public static function makeResponseID($source, $target) {
    return Config::$base . 'response/' . md5($source . '::' . $target);
  }

  public static function makeSourceID($source) {
    return Config::$base . 'source/' . md5($source);
  }

  public static function makeWebmentionID($source, $target) {
    return Config::$base . 'webmention/' . md5($source . '::' . $target . '::' . date('c'));
  }

  private static function filenameForResponse($responseID, $deleted=false) {
    $folder = dirname(__FILE__) . '/../../data/response/';
    preg_match('/response\/(.+)/', $responseID, $id);
    return $folder.'/'.$id[1].($deleted ? '.deleted' : '').'.json';
  }

  private static function filenameForSource($sourceID, $deleted=false) {
    $folder = dirname(__FILE__) . '/../../data/source/';
    preg_match('/source\/(.+)/', $sourceID, $id);
    return $folder.'/'.$id[1].($deleted ? '.deleted' : '').'.json';
  }

  public static function setResponseData($responseID, $data, $type='response') {
    redis()->setex($responseID, 3600*48, json_encode($data));
    // Also write to a file for archiving
    if($type == 'response')
      $filename = self::filenameForResponse($responseID);
    elseif($type == 'source')
      $filename = self::filenameForSource($responseID);
    $folder = dirname($filename);
    if(!file_exists($folder))
      mkdir($folder, 0755, true);
    file_put_contents($filename, json_encode($data));
  }

  public static function addResponse($testNum, $responseID, $testKey='test') {
    redis()->zadd(Config::$base . $testKey . '/' . $testNum . '/responses', time(), $responseID);
    // TODO: Remove old responses from the list
  }

  public static function deleteResponse($id, $testKey='test') {
    redis()->del($id);
    // Also delete this response ID from any response collections
    if($testKey == 'test') {
      foreach(DiscoveryTestData::data() as $num=>$data) {
        redis()->zrem(Config::$base . $testKey . '/' . $num . '/responses', $id);
      }
    } elseif($testKey == 'update') {
      foreach(UpdateTestData::data() as $num=>$data) {
        redis()->zrem(Config::$base . $testKey . '/' . $num . '/responses', $id);
      }
    }
    // Rename the file to *.deleted.json
    $oldfilename = self::filenameForResponse($id);
    if(file_exists($oldfilename))
      rename($oldfilename, self::filenameForResponse($id, true));
  }

  public static function getResponsesForTest($testNum, $testKey='test') {
    return redis()->zrevrangebyscore(Config::$base . $testKey . '/' . $testNum . '/responses',
      time()+300, time()-3600*24*14);
  }

  public static function getResponse($id) {
    $data = redis()->get($id);
    if($data)
      return new Response($data, $id);

    $filename = self::filenameForResponse($id);
    if(file_exists($filename))
      return new Response(file_get_contents($filename), $id);

    return null;
  }

  public static function getSource($id) {
    $data = redis()->get($id);
    if($data)
      return new Response($data, $id);

    $filename = self::filenameForSource($id);
    if(file_exists($filename))
      return new Response(file_get_contents($filename), $id);

    return null;
  }

  /***
   ** One-time keys for single-use webmention endpoints **
   **/

  public static function createOneTimeKey() {
    $key = random_string(20);
    redis()->setex(Config::$base . 'onetime/' . $key, 600, 'active');
    return $key;
  }

  // If the key exists (hasn't been used yet), it deletes the key and returns true.
  // Returns false if the key doesn't exist (the endpoint has expired).
  public static function useOneTimeKey($key) {
    $str = Config::$base . 'onetime/' . $key;
    if(redis()->get($str) == 'active') {
      redis()->del($str);
      return true;
    } else {
      return false;
    }
  }

  /***
   ** Helper methods for keeping track of update state
   **/

  // Check if this source URL has passed step 1/2/2
  public static function hasSourcePassedPart($responseID, $test, $part) {
    return redis()->get(Config::$base . 'update/' . $test . '/part/' . $part . '/' . $responseID) == 'passed';
  }

  // Store source URL has passed step 1/2/3
  public static function setSourceHasPassedPart($responseID, $test, $part) {
    redis()->setex(Config::$base . 'update/' . $test . '/part/' . $part . '/' . $responseID, 600, 'passed');
  }

  public static function addInProgressResponse($testNum, $responseID) {
    redis()->zadd(Config::$base . 'update/' . $testNum . '/inprogress/responses', time(), $responseID);
    // TODO: Remove old responses from the list
  }

  public static function removeInProgressResponse($testNum, $responseID) {
    redis()->zrem(Config::$base . 'update/' . $testNum . '/inprogress/responses', $responseID);
    for($part=1; $part<=3; $part++)
      redis()->del(Config::$base . 'update/' . $test . '/part/' . $part . '/' . $responseID);
  }

  public static function getInProgressResponses($testNum, $testKey='update') {
    return redis()->zrevrangebyscore(Config::$base . $testKey . '/' . $testNum . '/inprogress/responses',
      time()+300, time()-600);
  }

  public static function extendExpiration($test, $responseID) {
    // Extend the expiration of passing the three tests
    for($part=1; $part<=3; $part++)
      redis()->expire(Config::$base . 'update/' . $test . '/part/' . $part . '/' . $responseID, 600);
    // And bump the timer of the in progress item
    self::addInProgressResponse($test, $responseID);
  }


}
