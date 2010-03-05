<?php
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
	$url = $_POST['url'];
	$page_id = $_POST['page_id'];

	//check subscription name
	if (mb_strlen($_POST['sub_name']) == 0) {
		echo '{ "msg":"<div class=\'clean-error\'>You need to give your subscription a name.</div>"}';
		exit;
	}
	else {
		//get rid of all ' for mysql
		$sub_name = str_replace("'","\'", $_POST['sub_name']);
	}

	//check category
	if ($_POST['category'] == "" || $_POST['subcategory'] == "") {
		echo '{ "msg":"<div class=\'clean-error\'>You need to specify a category and subcategory.</div>"}';
		exit;
	}
	else {
		$category = $_POST['category'];
		$subcategory = $_POST['subcategory'];
	}

	//put subscription specific data in $sub_data array
	$sub_data = array("url" => $url, "user_id" => $user_id, "category" => $category, "subcategory" => $subcategory, "page_id" => $_POST['page_id']);

	try {
		//check url
		$urlregex = '/^(https?|ftp):\/\/(\S*)\Z/';
		if (! preg_match($urlregex, $sub_data['url']) ) {
			echo '{ "msg":"<div class=\'clean-error\'>URL doesn\'t have correct form. Please include http:// etc.</div>"}';
			exit;
		}

		$calendar  = new Calendar($sub_data);
		//parse throws error when file invalid
		$calendar->ensure_parse();

		//add url to db
		mysql_query("INSERT INTO subscriptions (sub_name, user_id, url, category, subcategory, page_id) VALUES ('$sub_name', '$user_id', '$url', '$category', '$subcategory', '$page_id')") or trigger_error(mysql_error());
		//get sub_id from db
		$query = mysql_query("select max(sub_id) from subscriptions");
		$calendar->sub_data['sub_id'] = mysql_result($query, 0);
		$_POST['sub_id'] = $calendar->sub_data['sub_id'];
		$sub_id = $calendar->sub_data['sub_id'];

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


		//checkout calendar
		ob_start();

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