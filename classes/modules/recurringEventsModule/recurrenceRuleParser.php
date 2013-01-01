<?php

/**
 * This class provides a parser for the RRULE property in iCalendar.
 *
 * @author maurobieg
 
 */

class recurrenceRuleParser {
	
        public $outputFormat = "Y-m-d\TH:i:s"; //has to be compatible with http://www.php.net/manual/de/function.date.php
        
	private $rulesArr;
	private $dtstartDateTime;
	private $startDateTime;
	private $endDateTime;
	
	private $tzid;
	
	private $recDate;
	private $eventsCount = 1;
	private $maxCount;
        private $until;
	
	private $startTimesArray;
	
	public function parseRule($rule, $dtstart, $startTime, $endTime) {
		/*
		 $rule is a string formatted according to the value of the iCalendar RRULE property, 
		 	e.g. "FREQ=DAILY;INTERVAL=1"
		 $dtstart is the start time of the event as strings 
		 	in the format of the DTSTART and DTEND iCalendar properties
		 $startTime and $endTime are strings as accepted by PHP strtotime(), see http://www.php.net/manual/en/datetime.formats.php
		 	The two values provide a window, events will only be generated within 
		 	that time window.
		 The result of this function is an array of start-datetimes of the recurring events, not including the 
		 	original occurence. The format can be specified by setting the $this->outputFormat field.
		 */
		 
		 //save input arguments to fields
		 $this->dtstartDateTime = new DateTime($dtstart);
		 $this->startDateTime = new DateTime($startTime);
		 $this->endDateTime = new DateTime($endTime);
		 
		 $this->startTimesArray = array();
		 
                 if ( empty($rule) ) {
                         throw new Exception("RRULE is empty string");
                 }
                 
		 //create $this->rulesArr like array('FREQ' => 'DAILY', 'INTERVAL' => '1')
		 $ruleParts = explode(";", $rule);
                 $this->rulesArr = array();
		 foreach ($ruleParts as $rulePart) {
		 	$explodedPart = explode("=", $rulePart, 2);
		 	$this->rulesArr[ $explodedPart[0] ] = $explodedPart[1];
		 }
		 $this->rulesArr = $this->rulesArr;
		
		//set Count
		if ( isset($this->rulesArr['COUNT']) )
			$this->maxCount = $this->rulesArr['COUNT'];
		else
			$this->maxCount = PHP_INT_MAX;
		
		//set Until
		$windowEndDateTime = $this->endDateTime;
		if (isset($this->rulesArr['UNTIL'])) {
			$until = new DateTime($this->rulesArr['UNTIL']);
			if ($until > $windowEndDateTime) {
				$until = $windowEndDateTime;
			}
		}
		else {
			$until = $windowEndDateTime;
		}
                $this->until = $until;
		
		//set default values
		if ( !isset($this->rulesArr['INTERVAL']) )
			$this->rulesArr['INTERVAL'] = 1;
		
		//set date of original event
		$this->recDate = clone $this->dtstartDateTime;
		
		if ( isset($this->rulesArr['FREQ']) ) {
			$freq = $this->rulesArr['FREQ'];
		} else
			throw new Exception("No FREQ found in RRULE");
		
		//iterate over the FREQ interval
		while ($this->eventsCount < $this->maxCount && $this->recDate <= $until) {
			
			//check for some (the supported) combinations of BY***-rules
			if ( isset($this->rulesArr['BYYEARDAY']) ) {
				//if ( $freq != 'YEARLY' )
				//	throw new Exception("BYYEARDAY must only be specified with FREQ=YEARLY");
				//$this->byYearDay();
				throw new Exception("BYYEARDAY not supported (yet?)");
			}
			elseif ( isset($this->rulesArr['BYMONTHDAY']) ) {
				if ($freq != 'MONTHLY')
					throw new Exception("BYMONTHDAY only supported with FREQ=MONTHLY (yet?)");
				//if ( isset($this->rulesArr['BYMONTH']) ) {
				//	$this->byMonthDay_and_byMonth();
				//}
				//else {
					$this->byMonthDay();
				//}
			} elseif( isset($this->rulesArr['BYMONTH']) ){
				if ( $freq == 'WEEKLY') {
					throw new Exception("BYMONTH must not occure with FREQ=WEEKLY");
				}
				
				if ($freq == 'MONTHLY') {
					$this->byDay_Monthly();
				}
				elseif ( isset($this->rulesArr['BYDAY']) ){
					$this->byMonth_and_byDay();
				}
				else {
					$this->byMonth();
				}
			}
			elseif ( isset($this->rulesArr['BYWEEKNO']) ){
				throw new Exception("BYWEEKNO not supported (yet?)");
				
				/*
				if ( isset($this->rulesArr['BYDAY']) ){
					$this->byWeekNo_and_byDay();
				}
				else {
					$this->byWeekNo();
				}
				*/
			}
			elseif ( isset($this->rulesArr['BYDAY']) ){
				if ($freq == 'WEEKLY')
					$this->byDay_Weekly();
				elseif ($freq == 'MONTHLY')
					$this->byDay_Monthly();
				elseif ($freq == 'YEARLY') {
					if (isset($this->rulesArr['BYMONTH']))
						$this->byMonth_and_byDay();
					else
						throw new Exception("BYDAY and FREQ=YEARLY only supported in combination with BYMONTH (yet?)");
				}
				else
					throw new Exception("BYDAY only supported with FREQ = WEEKLY, MONTHLY or YEARLY (yet?)");
			}
			else {
				$this->noByDefined();
			}
		}
	}
	
	public function setTimezone($tzid) {
		$this->tzid = $tzid;
	}
        
        public function getStartTimesArray(){
                return $this->startTimesArray;
        }
	
	public function serializeStartTimesArray() {
		$output = "";
		foreach($this->startTimesArray as $el) {
			$output .= $el."_";
		}
		return $output;
	}

	
	/*
	 * BY***-parsing functions
	 */
	
	
	private function byMonthDay() {
		//case: BYMONTHDAY
		//only supported FREQ in combination with this is MONTHLY
		
                
		$daysArr = explode(",", $this->rulesArr['BYMONTHDAY']);
		$date = clone $this->recDate;
		
		//fill up usual dates
		foreach ($daysArr as $day) {
			if (checkdate($date->format('m'), $day, $date->format('Y'))) {
				$date->setDate($date->format('Y'), $date->format('m'), $day);
				$this->appendToOutputArray($date);
			}
		}
                
                //increment by FREQ
                $this->countUpByMonthInterval();
	}
	
	private function byMonth_and_byDay() {
		//case: BYMONTH in combination with BYDAY
		//only supported FREQ in combination with this is YEARLY
                //between SU & SA
		
		//@TODO
		throw new Exception("BYMONTH in combination with BYDAY with FREQ=YEARLY not supported yet");
	}
	
	private function byMonth() {
		//case: BYMONTH
		//only supported FREQ in combination with this is YEARLY
                
		
		$monthArr = explode(",", $this->rulesArr['BYMONTH']);
		
		$date = clone $this->recDate;
		$year = $date->format('Y');
		$day = $date->format('d');
		
		foreach( $monthArr as $month) {
			//$month is number from 1 to 12
                        
                        if ( checkdate($month, $day, $year) ) {
                                $datetime = DateTime::createFromFormat("Y-m-d", $year.'-'.$month.'-'.$day);
                                $datetime->setTime($date->format('H'), $date->format('i'), $date->format('s'));
                                $this->appendToOutputArray( $datetime );
                        }
		}
                
                //increment by FREQ
                $this->recDate->modify( "+" . $this->rulesArr['INTERVAL'] . " year");
	}
	
	private function byDay_Weekly() {
		//case: BYDAY and FREQ=WEEKLY
		
                
		if ( isset($this->rulesArr['WKST']) && $this->rulesArr['WKST'] != 'MO')
			throw new Exception("WKST != MO not supported yet");
		
		$daysArr = explode(",", $this->rulesArr['BYDAY']);
		
		$date = clone $this->recDate;
		$weekdayInt = $this->weekdayToInt( $date->format('l') );
		
		//set $date to monday of the week we are in
		$date->modify("-".$weekdayInt." Days");
                
		foreach ($daysArr as $day) {
                        $monday = clone $date;
                        
			$monday->modify("+". $this->weekdayToInt($day) ." Days");
			
			$this->appendToOutputArray($monday);
		}
                
                //increment by FREQ
                $this->recDate->modify( "+" . $this->rulesArr['INTERVAL'] . " week");
	}
	
	private function byDay_Monthly() {
		//case: BYDAY and FREQ=MONTHLY
                
		
		$daysArr = explode(",", $this->rulesArr['BYDAY']);
		
		$date = clone $this->recDate;
		
		//set to first day of the month
		$date->setDate($date->format('Y'), $date->format('m'), 1);
		
		if ( isset($this->rulesArr['BYSETPOS']) ) {
			//if BYSETPOS is set we ignore numeric values like "2SU"
			$bySetPos = $this->rulesArr['BYSETPOS'];
			
			if ( count(explode(",", $bySetPos)) > 1)
				throw new Exception("only one value in BYSETPOS supported (yet?)");
			
			if ($bySetPos < 0) {
				$sign = "-";
				$bySetPos = abs($bySetPos);
				
                                $lastDayInMonth = cal_days_in_month(CAL_GREGORIAN, $date->format('m'), $date->format('Y'));
                                $date->setDate($date->format('Y'), $date->format('m'), $lastDayInMonth);
			} else {
				$sign = "+";
			}
			
			$count = 0; //counts the number of hits we had on the set weekdays in $daysArr
			$month = $date->format('n'); //if we overshoot our month we stop the loop
			
			if (isset($this->rulesArr['BYMONTH'])) {
				$byMonthRulesString = $this->rulesArr['BYMONTH'];
				$monthsArr = explode(",", $byMonthRulesString);
				$byMonthSet = true;
			} else {
				$monthsArr = array();
				$byMonthSet = false;;
			}
			
			
			$isInArr = in_array($month, $monthsArr);
			if (!$byMonthSet || $isInArr) {
			
				while ( ($count < $bySetPos) && ($date->format('n') == $month) ) {

					$weekday = strtoupper( substr($date->format('l'), 0, 2) ); //e.g. "MO"
					if ( in_array($weekday, $daysArr) ) {
						$count++;
						if ($count == $bySetPos) {
							//if we hit something we append it to array
							$this->appendToOutputArray($date);
							break;
						}
					}
					$date->modify($sign."1 day");
				}
			}
		}
		else {
			//if BYSETPOS is not set we look out for numeric values like "1MO"
			
			foreach ($daysArr as $day) {
				if ( strlen($day) > 2 ) {
					//weekday is preceeded by integer like "3TU" or "-1SU"
					$number = substr($day, 0, strlen($day)-2);
					$day = substr($day, -2);
					
					//e.g. new DateTime("sixth Tuesday of March 2012");
					$newDate = new DateTime(
						$this->intToOrdinalString($number)." "
						.$this->shortToLongWeekdayString($day)
						.' of '.$date->format('M').' '.$date->format('Y')
						);
					if ($newDate->format('M') == $date->format('M')) {
						//if it didn't overshoot into next month -> set time and add it to array
						$newDate->setTime($date->format('H'), $date->format('i'), $date->format('s'));
						$this->appendToOutputArray($newDate);
					}
				} else {
					//e.g. "TU" -> every Tuesday
					throw new Exception("FREQ=MONTHLY in combination with BYDAY without preceeding numeric value not supported (yet?)");
				}
			}
		}
                
                //increment by FREQ
                $this->recDate->setDate($this->recDate->format('Y'), $this->recDate->format('m'), 1);
                $this->recDate->modify("+" . $this->rulesArr['INTERVAL'] . " month");
	}

	private function noByDefined() {
		//case: no BY*** rule defined
		
                
                $this->appendToOutputArray( $this->recDate );
                
                //increment by FREQ
                $freq = $this->rulesArr['FREQ'];
                if ($freq == "MONTHLY") {
                        $this->countUpByMonthInterval();
                } else {
                        //count up recDate in interval set by FREQ and INTERVAL
			switch ($freq) {
				case "DAILY":
					$this->recDate->modify( "+".$this->rulesArr['INTERVAL']." Day" );
					break;
				default:						//e.g. YEARLY -> YEAR
					$this->recDate->modify( "+".$this->rulesArr['INTERVAL']." ".substr($freq, 0, -2) );
			}
                }
	}
	
	
	
	
	
	/*
	 * Helper functions
	 */
        
        private function countUpByMonthInterval(){
                //count up recDate in interval set by MONTH
                
                $month = $this->recDate->format('m');
                $year = $this->recDate->format('Y');
                $originalDay = $this->recDate->format('d');
                
                $date = clone $this->recDate;
                $date->setDate($year, $month, 1);
                
                do {
                        $date->modify("+" . $this->rulesArr['INTERVAL'] . " months");
                }
                while( ! checkdate($date->format('m'), $originalDay, $year) );
                
                $this->recDate->setDate($date->format('Y'), $date->format('m'), $originalDay);
        }
	
	private function appendToOutputArray($datetime) {
		//takes a php DateTime object

		//if we were to append the object we had to use 'clone' as it would else be assigned by reference in php5
		//but ->format gives a string so that's okay
		if ($datetime > $this->startDateTime && $datetime > $this->dtstartDateTime
                               && $this->eventsCount < $this->maxCount && $datetime <= $this->until) {
                        
			$this->startTimesArray[] = $datetime->format($this->outputFormat);
			$this->eventsCount++;
		}
	}
	
	
	private function weekdayToInt($weekday) {
		//takes string as 'SU' or 'Sunday' and returns 6 (range: 0-6)
		switch ( strtoupper($weekday) ) {
			case 'MO':
				return 0;
				break;
			case 'TU':
				return 1;
				break;
			case 'WE':
				return 2;
				break;
			case 'TH':
				return 3;
				break;
			case 'FR':
				return 4;
				break;
			case 'SA':
				return 5;
				break;
			case 'SU':
				return 6;
				break;
			
			case 'MONDAY':
				return 0;
				break;
			case 'TUESDAY':
				return 1;
				break;
			case 'WEDNESDAY':
				return 2;
				break;
			case 'THURSDAY':
				return 3;
				break;
			case 'FRIDAY':
				return 4;
				break;
			case 'SATURDAY':
				return 5;
				break;
			case 'SUNDAY':
				return 6;
				break;
			default:
				throw new Exception("Could not parse weekday: ".$weekday);
				return -1;
		}
	}
	
	private function shortToLongWeekdayString($weekday) {
		//takes e.g. 'MO' and returns 'Monday'
		
		switch ( strtoupper($weekday) ) {
			case 'MO':
				return "Monday";
				break;
			case 'TU':
				return "Tuesday";
				break;
			case 'WE':
				return "Wednesday";
				break;
			case 'TH':
				return "Thursday";
				break;
			case 'FR':
				return "Friday";
				break;
			case 'SA':
				return "Saturday";
				break;
			case 'SU':
				return "Sunday";
				break;
			default:
				throw new Exception("Could not parse weekday: ".$weekday);
		}
	}
	
	private function intToOrdinalString($int) {
		//$int may be -1 or between 1 and 5
		
		if ( !is_numeric($int) || $int < -1 || $int == 0 || $int > 5)
			throw new Exception($int." is not in range of intToOrdinalString()");
		
		if ($int == -1)
			$string = "last";
		else {
			$days[1] = 'first';
			$days[2] = 'second';
			$days[3] = 'third';
			$days[4] = 'fourth';
			$days[5] = 'fifth';
			
			$string = $days[$int];
		}
		return $string;
	}
}