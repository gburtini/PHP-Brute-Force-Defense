<?php
	/**
	 * SessionThrottle - provides exponential session-based rate limiting / throttling.
	 *
	 * This provides backwards compatibility to the original interface. You should use the ThingThrottle directly for future implementations.
	 *
	 * Please be aware this only mitigates one attack vector: a single session brute forcing a protected event WHICH ACCEPTS SESSION COOKIES.
	 * For any truly secure implementation, you should store this data server side and block on IP.
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
	namespace gburtini\bfd;
	require_once dirname(__FILE__) . "/Storage/SessionStorage.php";
	require_once dirname(__FILE__) . "/Shape/ExponentialShape.php";
	require_once dirname(__FILE__) . "/ThingThrottle.php";
    
    class SessionThrottle extends ThingThrottle {
		protected $name;
		protected $safe = 10; // the number of "harmless" checks
		protected $upper = 20;
		protected $rate = 1.3;	// the timeout grows a rate^n for failures n between safe and upper.
		protected $sleep = true;
		protected $sessionPrefix = "sessiontimeout_";

		protected $minimumSleep = 0;
		protected $maximumSleep = 0;
		public function __construct($name, $safe = 10, $upper = 20, $rate=1.3, $sleep = true) {
			$this->name = $name;
			$this->storage = new SessionStorage($this->sessionPrefix);
			$this->shape = new ExponentialShape($safe, $upper, $rate);
			$this->sleep = $sleep;
		}
	}
?>
