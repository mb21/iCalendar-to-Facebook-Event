<?php

/**
 * Description of qCalImporter
 *
 * @author maurobieg
 */

require_once 'lib/qCal/lib/autoload.php';
set_include_path(realpath("lib/qCal/lib") . PATH_SEPARATOR . get_include_path());

class qCalImporter extends Importer{

	public function downloadAndParse($calUrl, $startTimestamp, $endTimestamp, $subId=null, $eventXProperties=null) {
		
		global $config;
		global $database;
		
		// parse raw data
		$parser = new qCal_Parser();
		$rawdata = file_get_contents($calUrl, NULL, NULL, NULL, $config['maxiCalFileLength']);
		
		if (strlen($rawdata) >= $config['maxiCalFileLength'])
			throw new Exception("File was too large, i.e. larger than ". floor($config['maxiCalFileLength']/1000) ." KB." );
		
		if ($rawdata) {
			$ical = $parser->parse($rawdata);
			if (!$ical)
				throw new Exception('Could not parse iCalendar file.');
		 } else {
			throw new Exception("Could not download file from ".$calUrl);
		}
		
		//create subscription
		$sub = new Subscription();
		
		if ( isset($subId) )
			$sub->setSubId ($subId);
		
		/*
		 * put calendar properties
		 */
		
		$prop = $ical->getProperty('X-WR-TIMEZONE');
		if( isset($prop[0]) )
			$sub->setCalXWRTIMEZONE($prop[0]->getValue());
		
		$prop = $ical->getProperty('TZID');
		if( isset($prop[0]) )
			$sub->setCalTZID($prop[0]->getValue());
		
		
		/*
		 * put events
		 */
		
		$children = $ical->getChildren();
		$events = $children['VEVENT'];
		
		$eventArray = array();
		foreach ($events as $e){
			$prop = $e->getProperty('DTSTART');
			$dtstart = $prop[0]->getValue();
			
			$prop = $e->getProperty('DTEND');
			$dtend = $prop[0]->getValue();
			
			$dtstartStamp = strtotime($dtstart);
			
			if ( $dtstartStamp < $startTimestamp || $dtstartStamp > $endTimestamp ) {
				//if starttime of event isn't in window set, skip this event
				continue;
			} else {
				$event = array();
				$event['calDTSTART'] = $dtstart;
				$event['calDTEND'] = $dtend;
			}
			
			$prop = $e->getProperty('UID');
			$event['calUID'] = $prop[0]->getValue();
			
			$prop = $e->getProperty('LAST-MODIFIED');
			$lastModified = $prop[0]->getValue();
			$event['lastModifiedTimestamp'] = strtotime($lastModified);

			if ( isset($subId) && $database->notModified($event['lastModifiedTimestamp'], $event['calUID'], $subId) ) {
				//if event has not been modified skip it
				continue;
			}
			
			//$prop = $e->getProperty('?');
			//$event['calDTStartTZID'] = $prop[0]->getValue();
			
			//$prop = $e->getProperty('?');
			//$event['calDTEndTZID'] = $prop[0]->getValue();
			
			$prop = $e->getProperty('SUMMARY');
			if ($prop[0])
				$event['calSUMMARY'] = $prop[0]->getValue();
			else
				$event['calSUMMARY'] = "Untitled";
				
			$prop = $e->getProperty('DESCRIPTION');
			if ($prop[0])
				$event['calDESCRIPTION'] = $prop[0]->getValue();
			else
				$event['calDESCRIPTION'] = "";
				
			$prop = $e->getProperty('LOCATION');
			if ($prop[0])
				$event['calLOCATION'] = $prop[0]->getValue();
			else
				$event['calLOCATION'] = "";
				
			$prop = $e->getProperty('CLASS');
			if ($prop[0])
				$event['calCLASS'] = $prop[0]->getValue();
			
			//read in optional X-Properties
			if ( isset($eventXProperties) ){
				foreach ($eventXProperties as $xprop) {
					$prop = $e->getProperty($xprop);
					if ($prop[0])
						$event[$xprop] = $prop[0]->getValue();
				}
			}
			
			//push new event on array of events
			array_push($eventArray, $event);
		}
		$sub->setEventArray($eventArray);
		
		//save subscription object in field
		$this->subscription = $sub;
	}

}

?>
