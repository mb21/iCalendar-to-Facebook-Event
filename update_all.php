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

//Connect to Database
$con = mysql_connect($host,$db_user,$db_password);
if (!$con)
	die('Could not connect: '. mysql_error());
mysql_query("SET NAMES 'utf8';", $con);
mysql_query("SET CHARACTER SET 'utf8';", $con);
mysql_select_db($database_name,$con);

function __autoload($class_name) {
    require_once $class_name . '.php';
}

//update all
$result = mysql_query("SELECT * FROM subscriptions") or trigger_error(mysql_error());

$numb_events = 0;
$numb_subs = 0;

while($row = mysql_fetch_assoc($result)){
	$sub_data = array("sub_id" => $row['sub_id'], "url" => $row['url'], "user_id" => $row['user_id'], "category" => $row['category'], "subcategory" => $row['subcategory'], "page_id" => $row['page_id']);
	if ($sub_data['page_id'] == 0)
            $sub_data['page_id'] = '';

	echo "<br>try ".$row['url']."<br>";
	
	try{
		$numb_subs++;
		
		$calendar  = new Calendar($sub_data);
		$newevs = $calendar->update();
		
		$numb_events = $numb_events + $newevs;
		
		echo "done";
	}catch(Exception $e){
		$error = $e->getMessage().' Error code:'.$e->getCode();
		echo $error;
	}
}

echo "<br><br>Checked ".$numb_subs." subscriptions, ". $numb_events . " events created.";

mysql_close($con);
?>