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

ini_set('max_execution_time', 800);
ignore_user_abort(true);


//update all
$result = mysql_query("SELECT * FROM subscriptions") or trigger_error(mysql_error());

$numb_events = 0;
$numb_subs = 0;
$numb_valid_file_errors = 0;
$numb_session_key_errors = 0;
$numb_session_key_in_db_errors = 0;
$numb_perms_errors = 0;

while($row = mysql_fetch_assoc($result)) {

	$sub_data = array("sub_id" => $row['sub_id'], "url" => $row['url'], "user_id" => $row['user_id'], "category" => $row['category'], "subcategory" => $row['subcategory'], "page_id" => $row['page_id']);
	if ($sub_data['page_id'] == 0)
		$sub_data['page_id'] = '';

	try {
		$numb_subs++;
		$calendar  = new Calendar($sub_data);
		$newevs = $calendar->update();
		$numb_events = $numb_events + $newevs;
	}catch(Exception $e) {
		$error = $e->getMessage().' Error code:'.$e->getCode();
		echo $error;
	}

	$buffer = ob_get_contents();
	ob_clean();

	if (!empty($buffer)){
		$pos = strpos($buffer, "Error when parsing file");
		if(!($pos === FALSE))
			$numb_valid_file_errors++;
		$pos = strpos($buffer, "Session key invalid or no longer valid");
		if(!($pos === FALSE))
			$numb_session_key_errors++;
		$pos = strpos($buffer, "No session key found in database");
		if(!($pos === FALSE))
			$numb_session_key_in_db_errors++;
		$pos = strpos($buffer, "Permissions error");
		if(!($pos === FALSE))
			$numb_perms_errors++;

		$file = "logs/sub".$row['sub_id'].".html";
		$fh = fopen($file, 'w') or die("can't open file");
		fwrite($fh, "<html><body><h3>Error Log for Subscription ". $row['sub_id'] .", user:".$row['user_id']." <a href='".$row['url']."'>URL</a></h3>");
		fwrite($fh, $buffer);
		fwrite($fh, "</body></html>");
		fclose($fh);
	}
}
ob_end_clean();

echo "<br><br>".$numb_subs." subscriptions checked, ". $numb_events . " events created or updated.";

$file = "logs/update_all.html";
$fh = fopen($file, 'w') or die("can't open file");
fwrite($fh, "<html><body><h3>Time update_all.php run last time completely: ".date('c')."</h3><ul>
	<li>File invalid/parse error: ".$numb_valid_file_errors."</li>
	<li>Session key invalid: ".$numb_session_key_errors."</li>
	<li>No session key in db: ".$numb_session_key_in_db_errors."</li>
	<li>Permissions error: ".$numb_perms_errors."</li>
	</ul></body></html>");
fclose($fh);

mysql_close($con);
?>