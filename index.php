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

// EXTERNAL FILES
//echo '<head><link type="text/css" rel="stylesheet" href="'._HOST_URL.'styles.css"><script src="'._HOST_URL.'scripts.js"></script></head>';

//for testing the following code circumvents facebook's cache
echo "<head><style>";
echo htmlentities(file_get_contents('styles.css', true));
echo '</style><script type="text/javascript">';
include 'scripts.js.php';
echo "</script></head>";


//whether offline_access and create_event permissions are set
$perms = $facebook->api_client->users_hasAppPermission('offline_access') && $facebook->api_client->users_hasAppPermission('create_event');

//store session_key in db
if (isset($_POST["fb_sig_session_key"]) && $perms) {
	$session_key = $_POST["fb_sig_session_key"];

	$user_key = mysql_query("select session_key from users where user_id ='$user_id'") or trigger_error(mysql_error());
	if (mysql_num_rows($user_key) == 0) {
		mysql_query("INSERT INTO users (user_id, session_key) VALUES ('$user_id', '$session_key')") or trigger_error(mysql_error());
	}
	elseif(mysql_result($user_key, 0) != $session_key) {
		mysql_query("UPDATE users SET session_key='$session_key' WHERE user_id='$user_id'") or trigger_error(mysql_error());
	}
}


/////////////////////////////////
// GUI
/////////////////////////////////
?>
<fb:user-agent includes="ie">
	<strong>This app doesn't work in Internet Explorer 5 to 8. Please use a decent browser like <a href="http://getfirefox.com" target="_blank">Firefox</a>, <a href="http://www.apple.com/safari/" target="_blank">Safari</a> or <a href="http://www.google.com/chrome" target="_blank">Google Chrome</a>.</strong>
</fb:user-agent>


<fb:js-string var="cats_string.e1">
	<option value="">Select type:</option><option value="1">Birthday Party</option><option value="2">Cocktail Party</option><option value="3">Club Party</option><option value="5">Student House Party/Halls Party</option><option value="7">Barbecue</option><option value="8">Card Night</option><option value="9">Dinner Party</option><option value="10">Holiday Party</option><option value="11">Night of Mayhem</option><option value="12">Film/TV Night</option><option value="13">Drinking Games</option><option value="14">Bar Night/Pub Crawl</option><option value="15">LAN Party</option><option value="17">Mixer</option><option value="18">Sleepover</option><option value="19">Erotic Party</option><option value="20">Benefit</option><option value="21">Farewell Party</option><option value="22">House Party</option><option value="23">Reunion</option>
</fb:js-string>
<fb:js-string var="cats_string.e2">
	<option value="">Select type:</option><option value="24">Fundraiser</option><option value="25">Protest</option><option value="26">Rally</option>
</fb:js-string>
<fb:js-string var="cats_string.e3">
	<option value="">Select type:</option><option value="16">Study Group</option><option value="27">Class</option><option value="28">Lecture</option><option value="29">Office Hours</option><option value="30">Workshop</option>
</fb:js-string>
<fb:js-string var="cats_string.e4">
	<option value="">Select type:</option><option value="6">Business Meeting</option><option value="31">Club/Group Meeting</option><option value="32">Convention</option><option value="33">Hall/House Meeting</option><option value="34">Informational Meeting</option>
</fb:js-string>
<fb:js-string var="cats_string.e5">
	<option value="">Select type:</option><option value="4">Concert</option><option value="35">Audition</option><option value="36">Exhibit</option><option value="37">Jam Session</option><option value="38">Listening Party</option><option value="39">Opening</option><option value="40">Performance</option><option value="41">Preview</option><option value="42">Recital</option><option value="43">Rehearsal</option>
</fb:js-string>
<fb:js-string var="cats_string.e6">
	<option value="">Select type:</option><option value="44">Pep Rally</option><option value="45">Pick-Up</option><option value="46">Sporting Event</option><option value="47">Sports Practice</option><option value="48">Tournament</option>
</fb:js-string>
<fb:js-string var="cats_string.e7">
	<option value="">Select type:</option><option value="49">Camping Trip</option><option value="50">Day Trip</option><option value="51">Group Trip</option><option value="52">Road Trip</option>
</fb:js-string>
<fb:js-string var="cats_string.e8">
	<option value="">Select type:</option><option value="53">Carnival/Fun Fair</option><option value="54">Ceremony</option><option value="55">Festival</option><option value="56">Flea Market</option><option value="57">Retail</option><option value="58">Wedding</option><option value="29">Office Hours</option>
</fb:js-string>

<fb:js-string var="perms_rsvp">
	<p>You need to give this app <fb:prompt-permission perms="rsvp_event">permission to change your RSVP</fb:prompt-permission> or change your settings.</p>
</fb:js-string>
<fb:js-string var="perms_publish">
	<div class="clean-error">If you selected <i>Wall</i> you need to give this app <a href="#" onclick="Facebook.showPermissionDialog('publish_stream', null, true);">permission to publish</a> to your wall or the wall of your fan page if you selected one.</div>
</fb:js-string>

<!-- UNSUBSCRIBE? DIALOG-->
<div id="unsubscribe_dialog" class="dialog" style="display:none;">
	<div class="dialog_head" id="unsub_head"></div>
	<div class="dialog_body" id="unsub_text"></div>
	<div class="dialog_button_area">
		<form id="unsub_form">
			<input type="button" value="Cancel" onclick="document.getElementById('unsubscribe_dialog').setStyle('display','none');" class="dialog_buttons" />
			<input type="submit" value="Unsubscribe and remove all events from facebook" class="dialog_buttons" onclick="unsubscribe('unsub_remove'); return false;">
			<input type="submit" value="Only unsubscribe" class="dialog_buttons" onclick="unsubscribe('only_unsub'); return false;"/>
			<input type="hidden" id="unsub_sub_id" name="unsub_sub_id" />
			<input type="hidden" id="unsub_mode" name="unsub_mode" />
		</form>
	</div>
</div>



<!--
<div class="adv_opt">
	<h4>Picture</h4>
	<p>Associate a picture with this subscription which then will be added to every event created instead of the question mark.</p>
	<fb:iframe src="<?php echo _HOST_URL; ?>picture_upload_iframe.php" scrolling="no" frameborder="0" height="300px"/>
</div>
-->


<div>
	<!-- URL-SUBMIT FORM -->
	<form id="sub_form" method="get" action="index.php" promptpermission="offline_access, create_event">
		<p>You can subscribe to a calendar which supports the <a href="http://en.wikipedia.org/wiki/ICalendar" target="_blank">iCalendar format</a>. This calendar will then be periodically checked for new events. <a href="<?php echo _SITE_URL;?>Docs.php" target="_blank">Help</a></p>
		<div id="fields">
			<div class="box">
				<div class="inputs">
					<p>Name of subscription (anything meaningful):</p>
					<input type="text" name="sub_name" size="20" maxlength="30" value="<?php if(isset($_GET['sub_name'])) echo $_GET['sub_name']; ?>" />
				</div>

				<div class="inputs">
					<p>URL/Web address:</p>
					<input type="text" name="url" size="40" value="<?php if(isset($_GET['url'])) echo $_GET['url']; ?>">
				</div>

				<div class="inputs" id="subscribe">
					<?php
					if (!$perms) {
						echo '<input type="submit" value="Subscribe">';
						echo '<p>Upon submitting the form, you will be prompted to grant this app permission to create events even when you are not on facebook.</p>';
					}
					else {
						echo '<input type="submit" value="Subscribe" onclick="do_submit(\'sub_form\',\'' . _HOST_URL . 'receive_sub.php\', \'messages\', \'function(data) {div_id.setInnerXHTML(data.msg); if(data.sub_name)insertSub(data);}\'); return false;">';
					}
					?>

				</div>
			</div>
			<div class="box"><p>Facebook requires you to assign a category and subcategory to your events. Choose those that best fit your calendar.</p>
				<div class="categories">
				Category: <select id="category_select" onchange="category_change();" name="category">
						<option value="1">Party</option>
						<option value="2">Causes</option>
						<option value="3">Education</option>
						<option value="4">Meetings</option>
						<option value="5">Music/arts</option>
						<option value="6">Sports</option>
						<option value="7">Trips</option>
						<option SELECTED value="8">Other</option>
					</select><br/>
				Subategory: <select id="subcategory_select" name="subcategory">
						<option value="">Select type:</option>
						<option value="53">Carnival/Fun Fair</option>
						<option value="54">Ceremony</option>
						<option value="55">Festival</option>
						<option value="56">Flea Market</option>
						<option value="57">Retail</option>
						<option value="58">Wedding</option>
						<option SELECTED value="29">Office Hours</option>
					</select>
				</div>

				<!-- ADVANCED OPTIONS-->
				<div id="options_link">
					<a href="#" onClick="show_options()">Advanced options</a>
				</div>
				<div id="options" style="display:none;">
					<div id="option_title">
					</div>

					<input type="hidden" name="sub_id" id="adv_sub_id"/>

					<div class="adv_opt" id="adv_subname">
						<h4>Name of subscription</h4>
						<input type="text" id="adv_sub_name" value="" name="adv_sub_name" size="20" maxlength="30" />
					</div>

					<div class="adv_opt" id="adv_cats">
						<h4>Categories</h4>
						<div class="categories">
						Category: <select id="adv_category_select" onchange="category_change('adv_');" name="adv_category">
								<option value="1">Party</option>
								<option value="2">Causes</option>
								<option value="3">Education</option>
								<option value="4">Meetings</option>
								<option value="5">Music/arts</option>
								<option value="6">Sports</option>
								<option value="7">Trips</option>
								<option SELECTED value="8">Other</option>
							</select><br/>
						Subcategory: <select id="adv_subcategory_select" name="adv_subcategory">
								<option value="">Select type:</option>
								<option value="53">Carnival/Fun Fair</option>
								<option value="54">Ceremony</option>
								<option value="55">Festival</option>
								<option value="56">Flea Market</option>
								<option value="57">Retail</option>
								<option value="58">Wedding</option>
								<option SELECTED value="29">Office Hours</option>
							</select>
						</div>

					</div>

					<div class="adv_opt" id="adv_page">
						<h4>Group/Page</h4>
						<p>If you want to create these events for a facebook group or (fan-)page, enter its ID here. <a href="<?php echo _SITE_URL; ?>Docs.php" target="_blank">Help</a></p>
						<input id="adv_page" type="text" name="page_id" size="25" value="<?php if(isset($_GET['fb_page_id'])) echo $_GET['fb_page_id']; ?>">
					</div>



					<!--
					<div class="adv_opt">
						<h4>Picture</h4>
											<p>Associate a picture with this subscription which then will be added to every event created instead of the question mark.</p>
											<input type="file" name="picture" size="25" name="uploadedfile" />
					</div>

					<div class="adv_opt">
						<h4>Privacy</h4>
											<input type="radio" name="privacy" value="open" CHECKED/>OPEN, events are open and visible to everyone.<br/>
											<input type="radio" name="privacy" value="closed" />CLOSED, events are visible to everyone but require an invitation.<br/>
											<input type="radio" name="privacy" value="secret" />SECRET, events are invisible to those who have not been invited.<br/>
					</div>

					<div class="adv_opt">
						<h4>RSVP</h4>
											<input type="radio" name="rsvp" value="attending" CHECKED/>You are attending your events.<br/>
											<input type="radio" name="rsvp" value="unsure" />You are unsure.<br/>
											<input type="radio" name="rsvp" value="declined" />You are not attending.<br/>
					<?php // if (!$facebook->api_client->users_hasAppPermission('rsvp_event')) {
					//	echo '<p>If you choose something else than <i>attending</i>, you need to give the app <fb:prompt-permission perms="rsvp_event">permission to change your RSVP</fb:prompt-permission>.</p>';
					//}
					//
					?>
					</div>
					-->
					<div class="adv_opt">
						<h4>Wall</h4>
						<input type="checkbox" name="wall" id="wall"/>Publish events on your profile wall (or that of the page/group).<br/>
					</div>

					<div class="bottom_box">
						<div id="adv_update">
							<p>Do you want the new settings only to affect new events created or do you also want to update all existing events of this subscription?</p>
							<input type="radio" name="adv_update" value="new" CHECKED />Affect only new events.
							<input type="radio" name="adv_update" value="update" />Update also existing events.
						</div>

						<div id="adv_msg_div">	
							<?php if (!$facebook->api_client->users_hasAppPermission('publish_stream')) {
								echo 'If you selected <b>Wall</b> you need to give the app <a href="#" onclick="Facebook.showPermissionDialog(\'publish_stream\', null, true);">permission to publish</a> to your wall or the wall of your fan page if you selected one.';
							}

							?>
						</div>
						<br>
						<input type="submit" value="OK" onClick="close_options(); return false;" />
						<input type="submit" id="adv_cancel" value="Cancel" onClick="toggle_view('options'); return false;" />
					</div>
				</div>

			</div>
		</div>
	</form>
</div>

<!-- div to place AJAX MESSAGES in -->
<div id="messages">

	<?php
	if (isset($_GET['fb_perms'])) {
		if (!$perms) {
			echo '<fb:error><fb:message>For this app to be able to create events, you need to give it permission to do so. Please resubmit the form.</fb:message></fb:error>';
		}
		else {
			echo '<fb:success><fb:message>Facebook permissions are now set correctly. Please review the form and resubmit it.</fb:message></fb:success> ';
		}
	}
	?>
</div>


<!-- SUBSCRIPTIONS TABLE-->
<div id="subscriptions">
	<table id="subscription_table"><tbody>
			<tr class="table_heading"><th class="name"><h3>Subscription Name</h3></th><th class="tb_category">Category</th><th class="tb_subcategory">Subcategory</th><th class="tb_group">Group/Page</th><th class="edit"></th><th class="remove"></th></tr>


		</tbody>
	</table>
</div>

<script type="text/javascript">
	<!--

<?php
//Print Subscriptions array
$result = mysql_query("select * from subscriptions where user_id ='$user_id'") or trigger_error(mysql_error());
if (mysql_num_rows($result) > 0) {
	$arr = array();
	while($obj = mysql_fetch_object($result)) {
		$arr[] = $obj;
	}
	echo "var subscriptions = " . json_encode($arr) . ";";
}
?>

	//draw subscription_table
	for (var i=0; i<subscriptions.length;i++){
		insertSub(subscriptions[i]);
	}
	//-->
</script>

<p>If something is not working as expected, you might want to check your <a href="<?php echo _SITE_URL;?>display_error_log.php">error log</a>.</p>
<br/>
<?php mysql_close($con); ?>