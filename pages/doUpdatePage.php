<?php

try {
	if( !isset($_GET['subId']) )
		exit;
	
	$controller = new Controller();
	$controller->updateSub($_GET['subId']);
	
	$msg = urlencode('Subscription updated successfully.');
	header("Location: " . 'index.php?action=showSubscriptionList&success=1&successMsg=' . $msg);
	
} catch (Exception $e) {
	
        if ($config['debugMode']) {
                $logger->warning("Could not update subscription manually.", $e);
        }
        
	$errorMsg = urlencode($e->getMessage());
	header("Location: " . 'index.php?action=showSubscriptionList&error=1&errorMsg=' . $errorMsg);
}

?>
