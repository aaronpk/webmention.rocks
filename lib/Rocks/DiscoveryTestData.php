<?php
namespace Rocks;
use Config;

class DiscoveryTestData extends TestData {

  protected static function _testData($head) {
    $params = $head ? '?head=true' : '';

    return [
      // Link header with relative URL
      1 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '</test/1/webmention'.$params.'>; rel=webmention',
        'link_tag' => '',
        'name' => 'HTTP Link header, unquoted rel, relative URL',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link header with absolute URL
      2 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '<'.Config::$base.'test/2/webmention'.$params.'>; rel=webmention',
        'link_tag' => '',
        'name' => 'HTTP Link header, unquoted rel, absolute URL',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Link tag with relative URL
      3 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '',
        'link_tag' => '<link rel="webmention" href="/test/3/webmention'.$params.'">',
        'name' => 'HTML <link> tag, relative URL',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link tag with absolute URL
      4 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '',
        'link_tag' => '<link href="'.Config::$base.'test/4/webmention'.$params.'" rel="webmention">',
        'name' => 'HTML <link> tag, absolute URL',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The Webmention endpoint is listed as an absolute URL.',
      ],
      // <a> tag with relative URL
      5 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'HTML <a> tag, relative URL',
        'description' => 'This post advertises its <a rel="webmention" href="/test/5/webmention">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // <a> tag with absolute URL
      6 => [
        'published' => '2016-04-09T21:25:31-07:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'HTML <a> tag, absolute URL',
        'description' => 'This post advertises its <a href="'.Config::$base.'test/6/webmention" rel="webmention">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Odd-case Link header with absolute URL
      7 => [
        'published' => '2016-04-11T17:34:58-07:00',
        'link_header' => '<'.Config::$base.'test/7/webmention'.$params.'>; rel=webmention',
        'link_header_name' => 'LinK',
        'link_tag' => '',
        'name' => 'HTTP Link header with strange casing',
        'description' => 'This post advertises its Webmention endpoint with an HTTP header with intentionally unusual casing, "<code>LinK</code>". This helps you test whether you are handling HTTP header names in a case insensitive way.',
      ],
      // Link header with quoted rel name
      8 => [
        'published' => '2016-04-11T18:18:05-07:00',
        'link_header' => '<'.Config::$base.'test/8/webmention'.$params.'>; rel="webmention"',
        'link_tag' => '',
        'name' => 'HTTP Link header, quoted rel',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. Unlike tests #1 and #2, the rel value is quoted, since HTTP allows both <code>rel="webmention"</code> and <code>rel=webmention</code> for the Link header.',
      ],
      // Multiple rel values on a link tag
      9 => [
        'published' => '2016-04-14T17:05:16+02:00',
        'link_header' => '',
        'link_tag' => '<link rel="webmention somethingelse" href="'.Config::$base.'test/9/webmention">',
        'name' => 'Multiple rel values on a <link> tag',
        'description' => 'This post has a &lt;link&gt; tag with multiple rel values.',
      ],
      // Multiple rel values on a Link header
      10 => [
        'published' => '2016-04-14T17:05:16+02:00',
        'link_header' => '<'.Config::$base.'test/10/webmention'.$params.'>; rel="webmention somethingelse"',
        'link_tag' => '',
        'name' => 'Multiple rel values on a Link header',
        'description' => 'This post has an HTTP Link header with multiple rel values.',
      ],
      // Multiple endpoints defined, must use first
      11 => [
        'published' => '2016-04-14T17:05:16+02:00',
        'link_header' => '</test/11/webmention>; rel="webmention"',
        'link_tag' => '<link rel="webmention" href="/test/11/webmention/error">',
        'name' => 'Multiple Webmention endpoints advertised: Link, <link>, <a>',
        'description' => 'This post advertises its Webmention endpoint in the HTTP Link header, HTML &lt;link&gt; tag, as well as an <a href="/test/11/webmention/error" rel="webmention">&lt;a&gt; tag</a>. Your Webmention client must only send a Webmention to the one in the Link header.',
        'error_description' => 'You sent the Webmention to the wrong endpoint. This test checks whether you are sending to only the first endpoint discovered.',
      ],
      // rel=not-webmention should not receive a webmention
      12 => [
        'published' => '2016-04-14T17:44:23+02:00',
        'link_header' => '',
        'link_tag' => '<link rel="not-webmention" href="/test/12/webmention/error">',
        'name' => 'Checking for exact match of rel=webmention',
        'description' => 'This post contains a link tag with a rel value of "not-webmention", just to make sure you aren\'t using naïve string matching to find the endpoint. There is also a <a href="/test/12/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the wrong endpoint! You found the incorrect endpoint advertised with a value of rel=not-webmention. Make sure you\'re looking for an exact match of "webmention" when checking rel values.',
      ],
      // Tag in an HTML comment
      13 => [
        'published' => '2016-04-14T18:16:17+02:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'False endpoint inside an HTML comment',
        'description' => 'This post contains an HTML comment <!-- <a href="/test/13/webmention/error" rel="webmention"></a> --> that contains a rel=webmention element, which should not receive a Webmention since it\'s inside an HTML comment. There is also a <a href="/test/13/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the endpoint that was inside an HTML comment! Make sure you\'re actually parsing the HTML, and not just looking for a string match.'
      ],
      // Escaped HTML tag
      14 => [
        'published' => '2016-04-14T18:16:17+02:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'False endpoint in escaped HTML',
        'description' => 'This post contains sample code with escaped HTML which should not be discovered by the Webmention client. <code>&lt;a href="/test/14/webmention/error" rel="webmention"&gt;&lt;/a&gt;</code> There is also a <a href="/test/14/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the endpoint that was part of the escaped HTML! Make sure you\'re actually parsing the HTML, and not just looking for a string match.'
      ],
      // Webmention href is an empty string
      15 => [
        'published' => '2016-04-14T23:26:48+02:00',
        'link_header' => '',
        'link_tag' => '<link rel="webmention" href="">',
        'name' => 'Webmention href is an empty string',
        'description' => 'This post has a &lt;link&gt; tag where the href value is an empty string, meaning the page is its own Webmention endpoint. This tests the relative URL resolver of the sender to ensure an empty string is resolved to the page\'s URL.',
      ],
      // Multiple endpoints defined, must use first
      16 => [
        'published' => '2016-04-15T08:27:15+02:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'Multiple Webmention endpoints advertised: <a>, <link>',
        'description' => 'This post advertises its Webmention endpoint in an HTML <a href="/test/16/webmention" rel="webmention">&lt;a&gt; tag</a>, followed by a later definition in a &lt;link&gt; tag. Your Webmention client must only send a Webmention to the one in the &lt;a&gt; tag since it appears first in the document. <link rel="webmention" href="/test/16/webmention/error">',
        'error_description' => 'You sent the Webmention to the wrong endpoint. This test checks whether you are sending to only the first endpoint discovered.',
      ],
      // Multiple endpoints defined, must use first
      17 => [
        'published' => '2016-04-15T08:27:15+02:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'Multiple Webmention endpoints advertised: <link>, <a>',
        'description' => 'This post advertises its Webmention endpoint in an HTML &lt;link&gt; tag <link rel="webmention" href="/test/17/webmention"> followed by a later definition in an <a href="/test/17/webmention/error" rel="webmention">&lt;a&gt; tag</a>. Your Webmention client must only send a Webmention to the one in the &lt;link&gt; tag since it appears first in the document.',
        'error_description' => 'You sent the Webmention to the wrong endpoint. This test checks whether you are sending to only the first endpoint discovered.',
      ],
      18 => [
        'published' => '2016-04-15T08:27:15+02:00',
        'link_header' => [
          '<'.Config::$base.'test/18/webmention/error>; rel="other"',
          '<'.Config::$base.'test/18/webmention'.$params.'>; rel="webmention"',
        ],
        'link_tag' => '',
        'name' => 'Multiple HTTP Link headers',
        'description' => 'This post returns two HTTP Link headers, the first with a different rel value. This ensures your code correcly parses the HTTP response when multiple Link headers are returned.',
      ],
      19 => [
        'published' => '2016-04-15T08:27:15+02:00',
        'link_header' => '<'.Config::$base.'test/19/webmention/error>; rel="other", <'.Config::$base.'test/19/webmention'.$params.'>; rel="webmention"',
        'link_tag' => '',
        'name' => 'Single HTTP Link header with multiple values',
        'description' => 'This post returns one HTTP Link header with multiple values separated by a comma. This ensures your code correcly parses the HTTP headers.',
      ],
      // Webmention rel with no href
      20 => [
        'published' => '2016-04-15T11:03:35+02:00',
        'link_header' => '',
        'link_tag' => '',
        'name' => 'Link tag with no href attribute',
        'description' => 'This post has a &lt;link&gt; tag <link rel="webmention"> which has no href attribute. Your Webmention client should not find this link tag, and should send the webmention to <a href="/test/20/webmention" rel="webmention">this endpoint</a> instead.',
        'error_description' => 'You sent the Webmention to the wrong endpoint! Chances are your code found the link tag with rel=webmention and assumed that the lack of an href attribute makes the tag point to itself. Instead, you should skip this element since there is no href, and find the <a> tag in the body instead.'
      ],
      21 => [
        'published' => '2016-04-15T11:33:50+02:00',
        'link_header' => '',
        'link_tag' => '<link rel="webmention" href="/test/21/webmention?query=yes">',
        'name' => 'Webmention endpoint has query string parameters',
        'description' => 'This post\'s Webmention endpoint has query string parameters. Your Webmention client must preserve the query string parameters, and not send them in the post body.',
        'error_description' => 'The Webmention endpoint has a query string, but you either left it out, or sent the values as POST body parameters instead. Make sure you do not modify the URL discovered other than resolving it to an absolute URL.'
      ],

      // rel=webmention on a non-hyperlink tag
      // x => [
      //   'published' => '',
      //   'link_header' => '',
      //   'link_tag' => '',
      //   'name' => 'An <b> tag with rel=webmention attribute should not receive a Webmention',
      //   'description' => 'This post contains a <b href="/test/12/webmention/error" rel="webmention">&lt;b&gt;</b> tag with a rel=webmention attribute, but since Webmention endpoints can only be defined on hyperlink tags (&lt;link&gt; or &lt;a&gt;) it should not receive a webmention. There is also a correct endpoint defined, so if your comment appears below, it means you successfully ignored the false endpoint. <a href="/test/12/webmention" rel="webmention"></a>',
      //   'error_description' => 'You sent the Webmention to the endpoint advertised in the <b> tag, but your code should have skipped that and found the endpoint advertised in the <a> tag instead.',
      // ],
    ];
  }

}
