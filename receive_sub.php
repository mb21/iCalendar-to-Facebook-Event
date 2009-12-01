<?php
mb_internal_encoding('UTF-8');

/////////////////////////////////
// CONFIGURATION
/////////////////////////////////
require_once 'config.php';
require_once 'facebook/facebook.php';

$facebook = new Facebook($appapikey, $appsecret);
$user_id = $facebook->require_login();

echo '<div style="height: 30px"><img id="spinner" src="' . _HOST_URL .'loader.gif" alt="Loading..." style="display:none; padding:0 540px 1em 0;"></div>';

//whether offline_access and create_event permissions are set
$perms = $facebook->api_client->users_hasAppPermission('offline_access') && $facebook->api_client->users_hasAppPermission('create_event');

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


/////////////////////////////////
// PARSE NEW SUBSCRIPTION
/////////////////////////////////
if ($perms && isset($_POST['url'])){
	
	$url = $_POST['url'];
	$page_id = $_POST['page_id'];
	
	//check subscription name
	if (mb_strlen($_POST['sub_name']) == 0){
		echo '<fb:error><fb:message>You need to give your subscription a name.</fb:message></fb:error>';
		exit;
	}
	else{
		$sub_name = $_POST['sub_name'];
	}
	
	//check category
	if ($_POST['category'] == "" || $_POST['subcategory'] == ""){
		echo '<fb:error><fb:message>You need to specify a category and subcategory.</fb:message></fb:error>';
		exit;
	}
	else{
		$category = $_POST['category'];
		$subcategory = $_POST['subcategory'];
	}

	//put subscription specific data in $sub_data array
	$sub_data = array("url" => $url, "user_id" => $user_id, "category" => $category, "subcategory" => $subcategory, "page_id" => $_POST['page_id']);

	$calendar  = new Calendar($sub_data);

	//check url
	if (!$calendar->url_valid()){
		echo '<fb:error><fb:message>URL doesn\'t have correct form. Please include http:// etc.</fb:message></fb:error>';
		exit;
	}
	
	//check file
	if (!$calendar->file_valid()){
		echo '<fb:error><fb:message>File doesn\'t seem to be a valid <a href="http://en.wikipedia.org/wiki/ICalendar" target="_blank">iCalendar</a> file.</fb:message></fb:error>';
		exit;
	}
	if (!$calendar->file_valid_event()){
		echo '<fb:error><fb:message>File doesn\'t seem to contain any events. Calendars that contain only free/busy information are not supported.</fb:message></fb:error>';
		exit;
	}
	
	//check whether url is already in db
	$urls = mysql_query("select * from subscriptions where user_id ='$user_id' and url = '$url'") or trigger_error(mysql_error());
	if (mysql_num_rows($urls) == 0){
		//add url to db
		mysql_query("INSERT INTO subscriptions (sub_name, user_id, url, category, subcategory, page_id) VALUES ('$sub_name', '$user_id', '$url', '$category', '$subcategory', '$page_id')") or trigger_error(mysql_error());
	}
	else{
		//url already present
		echo '<fb:error><fb:message>You are already subscribed to this URL.</fb:message></fb:error>';
		exit;
	}
	//check whether user is already in db
	$urls = mysql_query("show tables like 'user$user_id'") or trigger_error(mysql_error());
	if (mysql_num_rows($urls) == 0){
		//create new user-table
		$sql = 'CREATE TABLE user'. $user_id .'
		(
		event_id bigint NOT NULL,
		PRIMARY KEY(event_id),
		UID varchar(50),
		summary varchar(300),
		from_url varchar(300)
		)';
		if (!mysql_query($sql)){
			echo "Error creating table: " . mysql_error();
		}
	}
	
	//checkout calendar
	$numb_events = $calendar->update();
	if ($numb_events == 1){
		echo '<fb:success><fb:message>1 Event created.</fb:message></fb:success>';
	}
	elseif ($numb_events > 1){
		echo '<fb:success><fb:message>'.$numb_events.' Events created.</fb:message></fb:success>';
	}
}


mysql_close($con);

?>