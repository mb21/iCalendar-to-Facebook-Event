<?php

chdir("..");

require_once 'include/initialize.php';

$subs = $database->selectAllActiveSubIds();

chdir("cron");

while ( $row = $subs->fetch() ) {
	echo exec($config['phpCommand'] . ' updateSub.php ' . $row->subId); // . '>/dev/null 2>&1'
}

//delete new events in db that couldn't be created on fb
$database->cleanUpNewEvents();

?>

