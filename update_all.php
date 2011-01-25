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
	require_once 'classes/' . $class_name . '.php';
}

ini_set('max_execution_time', 800);
ignore_user_abort(true);

$execution_time = time();


/////////////////////////////////
// CONFIG
//$argv[1] = 1;
$log_file = false;
$blacklist = array(2473, 170, 171, 2700, 3011, 3115);
/////////////////////////////////

//this job is run with argument 0 on hours 0,4,8,12,16,20
//this job is run with argument 1 on hours 2,6,10,14,18,22

//update all
$result = mysql_query("SELECT * FROM subscriptions WHERE active > 0") or trigger_error(mysql_error());
$half = (int) (mysql_num_rows($result) / 2);

if ($argv[1] == 0){
	//command line argument was 0 -> update the first half of subscriptions
	$result = mysql_query("SELECT * FROM subscriptions WHERE active > 0 LIMIT 0, $half") or trigger_error(mysql_error());
}
else{
	//command line argument was 1 -> update the second half of subscriptions
	$length = $half + 10;
	$result = mysql_query("SELECT * FROM subscriptions WHERE active > 0 LIMIT $half, $length") or trigger_error(mysql_error());
}

$numb_events = 0;
$numb_subs = 0;
$numb_file_too_large_errors = 0;
$numb_valid_file_errors = 0;
$numb_session_key_errors = 0;
$numb_session_key_in_db_errors = 0;
$numb_perms_errors = 0;
$numb_subs_with_an_error = 0;

while($row = mysql_fetch_assoc($result)) {
	$sub_id = $row['sub_id'];
	try {
		if ($log_file){
			//write to log what file was last tryed (maybe before fatal error)
			$running = (int) ((time() - $execution_time) / 60);
			
			$file = "trying.txt";
			$fh = fopen($file, 'a') or die("can't open file"); //append
			$write = "mem_usage: " . memory_get_usage() . " " . date("r") . " sub_id: " . $row['sub_id'] . " - running for " . $running . "min \r\n" ;
			fwrite($fh, $write);
			fclose($fh);
		}
				
		$numb_subs++;
		
		if ( !in_array($sub_id, $blacklist) ){
			$calendar  = new Calendar(NULL, $row);
			$newevs = $calendar->update();
			$numb_events = $numb_events + $newevs;
		}
		
		//log successful update
		$time_now = time();
		mysql_query("UPDATE subscriptions set last_successful_update = '$time_now' WHERE sub_id = $sub_id");
		
	}catch(Exception $e) {
		$error = '<strong>'.$e->getMessage().'</strong><br/>Error code:'.$e->getCode()."<br/>File:".$e->getFile()."<br/>Line:".$e->getLine()."<br/>Trace: ".$e->getTraceAsString();
		echo $error; //is caught below by buffer and written into database
	}

	$buffer = ob_get_contents();
	ob_clean();

	if (!empty($buffer)){
		$numb_subs_with_an_error++;
		
		$pos = strpos($buffer, "iCalendar file too large");
		if(!($pos === FALSE))
			$numb_file_too_large_errors++;
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

		$buffer = mysql_real_escape_string(date('r')."<br/>".$buffer);
		mysql_query("UPDATE subscriptions SET error_log='$buffer' WHERE sub_id='$sub_id'");
	}
}
ob_end_clean();

$s = time() - $execution_time;

$d = intval($s/86400);
$s -= $d*86400;

$h = intval($s/3600);
$s -= $h*3600;

$m = intval($s/60);
$s -= $m*60;

$str = "";
if ($d) $str .= $d . 'd ';
if ($h) $str .= $h . 'h ';
if ($m) $str .= $m . 'm ';
if ($s) $str .= $s . 's';

$execution_time = $str;

if ($log_file){
	//write to log that we are done
	$file = "trying.txt";
	$fh = fopen($file, 'a') or die("can't open file"); //append
	$write = "done in " . $execution_time . "\r\n" ;
	fwrite($fh, $write);
	fclose($fh);
}

$file = "log_". $argv[1] .".html";
$fh = fopen($file, 'w') or die("can't open file");
fwrite($fh, "<html><body><h3>Time update_all.php run last time completely: ".date('d.m.o H:s e')."</h3>
	<p>".$numb_subs." subscriptions checked, ". $numb_events . " events created or updated.</p>
	<p>".$numb_subs_with_an_error." subscriptions that have thrown an error.</p>
	<ul>
		<li>File too large: ".$numb_file_too_large_errors."</li>
		<li>File invalid/parse error: ".$numb_valid_file_errors."</li>
		<li>Session key invalid: ".$numb_session_key_errors."</li>
		<li>No session key in db: ".$numb_session_key_in_db_errors."</li>
		<li>Permissions error: ".$numb_perms_errors."</li>
	</ul>
	<p>Execution time: ".$execution_time."</p>
	</body></html>");
fclose($fh);

mysql_close($con);
?>