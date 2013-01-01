<?php

$subs = $database->selectSubscriptions($fbUserId);

include 'headerInclude.php';



$token = array(
	'access_token' => $database->getAccessToken($fbUserId)
);
try {
	$userPages = $facebook->api('/me/accounts', 'GET', $token);
	$userGroups = $facebook->api('/me/groups', 'GET', $token);
	$userPagesLoaded = true;
	
	$pageNames = array();
	foreach ($userPages['data'] as $page) {
		if ($page['category'] == "Application")
			$page['name'] = "Application page with id " . $page['id'];
		$pageNames[ $page['id'] ] = $page['name'];
	}
	foreach ($userGroups['data'] as $group) {
		if ($group['administrator'] == "true") {
			$pageNames[ $group['id'] ] = $group['name'];
		}
	}
} catch (FacebookApiException $e) {
	$logger->error("Could not get list of user's pages for display in GUI.", $e);
	$userPagesLoaded = false;
}



if( isset($_GET['success']) ) {
	echo '<div class="successBox">';
	echo $_GET['successMsg'];
	echo '</div>';
} elseif( isset($_GET['error']) ) {
	echo '<div class="errorBox">';
	echo '	<p>There has been an error.</p>';
	echo $_GET['errorMsg'];
	
	if ( isset($_GET['showUnsubscribeAnywayButton']) && $_GET['subId']) {
		?>
		<p>You can still choose to delete the subscription but 
			this App will not be able to make another attempt at deleting the events listed above. 
			However deleting them manually directly on Facebook will always work.</p>
		<input type='button' value='Delete subscription anyway'
			onclick="location.href='index.php?action=doUnsubscribe&amp;doNotDeleteEvents&amp;subId=<?php echo $_GET['subId']; ?>';" />
		<?php
	}
	
	echo '</div>';
}

?>

<h2>Your Subscriptions</h2>

<table class="subTable">
	<thead>
		<tr>
			<th class="thName">Name</th>
			<th class="thPage">Page</th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th>State</th>
		</tr>
	</thead>
	<tbody>
<?php while ($row = $subs->fetch()) {
	echo '<tr>';
	
	echo '<td>'.$row->subName.'</td>';
	if ($row->fbPageId) {
		if ($userPagesLoaded)
			$name = $pageNames[ $row->fbPageId ];
		else
			$name = $row->fbPageId;
		echo '<td><a href="http://www.facebook.com/profile.php?id='.$row->fbPageId.'" target="_blank">'.$name.'</a></td>';
	} else {
		echo '<td><a href="http://www.facebook.com/profile.php?id='.$fbUserId.'" target="_blank">profile</a></td>';
	}
	
	echo '<td><a href="index.php?action=doUpdate&amp;subId='.$row->subId.'">Update now</a></td>';
	
	echo '<td><a href="index.php?action=showErrorLog&amp;subId='.$row->subId.'">Log</a></td>';
	
	echo '<td><a href="index.php?action=showSubscribeToiCalendar&amp;editSub&amp;subId='.$row->subId.'">Edit</a></td>';
	
	$fnName = 'unsubscribe('.$row->subId.', \''.$row->subName.'\')';
	echo '<td><a href="#" onclick="'.$fnName.'">Delete</a></td>';
	
	$state = $row->active ? 'active' : '<span class="red">deactivated</span>';
	$fnName = $row->active ? 'deactivate' : 'activate';
	$fnName .= '('.$row->subId.', \''.$row->subName.'\')';
	echo '<td><a href="#" onclick="'.$fnName.'">'.$state.'</a></td>';
	
	echo '</tr>';
} 
?>
	</tbody>
</table>

<p><a href="index.php?action=showSubscribeToiCalendar">Subscribe a new calendar</a></p>

<?php include 'donateInclude.php'; ?>

<div class="hoverDialog" style="display: none"></div>


</div>

<div class="background" style="display: none"></div>

	</body>
</html>