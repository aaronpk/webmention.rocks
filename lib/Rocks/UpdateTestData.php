<?php
namespace Rocks;
use Config;

class UpdateTestData extends TestData {

  protected static function _testData() {
    static $key;

    if(!isset($key))
      $key = Redis::createOneTimeKey();

    return [
      1 => [
        'published' => '2016-04-16T03:05:00+02:00',
        'name' => 'Simple update',
        'description' => '<p>This test verifies your handling of adding a link when <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-updated-posts">updating a post</a>. You will pass this test when you both re-send a Webmention to a previously mentioned URL, as well as send a Webmention to a new URL that appears in the post.</p>
        <p>
          <ol>
            <li>Write a post that links to <a href="/update/1">this page</a>, and send Webmentions for your post.<br>
            Verify you see your post as "pending" on this page.</li>
            <li>Update your post to include a link to <a href="/update/1/part/2">' . Config::$base . 'update/1/part/2</a>, and send a webmention to the new URL.</li>
            <li>Send a webmention to the original URL as well. (The order of these is not significant.)</li>
            <li>You should see your post listed here in the green "Successful Tests" section when complete.</li>
          </ol>
        </p>
        <link rel="webmention" href="/update/1/part/1/webmention?key='.$key.'">
        ',
        'steps' => [
          1 => [
            'description' => 'This page doesn\'t do anything. You can ignore it.',
          ],
          2 => [
            'description' => 'This is a stub page for <a href="/update/1">Update Test #1</a>. Once you send an update Webmention to this page and the first page, you will have completed the test.
            <link rel="webmention" href="/update/1/part/2/webmention?key='.$key.'">',
          ]
        ],
        'checkboxes' => 3,
      ],
      2 => [
        'published' => '2016-04-15T18:30:31+02:00',
        'name' => 'Removing a link',
        'description' => '<p>This test verifies your handling of removing a link when <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-updated-posts">updating a post</a>. You will pass this test when you both re-send a Webmention to a previously mentioned URL, as well as re-send a Webmention to a URL that you removed from the post.</p><p>
          <ol>
            <li>Write a post that links to <a href="/update/2">'.Config::$base.'update/2</a> and <a href="/update/2/part/2">'.Config::$base.'update/2/part/2</a>, and send Webmentions for your post.</li>
            <li>Verify you see your post as "pending" on this post.</li>
            <li>Update your post and remove the link to <a href="/update/2/part/2">'.Config::$base.'update/2/part/2</a>, and send webmentions for your post again, to both URLs.</li>
            <li>You should see your post listed here in the green "Successful Tests" when complete.</li>
          </ol>
        </p>
        <link rel="webmention" href="/update/2/part/1/webmention?key='.$key.'">
        ',
        'steps' => [
          1 => [
            'description' => 'This page doesn\'t do anything. You can ignore it.',
          ],
          2 => [
            'description' => 'This is a stub page for <a href="/update/2">Update Test #1</a>. Once you remove your link to this post and send an update webmention, you will have completed this test.
            <link rel="webmention" href="/update/2/part/2/webmention?key='.$key.'">',
          ],
        ],
        'checkboxes' => 4,
      ]
    ];
  }

}
