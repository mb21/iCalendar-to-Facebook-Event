<?php

/**
 * This is the parent class of all modules
 * 
 * @author maurobieg
 */
abstract class Module {
	
	protected $sub;
	
	function __construct(&$sub) {
		$this->sub = $sub;
	}
	
	public abstract function apply();
	//this method is implemented by the child classes.
	
}

?>
