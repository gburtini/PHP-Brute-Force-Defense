<?php
  namespace gburtini\bfd;
  interface Storage {
    public function store($key, $value);
    public function get($key);
    public function delete($key);
  }
