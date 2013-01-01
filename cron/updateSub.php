<?php
/*
 * this script updates the sub with the subId that is provided as command line argument
 */

chdir("..");

require_once 'include/initialize.php';

$propagateExceptions = false;

try {
	if ( isset($argv[1]) ) {
		$subId = $argv[1];
	} elseif ( isset($_GET['subId']) ) {
		$subId = $_GET['subId'];
	} else {
		exit;
	}

	$controller = new Controller();

	$controller->updateSub($subId);
	
} catch (Exception $e) {
	$logger->error("updateSub.php could not update subscription", $e);
}

?>
