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
        'a_tag' => '',
      ],
      // Link header with absolute URL
      2 => [
        'link_tag' => '',
        'link_header' => '<'.Config::$base.'test/2/webmention'.$params.'>; rel=webmention',
        'a_tag' => '',
      ],
      // Link tag with relative URL
      3 => [
        'link_tag' => '<link rel="webmention" href="/test/1/webmention'.$params.'">',
        'link_header' => '',
        'a_tag' => '',
      ],
      // Link tag with absolute URL
      4 => [
        'link_tag' => '<link rel="webmention" href="'.Config::$base.'test/1/webmention'.$params.'">',
        'link_header' => '',
        'a_tag' => '',
      ],
      // <a> tag with relative URL
      5 => [
        'link_tag' => '',
        'link_header' => '',
        'a_tag' => '<a rel="webmention" href="/test/1/webmention'.$params.'">webmention endpoint</a>',
      ],
      // <a> tag with absolute URL
      6 => [
        'link_tag' => '',
        'link_header' => '',
        'a_tag' => '<a rel="webmention" href="'.Config::$base.'test/1/webmention'.$params.'">webmention endpoint</a>',
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

  public static function a_tag($num, $head) {
    return self::data($num, $head)['a_tag'];
  }

}
