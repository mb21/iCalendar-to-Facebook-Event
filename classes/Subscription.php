<?php


/**
 * This class provides a vessel to save the properties of a subscription plus its events
 *
 * @author maurobieg
 */
class Subscription {
	private $subId;
	private $subName;
	private $finalTimezone;

	private $calTZID;
	private $calXWRTIMEZONE;
	
	private $fbUserId;
	
	public $eventArray;
	
	/*
	 * eventArray consists of objects like $event
	 * 
	$event = Array(
		calDTSTART => "",
		calDTEND => "",
		calDTStartTZID => "",
		calDTEndTZID => "",
		calUID => "",
		calSUMMARY => "",
		calDESCRIPTION => "",
	 	calLOCATION => "",
		calCLASS => "",
		calRRULE => "",
		
		lastModifiedTimestamp => "",
	 	isPartOfRecurrenceSet => "",
		recurrenceSetUID => "",
                startDate => "", //needed for identification of recurrence instance, ->format("Ymd") i.e. YYYYMMDD
         
	  	fbName => "",
		fbDescription => "",
		fbStartTime => "",
		fbEndTime => "",
		fbLocation => "",
		fbPrivacy => "",
	 
		imageFileUrl => ""
	);
	 */
	
	public function getSubId() {
	    return $this->subId;
	}

	public function setSubId($subId) {
	    $this->subId = $subId;
	}
	
	public function getSubName() {
		return $this->subName;
	}

	public function setSubName($subName) {
		$this->subName = $subName;
	}

	public function getFinalTimezone() {
		return $this->finalTimezone;
	}

	public function setFinalTimezone($finalTimezone) {
		$this->finalTimezone = $finalTimezone;
	}

	public function getCalTZID() {
		return $this->calTZID;
	}

	public function setCalTZID($calTZID) {
		$this->calTZID = $calTZID;
	}

	public function getCalXWRTIMEZONE() {
		return $this->calXWRTIMEZONE;
	}

	public function setCalXWRTIMEZONE($calXWRTIMEZONE) {
		$this->calXWRTIMEZONE = $calXWRTIMEZONE;
	}

	public function getFbUserId() {
		return $this->fbUserId;
	}

	public function setFbUserId($fbUserId) {
		$this->fbUserId = $fbUserId;
	}

	public function getEventArray() {
		return $this->eventArray;
	}

	public function setEventArray($eventArray) {
		$this->eventArray = $eventArray;
	}

}

?>
