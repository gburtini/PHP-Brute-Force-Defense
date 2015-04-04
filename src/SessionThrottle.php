<?php
	/**
	 * SessionThrottle - provides exponential session-based rate limiting / throttling.
	 *
	 * Please be aware this only mitigates one attack vector: a single session brute forcing a protected event.
	 *
	 * All (important) parameters are passed within the constructor.
	 *	__construct($name, $safe, $upper, $rate, $sleep)
	 *		$name - a token for the element, function or method that this SessionThrottle instance is protecting
	 *		$safe - the number of failures a user gets "free" before they start to get rate limited, you can safely set this quite high for most login related applications.
	 * 		$upper - the highest power of $rate to be used. Out of the box, this is 20 (with rate 1.3) meaning the highest timeout is 1.3^20 = 190 seconds.
	 *		$rate - the base of the exponent used to calculate the time limit. By default, 1.3. Too large, and the throttler is too aggressive. Too small, and it won't be aggressive enough.
	 *		$sleep - true/false for whether ->test() should ATTEMPT to always return true (by simply sleeping until the timelimit has passed). 
	 *				NOTE: even if sleep is true, the sleep may get interrupted and thus return false. You must ccheck it.
	 * 
	 * Example use:
	 * 	$login_limit = new SessionThrottle("login_bob");	// can have "login" throttles or "login_%username%" throttles... or even just an expensive process can be throttled by this.
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

	/*
	*	NOTE: when using this, you should constantly be aware of other attack vectors and utilize additional tools such as
	*	IP based blocking, data-level blocking (block logins for a particular user account, rather than a particular session)
	*	and even, in emergencies, global level blocks. If you're using this for a login system, you should be aware of
	*	man in the middle attacks and password storage issues, the session timeout should be used in conjunction with a
	*	slow hash like bcrypt.
	*
	*	Another like to have here is a way to disable the timer for "certain" users, ala "device cookies". If a user has
	*	logged in before successfully, deliver them a cookie that allows future login attempts to avoid or slowdown the
	*	timeout as this is a lower risk path.
	*/
	use gburtini\bfd;
	class SessionThrottle {
		protected $name;
		protected $safe = 10; // the number of "harmless" checks
		protected $upper = 20;
		protected $rate = 1.3;	// the timeout grows a rate^n for failures n between safe and upper.
		protected $sleep = false;
		protected $sessionPrefix = "sessiontimeout_";

		protected $minimumSleep = 0;
		protected $maximumSleep = 0;
		public function __construct($name, $safe = 10, $upper = 20, $rate=1.3, $sleep = false) {
			session_start();

			if(!isset($_SESSION[$this->sessionPrefix . "fails"]))
				$_SESSION[$this->sessionPrefix . "fails"] = 0;

			$this->name = $name;
			$this->safe = intval($safe);
			$this->upper = intval($upper);
			$this->rate = floatval($rate);
			$this->sleep = (bool) $sleep;
		}

		protected function eq($i) {
			if($i > $this->safe+$this->upper)
				$i = $this->safe+$this->upper;

			$timeout = min($this->maximumSleep,
					max($this->minimumSleep,
						exp($this->rate, max(0, ($i - $this->safe)))
					   )
				      );
			return $timeout;
		}

		/**
		 * fail() should be called when we want to increment the failure counter for this protected function. If failing
		 * isn't the appropriate metaphor for your function, an alias increment() is provided.
		 */
		public function fail() {
			$_SESSION[$this->sessionPrefix . "fails"]++;
			$_SESSION[$this->sessionPrefix . "time"] = time();
		}
		public function increment() { return $this->fail(); }

		public function succeed() {
			$_SESSION[$this->sessionPrefix . "fails"] = 0;
			unset($_SESSION[$this->sessionPrefix . "time"]);
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
			if(!isset($_SESSION[$this->sessionPrefix . "time"]))
				return true;

			$earliest = $this->eq($_SESSION[$this->sessionPrefix . "fails"]) + $_SESSION[$this->sessionPrefix . "time"];
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
