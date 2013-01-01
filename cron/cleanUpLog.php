<?php

chdir("..");

require_once 'include/initialize.php';

try {
	$database->cleanUpLog();
} catch (Exception $e) {
	$logger->error("cleanUpLog.php failed", $e);
}

?>
