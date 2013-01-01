<?php

/**
 * either subscribe a new calendar
 * or
 * update an existing subscription
 *
 * @author maurobieg
 */

if( isset($_GET['editSub']) && isset($_GET['subId']) ) {
	$editSub = true;
	$subId = $_GET['subId'];
	
	$sub = $database->getSubscription($subId, $fbUserId);
	
	$subName = $sub->subName;
	$imageProperty = $sub->imageProperty;
} else {
	$editSub = false;
	
	if( isset($_GET['subName']))
		$subName = $_GET['subName'];
	else
		$subName = '';
	
	if( isset($_GET['imageProperty']))
		$imageProperty = $_GET['imageProperty'];
	else
		$imageProperty = '';
	
	if( isset($_GET['iCalendarUrl']))
		$iCalendarUrl = $_GET['iCalendarUrl'];
	else
		$iCalendarUrl = '';
	
	if( isset($_GET['pageId']))
		$pageId = $_GET['pageId'];
	else
		$pageId = 0;
}

if(!$editSub) {
	//get the pages of the user
	$token = array(
		'access_token' => $database->getAccessToken($fbUserId)
	);
	try {
		$userPages = $facebook->api('/me/accounts', 'GET', $token);
		
		//get groups
		try {
			$userGroups = $facebook->api('/me/groups', 'GET', $token);
			
			$userPagesLoaded = true;
			
		} catch (FacebookApiException $e) {
			$logger->error("Could not get list of user's groups for display in GUI.", $e);
			$userPagesLoaded = false;
		}
	} catch (FacebookApiException $e) {
		$logger->error("Could not get list of user's pages for display in GUI.", $e);
		$userPagesLoaded = false;
	}
}

include 'headerInclude.php';

if ( isset($_GET['error']) ) {
	echo '<div class="errorBox">';
	echo $_GET['errorMsg'];
	echo'</div>';
}

?>

<?php
	if($editSub) {
		echo '<form method="post" action="index.php?action=doEditSubscription">';
		echo '	<input type="hidden" name="subId" value="'.$subId.'" />';
	} else {
		echo '<form method="post" action="index.php?action=doSubscribe">';
	}
?>	
	<div class="property">
		<div class="label">Name of subscription (anything meaningful)&#58;</div>
		<input type="text" name="subName" value="<?php echo $subName; ?>" />
	</div>
	
<?php if(!$editSub) { ?>
	<div class="property">
		<div class="label">URL/Web address of the iCalendar file&#58;</div>
		<input type="text" name="iCalendarUrl" value="<?php echo $iCalendarUrl; ?>" id="iCalendarUrlInput" />
	</div>
	
	<div class="property">
		<div class="label">Create the events on&#58;</div>
		<select size="1" name="pageId">
		<option value="0" selected>Your personal profile page</option>
		<?php if ($userPagesLoaded) {
			foreach ($userPages['data'] as $page) {
				if ($page['category'] == "Application")
					$page['name'] = "Application page with id " . $page['id'];
				echo '<option value="' . $page['id'] . '"';
				
				if ($page['id'] == $pageId)
					echo 'selected="selected"';
				
				echo '>' . $page['name'] . '</option>';
			}
			
			foreach ($userGroups['data'] as $group) {
				if ($group['administrator'] == "true") {
					echo '<option value="' . $group['id'] . '"';
					
					if ($group['id'] == $pageId)
						echo 'selected="selected"';
					
					echo '>' . $group['name'] . '</option>';
				}
			}
		}
		?>
		</select>
		<?php if (!$userPagesLoaded) {
			echo '<div class="errorBox">Could not get the list of your pages from facebook. You can still subscribe to get the events created on your personal profile page.</div>';
		}
		?>
	</div>
	
	<div id="advancedOptionsLink"><a href="#">➝ Advanced options</a></div>
	<div id="advancedOptionsDiv" style="display: none">
<?php } ?>		
		<div class="property">
			<div class="label">Image&#58;
				<p class="labelText">If some of the events in your iCalendar file have a special <a href="http://en.wikipedia.org/wiki/ICalendar#Calendar_extensions" target="_blank">X-field</a> that cointains an URL which points to an image file, you can enter the name of that field here. e.g. <a href="http://www.google.com/support/calendar/bin/answer.py?answer=48526" target="_blank">X-GOOGLE-CALENDAR-CONTENT-URL</a> or ATTACH if that contains an URL.</p>
			</div>
			<input type="text" name="imageProperty" value="<?php echo $imageProperty; ?>" />
		</div>
                <!--
                <div class="property">
                        <div class="label">Append Link&#58;
                                <div class="floatdiv">
                                <input class="checkbox" type="checkbox" name="moduleAppendWebsiteUrl" value="<?php echo $moduleAppendWebsiteUrl; ?>" />
                                <p>If some events in your iCalendar file have a URL to a Website specified, append that to the event-description on Facebook.</p>
                                </div>
                        </div>
                </div>
                -->
		
<?php
if(!$editSub) {		
	echo '</div><input class="submitButton" type="submit" value="Subscribe" />';
} else {
	echo '<input class="submitButton" type="button" onclick="location.href=\'index.php?action=showSubscriptionList\'" value="Cancel" />
		<input class="submitButton" type="submit" value="Save changes" />';
}
?>	
	
	
</form>

<script>
	$('#advancedOptionsLink').click(function(){
		$('#advancedOptionsDiv').slideToggle();
	});
</script>

<?php include 'footerInclude.php'; ?>