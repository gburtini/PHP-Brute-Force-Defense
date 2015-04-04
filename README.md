PHP Brute Force Defense Tools
============================

_A lightweight framework for mitigating brute force attacks in an online architecture._

Right now, there is only one tool, a session-based rate limiter. It is very general and powerful, but there's a lot of work to do here.

Installation
------------

Installation is available via composer.

    composer require gburtini/bfd
    
    
Usage
-----

### SessionThrottle
* ``__construct(string $name, int $safe, int $upper, float $rate, boolean sleep)`` 
* ``fail()``, ``succeed()`` - used to report the outcome of the action being protected, a fail increments the counter and a succeed resets it. (aliased as ``increment()`` and ``reset()`` if success/fail don't make sense for your use case)
* ``test()`` - returns true or false to indicate whether you should allow the requested call to take place.

Constructing a SessionThrottle means setting the parameters.
* `$name` - a token for the element, function or method that this SessionThrottle instance is protecting
* `$safe` - the number of failures a user gets "free" before they start to get rate limited, you can safely set this quite high for most login related applications.
* `$upper` - the highest power of $rate to be used. Out of the box, this is 20 (with rate 1.3) meaning the highest timeout is 1.3^20 = 190 seconds.
* `$rate` - the base of the exponent used to calculate the time limit. By default, 1.3. Too large, and the throttler is too aggressive. Too small, and it won't be aggressive enough.
* `$sleep` - true/false for whether ->test() should ATTEMPT to always return true (by simply sleeping until the timelimit has passed). Even if sleep is true, the sleep may get interrupted and thus return false. You must check it the return value of `test`.

Example use:
    $login_limit = new SessionThrottle("login_bob");        // can have "login" throttles or "login_%username%" throttles... or even just an expensive process can be throttled by this.
    if($login_limit->test()) {
    	if(!checkLogin($user, $pass)) {
    		$login_limit->fail();
    	} else {
    		$login_limit->succeed(); // clear the timelimit
    	}
    } else {
    	showThrottleError();
    }

Future Work
-----------

The intent is to collect a whole set of tools for mitigating brute force attacks here. The `SessionThrottle` tool is just a start:
* Efficient IP-based blocking and throttling.
* Data-level blocking (non-session based limits on access to particular data)
* Global shutdown tools for mitigiating large-scale brute-force attacks.
* Device-cookie tools

License
-------
*Copyright (C) 2015 Giuseppe Burtini*

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
