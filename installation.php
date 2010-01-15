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

require 'config.php';
//Connect to Database
$con = mysql_connect($host,$db_user,$db_password);
if (!$con)
	die('Could not connect: '. mysql_error());
mysql_select_db($database_name,$con);
mysql_query("SET NAMES 'utf8';", $con);
mysql_query("SET CHARACTER SET 'utf8';", $con);

$sql = 'CREATE TABLE subscriptions
(
sub_id int NOT NULL AUTO_INCREMENT,
sub_name varchar(30),
PRIMARY KEY(sub_id),
url varchar(300),
category int,
subcategory int,
page_id bigint,
user_id bigint,
picture_path varchar(30)
)';
if (!mysql_query($sql)){
	echo "Error creating table: " . mysql_error();
}
else{
	echo "table subscriptions created<br>";
}

$sql = 'CREATE TABLE users
(
user_id bigint NOT NULL,
PRIMARY KEY(user_id),
session_key char(80)
)';
if (!mysql_query($sql)){
	echo "Error creating table: " . mysql_error();
}
else{
	echo "table users created<br>";
}

echo "installation finished";
?>