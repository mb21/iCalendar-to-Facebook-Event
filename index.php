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
//echo '<head><link type="text/css" rel="stylesheet" href="'._HOST_URL.'styles.css"></head>';
//for testing the following code circumvents facebook's css cache
echo "<head><style>";
echo htmlentities(file_get_contents('styles.css', true));
echo "</style></head>";


//whether offline_access and create_event permissions are set
$perms = $facebook->api_client->users_hasAppPermission('offline_access') && $facebook->api_client->users_hasAppPermission('create_event');

//store session_key in db
if (isset($_POST["fb_sig_session_key"]) && isset($_GET['fb_perms']) && $facebook->api_client->users_hasAppPermission('offline_access')){
	$user_exists = mysql_query("select * from users where user_id ='$user_id'") or trigger_error(mysql_error());
	if (mysql_num_rows($user_exists) == 0){
		$session_key = $_POST["fb_sig_session_key"];
		mysql_query("INSERT INTO users (user_id, session_key) VALUES ('$user_id', '$session_key')") or trigger_error(mysql_error());
	}
}


/////////////////////////////////
// GUI
/////////////////////////////////
?>

<script type="text/javascript">
<!--

function do_submit(formname, posturl, rewriteid) {
	var div_id = document.getElementById(rewriteid);
	var form = document.getElementById(formname);
    
	//display loading bar
	div_id.setInnerXHTML('<img src="<?php echo _HOST_URL; ?>loader.gif" alt="Loading..."/>');
	
	var ajax = new Ajax();
	ajax.responseType = Ajax.JSON;
	ajax.requireLogin = true;
	ajax.ondone = function(data) {
	div_id.setInnerXHTML(data.msg);
		if (data.sub_name){
			insertSub(data);
		}
	}
	var formdata = form.serialize();
	ajax.post(posturl, formdata);
}

<?php
// Print Subscriptions array
$result = mysql_query("select * from subscriptions where user_id ='$user_id'") or trigger_error(mysql_error());
if (mysql_num_rows($result) > 0){
	$arr = array();
	while($obj = mysql_fetch_object($result)){
		$arr[] = $obj;
	}
	echo "var subscriptions = " . json_encode($arr) . ";";
}
?>

function insertSub(json_sub){
	var table = document.getElementById('subscription_table');
	var row = document.createElement('tr');
	row.setId('sub_'+json_sub.sub_id);
	
	var sub_name = document.createElement('td');
	row.appendChild(sub_name);
	sub_name.setInnerXHTML("<h3 title='"+ json_sub.url +"'>" + json_sub.sub_name + "</h3>");

	var category = document.createElement('td');
	row.appendChild(category);
	category.setInnerXHTML("<p>" + json_sub.category + "</p>");

	var subcategory = document.createElement('td');
	row.appendChild(subcategory);
	subcategory.setInnerXHTML("<p>" + json_sub.subcategory + "</p>");
	
	var group = document.createElement('td');
	row.appendChild(group);
	if (json_sub.page_id == 0){
		var text = "<p>–</p>";
	}
	else{
		var text = '<p><a href="http://www.facebook.com/group.php?gid=' + json_sub.page_id + '">' + json_sub.page_id + '</a></p>';
	}
	group.setInnerXHTML(text);
	
	var edit = document.createElement('td');
	row.appendChild(edit);
	edit.setInnerXHTML('<p><a href="#">Edit</a></p>');
	edit.addEventListener('click', function() { show(json_sub.sub_id, '3em') });
	
	var remove = document.createElement('td');
	row.appendChild(remove);
	remove.setInnerXHTML('<p><a href="#">Unsubscribe</a></p>');
	remove.addEventListener('click', function() { show_unsubscribe(json_sub.sub_id, json_sub.sub_name ) });
	
	table.appendChild(row);
}

function removeSub(sub_id){
	var row = document.getElementById('sub_'+sub_id);
	document.getElementById('subscription_table').removeChild(row);
}

function show(id, siteHeight){
	var search=document.getElementById(id).getStyle('display');
	if(search == 'block')    {
		document.getElementById(id).setStyle('display','none');
		document.getElementById("subscriptions").setStyle('minHeight', '');
	}
	else{
		document.getElementById(id).setStyle('display','block');
		document.getElementById("subscriptions").setStyle('minHeight', siteHeight);
	}
}

function show_unsubscribe(sub_id, sub_name){
	//put sub_id in hidden input field
	document.getElementById('unsub_sub_id').setValue(sub_id);
	
	var id = "unsubscribe_dialog";
	//set text in dialog
	text = "<p>Are you sure you want to unsubscribe from calendar <i>"+sub_name+"</i>? Do you also want to remove all events of that subscription from facebook?</p>";
	document.getElementById("unsub_text").setInnerXHTML(text);
	text = "<h2 class=\"dialog_head_text\">Unsubscribe from calendar <i>"+sub_name+"</i></h2>";
	document.getElementById("unsub_head").setInnerXHTML(text);

	//make dialog visible
	document.getElementById(id).setStyle('display','block');
}

function unsubscribe(mode) {
	document.getElementById('unsubscribe_dialog').setStyle('display','none');
	
	var posturl = '<?php echo _HOST_URL; ?>unsubscribe.php';
	var div_id = document.getElementById('messages');
	var form = document.getElementById('unsub_form');

	//display loading bar
	div_id.setInnerXHTML('<img src="<?php echo _HOST_URL; ?>loader.gif" alt="Loading..."/>');
	
	var ajax = new Ajax();
	ajax.responseType = Ajax.JSON;
	ajax.requireLogin = true;
	ajax.ondone = function(data) {
		div_id.setInnerXHTML(data.msg);
		if (data.success){
			removeSub(data.sub_id);
		}
	}
	document.getElementById('unsub_mode').setValue(mode);
	formdata = form.serialize();
	ajax.post(posturl, formdata);
}
//--> 
</script> 

<!-- UNSUBSCRIBE? DIALOG-->
<div id="unsubscribe_dialog" class="dialog" style="display:none;">
	<div class="dialog_head" id="unsub_head"></div>
	<div class="dialog_body" id="unsub_text"></div>
	<div class="dialog_button_area">
	<form id="unsub_form">
		<input type="button" value="Cancel" onclick="document.getElementById('unsubscribe_dialog').setStyle('display','none');" class="dialog_buttons" />
		<input type="submit" value="Unsubscribe and remove all events from facebook" class="dialog_buttons" onclick="unsubscribe('unsub_remove'); return false;">
		<input type="submit" value="Only unsubscribe" class="dialog_buttons" onclick="unsubscribe('only_unsub'); return false;"/>
		<input type="text" id="unsub_sub_id" name="unsub_sub_id" style="display:none;" />
		<input type="text" id="unsub_mode" name="unsub_mode" style="display:none;" />
	</form>
</div>
</div>

<div>
	<!-- URL-SUBMIT FORM -->
	<form id="sub_form" method="get" action="index.php" promptpermission="offline_access, create_event">
		<p>You can subscribe to a calendar which supports the <a href="http://en.wikipedia.org/wiki/ICalendar" target="_blank">iCalendar format</a>. This calendar will then be periodically checked for new events:</p>
	<div id="fields">
		<div class="box">
			<div class="inputs">
				<p>Name of subscription (anything meaningful):</p>
				<input type="text" name="sub_name" size="20" maxlength="30" value="<?php if(isset($_GET['sub_name'])) echo $_GET['sub_name']; ?>">
			</div>
			
			<div class="inputs">
				<p>URL/Web address:</p>
				<input type="text" name="url" size="40" value="<?php if(isset($_GET['url'])) echo $_GET['url']; ?>">
			</div>
			
			<div class="inputs" id="subscribe">
				<?php
				if (!$perms){
					echo '<input type="submit" value="Subscribe">';	
					echo '<p>Upon submitting the form, you will be prompted to grant this app permission to create events even when you are not on facebook.</p>';
				}
				else{
					echo '<input type="submit" value="Subscribe" onclick="do_submit(\'sub_form\',\'' . _HOST_URL . 'receive_sub.php\', \'messages\'); return false;">';
				}
				?>
				
			</div>
		</div>
		<div class="box"><p>Facebook requires you to assign a category and subcategory to your events. Choose those that best fit your calendar or let them default to category: <i>Other</i> and subcategory: <i>Office Hours</i>.</p>
			<div class="categories">
				<div class="textfield">Category:</div><input type="text" name="category" size="2" maxlength="2" value="<?php if(isset($_GET['category'])) echo $_GET['category']; else echo '8';?>"><br/>
				<div class="textfield">Subcategory:</div><input type="text" name="subcategory" size="2" maxlength="2" value="<?php if(isset($_GET['subcategory'])) echo $_GET['subcategory']; else echo '29';?>"><br/>
			</div>
			<div id="catlistlink"><a href="#" onClick="show('catlist', '75em')">Categories</a></div>
			<div id="catlist" style="display:none;">
				<a href="#" onClick="show('catlist', '75em')"><img class="close" src="<?php echo _HOST_URL; ?>close.png" alt="Close"/></a>
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
			
			<!-- ADVANCED OPTIONS-->
			<div id="options_link">
				<a href="#" onClick="show('options', '5em')">Advanced options</a>
			</div>
			<div id="options" style="display:none;">
				<a href="#" onClick="show('options', '5em')"><img class="close" src="<?php echo _HOST_URL; ?>close.png" alt="Close"/></a>
				<div class="container">
					<div id="groupid">
						<h4>Group/Page</h4>
						<p>If you want to create these events for a facebook group or (fan-)page, enter its id here. (check the web-address of the grouppage for gid=XXX)</p>
						<form id="advanced_sub_form">
							<input type="text" name="page_id" size="10" value="<?php if(isset($_GET['page_id'])) echo $_GET['page_id']; ?>">
						</form>
					</div>
				</div>
			</div>
			
		</div>
	</div>
	</form>
</div>

<!-- div to place AJAX MESSAGES in -->
<div id="messages">

<?php
if (isset($_GET['fb_perms'])){
	if (!$perms){
		echo '<fb:error><fb:message>For this app to be able to create events, you need to give it permission to do so. Please resubmit the form.</fb:message></fb:error>';
	}
	else{
		echo '<script type="text/javascript">;
		do_submit(\'sub_form\',\'' . _HOST_URL . 'receive_sub.php\', \'messages\');
		</script>';
	}
}
?>
</div>


<!-- SUBSCRIPTIONS TABLE-->
<div id="subscriptions">
<table id="subscription_table"><tbody>
	<tr class="table_heading"><th class="name"><h3>Subscription Name</h3></th><th class="tb_category">Category</th><th class="tb_subcategory">Subcategory</th><th class="tb_group">Group</th><th class="edit"></th><th class="remove"></th></tr>
	
		
</tbody>
</table>
</div>

<script type="text/javascript">
<!--
//draw subscription_table
for (var i=0; i<subscriptions.length;i++){
	insertSub(subscriptions[i]);
}
//--> 
</script> 

<?php mysql_close($con); ?>