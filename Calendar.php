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
	// of form: $sub_data = array("sub_id" => $sub_id, "url" => $url, "user_id" => $user_id, "category" => $_GET['category'], "subcategory" => $_GET['subcategory'], "page_id" => $_GET['page_id']);
	
	public $iCalendar;
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
	
	public function update($force = FALSE){
		//updates all events in this calendar and returns number of new events created
		global $config;		
		$this->ensure_parse();
		$user_id = $this->sub_data['user_id'];
		$url = $this->sub_data['url'];
		$sub_id = $this->sub_data['sub_id'];
		
		//loop through calendar and add all new events to db as well as create new fb-events
		$numb_events = 0;
		while( $event = $this->iCalendar->getComponent( "vevent" )) { 
			$UID = $event->getProperty('UID');
                        $lm = $event->getProperty('LAST-MODIFIED');
                        if ($lm){
                            $lastupdated = $event->_date2timestamp($lm);
			}
                        else {
                            $lastupdated = '';
                        }

			$result = mysql_query("select * from user$user_id where sub_id ='$sub_id' and UID = '$UID'");
			if (mysql_num_rows($result) == 0 && !$force){
				//event doesn't exist yet, add it
				
				if($numb_events > $config['number_of_events_threshold']){
					//to not overstretch the facebook limits, add the other events later..
					throw new Exception($numb_events." Events created. More will be added automatically in a few minutes to not overstretch the facebook limits.");
				}
				
				//new event
				$event_obj = new Event($event,$this);
				
				if ($event_obj->get_start_time() > strtotime($config['old_event_threshold'])){
					//if event is older than a month, do not add it

					//create facebook event
					$event_id = $event_obj->post_to_fb();
					
					//get rid of all ' for mysql
					$summary = str_replace("'","\'", $event->getProperty('SUMMARY'));
	
					mysql_query("INSERT INTO user$user_id (event_id, UID, summary, lastupdated, sub_id) VALUES ('$event_id', '$UID', '$summary', '$lastupdated', '$sub_id')") or trigger_error(mysql_error());
					
					$numb_events++;
				}
			}
			else{
				//event already exists on fb or update is being forced
				$data = mysql_fetch_array($result);
				
				if ((!$lastupdated == '') || $force) {
					if ( ($lastupdated > $data['lastupdated']) || $force ) {
						//event already exists, but has been changed
						$event_id = $data['event_id'];
						//new event
						$event_obj = new Event($event,$this);
						
						// edit facebook event
						$status = $event_obj->update_to_fb($event_id) or trigger_error('Could not update event ' . $event_id);
						
						//get rid of all ' for mysql
						$summary = str_replace("'","\'", $event->getProperty('SUMMARY'));
						
						mysql_query("UPDATE user$user_id SET summary='$summary', lastupdated='$lastupdated' WHERE event_id='$event_id'") or trigger_error(mysql_error());
						
						$numb_events++;	
					}
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
		if(!isset($this->iCalendar)){
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
	      throw new Exception("Cannot retrieve URL.");
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
		
		$vcalendar = new vcalendar();
		$vcalendar->setConfig( "filestring", $this->file );
                $vcalendar->setConfig( "newlinechar", "\r\n" );
		if ( FALSE === $vcalendar->parse()){
                    throw new Exception("Error when parsing ics file.");
                }
		$this->iCalendar = $vcalendar;
	}
	
	
}
?>
