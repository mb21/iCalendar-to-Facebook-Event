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
date_default_timezone_set('UTC');

ob_start();

/////////////////////////////////
// CONFIGURATION
/////////////////////////////////
require_once 'config.php';
require_once 'facebook/facebook.php';

$facebook = new Facebook($appapikey, $appsecret);
$user_id = $facebook->require_login("offline_access, create_event");

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

$sub_id = $_GET['sub_id'];

$numb_events = 0;

try {
	//update that one sub
	$calendar  = new Calendar($sub_id);
	$newevs = $calendar->update();
	$numb_events = $numb_events + $newevs;
	
	//reactivate sub
	mysql_query("UPDATE subscriptions SET active = 1 WHERE sub_id = $sub_id AND user_id = $user_id") or trigger_error(mysql_error());
	
	echo "<h4>Success</h4><p>Your subscription has been reactivated and " . $numb_events . " events created or updated.</p>";
	
}catch(Exception $e) {
	$error = '<strong>'.$e->getMessage().'</strong><br/>Error code:'.$e->getCode();
	echo "<h4>Your subscriptions has not been reactivated because there is still something wrong with it.</h4><p>" . $error . "</p>";
}

mysql_close($con);
?>