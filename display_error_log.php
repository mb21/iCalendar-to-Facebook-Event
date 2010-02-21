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

echo '<head><link type="text/css" rel="stylesheet" href="'._HOST_URL.'styles.css"></head>';
?>

<p>iCalendar to Event – <a href="<?php echo _SITE_URL;?>Docs.php">Help/Documentation</a></p>
<br/>
<?php

$result = mysql_query("SELECT * FROM subscriptions WHERE user_id='$user_id' AND error_log IS NOT NULL");
if (mysql_num_rows($result) == 0){
	echo "There are no errors logged.";
}
else{
?>
<table id="subscription_table">
	<tbody>
		<tr class="table_heading"><th class="name_error"><h3>Subscription Name</h3></th><th>sub_id</th><th>Group/Page</th><th class="error_logged">Last Error logged</th></tr>

<?php
	while($row = mysql_fetch_array($result)){
		echo "<tr>";
		echo "<td>".$row['sub_name']."</td>";
		echo "<td>".$row['sub_id']."</td>";
		echo "<td>". $row['page_id']."</td>";
		echo "<td>". $row['error_log']."</td>";
		echo "</tr>";
	}
?>
	</tbody>
</table>
<?php
}

?>
<br/><br/>
<p>Common errors are:</p>
<li>
	<ul><b>Permissions error.</b> Facebook is blocking you from creating/editing events. This is usually temporarily and may have one of several reasons: You have added/modified too many events too fast, or you entered a page/group id and don't have permission to publish events on that page (that is you're not an administrator), or because facebook <a href="http://bugs.developers.facebook.com/show_bug.cgi?id=8797" target="_blank">isn't functioning correctly</a>.</ul>
	<ul><b>Error when parsing file.</b> Check whether your URL is still pointing to a correct iCalendar file.</ul>
	<ul><b>Could not cancel event.</b> Does the event still exist? Or maybe also a permission problem.</ul>
	<ul><b>Session key.</b> Have you given this app permission to publish events? In the worst case, try removing and re-adding this app.</ul>
</li>
<br/>
<?php mysql_close($con); ?>
