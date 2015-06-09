<?php
  namespace gburtini\bfd;

  /*
  * ThingThrottle - provides a generalized throttling infrastructure.
  *
  *	__construct($name, $storage, $shape, $sleep, $sleep)
  *		$name - a token for the element, function or method that this SessionThrottle instance is protecting
  *		$storage - an instance of a storage class such as SessionStorage or MemcacheStorage. The selected storage interface must provide three methods (store(name, value), get(name), delete(name)) and has security implications you should understand before chosing.
  * 	$shape - an instance of a shaping class such as ExponentialShape, which provides a method timeout($i) that computes a timeout for the $ith failure.
  *		$sleep - true/false for whether ->test() should ATTEMPT to always return true (by simply sleeping until the timelimit has passed).
  *				NOTE: even if sleep is true, the sleep may get interrupted and thus return false. You must ccheck it.
  *
  * Example use:
  * $login_limit = new SessionThrottle("login_bob");	// can have "login" throttles or "login_%username%" throttles... or even just an expensive process can be throttled by this.
  *	if($login_limit->test()) {
  * 		if(!checkLogin($user, $pass)) {
  *			$login_limit->fail();
  *		} else {
  *			$login_limit->succeed(); // clear the timelimit
  *		}
  *	} else {
  *		showThrottleError();
  *	}
  *
  */

	class ThingThrottle {
		protected $name;
		protected $storage;
		protected $shape;
    protected $sleep;
		public function __construct($name, $storage, $shape, $sleep=true) {
      $this->storage = $storage;
      $this->shape = $shape;
			$this->name = $name;
      $this->sleep = (bool) $sleep;
		}

		protected function eq($i) {
			return $this->shape->timeout($i);
		}

		/**
		 * fail() should be called when we want to increment the failure counter for this protected function. If failing
		 * isn't the appropriate metaphor for your function, an alias increment() is provided.
		 */
		public function fail() {
      $this->storage->store($this->name . "fails", $this->storage->get($this->name . "fails")+1);
      $this->storage->store($this->name . "time", time());
		}
		public function increment() { return $this->fail(); }

		public function succeed() {
      $this->storage->store($this->name . "fails", 0);
      $this->storage->delete($this->name . "time");
		}

		/**
		 * test() must be called -before- the protected function is. If it returns false, you should not call your
		 * protected function. If it returns true, sufficient time has passed and the user is granted a new trial.
		 *
		 * Even if $this->sleep = true, you need to check the return type, as sleeps can be interrupted by platform
		 * signals.
		 */
		public function test() {
			// check if we need to sleep/wait. return accordingly.
			if(null != $this->storage->get($this->name . "fails"))
				return true;

			$earliest = $this->eq($this->storage->get($this->name . "fails")) + $this->storage->get($this->name . "time");
			$now = time();
			if($now < $earliest) {
				if($this->sleep) {
					// sleep the gap between now and then, if it successfully sleeps, return true.
					if(sleep($earliest - $now) === 0) {
						return true;
					}
					return false;
				} else {
					return false;
				}
			} else { return true; }
		}
	}
?>
