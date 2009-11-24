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

class Calendar
{
	///////////////////////////////
	// PROPERTIES
	///////////////////////////////
	
	public $calendar_timezone;
	public $sub_data;
	// of form: $sub_data = array("url" => $url, "user_id" => $user_id, "category" => $_GET['category'], "subcategory" => $_GET['subcategory'], "page_id" => $_GET['page_id']);
	
	public $icsCalendar;
	
	public $file;
	
	
	
	///////////////////////////////
	//PUBLIC METHODS
	///////////////////////////////	
	
	function __construct($sub_data){
		//strip trailing '/' from url
		$url = $sub_data['url'];
		if (mb_substr($url,-1,1) == '/'){
			$sub_data['url'] = mb_substr($url,0,-1);
		}
		$this->sub_data = $sub_data;
	}
	
	public function url_valid(){
		//returns true if url is valid
		$urlregex = '/^(https?|ftp):\/\/(\S*)\Z/';
		return preg_match($urlregex, $this->sub_data['url']);
	}
	
	public function file_valid(){
		//returns true if file is valid
		$this->ensure_file();
		return preg_match('/BEGIN:VCALENDAR/', $this->file);
	}
	
	public function file_valid_event(){
		//returns true if file includes VEVENTs
		$this->ensure_file();
		return preg_match('/BEGIN:VEVENT/', $this->file);
	}
	
	public function update(){
		//updates all events in this calendar and returns number of new events created
		
		$this->ensure_parse();
		$user_id = $this->sub_data['user_id'];
		$url = $this->sub_data['url'];
		
		//loop through calendar and add all new events to db as well as create new fb-events
		$numb_events = 0;
		foreach ($this->icsCalendar as $event) {
			$UID = $event['UID'];
			
			$result = mysql_query("select * from user$user_id where from_url ='$url' and UID = '$UID'");
			if (mysql_num_rows($result) == 0){
				//event doesn't exist yet, add it
				
				if($numb_events > $config['number_of_events_threshold']){
					//to not overstretch the facebook limits, add the other events later..
					print('<fb:redirect url="'._SITE_URL.'?msg&wait='.$numb_events.'"/>');
					exit;
				}
				
				//new event
				$event_obj = new Event($event,$this);
				
				if ($event_obj->get_start_time() > strtotime($config['old_event_threshold'])){
					//if event is older than a month, do not add it

					//create facebook event
					$event_id = $event_obj->post_to_fb();
					
					//get rid of all ' for mysql
					$summary = str_replace("'","\'", $event['SUMMARY']);
	
					mysql_query("INSERT INTO user$user_id (event_id, UID, summary, from_url) VALUES ('$event_id', '$UID', '$summary','$url')") or trigger_error(mysql_error());
					
					$numb_events++;
				}
			}
		}
		
		return $numb_events;		
	}
	
	
	public function ensure_file(){
		//downloads the file if not already here
		if(!isset($this->file)){
			$this->get_file();
		}
	}
	
	public function ensure_parse(){
		//parses the file if not already done
		if(!isset($this->icsCalendar)){
			$this->parse();
		}
	}
	
	///////////////////////////////
	//PRIVATE METHODS
	///////////////////////////////
	
	private function get_file(){
	  //Downloads a file like $this->file = file_get_contents($this->sub_data['url']);
	  //for hosts which don't support file_get_contents
		
	   // get the host name and url path
	   $parsedUrl = parse_url($this->sub_data['url']);
	   $host = $parsedUrl['host'];
	   if (isset($parsedUrl['path'])) {
	      $path = $parsedUrl['path'];
	   } else {
	      // the url is pointing to the host like http://www.mysite.com
	      $path = '/';
	   }
	
	   if (isset($parsedUrl['query'])) {
	      $path .= '?' . $parsedUrl['query'];
	   }
	
	   if (isset($parsedUrl['port'])) {
	      $port = $parsedUrl['port'];
	   } else {
	      // most sites use port 80
	      $port = '80';
	   }
	
	   $timeout = 10;
	   $response = '';
	
	   // connect to the remote server
	   $fp = @fsockopen($host, '80', $errno, $errstr, $timeout );
	
	   if( !$fp ) {
	      echo "Cannot retrieve $url";
	   } else {
	      // send the necessary headers to get the file
	      fputs($fp, "GET $path HTTP/1.0\r\n" .
	                 "Host: $host\r\n" .
	                 "Connection: Close\r\n\r\n");
	
	      // retrieve the response from the remote server
	      while ( $line = fread( $fp, 4096 ) ) {
	         $response .= $line;
	      }
	
	      fclose( $fp );
	
	      // strip the headers
	      $pos      = mb_strpos($response, "\r\n\r\n");
	      $response = mb_substr($response, $pos + 4);
	   }
	
	   // return the file content
	   $this->file = $response;
	}
	
	private function parse() {
		///////////////////////////////
		// PARSES VEVENTs in ICS-file
		///////////////////////////////

		$this->ensure_file();
		if ($this->file_valid()){
			$ical = $this->file;
			
			//get timezone
			if (preg_match('/(X-WR-TIMEZONE.*?\\n)/si', $ical, $result)){
				$tmpholderarray = explode(":",$result[0]);
				preg_match('/(\S*)/', $tmpholderarray[1], $result);
				$this->calendar_timezone = $result[0];
			}
		
			preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $ical, $result, PREG_PATTERN_ORDER);
			for ($i = 0; $i < count($result[0]); $i++) {
				$tmpbyline = explode("\r\n", $result[0][$i]);
				
				foreach ($tmpbyline as $item) {
					$tmpholderarray = explode(":",$item);
					if (count($tmpholderarray) >1) { 
						$majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
					}
					
				}
				
				//get rid of backslashes before commas
				if(isset($majorarray['LOCATION']))
					$majorarray['LOCATION'] = str_replace("\,",",", $majorarray['LOCATION']);
				if(isset($majorarray['SUMMARY']))
					$majorarray['SUMMARY'] = str_replace("\,",",", $majorarray['SUMMARY']);
				
				//get description
				if (preg_match('/DESCRIPTION:(.*)\n[^\s]/siU', $result[0][$i], $regs)) {
					$data = $regs[1];			
					$data = str_replace("\\n", "\r\n", $data);
					$data = preg_replace('/\s\n\s/', "", $data);
					$data = str_replace("\,",",", $data);
					$data = str_replace("  ", " ", $data);
					$majorarray['DESCRIPTION'] = strip_tags($data);
				}
				
				$icalarray[] = $majorarray;
				unset($majorarray);
						 
			}
			$this->icsCalendar = $icalarray;
		}
		else{
			echo "ics file not valid";
		}
	}
}
?>
