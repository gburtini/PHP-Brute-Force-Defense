<?php
  namespace gburtini\bfd;
  require_once dirname(__FILE__) . "/Storage.php";
  class SessionStorage implements Storage {
    protected $sessionPrefix;
    public function __construct($sessionPrefix="") {
      session_start();
      $this->sessionPrefix = $sessionPrefix;
    }
    public function store($key, $value) {
      $_SESSION[$this->sessionPrefix . $key] = $value;
    }
    public function get($key, $default=null) {
      if(isset($_SESSION[$this->sessionPrefix . $key])) {
        return $_SESSION[$this->sessionPrefix . $key];
      } else {
        return $default;
      }
    }
    public function delete($key) {
      unset($_SESSION[$this->sessionPrefix . $key]);
    }
  }
?>
