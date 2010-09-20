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

//This file checks permissions before update of subscription

mb_internal_encoding('UTF-8');
/////////////////////////////////
// CONFIGURATION
/////////////////////////////////
require_once 'config.php';
require_once 'facebook/facebook.php';

$facebook = new Facebook($appapikey, $appsecret);
$user_id = $facebook->require_login();

$require_publish = FALSE;
if (isset($_POST['wall']) ) {

	try {
		try{
			$is_group = $facebook->api_client->groups_get(NULL, $_POST['page_id']);
		}
		catch (Exception $e){
			$is_group = FALSE;
		}
		
		if (!$is_group) {
			//$page_id is a page_id not a group_id

			$require_publish = TRUE;
			if ($_POST['page_id'] > 0) {
				$publish_perm = $facebook->api_client->users_hasAppPermission('publish_stream', $_POST['page_id']);
			}
		}
		elseif ($_POST['page_id'] == "") {
			$require_publish = TRUE;
			$publish_perm = $facebook->api_client->users_hasAppPermission('publish_stream');
		}
	}
	catch (Exception $e) {
		echo "{'msg':'publish'}";
		//echo $e->getMessage() . $e->getTraceAsString();
		exit;
	}
}

//$rsvp_perm = $facebook->api_client->users_hasAppPermission('rsvp_event');

//if ($_POST['rsvp'] != 'attending' && !$rsvp_perm){
//    echo "{'msg':'rsvp'}";
//}
if ($require_publish && !$publish_perm) {
	echo "{'msg':'publish'}";
}
else {
	echo "{'msg':'success'}";
}

?>
