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
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link header with absolute URL
      2 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/2/webmention'.$params.'>; rel=webmention',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Link tag with relative URL
      3 => [
        'link_tag' => '<link rel="webmention" href="/test/3/webmention'.$params.'">',
        'link_header' => '',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // Link tag with absolute URL
      4 => [
        'link_tag' => '<link rel="webmention" href="'.Config::$base.'test/4/webmention'.$params.'">',
        'link_header' => '',
        'description' => 'This post advertises its Webmention endpoint with an HTML <code>&lt;link&gt;</code> tag in the document. The Webmention endpoint is listed as an absolute URL.',
      ],
      // <a> tag with relative URL
      5 => [
        'link_tag' => '',
        'link_header' => '',
        'description' => 'This post advertises its <a rel="webmention" href="/test/5/webmention'.$params.'">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The URL is relative, so this will also test whether your discovery code properly resolves the relative URL.',
      ],
      // <a> tag with absolute URL
      6 => [
        'link_tag' => '',
        'link_header' => '',
        'description' => 'This post advertises its <a rel="webmention" href="'.Config::$base.'test/6/webmention">Webmention endpoint</a> with an HTML <code>&lt;a&gt;</code> tag in the body. The Webmention endpoint is listed as an absolute URL.',
      ],
      // Odd-case Link header with absolute URL
      7 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/7/webmention'.$params.'>; rel=webmention',
        'link_header_name' => 'LinK',
        'description' => 'This post advertises its Webmention endpoint with an HTTP header with intentionally unusual casing, "<code>LinK</code>". This helps you test whether you are handling HTTP header names in a case insensitive way.',
      ],
      8 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/8/webmention'.$params.'>; rel="webmention"',
        'description' => 'This post advertises its Webmention endpoint with an HTTP <code>Link</code> header. Unlike tests #1 and #2, the rel value is quoted, since HTTP allows both <code>rel="webmention"</code> and <code>rel=webmention</code> for the Link header.',
      ],
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
