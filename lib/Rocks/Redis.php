<?php
namespace Rocks;
use Config;

class Redis {

  /*** 
   ** User/Login data
   **/

  public static function haveSeenUserRecently($url, $set=false) {
    $key = Config::$base.'seen/'.md5($url);
    if($set) {
      redis()->setex($key, 60*60*24*7, 1);
    } else {
      return redis()->get($key) == 1;
    }
  }

  /***
   ** For sending webmentions
   **/

  // Generates a new code that will be used as the source URL for sending to the specified target.
  // The code is based off the test number and target URL so if the test is run again later the same code will be returned.
  public static function generateCodeForTarget($target, $num, $user) {
    $code = md5('target::'.$num.'::'.$target);
    $key = Config::$base . 'receive/' . $code . '/target';
    redis()->setex($key, 3600*72, json_encode([
      'target' => $target, 
      'num' => $num,
      'published' => date('Y-m-d H:i:s'),
      'user' => $user
    ]));
    return $code;
  }

  public static function getTargetDataFromCode($code) {
    $key = Config::$base . 'receive/' . $code . '/target';
    $data = redis()->get($key);
    if($data) {
      return json_decode($data);
    } else {
      return null;
    }
  }

  public static function saveReceiverTestResult($code, $result) {
    $key = Config::$base . 'receive/' . $code . '/target/results';
    redis()->setex($key, 3600*72, json_encode($result));
  }

  public static function getReceiverTestResult($code) {
    $key = Config::$base . 'receive/' . $code . '/target/results';
    $data = redis()->get($key);
    if($data) {
      return json_decode($data);
    } else {
      return null;
    }
  }

  /*** 
   ** For receiving webmentions
   **/

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
    if(array_key_exists(1, $id))
      return $folder.'/'.$id[1].($deleted ? '.deleted' : '').'.json';
    else
      return null;
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

  public static function getResponseData($responseID) {
    $data = redis()->get($responseID);
    if($data) {
      return json_decode($data);
    } else {
      return null;
    }
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

  public static function getAllResponses() {
    $glob = dirname(__FILE__) . '/../../data/response/*.json';
    $files = glob($glob);
    $responses = [];
    foreach($files as $f) {
      if(preg_match('/\/([a-z0-9]+)\.json/', $f, $match)) {
        $responses[] = Config::$base . 'response/' . $match[1];
      }
    }
    return $responses;
  }

  public static function getResponse($id, $onlyCached=false) {
    $data = redis()->get($id);
    if($data)
      return new Response($data, $id);

    if($onlyCached == false) {
      $filename = self::filenameForResponse($id);
      if(file_exists($filename))
        return new Response(file_get_contents($filename), $id);
    }

    return null;
  }

  public static function getSource($id, $onlyCached=false) {
    $data = redis()->get($id);
    if($data)
      return new Response($data, $id);

    if($onlyCached == false) {
      $filename = self::filenameForSource($id);
      if(file_exists($filename))
        return new Response(file_get_contents($filename), $id);
    }

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

  public static function setInProgressSourceData($sourceID, $data) {
    redis()->setex($sourceID, 600, json_encode($data));
  }

  // Check if this source URL has passed step 1/2/3/4
  public static function hasSourcePassedPart($responseID, $test, $part, $group='update') {
    return redis()->get(Config::$base . $group . '/' . $test . '/part/' . $part . '/' . $responseID) == 'passed';
  }

  // Store source URL has passed step 1/2/3/4
  public static function setSourceHasPassedPart($responseID, $test, $part, $group='update') {
    redis()->setex(Config::$base . $group . '/' . $test . '/part/' . $part . '/' . $responseID, 600, 'passed');
  }

  public static function addInProgressResponse($testNum, $responseID, $group='update') {
    redis()->zadd(Config::$base . $group . '/' . $testNum . '/inprogress/responses', time(), $responseID);
    // TODO: Remove old responses from the list
  }

  public static function removeInProgressResponse($testNum, $responseID, $group='update') {
    redis()->zrem(Config::$base . $group . '/' . $testNum . '/inprogress/responses', $responseID);
    for($part=1; $part<=3; $part++)
      redis()->del(Config::$base . $group . '/' . $testNum . '/part/' . $part . '/' . $responseID);
  }

  public static function getInProgressResponses($testNum, $group='update') {
    return redis()->zrevrangebyscore(Config::$base . $group . '/' . $testNum . '/inprogress/responses',
      time()+300, time()-600);
  }

  public static function extendExpiration($test, $responseID, $group='update') {
    // Extend the expiration of passing the three tests
    for($part=1; $part<=3; $part++)
      redis()->expire(Config::$base . $group . '/' . $test . '/part/' . $part . '/' . $responseID, 600);
    // And bump the timer of the in progress item
    self::addInProgressResponse($test, $responseID, $group);
  }


}
