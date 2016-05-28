<?php
namespace Rocks;
use Config;

class ReceiverTestData extends TestData {

  protected static function _testData() {
    static $key;

    if(!isset($key))
      $key = Redis::createOneTimeKey();

    return [
      1 => [
        'published' => '2016-05-28T15:00:00-07:00',
        'name' => 'Accepts valid Webmention request',
        'description' => '<p>This test verifies that you accept a Webmention request that contains a valid source and target URL. To pass this test, your Webmention endpoint must return either HTTP 200, 201 or 202 along with the <a href="https://www.w3.org/TR/webmention/#receiving-webmentions">appropriate headers</a>.</p>
        <p>If your endpoint returns HTTP 201, then it MUST also return a <code>Location</code> header. If it returns HTTP 200 or 202, then it MUST NOT include a <code>Location</code> header.</p>',
      ],
    ];
  }

}
