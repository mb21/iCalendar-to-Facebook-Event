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
    require_once $class_name . '.php';
}

// CSS STYLESHEET
echo '<head><link type="text/css" rel="stylesheet" href="'._HOST_URL.'styles.css"></head>';
//for testing the following code circumvents facebook's css cache
//echo "<head><style>";
//echo htmlentities(file_get_contents('styles.css', true));
//echo "</style></head>";


//whether offline_access and create_event permissions are set
$no_perms = !$facebook->api_client->users_hasAppPermission('offline_access') || !$facebook->api_client->users_hasAppPermission('create_event');

//store session_key in db
if (isset($_POST["fb_sig_session_key"]) && isset($_GET['fb_perms']) && $facebook->api_client->users_hasAppPermission('offline_access')){
	$user_exists = mysql_query("select * from users where user_id ='$user_id'") or trigger_error(mysql_error());
	if (mysql_num_rows($user_exists) == 0){
		$session_key = $_POST["fb_sig_session_key"];
		mysql_query("INSERT INTO users (user_id, session_key) VALUES ('$user_id', '$session_key')") or trigger_error(mysql_error());
	}
}



/////////////////////////////////
// PRINT USER ERRORS
/////////////////////////////////

if (isset($_GET['err'])){
	if (isset($_GET['fb_perms']) && $no_perms){
		echo '<fb:error><fb:message>For this app to be able to create events, you need to give it permission to do so. Please resubmit the form.</fb:message></fb:error>';
	}
	elseif (isset($_GET['url-not-correct'])){
		echo '<fb:error><fb:message>URL doesn\'t have correct form. Please include http:// etc.</fb:message></fb:error>';
	}
	elseif (isset($_GET['file-not-valid'])){
		echo '<fb:error><fb:message>File doesn\'t seem to be a valid <a href="http://en.wikipedia.org/wiki/ICalendar" target="_blank">iCalendar</a> file.</fb:message></fb:error>';
	}
	elseif (isset($_GET['file-no-event'])){
		echo '<fb:error><fb:message>File doesn\'t seem to contain any events. Calendars that contain only free/busy information are not supported.</fb:message></fb:error>';
	}
	elseif (isset($_GET['create_event-error'])){
		echo '<fb:error><fb:message>Couldn\'t create event. '.$_GET['create_event-error'].'.</fb:message></fb:error>';
	}
	elseif (isset($_GET['old-url'])){
		echo '<fb:error><fb:message>You are already subscribed to this URL.</fb:message></fb:error>';
	}
	elseif (isset($_GET['no_sub_name'])){
		echo '<fb:error><fb:message>You need to give your subscription a name.</fb:message></fb:error>';
	}
	elseif (isset($_GET['no_category'])){
		echo '<fb:error><fb:message>You need to specify a category and subcategory.</fb:message></fb:error>';
	}
}

/////////////////////////////////
// PRINT USER MESSAGES
/////////////////////////////////

if (isset($_GET['msg'])){
	if (isset($_GET['success'])){
		//success, event created!
		if ($_GET['success'] == 1)
			echo '<fb:success><fb:message>1 Event created.</fb:message></fb:success>';
		else
			echo '<fb:success><fb:message>'.$_GET['success'].' Events created.</fb:message></fb:success>';
	}
	elseif (isset($_GET['wait'])){
		//success, first 30 events created!
		echo '<fb:success><fb:message>'.$_GET['wait'].' Events created. More will be added automatically in a few minutes to not overstretch the facebook limits.</fb:message></fb:success>';
	}
}

if (isset($_GET['only_unsub'])){
		//only unsubscribe from subscription, events remain in table user$user_id

		$sub_id = $_GET['only_unsub'];
		mysql_query("DELETE FROM subscriptions WHERE sub_id='$sub_id'");

		echo '<fb:success><fb:message>You have successfully unsubscribed.</fb:message></fb:success>';
}
elseif (isset($_GET['unsub_remove'])){
		//remove all events on fb
		$sub_id = $_GET['unsub_remove'];
		$query = mysql_query("select url from subscriptions where user_id = '$user_id' and sub_id ='$sub_id'") or trigger_error(mysql_error());
		$url = mysql_result($query, 0);
		$result = mysql_query("select event_id from user$user_id where from_url='$url'") or trigger_error(mysql_error());
		$numb_events = 0;
		while($row = mysql_fetch_array($result)){
			//try to not over-do facebook...
			if($numb_events >= $config['number_of_events_threshold']){
				$numb_events = 0;
				print('<fb:redirect url="'._SITE_URL.'limit_reached.php"/>');
				sleep(5);
			}
			try{
				$facebook->api_client->events_cancel($row['event_id']);
				$numb_events++;
			}catch(Exception $e){
				$error = $e->getMessage().' Error code:'.$e->getCode();
				trigger_error($error);
			}
		}
		
		//remove them in the db
		mysql_query("DELETE FROM user$user_id WHERE from_url='$url'");
		mysql_query("DELETE FROM subscriptions WHERE sub_id='$sub_id'");
		
		echo '<fb:success><fb:message>You have successfully unsubscribed and all events on facebook from that subscription have been removed.</fb:message></fb:success>';
}

?>

<script type="text/javascript">
<!--
function show(id){
	var search=document.getElementById(id).getStyle('display');
	if(search == 'block')    {
		document.getElementById(id).setStyle('display','none');
		document.getElementById("subscriptions").setStyle('minHeight', '');
	}
	else{
		document.getElementById(id).setStyle('display','block');
		document.getElementById("subscriptions").setStyle('minHeight', '75em');
	}
}

function unsubscribe(sub_id, sub_name){
	var id = "unsubscribe_dialog";
	//set text in dialog
	text = "<p>Are you sure you want to unsubscribe from calendar <i>"+sub_name+"</i>? Do you also want to remove all events of that subscription from facebook?</p>";
	document.getElementById("unsub_text").setInnerXHTML(text);
	text = "<h2 class=\"dialog_head_text\">Unsubscribe from calendar <i>"+sub_name+"</i></h2>";
	document.getElementById("unsub_head").setInnerXHTML(text);

	//set links in dialog	
	document.getElementById("unsub_remove").setValue(sub_id);
	document.getElementById("only_unsub").setValue(sub_id);

	//make dialog visible
	document.getElementById(id).setStyle('display','block');
}
//--> 
</script> 

<div id="unsubscribe_dialog" class="dialog" style="display:none;">
	<div class="dialog_head" id="unsub_head"></div>
	<div class="dialog_body" id="unsub_text"></div>
	<div class="dialog_button_area">
		<input type="button" value="Cancel" onclick="document.getElementById('unsubscribe_dialog').setStyle('display','none');" class="dialog_buttons" />
		<form action="<?php echo _SITE_URL; ?>"><input type="text" id="unsub_remove" name="unsub_remove" style="display:none;" /><input type="submit" value="Unsubscribe and remove all events from facebook" class="dialog_buttons"></form>
		<form action="<?php echo _SITE_URL; ?>"><input type="text" id="only_unsub" name="only_unsub" style="display:none;"/><input type="submit" value="Only unsubscribe" class="dialog_buttons" /></form>
</div>
</div>

<div>
	<!--GUI: URL-SUBMIT FORM-->
	<form promptpermission="offline_access,create_event" method="get">
		<p>You can subscribe to a calendar which supports the <a href="http://en.wikipedia.org/wiki/ICalendar" target="_blank">iCalendar format</a>. This calendar will then be periodically checked for new events:</p>
	<div id="fields">
		<div class="box">
			<div class="inputs">
				<p>Name of subscription (anything meaningful):</p>
				<input type="text" name="sub_name" size="20" maxlength="30">
			</div>
			
			<div class="inputs">
				<p>URL/Web address:</p>
				<input type="text" name="url" size="40">
			</div>
			
			<div class="inputs" id="subscribe">
				<input type="submit" value="Subscribe">
				<?php
					if(!isset($_GET['fb_perms']) && $no_perms){
						echo '<p>Upon submitting the form, you will be prompted to grant this app permission to create events even when you are not on facebook.</p>';
					}
				?>
			</div>
		</div>
		<div class="box"><p>Facebook requires you to assign a category and subcategory to your events. Choose those that best fit your calendar or let them default to category: <i>Other</i> and subcategory: <i>Office Hours</i>.</p>
			<div class="categories">
				<div class="textfield">Category:</div><input type="text" name="category" size="1" maxlength="2" value="8"><br/>
				<div class="textfield">Subcategory:</div><input type="text" name="subcategory" size="1" maxlength="2" value="29"><br/>
			</div>
			<div id="catlistlink"><a href="#" onClick="show('catlist')">Categories</a></div>
			<div id="catlist" style="display:none;">
				<div class="cats">
				<h3>Categories</h3>
				<ul><li> 1: Party
				</li><li> 2: Causes
				</li><li> 3: Education
				</li><li> 4: Meetings
				</li><li> 5: Music/Arts
				</li><li> 6: Sports
				</li><li> 7: Trips
				</li><li> 8: Other
				</li></ul>
				</div>
				<div class="subcats">
				<h3>Subcategories</h3>
				<ul><li> 1: Birthday Party
				</li><li> 2: Cocktail Party
				</li><li> 3: Club Party
				</li><li> 4: Potluck
				</li><li> 5: Fraternity/Sorority Party
				</li><li> 6: Business Meeting
				</li><li> 7: Barbecue
				</li><li> 8: Card Night
				</li><li> 9: Dinner Party
				</li><li> 10: Holiday Party
				</li><li> 11: Night of Mayhem
				</li><li> 12: Movie/TV Night
				</li><li> 13: Drinking Games
				</li><li> 14: Bar Night
				</li><li> 15: LAN Party
				</li><li> 16: Brunch
				</li><li> 17: Mixer
				</li><li> 18: Slumber Party
				</li><li> 19: Erotic Party
				</li><li> 20: Benefit
				</li><li> 21: Goodbye Party
				</li><li> 22: House Party
				</li><li> 23: Reunion
				</li><li> 24: Fundraiser
				</li><li> 25: Protest
				</li><li> 26: Rally
				</li><li> 27: Class
				</li><li> 28: Lecture
				</li><li> 29: Office Hours
				</li><li> 30: Workshop
				</li><li> 31: Club/Group Meeting
				</li><li> 32: Convention
				</li><li> 33: Dorm/House Meeting
				</li><li> 34: Informational Meeting
				</li><li> 35: Audition
				</li><li> 36: Exhibit
				</li><li> 37: Jam Session
				</li><li> 38: Listening Party
				</li><li> 39: Opening
				</li><li> 40: Performance
				</li><li> 41: Preview
				</li><li> 42: Recital
				</li><li> 43: Rehearsal
				</li><li> 44: Pep Rally
				</li><li> 45: Pick-Up
				</li><li> 46: Sporting Event
				</li><li> 47: Sports Practice
				</li><li> 48: Tournament
				</li><li> 49: Camping Trip
				</li><li> 50: Daytrip
				</li><li> 51: Group Trip
				</li><li> 52: Roadtrip
				</li><li> 53: Carnival
				</li><li> 54: Ceremony
				</li><li> 55: Festival
				</li><li> 56: Flea Market
				</li><li> 57: Retail
				</li><li> 58: Wedding
				</li></ul>
				</div>
			</div>
			<div id="groupid">
				<p>If you want to create these events for a facebook group or page, enter its id here. (check the web-address of the grouppage for gid=XXX)</p>
				<input type="text" name="page_id" size="10" value="<?php if(isset($_GET['fb_page_id'])){echo $_GET['fb_page_id'];} ?>">
			</div>
		</div>
	</div>
	</form>
</div>


<div id="subscriptions">

<?php
/////////////////////////////////
// DRAW GUI: SUBSCRIPTIONS
/////////////////////////////////

$urls = mysql_query("select * from subscriptions where user_id ='$user_id'") or trigger_error(mysql_error());
if (mysql_num_rows($urls) > 0){
	echo '<table id="subscription_table"><tbody>';
	echo '<tr class="table_heading"><th class="name"><h3>Subscription Name</h3></th><th class="tb_category">Category</th><th class="tb_subcategory">Subcategory</th><th class="tb_group">Group</th><th class="unsubscribe"></th></tr>';
	//<th class="edit"></th>
	
	$result = mysql_query("SELECT * FROM subscriptions where user_id='$user_id'");
	while($row = mysql_fetch_array($result)){
		if($row['page_id'] > 0)
			$td_group = '<td><a href="http://www.facebook.com/group.php?gid=' . $row['page_id'] . '">' . $row['page_id'] . '</a></td>';
		else
			$td_group = "<td>–</td>";
		echo '<tr class="subs_row">';
		print('<td><h3 title=' . $row['url'] . ">" . $row['sub_name'] . '</h3></td>');
		print('<td>' . $row['category'] . '</td>');
		print('<td>' . $row['subcategory'] . '</td>');
		echo $td_group;
		//print('<td><a href="'._SITE_URL.'?msg&edit='.$row['sub_id'].'">Edit</a></td>');
		print('<td><a href="#" onClick="unsubscribe(\''.$row['sub_id'].'\',\''.$row['sub_name'].'\')">Unsubscribe</a></td>');
		echo "</tr>";
	}
	echo "</tbody></table>";
}

?>

</div>

<?php
/////////////////////////////////
// PARSE NEW SUBSCRIPTION
/////////////////////////////////
if (!$no_perms && isset($_GET['url'])){
	$url = $_GET['url'];
	$page_id = $_GET['page_id'];
	
	//check subscription name
	if (mb_strlen($_GET['sub_name']) == 0){
		print('<fb:redirect url="'._SITE_URL.'?err&no_sub_name"/>');
		exit;
	}
	else{
		$sub_name = $_GET['sub_name'];
	}
	
	//check category
	if ($_GET['category'] == "" || $_GET['subcategory'] == ""){
		print('<fb:redirect url="'._SITE_URL.'?err&no_category"/>');
		exit;
	}
	else{
		$category = $_GET['category'];
		$subcategory = $_GET['subcategory'];
	}

	//put subscription specific data in $sub_data array
	$sub_data = array("url" => $url, "user_id" => $user_id, "category" => $category, "subcategory" => $subcategory, "page_id" => $_GET['page_id']);

	$calendar  = new Calendar($sub_data);

	//check url
	if (!$calendar->url_valid()){
		print('<fb:redirect url="'._SITE_URL.'?err&url-not-correct"/>');
		exit;
	}
	
	//check file
	if (!$calendar->file_valid()){
		print('<fb:redirect url="'._SITE_URL.'?err&file-not-valid"/>');
		exit;
	}
	if (!$calendar->file_valid_event()){
		print('<fb:redirect url="'._SITE_URL.'?err&file-no-event"/>');
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
		print('<fb:redirect url="'._SITE_URL.'?err&old-url"/>');
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
	if ($numb_events > 0){
		print('<fb:redirect url="'._SITE_URL.'?msg&success='.$numb_events.'"/>');
	}
}



mysql_close($con);
?>