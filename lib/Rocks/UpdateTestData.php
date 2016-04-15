<?php
namespace Rocks;
use Config;

class UpdateTestData extends TestData {

  protected static function _testData() {
    return [
      1 => [
        'published' => '2016-04-15T17:30:31+02:00',
        'name' => 'Simple update',
        'description' => '<p>This test verifies your handling of adding a link when <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-updated-posts">updating a post</a>. You will pass this test when you both re-send a Webmention to a previously mentioned URL, as well as send a Webmention to a new URL that appears in the post.</p>
        <p>
          <ol>
            <li>Write a post that links to <a href="/update/1/step/1">'.Config::$base.'update/1/step/1</a>, and send Webmentions for your post.</li>
            <li>Verify you see your post as "pending" <a href="/update/1/step/1">on the page</a>.</li>
            <li>Update your post to include a link to <a href="/update/1">this page</a>, and send webmentions for your post again, to both URLs.</li>
            <li>You should see your post appear here when successful.</li>
          </ol>
        </p>
        <link rel="webmention" href="/update/1/step/2/webmention">
        ',
        'steps' => [
          1 => [
            'description' => 'You should see your post listed here when you\'ve completed <a href="/update/1">step 1 of the test</a>. Once you complete step 2, it will be removed from this page and will show up on the main test page.
            <link rel="webmention" href="/update/1/step/1/webmention">',
          ],
          2 => [
            'description' => 'This page doesn\'t do anything. You can ignore it.',
          ]
        ],
      ],
      2 => [
        'published' => '2016-04-15T18:30:31+02:00',
        'name' => 'Removing a link',
        'description' => '<p>This test verifies your handling of removing a link when <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-updated-posts">updating a post</a>. You will pass this test when you both re-send a Webmention to a previously mentioned URL, as well as re-send a Webmention to a URL that you removed from the post.</p><p>
          <ol>
            <li>Write a post that links to <a href="/update/2">'.Config::$base.'update/2</a> and <a href="/update/2/step/1">'.Config::$base.'update/2/step/1</a>, and send Webmentions for your post.</li>
            <li>Verify you see your post as "pending" <a href="/update/2/step/1">here</a>.</li>
            <li>Update your post and remove the link to <a href="/update/2/step/1">'.Config::$base.'update/2/step/1</a>, and send webmentions for your post again, to both URLs.</li>
            <li>You should see your post appear here with the new content when successful.</li>
          </ol>
        </p>
        <link rel="webmention" href="/update/2/step/2/webmention">
        ',
        'steps' => [
          1 => [
            'description' => 'You should see your post listed here when you\'ve completed <a href="/update/2">step 1 of the test</a>. Once you complete step 2, it will be removed from this page and will show up on the main test page.
            <link rel="webmention" href="/update/2/step/1/webmention">',
          ],
          2 => [
            'description' => 'This page doesn\'t do anything. You can ignore it.',
          ]
        ],
      ]
    ];
  }

}
