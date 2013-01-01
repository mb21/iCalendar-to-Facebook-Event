<?php

require_once 'include/initialize.php';

if ($config['debugWithoutFacebook'])
	$fbUserId = 713344833;
else
	$fbUserId = $facebook->getUser();

$logger->setCurrentFbUserId($fbUserId);

if ($fbUserId) {
	//if user logged in store access token in db

	if (!$config['debugWithoutFacebook']) {
		$token = $facebook->getAccessToken();
		$database->storeAccessToken($token, $fbUserId);
	}
	
	$propagateExceptions = true;
	
	if ( isset($_GET['action']) ) {
		switch ($_GET['action']) {
			case "showSubscribeToiCalendar":
				require 'pages/subscribeToiCalendarPage.php';
				break;
			case "doUpdate":
				require 'pages/doUpdatePage.php';
				break;
			case "doSubscribe":
				require 'pages/doSubscribePage.php';
				break;
			case "doUnsubscribe":
				require 'pages/doUnsubscribePage.php';
				break;
			case "showErrorLog":
				require 'pages/showErrorLogPage.php';
				break;
			case "doEditSubscription":
				require 'pages/doEditSubscriptionPage.php';
				break;
			case "doDeactivate":
				require 'pages/doDeactivatePage.php';
				break;
			case "doActivate":
				require 'pages/doActivatePage.php';
				break;
			case "showDocs":
					require "pages/showDocs.php";
					break;
			case "showPolicy":
					require "pages/showPolicy.php";
					break;
			case "showPricing":
					require "pages/showPricing.php";
					break;
			default:
				//if $action == "showSubscriptionList"
				require 'pages/subscriptionListPage.php';
		}
	} else {
		require 'pages/subscriptionListPage.php';
	}
	
} else {
	//user not logged in yet	
	require 'pages/loginPage.php';
}
?>