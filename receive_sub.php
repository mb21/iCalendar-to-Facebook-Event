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

//whether offline_access and create_event permissions are set
$perms = $facebook->api_client->users_hasAppPermission('offline_access') && $facebook->api_client->users_hasAppPermission('create_event');

//$perms = TRUE; //   <<<<-----
//$user_id = "713344833";

//Connect to Database
$con = mysql_connect($host,$db_user,$db_password);
if (!$con)
	die('Could not connect: '. mysql_error());
mysql_query("SET NAMES 'utf8';", $con);
mysql_query("SET CHARACTER SET 'utf8';", $con);
mysql_select_db($database_name,$con);

function __autoload($class_name) {
	require_once 'classes/' . $class_name . '.php';
}


/////////////////////////////////
// PARSE NEW SUBSCRIPTION
/////////////////////////////////

//$_POST = array("adv_update" => "new","rsvp" => "attending","privacy" => "open","uploadedfile" => "","page_id" => "","adv_subcategory" => 29,"adv_category" => 8,"adv_sub_name" => "","sub_id" => "","subcategory" => "29","category" => "8","url" => "http://ics-to-fbevent.project21.ch/basic.ics","sub_name" => "nay");

if ($perms && isset($_POST['url'])) {

	$_POST['user_id'] = $user_id;
	$calendar  = new Calendar(NULL, $_POST);

	try {
		//parse throws error when file invalid
		$calendar->ensure_parse();
		$calendar->insert_into_db();
		$sub_id = $calendar->sub_data['sub_id'];
		$_POST['sub_id'] = $sub_id;

		//check whether user is already in db
		$urls = mysql_query("show tables like 'user$user_id'") or trigger_error(mysql_error());
		if (mysql_num_rows($urls) == 0) {
			//create new user-table
			$sql = 'CREATE TABLE user'. $user_id .'
			(
			event_id bigint UNSIGNED NOT NULL,
			PRIMARY KEY(event_id),
			UID varchar(256),
			summary varchar(300),
			lastupdated varchar(20),
			sub_id int(11)
			)';
			if (!mysql_query($sql)) {
				echo "Error creating table: " . mysql_error();
			}
		}



		//response
		ob_start();
		$_POST['msg'] = "<div class='clean-ok'>Subscription added. Events will be created slowly to not overstretch the facebook limits.</div>";
		echo json_encode($_POST);

		// get the size of the output
		$size = ob_get_length();

		// send headers to tell the browser to close the connection
		header("Content-Length: $size");
		header('Connection: close');

		// flush all output
		ob_end_flush();
		ob_flush();
		flush();


		ob_start();

		//checkout calendar
		$numb_events = $calendar->update();

		$buffer = ob_get_contents();
		ob_clean();

		if (!empty($buffer)) {
			$buffer = mysql_real_escape_string(date('c')." ".$buffer);
			mysql_query("UPDATE subscriptions SET error_log='$buffer' WHERE sub_id='$sub_id'");
		}
	}
	catch(Exception $e) {
		$response['msg'] = "<div class='clean-error'>" . $e->getMessage() ."</div>";
		$response['success'] = 0;
		echo json_encode($response);
	}

}


mysql_close($con);

?>