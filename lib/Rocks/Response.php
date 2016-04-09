<?php
namespace Rocks;

use DateTime;

class Response {

  private $_data;
  private $_comment;

  public function __construct($json) {
    $data = json_decode($json, true);
    $this->_data = $data;
    if(@isset($data['comment']['data'])) {
      $this->_comment = $data['comment']['data'];
    }
  }

  public function __get($key) {
    if(method_exists($this, $key))
      return $this->$key();
  }

  public function author_photo() {
    if($this->_comment) {
      if(@isset($this->_comment['author']['photo'])) {
        return $this->_comment['author']['photo'];
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

  public function content() {
    if($this->_comment) {
      if(@isset($this->_comment['content']['html']) && $this->_comment['content']['html']) {
        return $this->_comment['content']['html'];
      }
      if(@isset($this->_comment['content']['text']) && $this->_comment['content']['text']) {
        return $this->_comment['content']['text'];
      }
    }
    return null;
  }

  public function published() {
    if($this->_comment) {
      if(@isset($this->_comment['published']) && $this->_comment['published']) {
        return new DateTime($this->_comment['published']);
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

}
