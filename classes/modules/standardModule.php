<?php

/**
 * This standard Module is usually applied first to the events. It fills in the basic parameters for fb.
 *
 * @author maurobieg
 */
class standardModule extends Module{
	
	public function apply() {
		global $config;
		global $database;
		
		$sub = $this->sub;
		
		$sub->setFinalTimezone( $this->getTimezone() );
                
                //delete events that are too old
                foreach ($sub->eventArray as $key => $e) {
                        if ( strtotime($e['calDTSTART']) < strtotime($config['defaultWindowOpen']) ) {
                                unset($sub->eventArray[$key]);
                        }
                }
                
		foreach ($sub->eventArray as &$e) {
                        
			$e['fbName'] = mb_substr($e['calSUMMARY'], 0, $config['maxFbTitleLength']);
			
			$e['fbDescription'] = $e['calDESCRIPTION'];
			
			$e['fbLocation'] = $e['calLOCATION'];
			
			if( isset($e['calDTStartTZID']) )
				$e['fbStartTime'] = $this->toFbTime( $e['calDTSTART'], $e['calDTStartTZID'] );
			else
				$e['fbStartTime'] = $this->toFbTime( $e['calDTSTART']);
			
			if( isset($e['calDTEndTZID']) )
				$e['fbEndTime'] = $this->toFbTime( $e['calDTEND'], $e['calDTEndTZID'] );
			else
				$e['fbEndTime'] = $this->toFbTime( $e['calDTEND'] );
			
                        if ($e['fbStartTime'] == $e['fbEndTime']) {
                                //event must have a duration
                                $e['fbEndTime']++;
                        }
                        
                        
			if ( isset($e['calCLASS']) &&  $e['calCLASS'] == 'PRIVATE')
				$e['fbPrivacy'] = "CLOSED";
			elseif ( isset($e['calCLASS']) &&  $e['calCLASS'] == 'CONFIDENTIAL')
				$e['fbPrivacy'] = "SECRET";
			else
				$e['fbPrivacy'] = "OPEN";
			
			$imageProperty = $database->getImageProperty( $sub->getSubId() );
			if ($imageProperty) {
				$e['imageFileUrl'] = $e[ $imageProperty ];
			}
		}
	}
	
	
	private function getTimezone() {
		// sets finalTimezone to either calTZID or calXWRTIMEZONE
		
		if ( !is_null($this->sub->getCalTZID()) ) {
			$tz = $this->sub->getCalTZID();
		} elseif ( !is_null($this->sub->getCalXWRTIMEZONE()) ) {
			$tz = $this->sub->getCalXWRTIMEZONE();
		}
		
		if( !isset($tz) )
			$tz = 0;
		
		return $tz;
	}
	
	
	private function toFbTime($time, $eventTZ = null) {
		/*
		 * because facebook is stupid the time of the event displayed is the same for each user, 
		 * regardless of his time zone.
		 */
		
		date_default_timezone_set('UTC');
		
		$timestamp = strtotime($time);
		
		if ( !isset($eventTZ) && $this->sub->getFinalTimezone() ) {
			//assume event time is in UTC and that the user
			// wants his event time displayed in the calendar timezone (presumably
			// his local timezone) and not in UTC. Thus we calculate the offset.
			$calTz = new DateTimeZone($this->sub->getFinalTimezone());
			$datetime = new DateTime("@$timestamp");
			$tzOffset = timezone_offset_get($calTz, $datetime);
			$timestamp += $tzOffset;
		}
		
		//adjust for newest facebook bug.. 
		//adds 7 or 8 hours (depending on whether it's daylight saving time over in sunny California) to the timestamp
		$californiaTz = new DateTimeZone('America/Los_Angeles');
		$datetime = new DateTime("@$timestamp");
		$tzOffset = timezone_offset_get($californiaTz, $datetime);
		$timestamp -= $tzOffset;
		
		//return date("c", $timestamp); //output in ISO 8601, e.g. 2004-02-12T15:19:21+00:00
		return $timestamp;
	}
}

?>
