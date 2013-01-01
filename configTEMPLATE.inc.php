<?php

/*
 * This is the config file template.
 * After filling in your data, change the file name of this file to 'config.inc.php'.
 * 
 */


$config = Array(
	"dbName" => "CalendarToFacebook",
	"dbHost" => "localhost",
	"dbUser" => "",
	"dbPassword" => "",
	
	"facebookAppId" => "",
	"facebookSecret" => "",
	
	//e.g. /usr/bin/php
	"phpCommand" => "php",

	"debugMode" => true,
	"debugWithoutFacebook" => false,

	"logFile" => "log/log.txt",
	"logMaxFileSize" => 100000,
	"keepLogEntriesInDb" => "-2 days",
    "logLevel" => "INFO", //possible values: (logs nothing) "OFF", "ERROR", "WARN", "INFO" (logs everything)
        
    "deactivateSubsWithLastSuccessfullPublish" => "-3 months",
        
	"maxiCalFileLength" => 1000000,
	"maxImageFileLength" => 1500000,

	// events with DTSTART outside this window are ignored by default
    "defaultWindowOpen" => "now",
	"defaultWindowClose" => "+3 months",
    "defaultReccurWindowOpen" => "-4 years", //recurring events won't be calculated if the DTSTART of the original event is earlier than set value

	//time in seconds to sleep before processing more events (fb limits)
	"waitForNextEventPublish" => 10,

	//maximal length of event title, rest will be cropped (last time checked facebook didn't allow more than ~70)
	"maxFbTitleLength" => 70,
);

?>
