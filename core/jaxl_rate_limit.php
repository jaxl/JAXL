<?php

require_once 'core/jaxl_util.php';

class JAXLRateLimit {
	
	private $max_rate;
	private $last_time;
	private $last_rate;
	
	public function __construct($max_rate) {
		$this->last_time = $this->ts();
		$this->max_rate = $max_rate;
		$this->last_rate = 0;
	}
	
	public function __destruct() {
		
	}
	
	// size in bytes
	public function update($size) {
		$now = $this->ts();
		$elapsed = (float)$now - (float)$this->last_time;
		$this->last_time = $now;
		
		$max_rate = $this->max_rate / $elapsed;
		$curr_rate = $size / $elapsed;
	}
	
	public function ts() {
		return microtime(true);
	}
}

?>