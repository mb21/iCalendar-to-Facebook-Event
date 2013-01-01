<?php

try {
	if( !isset($_GET['subId']) )
		exit;
	
	$logger->setCurrentSubId($_GET['subId']);
	
	$database->deactivate( $_GET['subId'], $fbUserId );
	
	header("Location: index.php?action=showSubscriptionList");
	
} catch (Exception $e) {
	//send error back to user
	
	$errorMsg = urlencode($e->getMessage());
	header("Location: index.php?action=showSubscriptionList&error=1&errorMsg=" . $errorMsg);
}

?>