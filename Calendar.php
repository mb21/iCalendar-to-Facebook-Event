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
	public $sub_data;
	// of form: $sub_data = array("sub_id" => $sub_id, "url" => $url, "user_id" => $user_id, "category" => $_GET['category'], "subcategory" => $_GET['subcategory'], "page_id" => $_GET['page_id']);

	public $iCalendar;

	///////////////////////////////
	//PUBLIC METHODS
	///////////////////////////////

	function __construct($sub_data) {
		//strip trailing '/' from url
		$url = $sub_data['url'];
		if (mb_substr($url,-1,1) == '/') {
			$sub_data['url'] = mb_substr($url,0,-1);
		}
		$this->sub_data = $sub_data;
	}

	public function ensure_timezone(){
		if(! isset($this->calendar_timezone))
			   $this->set_timezone();
	}

	public function update($force = FALSE) {
		//updates all events in this calendar and returns number of new events created
		global $config;

		date_default_timezone_set('UTC');

		$this->ensure_parse();
		$user_id = $this->sub_data['user_id'];
		$url = $this->sub_data['url'];
		$sub_id = $this->sub_data['sub_id'];

		$this->ensure_timezone();

		//loop through calendar and add all new events to db as well as create new fb-events
		$numb_events = 0;
		while( $event = $this->iCalendar->getComponent( 'vevent' )) {
			$UID = $event->getProperty('UID');
			$lm = $event->getProperty('LAST-MODIFIED');
			if ($lm) {
				$lastupdated = $event->_date2timestamp($lm);
			}

			$result = mysql_query("select * from user$user_id where sub_id ='$sub_id' and UID = '$UID'");
			if (mysql_num_rows($result) == 0 && !$force) {
				//event doesn't exist yet, add it

				if($numb_events > $config['number_of_events_threshold']) {
					//to not overstretch the facebook limits, add the other events later..
					//ignore_user_abort(true);

					ob_start();
					$response['msg'] = "<div class='clean-ok'>" .$numb_events. " Events processed. More will be handled in a few seconds to not stretch the facebook limits.</div>";
					echo json_encode($response);

					// get the size of the output
					$size = ob_get_length();

					// send headers to tell the browser to close the connection
					header("Content-Length: $size");
					header('Connection: close');

					// flush all output
					ob_end_flush();
					ob_flush();
					flush();

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
						$summary = str_replace("'","\'", $event->getProperty('SUMMARY'));

						mysql_query("INSERT INTO user$user_id (event_id, UID, summary, lastupdated, sub_id) VALUES ('$event_id', '$UID', '$summary', '$lastupdated', '$sub_id')") or trigger_error(mysql_error());

						$numb_events++;
					}
				}
			}
			else {
				//event already exists on fb or update is being forced
				$data = mysql_fetch_array($result);

				if (isset($lastupdated) || $force) {
					if ( ($lastupdated > $data['lastupdated']) || $force ) {
						//event already exists, but has been changed
						$event_id = $data['event_id'];
						//new event
						$event_obj = new Event($event,$this);

						// edit facebook event
						$status = $event_obj->update_to_fb($event_id) or trigger_error('Could not update event ' . $event_id);

						if ($status == 1) {
							//if successfull
							//get rid of all ' for mysql
							$summary = str_replace("'","\'", $event->getProperty('SUMMARY'));

							mysql_query("UPDATE user$user_id SET summary='$summary', lastupdated='$lastupdated' WHERE event_id='$event_id'") or trigger_error(mysql_error());

							$numb_events++;
						}
					}
				}
			}
		}

		return $numb_events;
	}

	public function ensure_parse() {
		//parses the file if not already done
		if(!isset($this->iCalendar)) {
			$this->parse();
		}
	}

	///////////////////////////////
	//PRIVATE METHODS
	///////////////////////////////

	private function parse() {
		///////////////////////////////
		// PARSES VEVENTs in ICS-file
		///////////////////////////////

		$vcalendar = new vcalendar();
		$vcalendar->setConfig( "url", $this->sub_data['url'] );
		$vcalendar->setConfig( "newlinechar", "\r\n" );
		if ( FALSE === $vcalendar->parse()) {
			throw new Exception("Error when parsing file. Is this really a <a href='http://severinghaus.org/projects/icv/' target='_blank'>valid</a> iCalendar file?");
		}
		$this->iCalendar = $vcalendar;
	}

	private function set_timezone(){
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


}
?>
