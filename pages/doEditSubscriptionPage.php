<?php

try {
	
	$database->updateSubscriptionData($_POST, $fbUserId);
	
	$msg = urlencode('Subscription was changed successfully!');
	header("Location: " . 'index.php?action=showSubscriptionList&success=1&successMsg=' . $msg);
	
} catch (Exception $e) {
	
	$errorMsg = urlencode('<p>Could not change subscription because there was an error.</p>' . $e->getMessage());
	header("Location: " . 'index.php?action=showSubscribeToiCalendar&editSub=1&error=1&errorMsg=' . $errorMsg . $fields);
}

?>
