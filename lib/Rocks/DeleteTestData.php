<?php
namespace Rocks;
use Config;

class DeleteTestData extends TestData {

  protected static function _testData() {
    static $key;

    if(!isset($key))
      $key = Redis::createOneTimeKey();

    return [
      1 => [
        'published' => '2016-04-24T19:05:00-07:00',
        'name' => 'Simple delete',
        'description' => '<p>This test verifies that you properly send Webmentions when you <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-deleted-posts">delete a post</a>. You will pass this test when you send a Webmention to a URL that you had previously mentioned in a post.</p>
        <p>
          <ol>
            <li>Write a post that links to <a href="/delete/1">this page</a>, and send Webmentions for your post.<br>
            Verify you see your post as "pending" on this page.</li>
            <li>Delete your post, and ensure that the post\'s URL is now returning HTTP 410.</li>
            <li>Send a Webmention to this page again.</li>
            <li>You should see your post listed here in the green "Successful Tests" section when complete.</li>
          </ol>
        </p>
        <link rel="webmention" href="/delete/1/webmention?key='.$key.'">
        ',
        'checkboxes' => 2,
      ],
    ];
  }

}
