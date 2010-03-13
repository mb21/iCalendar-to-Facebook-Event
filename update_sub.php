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

function __autoload($class_name) {
	require_once 'classes/' . $class_name . '.php';
}

$sub_id = $_POST['sub_id'];
$result = mysql_query("select url, page_id from subscriptions where user_id='$user_id' and sub_id='$sub_id'");
$row = mysql_fetch_assoc($result);

$_POST['url'] = $row['url'];
$_POST['page_id'] = $row['page_id'];

$_POST['user_id'] = $user_id;
$_POST['category'] = $_POST['adv_category'];
$_POST['subcategory'] = $_POST['adv_subcategory'];
$_POST['sub_name'] = $_POST['adv_sub_name'];

$calendar  = new Calendar(NULL, $_POST);

try {
	$calendar->update_in_db();

	//response
	$_POST['msg'] = "<div class='clean-ok'>Subscription " . $_POST['sub_name'] . " is being updated.</div>";

	//so the GUI will know it needs to update the subscription and not create a new one
	unset($_POST['sub_name']);
	echo json_encode($_POST);

	if ($_POST['adv_update'] == "update") {
		$numb_events = $calendar->update(TRUE); //force update of calendar
	}

}
catch(Exception $e) {
	$response['msg'] = "<div class='clean-error'>" . $e->getMessage() ."</div>";
	$response['success'] = 0;
	echo json_encode($response);
}

mysql_close($con);

?>