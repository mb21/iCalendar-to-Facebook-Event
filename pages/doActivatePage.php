<?php

try {
	if( !isset($_GET['subId']) )
		exit;
	
	$logger->setCurrentSubId($_GET['subId']);
	
	$controller = new Controller();
	$controller->updateSub($_GET['subId']);
	
	$database->activate( $_GET['subId'], $fbUserId );
	
	$msg = urlencode('Subscription was reactivated successfully!');
	header("Location: index.php?action=showSubscriptionList&success=1&successMsg=" . $msg);
	
} catch (Exception $e) {
	//send error back to user
	
	$errorMsg = urlencode($e->getMessage());
	header("Location: index.php?action=showSubscriptionList&error=1&errorMsg=<p>Could not reactivate this subscription because there occurred an error when trying to update its events.</p>" . $errorMsg);
}

?>