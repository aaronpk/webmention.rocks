<?php
namespace Rocks;
use Config;

class TestData {

  public static function data($num=false, $head=false) {
    $params = $head ? '?head=true' : '';

    $data = [
      // Link header with relative URL
      1 => [
        'link_tag' => '',
        'link_header' => '</test/1/webmention'.$params.'>; rel=webmention',
        'name' => 'HTTP Link header, unquoted rel, relative URL',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link header with absolute URL
      2 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/2/webmention'.$params.'>; rel=webmention',
        'name' => 'HTTP Link header, unquoted rel, absolute URL',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Link tag with relative URL
      3 => [
        'link_tag' => '<link rel="webmention" href="/test/3/webmention'.$params.'">',
        'link_header' => '',
        'name' => 'HTML <link> tag, relative URL',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link tag with absolute URL
      4 => [
        'link_tag' => '<link href="'.Config::$base.'test/4/webmention'.$params.'" rel="webmention">',
        'link_header' => '',
        'name' => 'HTML <link> tag, absolute URL',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The Webmention endpoint is listed as an absolute URL.',
      ],
      // <a> tag with relative URL
      5 => [
        'link_tag' => '',
        'link_header' => '',
        'name' => 'HTML <a> tag, relative URL',
        'description' => 'This post advertises its <a rel="webmention" href="/test/5/webmention'.$params.'">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // <a> tag with absolute URL
      6 => [
        'link_tag' => '',
        'link_header' => '',
        'name' => 'HTML <a> tag, absolute URL',
        'description' => 'This post advertises its <a href="'.Config::$base.'test/6/webmention" rel="webmention">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Odd-case Link header with absolute URL
      7 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/7/webmention'.$params.'>; rel=webmention',
        'link_header_name' => 'LinK',
        'name' => 'HTTP Link header with strange casing',
        'description' => 'This post advertises its Webmention endpoint with an HTTP header with intentionally unusual casing, "<code>LinK</code>". This helps you test whether you are handling HTTP header names in a case insensitive way.',
      ],
      // Link header with quoted rel name
      8 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/8/webmention'.$params.'>; rel="webmention"',
        'name' => 'HTTP Link header, quoted rel',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. Unlike tests #1 and #2, the rel value is quoted, since HTTP allows both <code>rel="webmention"</code> and <code>rel=webmention</code> for the Link header.',
      ],
      // Multiple rel values on a link tag
      9 => [
        'link_tag' => '<link rel="webmention somethingelse" href="'.Config::$base.'test/9/webmention">',
        'link_header' => '',
        'name' => 'Multiple rel values on a <link> tag',
        'description' => 'This post has a &lt;link&gt; tag with multiple rel values.',
      ],
      // Multiple rel values on a Link header
      10 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/10/webmention'.$params.'>; rel="webmention somethingelse"',
        'name' => 'Multiple rel values on a Link header',
        'description' => 'This post has an HTTP Link header with multiple rel values.',
      ],
      // Multiple endpoints defined, must use first
      11 => [
        'link_tag' => '<link rel="webmention" href="/test/11/webmention">',
        'link_header' => '</test/11/webmention?error>; rel="webmention"',
        'name' => 'Multiple Webmention endpoints advertised',
        'description' => 'This post advertises its Webmention endpoint in the HTTP Link header, HTML &lt;link&gt; tag, as well as an <a href="/test/11/webmention?error" rel="webmention">&lt;a&gt; tag</a>. Your Webmention client must only send a Webmention to the one in the Link header.',
        'error_description' => 'You sent the Webmention to the wrong endpoint. This test checks whether you are sending to only the first endpoint discovered.',
      ],
      // rel=not-webmention should not receive a webmention
      12 => [
        'link_tag' => '<link rel="not-webmention" href="/test/12/webmention?error">',
        'link_header' => '',
        'name' => 'Checking for exact match of rel=webmention',
        'description' => 'This post contains a link tag with a rel value of "not-webmention", just to make sure you aren\'t using naïve string matching to find the endpoint. There is also a <a href="/test/12/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the wrong endpoint! You found the incorrect endpoint advertised with a value of rel=not-webmention. Make sure you\'re looking for an exact match of "webmention" when checking rel values.',
      ],
      // Tag in an HTML comment
      13 => [
        'link_tag' => '',
        'link_header' => '',
        'name' => 'False endpoint inside an HTML comment',
        'description' => 'This post contains an HTML comment <!-- <a href="/test/13/webmention?error" rel="webmention"></a> --> that contains a rel=webmention element, which should not receive a Webmention since it\'s inside an HTML comment. There is also a <a href="/test/13/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the endpoint that was inside an HTML comment! Make sure you\'re actually parsing the HTML, and not just looking for a string match.'
      ],
      // Escaped HTML tag
      14 => [
        'link_tag' => '',
        'link_header' => '',
        'name' => 'False endpoint in escaped HTML',
        'description' => 'This post contains sample code with escaped HTML which should not be discovered by the Webmention client. <code>&lt;a href="/test/14/webmention?error" rel="webmention"&gt;&lt;/a&gt;</code> There is also a <a href="/test/14/webmention" rel="webmention">correct endpoint</a> defined, so if your comment appears below, it means you successfully ignored the false endpoint.',
        'error_description' => 'You sent the Webmention to the endpoint that was part of the escaped HTML! Make sure you\'re actually parsing the HTML, and not just looking for a string match.'
      ],

      // rel=webmention on a non-hyperlink tag
      // x => [
      //   'link_tag' => '',
      //   'link_header' => '',
      //   'name' => 'An <b> tag with rel=webmention attribute should not receive a Webmention',
      //   'description' => 'This post contains a <b href="/test/12/webmention?error" rel="webmention">&lt;b&gt;</b> tag with a rel=webmention attribute, but since Webmention endpoints can only be defined on hyperlink tags (&lt;link&gt; or &lt;a&gt;) it should not receive a webmention. There is also a correct endpoint defined, so if your comment appears below, it means you successfully ignored the false endpoint. <a href="/test/12/webmention" rel="webmention"></a>',
      //   'error_description' => 'You sent the Webmention to the endpoint advertised in the <b> tag, but your code should have skipped that and found the endpoint advertised in the <a> tag instead.',
      // ],
    ];
    if($num) {
      if(array_key_exists($num, $data)) {
        return $data[$num];
      } else {
        return false;
      }
    } else {
      return $data;
    }
  }

  public static function exists($num) {
    return (bool)self::data($num);
  }

  public static function link_tag($num, $head) {
    return self::data($num, $head)['link_tag'];
  }

  public static function link_header($num, $head) {
    return self::data($num, $head)['link_header'];
  }

  public static function link_header_name($num, $head) {
    $data = self::data($num, $head);
    if(array_key_exists('link_header_name', $data))
      return $data['link_header_name'];
    else
      return 'Link';
  }

  public static function a_tag($num, $head) {
    return self::data($num, $head)['a_tag'];
  }

}
