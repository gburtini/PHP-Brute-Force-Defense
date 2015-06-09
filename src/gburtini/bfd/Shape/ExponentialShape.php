<?php
  namespace gburtini\bfd;
  class ExponentialShape implements Shape {
      protected $safe = 10; // the number of "harmless" checks
      protected $upper = 20;
      protected $rate = 1.3;	// the timeout grows a rate^n for failures n between safe and upper.
      protected $minimumSleep = 0;
      protected $maximumSleep = 60;
      public function __construct($safe=10, $upper=20, $rate=1.3, $min = 0, $max=null) {
        $this->safe = intval($safe);
        $this->upper = intval($upper);
        $this->rate = floatval($rate) ;

        $this->minimumSleep = $min;
        if($max === null)
          $this->maximumSleep = $this->timeout($this->safe + $this->upper);
        else
          $this->maximumSleep = $max;
      }

      public function timeout($i) {
        if($i > $this->safe+$this->upper)
          $i = $this->safe+$this->upper;

        $timeout = min($this->maximumSleep,
            max($this->minimumSleep,
              pow($this->rate, max(0, ($i - $this->safe)))
            )
        );
        return $timeout;
      }
  }
?>
