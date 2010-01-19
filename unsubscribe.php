
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


mb_internal_encoding('UTF-8');
/////////////////////////////////
// CONFIGURATION
/////////////////////////////////
require_once 'config.php';
require_once 'facebook/facebook.php';

$facebook = new Facebook($appapikey, $appsecret);
$user_id = $facebook->require_login();

//Connect to Database
$con = mysql_connect($host,$db_user,$db_password);
if (!$con)
	die('Could not connect: '. mysql_error());
mysql_query("SET NAMES 'utf8';", $con);
mysql_query("SET CHARACTER SET 'utf8';", $con);
mysql_select_db($database_name,$con);

if (isset($_POST['unsub_sub_id'])){
	$only_unsub = ($_POST['unsub_mode'] == 'only_unsub');
	$sub_id = $_POST['unsub_sub_id'];

	try{
		if (!$only_unsub){
				//remove all events on fb
				
				$query = mysql_query("select url from subscriptions where user_id = '$user_id' and sub_id ='$sub_id'") or trigger_error(mysql_error());
				$url = mysql_result($query, 0);
				$result = mysql_query("select event_id from user$user_id where sub_id='$sub_id'") or trigger_error(mysql_error());
				$numb_events = 0;
				while($row = mysql_fetch_array($result)){
					//try to not over-do facebook...
					if($numb_events >= $config['number_of_events_threshold']){
						$numb_events = 0;
						print('<fb:redirect url="'._SITE_URL.'limit_reached.php"/>');
						sleep(5);
					}
						$facebook->api_client->events_cancel($row['event_id']);
						$numb_events++;
				}
				
				//remove events from db
				mysql_query("DELETE FROM user$user_id WHERE sub_id='$sub_id'");
		}
		
		//remove subscription from db
		mysql_query("DELETE FROM subscriptions WHERE sub_id='$sub_id'");
	
		
		//AJAX response
		$response["sub_id"] = $sub_id;
		$response["success"] = "1";
		if ($only_unsub){
			$response['msg'] = "<div class='clean-ok'>You have successfully unsubscribed.</div>";
		}
		else{
			$response['msg'] = "<div class='clean-ok'>You have successfully unsubscribed and all events on facebook from that subscription have been removed.</div>";
		}
		echo json_encode($response);
	}
	catch(Exception $e){
		echo "{'msg':'<div class=\'clean-error\'>" . $e->getMessage() ."</div>', 'success' = '0'}";
	}
}


?>
