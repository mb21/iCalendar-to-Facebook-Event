<?php

/**
 * This class contains all SQL. Upon creation a database connection is established 
 * and torn down on destruction of the object.
 *
 * @author maurobieg
 */
class Database {
	
	private $DBH;
	
	function __construct() {
		//create database handle
		
		global $config;
		global $logger;
		
		try{
			$this->DBH = new PDO("mysql:host=" . $config['dbHost'] . ";dbname=" . $config['dbName'], 
				   $config['dbUser'], $config['dbPassword']);
			$this->DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch(PDOException $e){
			$logger->error("Could not connect to database. " . $e->getMessage(), $e);
			exit;
		}
	}
	
	function __destruct() {
		//close connection
		$DBH = null;
	}
	
	
	
	
	public function insertOrUpdateLog($level, $message, $debugInfo, $ip, $timestamp, $fbUserId, $subId, $ourEventId){
		$STH = $this->DBH->prepare("
			INSERT INTO log (level, message, debugInfo, ip, timestamp, fbUserId, subId, ourEventId, errorCount) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)

		");
/*                        ON DUPLICATE KEY UPDATE level=VALUES(level), message=VALUES(message), debugInfo=VALUES(debugInfo), 
                                ip=VALUES(ip), timestamp=VALUES(timestamp), ourEventId=VALUES(ourEventId), errorCount=errorCount+1
 * 
 */		
		$data = Array($level, $message, $debugInfo, $ip, $timestamp, $fbUserId, $subId, $ourEventId);
		$STH->execute($data);
	}
	
	public function cleanUpNewEvents() {
		$this->DBH->query("
			DELETE FROM events WHERE state='new'
		");
	}
	
	public function cleanUpLog(){
		global $config;
		
		$this->DBH->query("
			DELETE FROM log WHERE timestamp < " . strtotime($config['keepLogEntriesInDb'])
		);
	}
	
        public function deactivateDeadSubs() {
                global $config;
                
                $this->DBH->query("
			UPDATE subscriptions
			SET active = 0
			WHERE lastSuccessfulPublishTimestamp < " . strtotime($config['deactivateSubsWithLastSuccessfullPublish'])
		);
        }
	
	
	public function getAccessToken($fbUserId) {
		//returns the facebook access token as a string
		
		$STH = $this->DBH->prepare('
			SELECT fbAccessToken
			FROM users
			WHERE fbUserId = ?
		');
		$STH->execute(array($fbUserId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		$row = $STH->fetch();
		
		return $row->fbAccessToken;
	}
	
	public function storeAccessToken($token, $fbUserId) {
		//update or insert facebook access token
		
		$STH = $this->DBH->prepare("
			INSERT INTO users (fbUserId, fbAccessToken)
			VALUES (?, ?)
			ON DUPLICATE KEY UPDATE fbAccessToken = VALUES(fbAccessToken)
		");
		
		$data = Array($fbUserId, $token);
		$STH->execute($data);
	}
	
	public function selectUserIdAndAccessToken($subId) {
		
		$STH = $this->DBH->prepare('
			SELECT users.fbUserId, users.fbAccessToken
			FROM users, subscriptions
			WHERE users.fbUserId = subscriptions.fbUserId AND subscriptions.subId = ?
		');
		$STH->execute(array($subId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
		
	}
        
        /* SUBSCRIPTIONS */
        
        public function getCalUrl($subId) {
                $STH = $this->DBH->prepare('
                            SELECT calUrl
                            FROM subscriptions
                            WHERE subId = ?
                    ');
                $STH->execute(array($subId));

                $STH->setFetchMode(PDO::FETCH_OBJ);
                
		if ( $row = $STH->fetch() )
	                return $row->calUrl;
		else
			throw new Exception("sub with id ".$subId." doesn't exist.");
        }
	
	public function getImageProperty($subId) {
		$STH = $this->DBH->prepare('
                            SELECT imageProperty
                            FROM subscriptions
                            WHERE subId = ?
                    ');
                $STH->execute(array($subId));

                $STH->setFetchMode(PDO::FETCH_OBJ);
                if( $row = $STH->fetch() )
	                return $row->imageProperty;
		else
			return null;
	}
	
	public function insertOrUpdateEvents($subscription) {
		//takes an object of class Subscription and saves the fb* fields of its events to the db
		$STH = $this->DBH->prepare("
			INSERT INTO events (calUID, recurrenceSetUID, startDate, subId, state, lastModifiedTimestamp, fbName, fbDescription, 
			    fbStartTime, fbEndTime, fbLocation, fbPrivacy, imageFileUrl)
			VALUES (:calUID, :recurrenceSetUID, :startDate, :subId, 'new', :lastModifiedTimestamp, 
			    :fbName, :fbDescription, :fbStartTime, 
			    :fbEndTime, :fbLocation, :fbPrivacy, :imageFileUrl)
			ON DUPLICATE KEY UPDATE state = 'updated', lastModifiedTimestamp = :lastModifiedTimestamp, 
			    recurrenceSetUID = :recurrenceSetUID, startDate = :startDate, fbName = :fbName, 
			    fbDescription = :fbDescription, fbStartTime = :fbStartTime, 
			    fbEndTime = :fbEndTime, fbLocation = :fbLocation, 
			    fbPrivacy = :fbPrivacy, imageFileUrl = :imageFileUrl
		");
		
		foreach ($subscription->eventArray as &$e) {
			$data = Array(
			    ':subId' => $subscription->getSubId(),
			    ':lastModifiedTimestamp' => $e['lastModifiedTimestamp'],
			    
			    ':fbName' => $e['fbName'], 
			    ':fbDescription' => $e['fbDescription'],
			    ':fbStartTime' => $e['fbStartTime'], 
			    ':fbEndTime' => $e['fbEndTime'],
			    ':fbLocation' => $e['fbLocation'],
			);
			if( isset($e['fbPrivacy']) )
				$data[':fbPrivacy'] = $e['fbPrivacy'];
			else
				$data[':fbPrivacy'] = null;
			
			if( isset($e['imageFileUrl']) )
				$data[':imageFileUrl'] = $e['imageFileUrl'];
			else
				$data[':imageFileUrl'] = null;
                        
                        if ( isset($e['calUID']) ) {
                                $data[':calUID'] = $e['calUID'];
                        } else {
                                $data[':calUID'] = null;
                        }

			if ( isset($e['isPartOfRecurrenceSet']) && $e['isPartOfRecurrenceSet'] ) {
                                
                                $data[':recurrenceSetUID'] = $e['recurrenceSetUID'];
                                
                                if (isset($e['startDate'])) {
                                       $data[':startDate'] = $e['startDate']; 
                                } else {
                                        $data[':startDate'] = null;
                                }
                        } else {
                               $data[':recurrenceSetUID'] = null;
                               $data[':startDate'] = null;
                        }
			
		        $STH->execute($data);
		}
	}
        
        public function setSuccessfulImport($subId) {
                //sets lastSuccessfulImportTimestamp in the database to now
                
                $timestamp = strtotime("now");
                
                $STH = $this->DBH->prepare("
			UPDATE subscriptions
			SET lastSuccessfulImportTimestamp = ?
			WHERE subId = ?
		");
		
		$STH->execute( Array($timestamp, $subId) );
        }
        
        public function setSuccessfulPublish($subId) {
                //sets lastSuccessfulPublishTimestamp in the database to now
                
                $timestamp = strtotime("now");
                
                $STH = $this->DBH->prepare("
			UPDATE subscriptions
			SET lastSuccessfulPublishTimestamp = ?
			WHERE subId = ?
		");
		
		$STH->execute( Array($timestamp, $subId) );
        }
	
	public function hasSuccessfulImport($subId) {
		//returns true if there ever has been a successful import, false otherwise
		
		$STH = $this->DBH->prepare('
                            SELECT lastSuccessfulImportTimestamp
                            FROM subscriptions
                            WHERE subId = ?
                    ');
                $STH->execute(array($subId));

                $STH->setFetchMode(PDO::FETCH_OBJ);
                $row = $STH->fetch();
		
		if( isset($row->lastSuccessfulImportTimestamp) )
			return true;
		else
			return false;
	}
	
	
	/* EVENTS */
	
	public function selectAllEvents($subId) {
		
		$STH = $this->DBH->prepare('
			SELECT *
			FROM events
			WHERE subId = ?
		');
		$STH->execute(array($subId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
	}
	
	public function selectNewEvents($subId) {
		//selects events to be created or updated (plus the fbPageId)
		
		$STH = $this->DBH->prepare("
			SELECT events.*, subscriptions.fbPageId
			FROM events, subscriptions 
			WHERE events.subId = ? AND (events.state = 'updated' OR events.state = 'new') AND subscriptions.subId = ?
		");
		$STH->execute( array($subId, $subId) );
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
		
	}
	
	public function setEventUpdated($ourEventId, $fbEventId) {
		
		$STH = $this->DBH->prepare("
			UPDATE events
			SET state = 'current', fbEventId = ?
			WHERE ourEventId = ?
		");
		
		$STH->execute( array($fbEventId, $ourEventId) );
	}
	
	public function setEventDeleted($ourEventId) {
		
		$STH = $this->DBH->prepare("
			DELETE FROM events
			WHERE ourEventId = ?
		");
		
		$STH->execute( array($ourEventId) );
	}
	
	public function deleteEvents($subId) {
		$STH = $this->DBH->prepare("
			DELETE FROM events
			WHERE subId = ?
		");
		
		$STH->execute( array($subId) );
	}
	
	public function notModified($lastModifiedTimestamp, $calUID, $subId) {
		//returns true if the event has not been modified, i.e.
		//if the $lastModifiedTimestamp is the same as in the db
		//returns false if the event isn't found in the db or has been modified
		
		$STH = $this->DBH->prepare('
                            SELECT lastModifiedTimestamp
                            FROM events
                            WHERE calUID = ? AND subId = ?
                    ');
                $STH->execute(array($calUID, $subId));

                $STH->setFetchMode(PDO::FETCH_OBJ);
                $row = $STH->fetch();
		
		if( isset($row->lastModifiedTimestamp) )
			$notModified = ( $row->lastModifiedTimestamp == $lastModifiedTimestamp );
		else
			$notModified = false;
		
		return $notModified;
	}
	
	public function selectAllActiveSubIds() {
		
		$STH = $this->DBH->query('
			SELECT subId 
			FROM subscriptions 
			WHERE active = 1
		');
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
	}
	
	/* GUI */
	
	public function selectSubscriptions($fbUserId) {
		
		$STH = $this->DBH->prepare('
			SELECT * 
			FROM subscriptions 
			WHERE fbUserId = ?
		');
		$STH->execute(array($fbUserId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
	}
	
	public function insertSubscription($subName, $fbUserId, $calUrl, $fbPageId, $imageProperty) {
		//insert a new subscription and returns its subId
		
		$STH = $this->DBH->prepare("
			INSERT INTO subscriptions (subName, fbUserId, calUrl, fbPageId, imageProperty, active) 
			VALUES (?, ?, ?, ?, ?, 1)
		");
		
		if( strlen($imageProperty) < 4)
			$imageProperty = null;
		
		$data = Array($subName, $fbUserId, $calUrl, $fbPageId, $imageProperty);
		$STH->execute($data);
		
		return $this->DBH->lastInsertId();
	}
	
	public function selectErrorLog($subId) {
		
		$STH = $this->DBH->prepare("
			SELECT * 
			FROM log 
			WHERE subId = ? AND level = 'ERROR'
			ORDER BY logId DESC
			LIMIT 20
		");
		$STH->execute(array($subId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
	}
	
	public function selectInfoLog($subId) {
		//actually ERROR plus INFO log
		
		$STH = $this->DBH->prepare("
			SELECT * 
			FROM log 
			WHERE subId = ? AND level = 'INFO'
			ORDER BY logId DESC
			LIMIT 20
		");
		$STH->execute(array($subId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		return $STH;
	}
	
	public function getSubscription($subId, $fbUserId) {
		
		$STH = $this->DBH->prepare("
			SELECT *
			FROM subscriptions 
			WHERE subId = ? AND fbUserId = ?
		");
		$STH->execute(array($subId, $fbUserId));
		
		$STH->setFetchMode(PDO::FETCH_OBJ);
		$row = $STH->fetch();
		
		return $row;
	}
	
	public function updateSubscriptionData($post, $fbUserId) {
		
		if ($post['imageProperty'] == '')
			$post['imageProperty'] = null;
		
		$STH = $this->DBH->prepare("
			UPDATE subscriptions
			SET subName = ?, imageProperty = ?
			WHERE subId = ? AND fbUserId = ?
		");
		
		$data = Array($post['subName'], $post['imageProperty'], $post['subId'], $fbUserId);
		$STH->execute($data);
	}
	
	public function deleteSubscription($subId) {
		
		$STH = $this->DBH->prepare("
			DELETE FROM subscriptions
			WHERE subId = ?
		");
		
		$STH->execute(array($subId));
	}
	
	public function deactivate($subId, $fbUserId) {
		//fbUserId is required so people can only deactivate their own subs
		
		$STH = $this->DBH->prepare("
			UPDATE subscriptions
			SET active = 0 
			WHERE subId = ? AND fbUserId = ?
		");
		
		$data = Array($subId, $fbUserId);
		$STH->execute($data);
	}
	
	public function activate($subId, $fbUserId) {
		//fbUserId is required so people can only reactivate their own subs
		
		$STH = $this->DBH->prepare("
			UPDATE subscriptions
			SET active = 1 
			WHERE subId = ? AND fbUserId = ?
		");
		
		$data = Array($subId, $fbUserId);
		$STH->execute($data);
	}
        
        public function getModules($subId) {
                $STH = $this->DBH->prepare("
			SELECT module
                        FROM subscribedModules as s, modules as m
                        WHERE subId = ? AND s.module = m.moduleName
                        ORDER BY m.moduleOrder
		");
		$STH->execute(array($subId));
		$STH->setFetchMode(PDO::FETCH_OBJ);
                
                return $STH;
        }
}

?>
