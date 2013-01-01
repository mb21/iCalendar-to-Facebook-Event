<?php

try {
	if( !isset($_GET['subId']) )
		exit;
	
	$subId = $_GET['subId'];
	
	$logger->setCurrentSubId($subId);
	
	$STH = $database->selectUserIdAndAccessToken($subId);
	$row = $STH->fetch();
	$subUserId = $row->fbUserId;
	$token = $row->fbAccessToken;
	
	if ($subUserId != $fbUserId)
		exit;
	
	if( !isset($_GET['doNotDeleteEvents']) ) {
		
		$STH = $database->selectAllEvents($subId);

		$notDeleted = array();

		$thereIsAnotherRow = ( $row = $STH->fetch() );
		while($thereIsAnotherRow) {
			$logger->setCurrentOurEventId($row->ourEventId);

			$tokenArray = array(
			    'access_token' => $token
			);

			try {
				$success = $facebook->api('/' . $row->fbEventId, "DELETE", $tokenArray);

				if ($success) {
					$database->setEventDeleted($row->ourEventId);
					$logger->info("Event deleted on facebook. fbEventId: " . $row->fbEventId);
				} else {
					throw Exception("fb exception");
				}
			} catch (Exception $e) {
				$logger->warning("Event could not be deleted on Facebook.", $e);

				array_push($notDeleted, $row->fbEventId);
			}

			$logger->unsetCurrentOurEventId();

			$thereIsAnotherRow = $row = $STH->fetch();
			if ($thereIsAnotherRow)
				sleep($config['waitForNextEventPublish']);
		}
		
	} else {
		// IF not doNotDeleteEvents
		// i.e. if no delete events from facebook but only form db
		$database->deleteEvents($subId);
	}
	
	
	if( empty($notDeleted) ) {
		$database->deleteSubscription($subId);
		$msg = urlencode('Deleted subscription successfully.');
		header("Location: " . 'index.php?action=showSubscriptionList&success=1&successMsg=' . $msg);
	} else {
		
		$errorMsg = "<p>The following events could not be deleted.</p> <ul>";
		foreach ($notDeleted as $fbEventId) {
			$errorMsg .= "<li><a href='http://www.facebook.com/event.php?eid="
				. $fbEventId."' target='_blank'>".$fbEventId."</a></li>";
		}
		$errorMsg .= "</ul>";
		header("Location: " . 'index.php?action=showSubscriptionList&error=1&showUnsubscribeAnywayButton=1&subId='
			.$subId.'&errorMsg='.urlencode($errorMsg) );
	}
	
} catch (Exception $e) {
	//send error back to user
	
	$errorMsg = urlencode($e->getMessage());
	header("Location: " . 'index.php?action=showSubscriptionList&error=1&errorMsg=' . $errorMsg);
}

?>
