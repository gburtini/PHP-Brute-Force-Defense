<?php
  namespace gburtini\bfd;
  class APCIPStorage extends APCStorage {
    public function __construct($sessionPrefix, $ttl=604800) {
      $sessionPrefix = $sessionPrefix . $this->getIP();
      return parent::__construct($sessionPrefix, $ttl);
    }
    public function getIP() {
      // NOTE: X-FORWARDED-FOR is a good idea but it can be set by anyone, so we don't trust it.
      return $_SERVER['REMOTE_ADDR'];
    }
  }
?>
