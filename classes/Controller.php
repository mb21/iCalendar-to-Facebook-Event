<?php


/**
 * With a Controller object you can invoke common functionality to bring certain subscription up to date on facebook.
 *
 * @author maurobieg
 */
class Controller {
	
	private $importer;
	private $publisher;
	
	public function checkUrl($calUrl) {
		$this->setImporter($calUrl);
	}
	
	public function updateSub($subId){
		//fetches new events and publishes them to facebook
		//regardless whether the subscription is active or not
		
		global $logger;
		
		$logger->setCurrentSubId($subId);
		
		$this->importSub($subId);
		$this->publishSub($subId);
		
		$logger->unsetCurrentSubId();
	}
	
	public function importSub($subId){
		global $database;

                //download and parse iCal-file
                $calUrl = $database->getCalUrl($subId);
                $this->setImporter($calUrl, $subId);
		
                $moduleNames = array();
                $STH = $database->getModules($subId);
                while ( $row = $STH->fetch() ) {
                        $moduleNames[] = $row->module;
                }
                $moduleNames[] = 'standardModule';
                
                //TODO: select right modules
		$this->importer->applyModules($moduleNames);
		$this->importer->saveToDb();
		
		$database->setSuccessfulImport($subId);
	}
	
	public function publishSub($subId) {
		global $database;
                global $logger;
		global $propagateExceptions;
                
		$this->publisher = new Publisher();
                
                try{
                        $this->publisher->publishSubscription($subId);
                        $database->setSuccessfulPublish($subId);
                } catch(Exception $e){
                        if ($propagateExceptions)
                                throw $e;
                        else
                                $logger->warning("Event could not be created on Facebook.", $e);
                }
	}
	
	
	private function setImporter($calUrl, $subId = NULL) {
		global $config;
		global $database;
		
		$imageProperty = $database->getImageProperty($subId);
		if ($imageProperty) {
			$eventXProperties = array($imageProperty);
		} else {
			$eventXProperties = null;
		}
		
		$this->importer = new iCalcreatorImporter(); //or qCalImporter()
		
		$this->importer->downloadAndParse(
				$calUrl,
				strtotime( $config['defaultReccurWindowOpen'] ), 
				strtotime( $config['defaultWindowClose'] ),
				$subId,
				$eventXProperties
			);
	}
}

?>
