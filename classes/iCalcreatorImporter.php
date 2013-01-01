<?php

/**
 * Description of iCalImporter
 *
 * @author maurobieg
 */
require_once 'lib/iCalcreator/iCalcreator.class.php';

class iCalcreatorImporter extends Importer {

	public function downloadAndParse($calUrl, $startTimestamp, $endTimestamp, $subId=null, $eventXProperties=null) {

		global $config;
		global $database;

		$rawdata = @file_get_contents($calUrl, NULL, NULL, NULL, $config['maxiCalFileLength']);
		
		if ($rawdata === false)
			throw new Exception("Could not download iCalendar-file from ".$calUrl);
		
		if (strlen($rawdata) >= $config['maxiCalFileLength'])
			throw new Exception("File was too large, i.e. larger than " . floor($config['maxiCalFileLength'] / 1000) . " KB.");

		$vcalendar = new vcalendar();
		$vcalendar->setConfig("newlinechar", "\r\n");
		if (FALSE === $vcalendar->parse($rawdata)) {
			$vcalendar->setConfig("newlinechar", "\n");
			if (FALSE === $vcalendar->parse($rawdata)) {
			throw new Exception("Error when parsing iCalendar file.");
			}
		}

		//create subscription
		$sub = new Subscription();

		if (isset($subId))
			$sub->setSubId($subId);

		/*
		 * put calendar properties
		 */

		$vtimezone = $vcalendar->getComponent('vtimezone');
		if ($vtimezone) {
			$sub->setCalTZID($vtimezone->getProperty("TZID"));
		}

		$vtimezone = $vcalendar->getProperty("X-WR-TIMEZONE");
		if ($vtimezone)
			$sub->setCalXWRTIMEZONE($vtimezone[1]);


		/*
		 * put events
		 */

		$eventArray = array();
		while ($e = $vcalendar->getComponent('vevent')) {
                        
                        $event = array();
                        
			$dtstartValueParam = $e->getProperty('DTSTART', FALSE, TRUE);
			$dtstart = $this->timeArrayToIso($dtstartValueParam['value']);
                        if ( !isset($dtstartValueParam['value']['hour'] ) ){
                              $event['calDTStartTZID'] = "UTC";
                              $event['calDTEndTZID'] = "UTC";
                        }

			$dtendValueParam = $e->getProperty('DTEND', FALSE, TRUE);
                        if ($dtendValueParam) {
                                $dtend = $this->timeArrayToIso($dtendValueParam['value']);
                        } else {
                                //no DTEND in cal
                                $dtstartDateTime = new DateTime($dtstart);
                                $dtend = $dtstartDateTime->modify("+1 hour")->format("Y-m-d\TH:i:s");
                        }

			$dtstartStamp = strtotime($dtstart);

			if ($dtstartStamp < $startTimestamp || $dtstartStamp > $endTimestamp) {
				//if starttime of event isn't in window set, skip this event
				continue;
			} else {
				$event['calDTSTART'] = $dtstart;
				$event['calDTEND'] = $dtend;
			}

			$event['calUID'] = $e->getProperty('UID');

			$lastModified = $this->timeArrayToIso($e->getProperty('LAST-MODIFIED'));
			$event['lastModifiedTimestamp'] = strtotime($lastModified);

			if (isset($subId) && $database->notModified($event['lastModifiedTimestamp'], $event['calUID'], $subId)) {
				//if event has not been modified skip it
				continue;
			}

			$params = $dtstartValueParam['params'];
			if ( isset($params['TZID']) ) {
				$event['calDTStartTZID'] = $params['TZID'];
			}

			$params = $dtendValueParam['params'];
			if ( isset($params['TZID']) ) {
				$event['calDTEndTZID'] = $params['TZID'];
			}


			$prop = $e->getProperty('SUMMARY');
			if ($prop)
				$event['calSUMMARY'] = $prop;
			else
				$event['calSUMMARY'] = "Untitled";

			$prop = $e->getProperty('DESCRIPTION');
			if ($prop)
				$event['calDESCRIPTION'] = str_replace("\\n", "\r\n", $prop);
			else
				$event['calDESCRIPTION'] = "";

			$prop = $e->getProperty('LOCATION');
			if ($prop)
				$event['calLOCATION'] = $prop;
			else
				$event['calLOCATION'] = "";

			$prop = $e->getProperty('CLASS');
			if ($prop)
				$event['calCLASS'] = $prop;
			
                        /*
                         *  this unfortunately returns a confused array
			$prop = $e->getProperty('RRULE');
			if ($prop)
				$event['calRRULE'] = $prop;
                         * so we use our hacked-in stuff
                         */
                        $prop = $e->unparsedRrule;
                        if ( isset($prop) )
				$event['calRRULE'] = $prop;

			//read in optional X-Properties
			if (isset($eventXProperties)) {
				foreach ($eventXProperties as $xprop) {
					$prop = $e->getProperty($xprop);
					if ($prop) {
						if ($xprop == "ATTACH") {
							if (substr_count($prop, "%3A%2F%2F") > 0) {
								//decode percent-encoding
								$event[$xprop] = rawurldecode($prop);
							} else {
								$event[$xprop] = $prop;
							}
						} else {
							//is X-property
							$event[$xprop] = $prop[1];
						}
					}
				}
			}

			//push new event on array of events
			array_push($eventArray, $event);
		}
		$sub->setEventArray($eventArray);

		//save subscription object in field
		$this->subscription = $sub;
	}

	private function timeArrayToIso($timeArr) {
		//takes a iCalcreator date/time array and returns it as a ISO 8601 string
                if (isset($timeArr['hour'])){
                        $time = $timeArr['hour'] . ':' . $timeArr['min'] . ':' . $timeArr['sec'];
                } else {
                        $time = "00:00:00";
                }
		return $timeArr['year'] . '-' . $timeArr['month'] . '-' . $timeArr['day']
			. 'T' . $time;
		;
	}

}

?>
