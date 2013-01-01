<?php

/**
 * This is the parent class of all importers. Importers are responsible for getting the event data, 
 * processing the data (by applying modules) and saving it in the database.
 *
 * @author maurobieg
 */
abstract class Importer {
	
	public $subscription; //an instance of class Subscription
	
	public abstract function downloadAndParse($calUrl, $startTimestamp, $endTimestamp, 
			$subId=null, $eventXProperties=null);
		/*
		 * This method should be implemented in the child classes
		 * 
		 * Downloads the iCalendar data from $calUrl, overwrites $this->subscription with a new 
		 * object and stores the data in its cal* fields.
		 * Only events whose DTSTART is between start- and endTimestamp are imported.
		 * Also, if certain modules need non-standard X-PROPERTIES of events they need to be 
		 * specified as a string-array here to be imported.
		 */
	
	public function applyModules($moduleNames){
		/*
		 * moduleNames is an array of strings. 
		 * applies the modules to the subscription field in the order specified
		 */
		
		foreach ($moduleNames as $moduleName) {
			
			require_once 'classes/modules/' . $moduleName . '.php';
			
			//execute the function with the name stored in $module
			$module = new $moduleName($this->subscription);
			$module->apply();
		}
	}
	
	public function saveToDb(){
		/*
		 * saves the subscription's fields to the database
		 */
		 
		global $database;
		
		$database->insertOrUpdateEvents($this->subscription);
	}
	
}

?>
