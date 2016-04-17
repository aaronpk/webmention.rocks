<?php
namespace Rocks;

use DateTime, Exception;
use EmojiRecognizer;
use DomDocument;

class Response {

  public $id;
  public $_data;
  private $_comment;

  public function __construct($json, $id) {
    if(is_array($json))
      $data = $json;
    else
      $data = json_decode($json, true);
    $this->_data = $data;
    if(@isset($data['comment']['data'])) {
      $this->_comment = $data['comment']['data'];
    }
    $this->id = $id;
  }

  public function __get($key) {
    if(method_exists($this, $key))
      return $this->$key();
  }

  public function hash() {
    return self::hashForId($this->id);
  }

  public static function hashForId($id) {
    preg_match('/\/([^\/]+)$/', $id, $match);
    return $match[1];
  }

  public function author_photo() {
    if($this->_comment) {
      if(@isset($this->_comment['author']['photo'])) {
        return \ImageProxy::url($this->_comment['author']['photo']);
      }
    }
    return null;
  }

  public function author_name() {
    if($this->_comment) {
      if(@isset($this->_comment['author']['name'])) {
        return $this->_comment['author']['name'];
      }
    }
    return null;
  }

  public function author_url() {
    if($this->_comment) {
      if(@isset($this->_comment['author']['url'])) {
        return $this->_comment['author']['url'];
      }
    }
    return null;
  }

  public function name() {
    if($this->_comment) {
      if(@isset($this->_comment['name']) && $this->_comment['name']) {
        return $this->_comment['name'];
      }
    }
    return false;
  }

  public function content() {
    if($this->_comment) {
      if(@isset($this->_comment['content']['html']) && $this->_comment['content']['html']) {
        return self::add_nofollow_and_img_proxy($this->_comment['content']['html']);
      }
      if(@isset($this->_comment['content']['text']) && $this->_comment['content']['text']) {
        return $this->_comment['content']['text'];
      }
    }
    return null;
  }

  public function text_content() {
    if($this->_comment) {
      if(@isset($this->_comment['content']['text']) && $this->_comment['content']['text']) {
        return $this->_comment['content']['text'];
      }
    }
    return null;
  }

  public function content_is_html() {
    if($this->_comment) {
      if(@isset($this->_comment['content']['html']) && $this->_comment['content']['html']) {
        return true;
      }
    }
    return false;
  }

  public function published() {
    if($this->_comment) {
      if(@isset($this->_comment['published']) && $this->_comment['published']) {
        try {
          return new DateTime($this->_comment['published']);
        } catch(Exception $e) {
          return null;
        }
      }
    }
    return null;
  }

  public function url() {
    if($this->_comment) {
      if(@isset($this->_comment['url']) && $this->_comment['url']) {
        return $this->_comment['url'];
      }
    }
    return null;
  }

  public function url_host() {
    $url = $this->url();
    if($url) {
      return parse_url($url, PHP_URL_HOST);
    }
    return null;
  }

  public function source() {
    return $this->_data['source'];
  }

  public function source_host() {
    $url = $this->source();
    if($url) {
      return parse_url($url, PHP_URL_HOST);
    }
    return null;
  }

  // Return url if present, otherwise source
  public function href() {
    return $this->url() ?: $this->source();
  }

  public function getMentionType() {
    if($this->isTypeOf('like-of'))
      return 'like';
    if($this->isTypeOf('repost-of'))
      return 'repost';
    if($this->isTypeOf('bookmark-of'))
      return 'bookmark';
    if($this->isTypeOf('in-reply-to')) {
      // Check if this post is a reacji (the reply text is a single emoji character)
      if($comment = $this->text_content()) {
        if(EmojiRecognizer::isSingleEmoji($comment)) {
          return 'reacji';
        }
      }
      return 'reply';
    }
    return 'mention';
  }

  public function isTypeOf($property) {
    return array_key_exists($property, $this->_comment)
      && in_array($this->_data['target'], $this->_comment[$property]);
  }

  public static function facepileTypes() {
    return ['like','repost','bookmark'];
  }

  public static function facepileTypeIcon($type) {
    switch($type) {
      case 'like':
        return 'star';
      case 'repost':
        return 'retweet';
      case 'bookmark':
        return 'bookmark';
    }
  }

  private static function add_nofollow_and_img_proxy($html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // suppress parse errors and warnings
    // Force interpreting this as UTF-8
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();

    $anchors = $dom->getElementsByTagName('a');
    foreach($anchors as $anchor) { 
      $rel = array(); 

      if($anchor->hasAttribute('rel') && ($relAtt = $anchor->getAttribute('rel')) !== '') {
         $rel = preg_split('/\s+/', trim($relAtt));
      }

      if(in_array('nofollow', $rel)) {
        continue;
      }

      $rel[] = 'nofollow';
      $anchor->setAttribute('rel', implode(' ', $rel));
    }

    $imgs = $dom->getElementsByTagName('img');
    foreach($imgs as $img) {
      if($img->hasAttribute('src') && ($src = $img->getAttribute('src')) !== '') {
        $src = \ImageProxy::url($src);
        $img->setAttribute('src', $src);
      }
    }

    $dom->saveHTML();

    $html = '';
    foreach($dom->getElementsByTagName('body')->item(0)->childNodes as $element) {
      $html .= $dom->saveHTML($element);
    }
    return $html;
  }

}
