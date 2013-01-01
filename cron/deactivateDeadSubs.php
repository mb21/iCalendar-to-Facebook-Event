<?php

chdir("..");

require_once 'include/initialize.php';

try {
	$database->deactivateDeadSubs();
} catch (Exception $e) {
	$logger->error("deactivateDeadSubs.php failed", $e);
}

?>
