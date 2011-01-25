	<head>
    <style>
        .list{
            padding-left: 1.5em;
            list-style-type: disc;
            display: list-item;
        }
    </style>
</head>

<h2>About</h2>
<p>With this app you can subscribe to an <a href="http://en.wikipedia.org/wiki/ICalendar">iCalendar</a> file which then gets regularly checked for updates. When new events in the calendar become available, it will create a facebook event in your name.</p>

<h4>Example: Google Calendar</h4>
<p>Most calendars export to the iCalendar format, usually a file with the suffix .ics. For example, to get your Google Calendar URL: in <a href="http://calendar.google.com/">Google Calendar</a> go to Settings -> Calendars -> choose a calendar -> public ICAL.</p>

<h4 id="pages">Pages</h4>
<p>You can add this app to your facebook pages (fan-pages) to create events for them. Here is how: Go to the <a href="http://www.facebook.com/apps/application.php?v=info&id=164414672850" target="_blank">page of this app</a> and click on the left on "Add to my Page". Add it to those of your pages you want, then close the popup. Now go to your page (fanpage) and edit it. Under Applications, you now should see "iCalendar to Event". Click there once again on Edit which should get you to the iCalendar to Event page with the page_id already filled in under Advanced Options.</p>

<h4 id="groups">Groups</h4>
<p>You can create events for a facebook group. Simply enter the group-id into the field when subscribing. To get your group-id, check the web-address of the grouppage for gid=XXX.</p>

<h4 id="images">Images</h4>
<p>If some of the events in your iCalendar file have a special <a href="http://en.wikipedia.org/wiki/ICalendar#Calendar_extensions" target="_blank">X-field</a> that cointains an URL which points to an image file, you can enter the name of that field in the 'Picture' field in the advanced options of this App. For example if your ics file looks like <a href="http://www.google.com/support/calendar/bin/answer.py?answer=48526" target="_blank">the following</a>
<p><code>
BEGIN:VEVENT<br/>
DTSTART;VALUE=DATE:20060704<br/>
DTEND;VALUE=DATE:20060705<br/>
SUMMARY:Independence Day<br/>
X-GOOGLE-CALENDAR-CONTENT-URL:http://www.google.com/logos/july4th06.gif<br/>
END:VEVENT<br/>
</code></p>
then you would write X-GOOGLE-CALENDAR-CONTENT-URL in the Picture field in the <i>advanced options</i> of a new subscription or click <i>edit</i> for an existing one. Or you can write ATTACH if you have attached an image by URL.</p>

<p>If you're using Google Calendar you can do the following:
	<ol>
		<li>in the iCalendar-to-Event app, in Advanced options (or Edit for an existing subscription), write 'ATTACH' (without the quotations marks) in the Picture field</li>
		<li>in Google Calendar, click on the Labs icon in the upper right corner (the green potion)</li>
		<li>enable the Event attachments feature.</li>
		<li>now, when you create a new event you can add an attachement. choose an image</li>
	</ol>
</p>

<h4 id="reactivate">Subscriptions was deactivated</h4>
<p>If the app always encounters an error when updating a subscription for an extended period of time, that subscription will be deactivated. This is displayed in a red box on the iCalendar-to-Event app subscriptions list (if you see nothing there then all your subscriptions are fine). You can try to reactivate it there which will only work if the error has been fixed. This is done as to not bother my server with checking lots of ics-files that don't work and aren't used anymore.</p>

<h4 id="faq">Miscellaneous/FAQ</h4>
<ul class="list">
<li>Don't add a calendar with lots of event, remove it again, add it again etc. Facebook imposes certain <a href="http://www.facebook.com/help/?page=1052" target="_blank">limits</a> and they don't like adding/removing lots of event too fast. When they decide you posted too much too quick the App will simply fail to create new events for you and usually there is a cryptic message in your <a href="http://apps.facebook.com/icalendar-to-event/display_error_log.php" target="_blank" >error log</a>.</li>
<li>If you change events in the ical file the facebook events will get updated but not the other way around.</li>
<li>New events are usually created every four hours or so.</li>
<li>If the App doesn't recognize your ics file you should make sure that the URL doesn't redirect. You can <a href="http://www.internetofficer.com/seo-tool/redirect-check/">check for redirections here</a> and if any are detected you should use the resolved URL.</li>
<li>If you see post of events on your wall although you have disabled 'posting to wall' in the advanced options in the iCalendar-to-Event App, it is probably Facebook's own 'Events' App that is posting to your wall and not my app. Please try the following:<br/>
<ol>
<li>on the wall move your mouse over the post -> a cross (or X) should appear at the right</li>
<li>click on the cross -> a small menu appears</li>
<li>select "Remove publishing rights of Events"</li>
</ol>
</li>
<li>When editing a subscription, and you selected <i>Wall</i>, even if you choose <i>Update also existing events</i> there will be no wall posts created for old events.</li>
<li>Internet Explorer might have some problems with this app. Get a decent browser like <a href="http://www.getfirefox.com">Firefox</a> or <a href="http://www.google.com/chrome/">Chrome</a>.</li>
</ul>

<br/>
<h2 id="development">Future development</h2>
<br/>
<h4>Planned Features</h4>
<p>Here is a list of planned features and options that are not yet supported:</p>
<ul class="list">
<li>support recurring events in ics files (there's a <a target="_blank" href="http://www.pledgie.com/campaigns/13800">fundraiser for that</a>)</li>
<li>associate a picture with a subscription which then will be added to every event created</li>
<li>your default RSVP: not attending</li>
<li>invite members of group/fans</li>
</ul>

<br/>
<h4>Contribute</h4>
<p>This is free and open source software written in PHP. Feel free to <a href="http://github.com/mb21/iCalendar-to-Facebook-Event">contribute</a>!</p>

<h4>Support</h4>
<p>I'm working on this app in my spare time and running it on my private server. Please feel free to donate some money. Thank you!</p>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
