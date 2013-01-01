<?php

/**
 * This Module fills in all recurring events within the set window of time.
 *
 * @author maurobieg
 */

require_once 'recurringEventsModule/recurrenceRuleParser.php';

class recurringEventsModule extends Module{
	
	public function apply() {
		global $config;
                global $logger;
		
		$sub = $this->sub;
		$eventArray = &$sub->eventArray;
		
		$rrule = new recurrenceRuleParser();
		
		//fill in recurring events
                $tmpEventArray = array();
		foreach ($eventArray as &$e) {
			if (isset($e['calRRULE'])){
                                
                                try {
                                        $ruleString = $e['calRRULE'];
                                        $rrule->parseRule($ruleString, $e['calDTSTART'], $config['defaultReccurWindowOpen'], $config['defaultWindowClose']);

                                        $e['isPartOfRecurrenceSet'] = true;
                                        $e['recurrenceSetUID'] = $e['calUID'];

                                        $startDateTime = new DateTime($e['calDTSTART']);
                                        $endDateTime = new DateTime($e['calDTEND']);
                                        $interval = $startDateTime->diff($endDateTime);

                                        foreach ($rrule->getStartTimesArray() as $startTime){

                                                $newEvent = $e;
                                                
                                                //starttime
                                                $newStartDateTime = new DateTime($startTime);
                                                $newEvent['calDTSTART'] = $newStartDateTime->format("Y-m-d\TH:i:s");
                                                $newEvent['startDate'] = $newStartDateTime->format("Ymd");
                                                
                                                //endtime
                                                $newStartDateTime->add($interval);
                                                $newEvent['calDTEND'] = $newStartDateTime->format("Y-m-d\TH:i:s");
                                                
                                                $newEvent['recurrenceSetUID'] = $e['calUID'];
                                                unset( $newEvent['calUID'] );

                                                //append new recurring event to eventArray
                                                $tmpEventArray[] = $newEvent;     
                                        }
                                } catch (Exception $ex) {
                                        $logger->warning("Couldn't instantiate recurring event.", $ex);
                                }
                        }
		}
                $eventArray = array_merge($eventArray, $tmpEventArray);
		
	}
        
        /*
        public function rulesArrayToString($arr) {
                //parse the strange RRULE array that comes from the iCalcreator lib into the original string (like in the iCal standard)
                
                $out = "";
                
                foreach($arr as $key => $value) {
                        
                        if ($key == "UNTIL") {
                                $out .= "UNTIL=";
                                $out .= $value['year'];
                                $out .= $value['month'];
                                $out .= $value['day'];
                                if ( isset($value['hour']) ) {
                                        $out .= "T" . $value['hour'];
                                        if ( isset($value['min']) )
                                                $out .= $value['min'];
                                        else
                                                $out .= "00";
                                        if ( isset($value['sec']) )
                                                $out .= $value['sec'];
                                        else
                                                $out .= "00";
                                }
                        } else {
                                if (is_array($value)) {
                                        $out .= $key . "=";
                                        foreach ($value as $val) {
                                                if (is_array($val)) {
                                                       foreach ($val as $v) {
                                                               if ( $key == "BYDAY") {
                                                                        if (!is_numeric($v))
                                                                                $out .= $v . ",";
                                                                        else
                                                                                $out .= $v;
                                                               } else {
                                                                       $out .= $v . ",";
                                                               }
                                                       } 
                                                } else {
                                                        $out .= $val . ",";
                                                }
                                        }
                                        $out = substr($out, 0, strlen($out)-1);
                                } else {
                                        $out .= $key . "=" . $value;
                                }
                        }
                        
                        $out .= ";";
                }
                
                $out = substr($out, 0, strlen($out)-1);
                
                return $out;
        }
         *
         */
}

?>