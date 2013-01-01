<?php
/**
 * This tests the qCal_Date_Recur class thoroughly...
 */
class UnitTestCase_Recur extends UnitTestCase {

	public function setUp() {
	
		// set up and stuff
	
	}
	
	public function tearDown() {
	
		// mr. gorbachev, tear down this wall!!
	
	}
	
	public function testGetInstancesStartMustComeBeforeEnd() {
	
		$recur = new qCal_DateTime_Recur_Yearly;
		$recur->interval(1);
		$this->expectException(new qCal_DateTime_Exception_InvalidRecur('Start date must come before end date'));
		$dates = $recur->getRecurrences('08/01/2009', '07/01/2009');
	
	}
	
	public function testGetInstancesRequiresInterval() {
	
		$recur = new qCal_DateTime_Recur_Yearly;
		$this->expectException(new qCal_DateTime_Exception_InvalidRecur('You must specify an interval'));
		$dates = $recur->getRecurrences('08/01/2009', '09/01/2009');
	
	}
	
	public function testGetters() {
	
		$recur = new qCal_DateTime_Recur_Yearly;
		$recur->count(10)
			->byMonth(2)
			->byDay('TU');
		$this->assertEqual($recur->count(), 10);
		$this->assertEqual($recur->byMonth(), array(2));
		$this->assertEqual($recur->byDay(), array('TU'));
		$this->assertEqual($recur->byMonthDay(), null);
	
	}
	
	public function testSetWeekworkStart() {
	
		$recur = new qCal_DateTime_Recur_Minutely;
		$recur->wkst('SU'); // set the work week start to Sunday
		$this->assertEqual($recur->wkst(), 'SU');
		// invalid work day should throw an exception
		$this->expectException(new qCal_DateTime_Exception_InvalidRecur('"FOO" is not a valid week day, must be one of the following: MO, TU, WE, TH, FR, SA, SU'));
		$recur->wkst('FOO');
	
	}
	
	public function testCanHaveCountOrUntilButNotBoth() {
	
		$rule = new qCal_DateTime_Recur_Hourly;
		$this->expectException(new qCal_DateTime_Exception_InvalidRecur('A recurrence count and an until date cannot both be specified'));
		$rule->count(10)
			->until('02/12/2009');
	
	}
	
	/**
	 * Within the recur object, we use objects to compare the current date with
	 * rule modifiers. This tests their functionality.
	 */
	// public function testLoopHelpers() {
	// 
	// 	// let's assume we want a recurrence every 30 seconds starting from 01/01/2000 at 12:00am
	// 	// and ending at 01/01/2000 at 12:05:15am
	// 	$sInstances = array();
	// 	$secondly = new qCal_Date_Recur_Looper_Secondly('01/01/2000 12:00am');
	// 	while($secondly->onOrBefore('01/01/2000 12:05:15am')) {
	// 		// get current instance
	// 		$sInstances[] = $secondly->getInstance();
	// 		// increment by 30 seconds
	// 		$secondly->increment(30);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/01/2000 12:00am');
	// 	$after = new qCal_Date('01/01/2000 12:00:01am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$sly = new qCal_Date_Recur_Looper_Secondly('01/01/2000 12:00am');
	// 	$this->assertTrue($sly->onOrBefore($on));
	// 	$this->assertTrue($sly->onOrBefore($after));
	// 	$this->assertFalse($sly->onOrBefore($before));
	// 	
	// 	$this->assertEqual($sInstances, array(
	// 		new qCal_Date('01/01/2000 12:00am'),
	// 		new qCal_Date('01/01/2000 12:00:30am'),
	// 		new qCal_Date('01/01/2000 12:01am'),
	// 		new qCal_Date('01/01/2000 12:01:30am'),
	// 		new qCal_Date('01/01/2000 12:02am'),
	// 		new qCal_Date('01/01/2000 12:02:30am'),
	// 		new qCal_Date('01/01/2000 12:03am'),
	// 		new qCal_Date('01/01/2000 12:03:30am'),
	// 		new qCal_Date('01/01/2000 12:04am'),
	// 		new qCal_Date('01/01/2000 12:04:30am'),
	// 		new qCal_Date('01/01/2000 12:05am'),
	// 	));
	// 	
	// 	// let's assume we want a recurrence every 20 minutes starting from 01/01/2000 at 12:00am
	// 	// and ending at 01/01/2000 at 3:50:15am
	// 	$minInstances = array();
	// 	$minutely = new qCal_Date_Recur_Looper_Minutely('01/01/2000 12:00am');
	// 	while($minutely->onOrBefore('01/01/2000 3:50:15am')) {
	// 		// get current instance
	// 		$minInstances[] = $minutely->getInstance();
	// 		// increment by 30 seconds
	// 		$minutely->increment(20);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/01/2000 12:00:20am');
	// 	$after = new qCal_Date('01/01/2000 12:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$mly = new qCal_Date_Recur_Looper_Minutely('01/01/2000 12:00am');
	// 	$this->assertTrue($mly->onOrBefore($on));
	// 	$this->assertTrue($mly->onOrBefore($after));
	// 	$this->assertFalse($mly->onOrBefore($before));
	// 	
	// 	$this->assertEqual($minInstances, array(
	// 		new qCal_Date('01/01/2000 12:00am'),
	// 		new qCal_Date('01/01/2000 12:20am'),
	// 		new qCal_Date('01/01/2000 12:40am'),
	// 		new qCal_Date('01/01/2000 01:00am'),
	// 		new qCal_Date('01/01/2000 01:20am'),
	// 		new qCal_Date('01/01/2000 01:40am'),
	// 		new qCal_Date('01/01/2000 02:00am'),
	// 		new qCal_Date('01/01/2000 02:20am'),
	// 		new qCal_Date('01/01/2000 02:40am'),
	// 		new qCal_Date('01/01/2000 03:00am'),
	// 		new qCal_Date('01/01/2000 03:20am'),
	// 		new qCal_Date('01/01/2000 03:40am'),
	// 	));
	// 	
	// 
	// 	// let's assume we want a recurrence every other hour starting from 01/01/2000 at 12:00am
	// 	// and ending at 01/01/2000 at 6:00 am
	// 	$hrInstances = array();
	// 	$hourly = new qCal_Date_Recur_Looper_Hourly('01/01/2000 12:00am');
	// 	while($hourly->onOrBefore('01/01/2000 6:00am')) {
	// 		// get current instance
	// 		$hrInstances[] = $hourly->getInstance();
	// 		// increment by 2 hours
	// 		$hourly->increment(2);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/01/2000 12:45:20am');
	// 	$after = new qCal_Date('01/01/2000 1:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$hly = new qCal_Date_Recur_Looper_Hourly('01/01/2000 12:00am');
	// 	$this->assertTrue($hly->onOrBefore($on));
	// 	$this->assertTrue($hly->onOrBefore($after));
	// 	$this->assertFalse($hly->onOrBefore($before));
	// 
	// 	$this->assertEqual($hrInstances, array(
	// 		new qCal_Date('01/01/2000 12:00am'),
	// 		new qCal_Date('01/01/2000 02:00am'),
	// 		new qCal_Date('01/01/2000 04:00am'),
	// 		new qCal_Date('01/01/2000 06:00am'),
	// 	));
	// 	
	// 	// let's assume we want a recurrence every three days starting from 01/01/2000 at 9:00am
	// 	// and ending at 01/27/2000 at 6:00 am
	// 	// @todo This kind of presents a problem... daily rules probably shouldn't really have a time component,
	// 	// daily rules should represent whole days rather than days at a certain time. my qCal_Date object requires
	// 	// a time because it just extends the built-in DateTime class. I need to figure out how I want to deal with this.
	// 	$dInstances = array();
	// 	$daily = new qCal_Date_Recur_Looper_Daily('01/01/2000');
	// 	while($daily->onOrBefore('01/27/2000')) {
	// 		// get current instance
	// 		$dInstances[] = $daily->getInstance();
	// 		// increment by 3 days
	// 		$daily->increment(3);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/01/2000 12:45:20am');
	// 	$after = new qCal_Date('01/02/2000 1:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$dly = new qCal_Date_Recur_Looper_Daily('01/01/2000 12:00am');
	// 	$this->assertTrue($dly->onOrBefore($on));
	// 	$this->assertTrue($dly->onOrBefore($after));
	// 	$this->assertFalse($dly->onOrBefore($before));
	// 	
	// 	$this->assertEqual($dInstances, array(
	// 		new qCal_Date('01/01/2000'),
	// 		new qCal_Date('01/04/2000'),
	// 		new qCal_Date('01/07/2000'),
	// 		new qCal_Date('01/10/2000'),
	// 		new qCal_Date('01/13/2000'),
	// 		new qCal_Date('01/16/2000'),
	// 		new qCal_Date('01/19/2000'),
	// 		new qCal_Date('01/22/2000'),
	// 		new qCal_Date('01/25/2000'),
	// 	));
	// 	
	// 	// let's assume we want a recurrence every week starting from 01/01/2000
	// 	// and ending at 02/02/2000
	// 	// @todo This has the same issues as daily does with times being attached when they shouldn't be
	// 	$wInstances = array();
	// 	$weekly = new qCal_Date_Recur_Looper_Weekly('01/01/2000');
	// 	while($weekly->onOrBefore('02/02/2000')) {
	// 		// get current instance
	// 		$wInstances[] = $weekly->getInstance();
	// 		// increment by a week
	// 		$weekly->increment(1);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/03/2000 12:45:20am');
	// 	$after = new qCal_Date('01/09/2000 1:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$wly = new qCal_Date_Recur_Looper_Weekly('01/01/2000 12:00am');
	// 	$this->assertTrue($wly->onOrBefore($on));
	// 	$this->assertTrue($wly->onOrBefore($after));
	// 	$this->assertFalse($wly->onOrBefore($before));
	// 	
	// 	$this->assertEqual($wInstances, array(
	// 		new qCal_Date('01/01/2000'),
	// 		new qCal_Date('01/08/2000'),
	// 		new qCal_Date('01/15/2000'),
	// 		new qCal_Date('01/22/2000'),
	// 		new qCal_Date('01/29/2000'),
	// 	));
	// 	
	// 	// let's assume we want a recurrence every 6 months starting from 01/01/2000
	// 	// and ending at 02/08/2004
	// 	// @todo This has the same issues as daily does with times being attached when they shouldn't be
	// 	$mInstances = array();
	// 	$monthly = new qCal_Date_Recur_Looper_Monthly('01/01/2000');
	// 	while($monthly->onOrBefore('02/08/2004')) {
	// 		// get current instance
	// 		$mInstances[] = $monthly->getInstance();
	// 		// increment by 6 months
	// 		$monthly->increment(6);
	// 	}
	// 	
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/03/2000 12:45:20am');
	// 	$after = new qCal_Date('02/01/2000 1:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$mly = new qCal_Date_Recur_Looper_Monthly('01/01/2000 12:00am');
	// 	$this->assertTrue($mly->onOrBefore($on));
	// 	$this->assertTrue($mly->onOrBefore($after));
	// 	$this->assertFalse($mly->onOrBefore($before));
	// 	
	// 	$this->assertEqual($mInstances, array(
	// 		new qCal_Date('01/01/2000'),
	// 		new qCal_Date('07/01/2000'),
	// 		new qCal_Date('01/01/2001'),
	// 		new qCal_Date('07/01/2001'),
	// 		new qCal_Date('01/01/2002'),
	// 		new qCal_Date('07/01/2002'),
	// 		new qCal_Date('01/01/2003'),
	// 		new qCal_Date('07/01/2003'),
	// 		new qCal_Date('01/01/2004'),
	// 	));
	// 	
	// 	// let's assume we want a recurrence every year starting from 01/01/2000
	// 	// and ending at 02/08/2004
	// 	// @todo This has the same issues as daily does with times being attached when they shouldn't be
	// 	$yInstances = array();
	// 	$yearly = new qCal_Date_Recur_Looper_Yearly('01/01/2000');
	// 	while($yearly->onOrBefore('02/08/2004')) {
	// 		// get current instance
	// 		$yInstances[] = $yearly->getInstance();
	// 		// increment by 6 months
	// 		$yearly->increment(1);
	// 	}
	// 
	// 	// make sure onOrBefore works right
	// 	$on = new qCal_Date('01/03/2000 12:45:20am');
	// 	$after = new qCal_Date('02/01/2000 1:02:00am');
	// 	$before = new qCal_Date('12/31/1999 11:59:59pm');
	// 	$yly = new qCal_Date_Recur_Looper_Yearly('01/01/2000 12:00am');
	// 	$this->assertTrue($yly->onOrBefore($on));
	// 	$this->assertTrue($yly->onOrBefore($after));
	// 	$this->assertFalse($yly->onOrBefore($before));
	// 
	// 	$this->assertEqual($yInstances, array(
	// 		new qCal_Date('01/01/2000'),
	// 		new qCal_Date('01/01/2001'),
	// 		new qCal_Date('01/01/2002'),
	// 		new qCal_Date('01/01/2003'),
	// 		new qCal_Date('01/01/2004'),
	// 	));
	// 
	// }
	
	public function testLooperFactory() {
	
		$yearly = qCal_DateTime_Recur::factory('yearly', time());
		$this->assertIsA($yearly, 'qCal_DateTime_Recur_Yearly');
		$monthly = qCal_DateTime_Recur::factory('MonTHLY', time());
		$this->assertIsA($monthly, 'qCal_DateTime_Recur_Monthly');
		$weekly = qCal_DateTime_Recur::factory('WEEKLY', time());
		$this->assertIsA($weekly, 'qCal_DateTime_Recur_Weekly');
		$daily = qCal_DateTime_Recur::factory('Daily', time());
		$this->assertIsA($daily, 'qCal_DateTime_Recur_Daily');
		$hourly = qCal_DateTime_Recur::factory('hourly', time());
		$this->assertIsA($hourly, 'qCal_DateTime_Recur_Hourly');
		$minutely = qCal_DateTime_Recur::factory('minutely', time());
		$this->assertIsA($minutely, 'qCal_DateTime_Recur_Minutely');
		$secondly = qCal_DateTime_Recur::factory('SeCoNdLy', time());
		$this->assertIsA($secondly, 'qCal_DateTime_Recur_Secondly');
	
	}
	
	public function xxxtestBuildRule() {
	
		$recur = new qCal_DateTime_Recur_Yearly;
		$recur->interval(2) // every other year
			->byMonth(array(1,2,3)) // every other year in january, february and march
			->byMonthDay(10) // every 10th of the month
			->byDay(array('1SU', '2TU', 'MO'))
			->byHour(array(8,9)) // every first sunday in january, february, and march of every other year at 8am and 9am
			->byMinute(30); // every last sunday in january, february, and march of every other year at 8:30am and 9:30am
		$start = '08/24/1995';
		$end = '08/24/2009';
		
		$dates = $recur->getRecurrences($start, $end);
		//pr($dates); // should return an array of qCal_Dates that represent every instance in the timespan
		foreach ($dates as $date) {
			// pr($date->format('r'));
		}
	
	}
	
	/**
	 * Let's start with a really simple rule and go from there...
	 */
	// public function testBuildSimpleRule() {
	// 
	// 	$rule = new qCal_Date_Recur('daily');
	// 	$rule->interval(1);
	// 	// should return every day in august
	// 	$dates = $rule->getRecurrences('08/01/2009', '08/31/2009');
	// 	// pr($dates[30]->format('m/d/Y')); // this is wrong... should not include 9/1/2009 and it does
	// 	$this->assertEqual(count($dates), 31);
	// 
	// }

}