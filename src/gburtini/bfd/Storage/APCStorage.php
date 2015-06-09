<?php
  namespace gburtini\bfd;
  class APCStorage implements Storage {
    protected $sessionPrefix;
    protected $ttl;
    public function __construct($sessionPrefix="", $ttl=604800) {
      $this->sessionPrefix = $sessionPrefix;
      $this->ttl = $ttl;
    }
    public function store($key, $value) {
      apc_store($this->sessionPrefix . $key, $value);
    }
    public function get($key, $default=null) {
      $result = apc_fetch($this->sessionPrefix . $key, $success);

      if($success) {
        return $result;
      } else {
        return $default;
      }
    }
    public function delete($key) {
      apc_delete($this->sessionPrefix . $key);
    }
  }
?>
