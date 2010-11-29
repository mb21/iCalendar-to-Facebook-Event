<?php
/*
    This file is part of iCalendar-to-Facebook-Event.

    iCalendar-to-Facebook-Event is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    iCalendar-to-Facebook-Event is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with iCalendar-to-Facebook-Event.  If not, see <http://www.gnu.org/licenses/>.
*/

class Calendar {
	///////////////////////////////
	// PROPERTIES
	///////////////////////////////

	public $calendar_timezone;
	public $newline_char;
	public $iCalendar;	//object of class vcalendar
	public $sub_data;
//$sub_data is of form:
//		array(
//		"url" => $url,
//		"user_id" => $user_id,
//		"category" => $category,
//		"subcategory" => $subcategory,
//		"page_id" => $page_id,
//		"picture" => $picture,
//		"wall" => $wall,
//		"image_field" => $image_field;
//		);

	private $session_key;

	///////////////////////////////
	//PUBLIC METHODS
	///////////////////////////////

	function __construct($sub_id, $sub_data = FALSE) {
		//the optional parameter $sub_data is an array of the form specified above

		if ($sub_data === FALSE) {
			//no sub_data supplied, fetch it from db
			$result = mysql_query("SELECT * FROM subscriptions where sub_id='$sub_id'");
			if (mysql_num_rows($result) == 0)
				throw new Exception("No subscription with sub_id=".$sub_id." found.");
			$sub_data = mysql_fetch_assoc($result);
		}
		else {
			//check supplied sub_data
			$sub_data = $this->check_sub_data($sub_data);
		}

		$this->sub_data = $sub_data;
	}

	public function update($force = FALSE) {
		//updates all events in this calendar and returns number of new events created
		global $config;

		date_default_timezone_set('UTC');

		$this->ensure_parse();
		$user_id = $this->sub_data['user_id'];
		$url = $this->sub_data['url'];
		$sub_id = $this->sub_data['sub_id'];

		//go figure timezone of calendar
		$this->ensure_timezone();

		//loop through the parsed icalendar and add all new events to db as well as create new fb-events
		$numb_events = 0;
		while( $event = $this->iCalendar->getComponent( 'vevent' )) {
			$UID = $event->getProperty('UID');
			$UID = trim($UID);
			$lm = $event->getProperty('LAST-MODIFIED');
			if ($lm) {
				$lastupdated = $event->_date2timestamp($lm);
			}
			else {
				$lastupdated = NULL;
			}

			$sql_error = FALSE;
			$result = mysql_query("select * from user$user_id where sub_id ='$sub_id' and binary UID = '$UID'") or $sql_error = TRUE;
			if ($result && !($data = mysql_fetch_array($result)) && !$sql_error && !$force) {
				//event doesn't exist yet, add it

				if($numb_events > $config['number_of_events_threshold']) {
					//to not overstretch the facebook limits, add the other events later..
					ignore_user_abort(true);

					$numb_events = 0;
					sleep($config['sleep_time']);
				}

				$dts = $event->getProperty('DTSTART');
				if ($dts) {
					$dtstart = $event->_date2timestamp($dts);
				}
				if ($dtstart > strtotime($config['old_event_threshold'])) {
					//only add event if it is not older than a month

					$event_obj = new Event($event,$this);
					//create facebook event
					$event_id = $event_obj->post_to_fb();

					if ($event_id > 0) {
						//if successful
						//get rid of all ' for mysql
						$summary = mysql_real_escape_string($event->getProperty('SUMMARY'));

						//and save event to db
						mysql_query("INSERT INTO user$user_id (event_id, UID, summary, lastupdated, sub_id) VALUES ('$event_id', '$UID', '$summary', '$lastupdated', '$sub_id')") or trigger_error(mysql_error());

						$numb_events++;
					}

					if ($this->sub_data['wall'])
						$event_obj->post_to_wall();
				}
			}
			elseif($result && !$sql_error) {
				//event already exists on fb or update is being forced

				if (isset($lastupdated) || $force) {
					if ( ($lastupdated > $data['lastupdated']) || $force ) {
						//event already exists, but has been changed
						$event_id = $data['event_id'];
						//new event
						$event_obj = new Event($event,$this);

						// edit facebook event
						if (FALSE === ($status = $event_obj->update_to_fb($event_id)) )
							throw new Exception("Could not update event " . $event_id);

						if ($status == 1) {
							//if successfull
							//get rid of all ' for mysql
							$summary = mysql_real_escape_string($event->getProperty('SUMMARY'));

							mysql_query("UPDATE user$user_id SET summary='$summary', lastupdated='$lastupdated' WHERE event_id='$event_id'") or trigger_error(mysql_error());

							$numb_events++;
						}
					}
				}
			}
		}

		return $numb_events;
	}

	public function insert_into_db() {
		//inserts a new subscription into the db

		$user_id = $this->sub_data['user_id'];
		$sub_name = $this->sub_data['sub_name'];
		$url = $this->sub_data['url'];
		$category = $this->sub_data['category'];
		$subcategory = $this->sub_data['subcategory'];
		$page_id = $this->sub_data['page_id'];
		$wall = $this->sub_data['wall'];
		$image_field = $this->sub_data['image_field'];

		mysql_query("INSERT INTO subscriptions (sub_name, user_id, url, category, subcategory, page_id, wall, image_field) VALUES ('$sub_name', '$user_id', '$url', '$category', '$subcategory', '$page_id', '$wall', '$image_field')") or trigger_error(mysql_error());
		//get sub_id from db
		$query = mysql_query("select max(sub_id) from subscriptions");
		$this->sub_data['sub_id'] = mysql_result($query, 0);
	}

	public function update_in_db() {
		//updates an existing subscription in the db from this calendar object

		$user_id = $this->sub_data['user_id'];
		$sub_name = $this->sub_data['sub_name'];
		$url = $this->sub_data['url'];
		$category = $this->sub_data['category'];
		$subcategory = $this->sub_data['subcategory'];
		$page_id = $this->sub_data['page_id'];
		$wall = $this->sub_data['wall'];
		$image_field = $this->sub_data['image_field'];

		$sub_id = $this->sub_data['sub_id'];

		//update db
		mysql_query("UPDATE subscriptions SET sub_name='$sub_name', page_id='$page_id', category='$category',
                subcategory='$subcategory', wall='$wall', image_field='$image_field' WHERE sub_id='$sub_id' AND user_id='$user_id'") or trigger_error(mysql_error());
	}

	public function get_session_key() {
		//returns the session_key of the user of this calendar

		if (!isset($this->session_key)) {
			$user_id = $this->sub_data['user_id'];
			$result = mysql_query("SELECT session_key FROM users WHERE user_id='$user_id'") or trigger_error(mysql_error());
			if (mysql_num_rows($result) > 0)
				$this->session_key = mysql_result($result, 0);
			else {
				throw new Exception("No session key found in database.");
			}
		}

		return $this->session_key;
	}

	public function ensure_parse() {
		//parses the file if not already done
		if(!isset($this->iCalendar)) {
			$this->parse();
		}
	}

	public function ensure_timezone() {
		//figures the timezone if not already done
		if(! isset($this->calendar_timezone))
			$this->set_timezone();
	}

	///////////////////////////////
	//PRIVATE METHODS
	///////////////////////////////

	private function parse() {
		// parses iCalendar/ics file

		$vcalendar = new vcalendar();
		$vcalendar->setConfig( "url", $this->sub_data['url'] );
		$vcalendar->setConfig( "newlinechar", "\r\n" );
		if ( FALSE === $vcalendar->parse()) {
			//try other newline character
			$vcalendar->setConfig( "newlinechar", "\n" );
			$this->newline_char = "n";
			if ( FALSE === $vcalendar->parse()) {
				throw new Exception("Error when parsing file. Is this really a <a href='http://icalvalid.cloudapp.net/' target='_blank'>valid</a> iCalendar file?");
			}
		}
		$this->iCalendar = $vcalendar;
	}

	private function set_timezone() {
		//sets $this->calendar_timezone

		$vtimezone = $this->iCalendar->getComponent('vtimezone');
		if ($vtimezone) {
			$this->calendar_timezone = $vtimezone->getProperty("TZID");
		}
		else {
			$vtimezone = $this->iCalendar->getProperty( "X-WR-TIMEZONE" );
			if ($vtimezone)
				$this->calendar_timezone = $vtimezone[1];
		}
	}

	private function check_sub_data($sub_data) {
		//checks whether the $sub_data entered by the user is valid

		//check subscription name
		if (mb_strlen($sub_data['sub_name']) == 0) {
			echo '{ "msg":"<div class=\'clean-error\'>You need to give your subscription a name.</div>"}';
			exit;
		}
		else {
			//get rid of all ' for mysql
			$sub_data['sub_name'] = str_replace("'","\'", $sub_data['sub_name']);
		}

		//check category
		if ($sub_data['category'] == "" || $sub_data['subcategory'] == "") {
			echo '{ "msg":"<div class=\'clean-error\'>You need to specify a category and subcategory.</div>"}';
			exit;
		}

		if ( isset($sub_data['wall']) )
			$sub_data['wall'] = TRUE;
		else
			$sub_data['wall'] = FALSE;

		//check url
		$sub_data['url'] = trim($sub_data['url']);
		$urlregex = '/^(https?|ftp):\/\/(\S*)\Z/';
		if (! preg_match($urlregex, $sub_data['url']) ) {
			echo '{ "msg":"<div class=\'clean-error\'>URL doesn\'t have correct form. Please include http:// etc.</div>"}';
			exit;
		}


		return $sub_data;
	}


}
?>
