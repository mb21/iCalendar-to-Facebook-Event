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

$publish_perm = $facebook->api_client->users_hasAppPermission('publish_stream');
$rsvp_perm = $facebook->api_client->users_hasAppPermission('rsvp_event');

$require_pubish = ( isset($_POST['wall']) || ($_POST['uploadedfile'] != '') );

if ($_POST['rsvp'] != 'attending' && !$rsvp_perm){
    echo "{'msg':'rsvp'}";
}
elseif ($require_pubish && !$publish_perm) {
    echo "{'msg':'publish'}";
}
else{
    echo "{'msg':'success'}";
}

?>
