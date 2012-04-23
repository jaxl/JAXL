<?php

class Fsm {
	
	private $state;
	private $data;
	
	public function __construct($initial_state, $state_data=NULL) {
		$this->state = $initial_state;
		$this->data = $state_data;
	}
	
	public function __destruct() {
		
	}
	
	public function move($arg) {
		$ret = call_user_func($this->state, $this->data, $arg);
		$this->state = $ret[0];
		$this->data = $ret[1];
	}
	
}

?>