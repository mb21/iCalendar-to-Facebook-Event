<?php

include 'headerInclude.php';
?>


<h2>About</h2>
<p>With this app you can subscribe to an <a href="http://en.wikipedia.org/wiki/ICalendar">iCalendar</a> file which then gets regularly checked for updates. When new events in the calendar become available, it will create a facebook event in your name. This works also for Facebook pages and groups where you are an administrator.</p>

<h4>Example: Google Calendar</h4>
<p>Most calendars export to the iCalendar format, usually a file with the suffix .ics. For example, to get your Google Calendar URL: in <a href="http://calendar.google.com/">Google Calendar</a> go to Settings -> Calendars -> choose a calendar -> public ICAL.</p>

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

<h4 id="reactivate">Subscription was deactivated</h4>
<p>If the app always encounters an error when updating a subscription for an extended period of time, that subscription will be deactivated. It will say 'deactivated' in the iCalendar-to-Event app subscriptions list. You can try to reactivate it there which will only work if the error has been fixed. This is done as to not bother my server with checking lots of ics-files that don't work and aren't used anymore.</p>


<h4 id="faq">Miscellaneous/FAQ</h4>
<ul class="list">
<li>Don't add a calendar with lots of event, remove it again, add it again etc. Facebook imposes certain <a href="http://www.facebook.com/help/?page=1052" target="_blank">limits</a> and they don't like adding/removing lots of event too fast. When they decide you posted too much too quick the App will simply fail to create new events for you and usually there is a cryptic message in your Log.</li>
<li>Facebook doesn't support adding events that have a starttime in the past. So currently I've set the App to post only events from now until 3 months into the future.</li>
<li>If you change events in the ical file the facebook events will get updated but not the other way around. Also if you delete an event in your calendar it will remain on facebook.</li>
<li>New events are usually created every four hours or so.</li>
<li>If the events in your calendar are encoded in UTC (i.e. have a Z at the end) your calendar should have a calendar-timezone. Either VTIMEZONE or X-WR-TIMEZONE work fine. Otherwise the app won't know in what time you want the events displayed (and no, facebook unfortunately <a href="http://bugs.developers.facebook.net/show_bug.cgi?id=7210" target="_blank">doesn't support timezones</a>).</li>
<li>If you see post of events on your wall it is Facebook's own 'Events' App that is posting to your wall and not my app. While I currently don't know of any way to disable that behaviour for your personal page, the following works for pages:
<ol>
<li>Go to your page and click 'Edit page'</li>
<li>Select 'Apps' in the list on the left</li>
<li>At the Events app click 'Edit settings' -> 'Additional Permissions' and deselect 'Publish content to my wall'</li>
</ol>
</li>
<li>Internet Explorer might have some problems with this app. Get a decent browser like <a href="http://www.getfirefox.com">Firefox</a> or <a href="http://www.google.com/chrome/">Chrome</a>.</li>
<li><a href="index.php?action=showPolicy">Privacy Policy</a></li>
</ul>

<br/>
<h2 id="development">Future development</h2>
<h4>Planned Features</h4>
<p>Here is a list of planned features and options that are not yet supported:</p>
<ul class="list">
<li>support recurring events in ics files (there's a <a target="_blank" href="http://www.pledgie.com/campaigns/13800">fundraiser for that</a>)</li>
<li>associate a picture with a subscription which then will be added to every event created</li>
<li>your default RSVP: not attending</li>
<li>invite fans</li>
</ul>

<!--<br/>
<h4>Contribute</h4>
<p>This is free and open source software written in PHP. Feel free to <a href="http://github.com/mb21/iCalendar-to-Facebook-Event">contribute</a>!</p>
-->
<h4>Support</h4>
<p>I'm working on this app in my spare time and running it on my private server. Please feel free to donate some money. Thank you!</p>

<?php
include 'footerInclude.php';
?>
