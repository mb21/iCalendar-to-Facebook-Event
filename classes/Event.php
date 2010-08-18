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

class Event {
	// property declaration
	public $calendar;
	public $fbEvent;
	private $vEvent;
	private $event_id;
	private $start_time; //start datetime as string in the TZ the user wants it to see (not pacific time or UTC)
	private $image_file_path;
	private $image_file_url;

	///////////////////////////////
	//PUBLIC METHODS
	///////////////////////////////

	function __construct($event, $calendar) {
		$this->vEvent = $event;
		$this->calendar = $calendar;

		//figure what image to use if any
		$image_field = $this->calendar->sub_data['image_field'];
		if (strlen($image_field) > 2) {
			if ($image_field == "ATTACH"){
				//set image URL from ATTACH field
				$this->image_file_url = $this->vEvent->getProperty('ATTACH');
				if (substr_count($this->image_file_url, "%3A%2F%2F") > 0){
					//decode percent-encoding
					$this->image_file_url = rawurldecode($this->image_file_url);
				}
			}
			else{
				//set image URL from X-property
				$xprop = $this->vEvent->getProperty($image_field);
				$this->image_file_url = $xprop[1];
			}
			$this->get_image_file();
		}
		elseif ($this->calendar->sub_data['picture']) {
			//use generic subscription picture
			$this->image_file_path = "pictures/".$this->calendar->sub_data['sub_id'];
			$this->image_file_url = _HOST_URL . $this->image_file_path;
		}
	}

	function __destruct() {
		//delete image in pictures/tmp
		if ($this->image_file_path && !$this->calendar->sub_data['picture'])
			unlink($this->image_file_path);
	}

	public function post_to_fb() {
		//creates a new event on facebook

		global $facebook;
		$this->ensure_convert();

		$user_id = $this->calendar->sub_data['user_id'];
		$session_key = $this->calendar->get_session_key();
		$facebook->set_user($user_id, $session_key);

		//post array to facebook
		if ($this->image_file_path)
			$event_id = $facebook->api_client->events_create(json_encode($this->fbEvent), $this->image_file_path);
		else
			$event_id = $facebook->api_client->events_create(json_encode($this->fbEvent));

		$this->event_id = $event_id;
		return $event_id;
	}


	public function update_to_fb($eid) {
		//updates an existing facebook event

		global $facebook;
		$this->ensure_convert();

		$user_id = $this->calendar->sub_data['user_id'];
		$session_key = $this->calendar->get_session_key();
		$facebook->set_user($user_id, $session_key);

		//post array to facebook
		try {
			if ($this->image_file_path)
				$status = $facebook->api_client->events_edit($eid, json_encode($this->fbEvent), $this->image_file_path);
			else
				$status = $facebook->api_client->events_edit($eid, json_encode($this->fbEvent));
		}
		catch(Exception $e) {
			//To avoid error "You are no longer able to change the name of this event."
			$event_without_name = $this->fbEvent;
			unset($event_without_name['name']);
			if ($this->image_file_path)
				$status = $facebook->api_client->events_edit($eid, json_encode($event_without_name, $this->image_file_path));
			else
				$status = $facebook->api_client->events_edit($eid, json_encode($event_without_name));
		}

		return $status;
	}

	public function post_to_wall() {
		//posts a small message and link to the event to the wall/stream of the user or page
		//works only when post_to_fb has been called on the same object before (because of $this->event_id)

		global $facebook;
		global $config;

		$user_id = $this->calendar->sub_data['user_id'];
		$session_key = $this->calendar->get_session_key();
		$facebook->set_user($user_id, $session_key);

		$page_id = $this->calendar->sub_data['page_id'];
		if ($page_id == 0) {
			$page_id = NULL;
		}

		//format start date/time
		date_default_timezone_set('UTC');
		$user_details = $facebook->api_client->users_getInfo($user_id, 'locale');
		$user_zero = $user_details[0];
		$locale = $user_zero['locale'];
		setlocale(LC_TIME, $locale);
		$start_time = strftime("%A, %d. %B %Y %R", $this->start_time);

		$message = '';

		//localization of wall texts (set language)
		if ( isset($config['wall_text'][$locale]) )
			$caption = "{*actor*} ".$config['wall_text'][$locale];
		else
			$caption = "{*actor*} ".$config['wall_text']["en_GB"];
		if ( isset($config['text_time'][$locale]) )
			$text_time = $config['text_time'][$locale];
		else
			$text_time = $config['text_time']["en_GB"];

		$attachment = array(
			   'name' => $this->fbEvent['name'],
			   'href' => 'http://www.facebook.com/event.php?eid=' . $this->event_id,
			   'caption' => $caption,
			   'properties' => array($text_time => utf8_encode($start_time)),
		);

		if ($this->image_file_path) {
			//add picture
			$attachment['media'] = array(array('type' => 'image', 'src' => $this->image_file_url, 'href' => 'http://www.facebook.com/event.php?eid=' . $this->event_id));
		}
		$attachment = json_encode($attachment);

		//check whether $page_id is a page-ID or a group-ID
		$is_group = $facebook->api_client->groups_get(NULL, $page_id);

		if ($is_group)
			$facebook->api_client->stream_publish($message, $attachment, NULL, $page_id);
		else
			$facebook->api_client->stream_publish($message, $attachment, NULL, NULL, $page_id);

	}

	public function ensure_convert() {
		if(!isset($this->fbEvent)) {
			$this->convert();
		}
	}


///////////////////////////////
//PRIVATE METHODS
///////////////////////////////	

	private function convert() {
		//converts vEvent to fbEvent
		global $config;

		//category
		$event['category'] = $this->calendar->sub_data['category'];
		//subcategory
		$event['subcategory'] = $this->calendar->sub_data['subcategory'];
		//host
		$event['host'] = $this->calendar->sub_data['user_id'];
		//page_id
		$event['page_id'] = $this->calendar->sub_data['page_id'];
		if ($event['page_id'] == 0 || $event['page_id'] == '')
			unset($event['page_id']);
		
		//location
		$location_arr = $this->vEvent->getProperty('LOCATION', FALSE, TRUE);
		$location = $location_arr['value'];

		//check for Quoted-Printable encoding
		if ($location_arr['params']['ENCODING'] == "QUOTED-PRINTABLE")
			$location = quoted_printable_decode($location);
		if (!$location || $location == '')
			$event['location'] = ' ';
		else
			$event['location'] = $location;

		//summary
		$summary_arr = $this->vEvent->getProperty('SUMMARY', FALSE , TRUE);
		$summary = $summary_arr['value'];
		if (!$summary)
			$event['name'] = "unknown name";
		else {
			//check for Quoted-Printable encoding
			if ($summary_arr['params']['ENCODING'] == "QUOTED-PRINTABLE")
				$summary = quoted_printable_decode($summary);

			//facebook doesn't allow title names that are too long
			$event['name'] = mb_substr($summary, 0, $config['max_length_title']);
		}

		$weburl= $this->vEvent->getProperty('URL');

		//description
		$description_arr = $this->vEvent->getProperty('DESCRIPTION', FALSE, TRUE);
		$description = $description_arr['value'];

		if ($description) {
			//check for Quoted-Printable encoding
			if ($description_arr['params']['ENCODING'] == "QUOTED-PRINTABLE")
				$description = quoted_printable_decode($description);

			$description = str_replace("\\n", "\r\n", $description, $count);
			if ($this->calendar->newline_char == "n") {
				$description = str_replace("\\r", "", $description, $count);
			}
		}
		if ($weburl)
			$description .= "\r\n\r\n".$weburl;
		if ($description)
			$event['description'] = $description;

		//start_time
		$event["start_time"] = $this->to_facebook_time("DTSTART");

		//end_time
		$event["end_time"] = $this->to_facebook_time("DTEND");

		$this->fbEvent = $event;
	}


	private function to_facebook_time($key) {
		//takes an ics-key (like DTSTART or DTEND) and outputs this in the time-format facebook currently uses in Events.create
		/*
		because facebook is stupid the time of the event displayed is the same for each user, regardless of his time zone.
		facebook does take UTC time now though (as opposed to Pacific earlier). So we have to calculate the offset...
		*/


		$dt = $this->vEvent->getProperty($key, FALSE, TRUE);
		$time_arr = $dt["value"];

		date_default_timezone_set('UTC');


		if (!$dt && strtoupper($key) == "DTEND") {
			//if only DURATION specified calculate DTEND

			$duration_arr = $this->vEvent->getProperty('DURATION');
			if (!$duration_arr)
				$duration_arr['hour'] = 2;

			$start_dt = $this->vEvent->getProperty('DTSTART', FALSE, TRUE);
			$start_dt = $start_dt["value"];
			$start_time = $this->vEvent->_date2timestamp($start_dt);
			if (isset($duration_arr['week']))
				$duration .= $duration_arr['week'] . "weeks ";
			if (isset($duration_arr['day']))
				$duration .= $duration_arr['day'] . "days ";
			if (isset($duration_arr['hour']))
				$duration .= $duration_arr['hour'] . "hours ";
			if (isset($duration_arr['min']))
				$duration .= $duration_arr['min'] . "minutes ";
			if (isset($duration_arr['sec']))
				$duration .= $duration_arr['sec'] . "seconds ";

			$time = strtotime($duration, $start_time); //end_time = start_time + duration
		}
		else {
			$time = $this->vEvent->_date2timestamp($time_arr);
		}


		if(!isset($dt["params"]["TZID"])) {
			//if no timezone specified check for calendar timezone
			$calendar_tz = $this->calendar->calendar_timezone;

			if (isset($calendar_tz) && isset($time_arr["tz"]) && $time_arr["tz"] == "Z") {
				// if calendar timezone is set and event is in UTC we assume that the user
				// wants his event time displayed in the calendar timezone (presumably
				// his local timezone) and not in UTC. Thus we calculate the offset.
				$user_tz = new DateTimeZone($calendar_tz);
				$datetime = new DateTime("@$time");
				$tz_offset = timezone_offset_get($user_tz, $datetime);
				$time += $tz_offset;
			}
		}


		//save start time for post_to_wall()
		if ($key == "DTSTART")
			$this->start_time = $time;

		date_default_timezone_set('UTC');

		//adjust for newest facebook bug.. 
		//adds 14 or 16 hours (depending on wether its daylight saving time over in sunny California) to the timestamp
		$user_tz = new DateTimeZone('America/Los_Angeles');
		$datetime = new DateTime("@$time");
		$tz_offset = timezone_offset_get($user_tz, $datetime);
		$time -= (2 * $tz_offset);
		
		return $time;
	}

	private function get_image_file() {
		//download image for specific event
		
		$image_url = $this->image_file_url;

		try {
			$newfilename = "pictures/tmp/" . rand(100000, 10000000);

			$out = fopen($newfilename, 'wb');
			if ($out == FALSE) {
				throw new Exception("Could not open location $newfilename.");
			}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_FILE, $out);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, $image_url);

			curl_exec($ch);

			if (curl_error($ch))
				throw new Exception(curl_error($ch));

			curl_close($ch);

			$this->image_file_path = $newfilename;
		}
		catch (Exception $e) {
			echo $e->getMessage();

			return NULL;
		}
	}

}
?>
