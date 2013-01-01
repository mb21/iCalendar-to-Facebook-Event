<?php

/**
 * Redirects the user to the facebook login page. after that he comes back..
 * Here the facebook permissions are requested
 *
 * @author maurobieg
 */


$loginUrl = $facebook->getLoginUrl(array(
	'canvas' => 1,
	'fbconnect' => 0,
	'scope' => 'offline_access,create_event,manage_pages,user_groups'
	//'redirect_uri' => ""
));

header("Location: " . $loginUrl); 

?>
