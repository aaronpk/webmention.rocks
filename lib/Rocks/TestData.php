<?php
namespace Rocks;
use Config;

class TestData {

  public static function data($num=false, $head=false) {
    $data = static::_testData($head);

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
