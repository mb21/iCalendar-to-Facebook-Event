<?php
mb_internal_encoding('UTF-8');

//after filling in your data, change the file name of this file to 'config.php'

//canvas url
define('_SITE_URL','http://apps.facebook.com/test-ics-to-event/'); // your Canvas URL
define('_HOST_URL','http://ics-to-fbevent.project21.ch/'); // your Canvas Callback URL

//facebook
$appapikey = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$appsecret = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

//database
$database_name = 'ics-to-fbevent';
$host = 'localhost';
$db_user = '';
$db_password = '';



////////////////////
// config parameters
////////////////////

//do not add more than "number" events at the same time to not overstretch the facebook limits
$config['number_of_events_threshold'] = 19; 

//time to sleep in seconds before processing more events (fb limits)
$config['sleep_time'] = 10;

//event is not added if it is older than now "-1 month"
$config['old_event_threshold'] = "-1 month";

//maximal length of event title, rest will be cropped (facebook doesn't allow more than ~70)
$config['max_length_title'] = 70;


// translation of wall post caption
// http://wiki.developers.facebook.com/index.php/Facebook_Locales
$config['wall_text'] = array(
	"de_DE" => "hat diese neue Veranstaltung erstellt. Kommst du auch?",
	"en_GB" => "posted this new event. Are you coming too?",
	"en_US" => "posted this new event. Are you coming too?",
	"en_UD" => "posted this new event. Are you coming too?"
	);

$config['text_time'] = array(
	"de_DE" => "Zeit",
	"en_GB" => "Time",
	"en_US" => "Time",
	"en_UD" => "Time"
	);
	
?>