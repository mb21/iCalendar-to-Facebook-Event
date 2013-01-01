<?php

/**
 * Tries to subscribe the calendar.
 *
 * @author maurobieg
 */


try {
	
	// CHECK INPUT
	
	if ( !isset($_POST['subName']) || mb_strlen($_POST['subName']) == 0) {
		throw new Exception("You need to enter a name for your subscription.");
	}
	
	$urlregex = '/^(https?|ftp):\/\/(\S*)\Z/';
	if ( !isset($_POST['iCalendarUrl']) || mb_strlen($_POST['iCalendarUrl']) == 0 
		   || ! preg_match($urlregex, trim($_POST['iCalendarUrl'])) ) {
		
		throw new Exception("You need to enter a valid URL starting with http://");
	}
	
	
	//check whether the iCalendar can be parsed
	$controller = new Controller();
	$controller->checkUrl($_POST['iCalendarUrl']);
	
	$subId = $database->insertSubscription($_POST['subName'], $fbUserId, $_POST['iCalendarUrl'], $_POST['pageId'], $_POST['imageProperty']);
	
	$controller->updateSub($subId);
	
	//send user to subscription list
	$msg = urlencode('Subscription was added successfully!');
	header("Location: " . 'index.php?action=showSubscriptionList&success=1&successMsg=' . $msg);
	
} catch (Exception $e) {
	//send error back to user
	
	if ($config['debugMode'])
		$logger->error("doSubscribePage: ", $e);
	
	$subId = $logger->getCurrentSubId();
	if( $database->hasSuccessfulImport($subId) ) {
		//subscription is added to db but there went something wrong during publishing to fb
		
		$errorMsg = urlencode('<p>Subscription added successfully but there was an error when trying to post an event to Facebook.</p>' . $e->getMessage());
		header("Location: " . 'index.php?action=showSubscriptionList&error=1&errorMsg=' . $errorMsg);
	} else {
		//could not add subscription
		
		$fields = '';
		foreach ($_POST as $key => $value)
			$fields .= '&'.$key.'='.$value;
		
		$errorMsg = urlencode('<p>Could not subscribe this calendar because there was an error.</p>' . $e->getMessage());
		header("Location: " . 'index.php?action=showSubscribeToiCalendar&error=1&errorMsg=' . $errorMsg . $fields);
	}
}


?>