function activate(subId, subName) {
	$('<p>The subscription <i>'+subName+'</i> is currently deactivated. Do you want to reactivate it?</p>'
		+ '<p>It might have been deactivated because there was an error for an extended period of time.</p>'
		+ '<input type="button" onclick="hideHoverDialog()" value="Cancel">'
		+ '<input type="button" onclick="location.href=\'index.php?action=doActivate&subId='+subId+'\'" value="Activate">'
		).appendTo('.hoverDialog');
	showHoverDialog();
}

function deactivate(subId, subName) {
	$('<p>The subscription <i>'+subName+'</i> is currently active. Do you want to deactivate it?</p>'
		+ '<p>Existing events will remain on facebook but no new events will be created.</p>'
		+ '<input type="button" onclick="hideHoverDialog()" value="Cancel">'
		+ '<input type="button" onclick="location.href=\'index.php?action=doDeactivate&subId='+subId+'\'" value="Deactivate">'
		).appendTo('.hoverDialog');
	showHoverDialog();
}

function unsubscribe(subId, subName) {
	$('<p>Are you sure you want to delete the subscription <i>'+subName+'</i> and all its events from facebook?</p>'
		+ '<input type="button" onclick="hideHoverDialog()" value="Cancel">'
		+ '<input type="button" onclick="location.href=\'index.php?action=doUnsubscribe&subId='+subId+'\'" value="Delete all Events">'
		).appendTo('.hoverDialog');
	showHoverDialog();
}

function showHoverDialog() {
	$('.hoverDialog, .background').css('display', 'block');
}
function hideHoverDialog() {
	$('.hoverDialog, .background').empty().css('display', 'none');
}