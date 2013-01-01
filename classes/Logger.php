<?php

/**
 * Description of Logger
 *
 * @author maurobieg
 */
class Logger {

	private $loggingTarget = "file";
	private $database;
	
	private $currentFbUserId;
	private $currentSubId;
	private $ourEventId;
	
	
	/* SETTERS */
	
	public function setCurrentFbUserId($currentFbUserId) {
		$this->currentFbUserId = $currentFbUserId;
	}
	
	public function setCurrentSubId($currentSubId) {
		$this->currentSubId = $currentSubId;
	}
	public function getCurrentSubId() {
		return $this->currentSubId;
	}
	public function unsetCurrentSubId() {
		$this->currentSubId = null;
	}

	public function setCurrentOurEventId($ourEventId) {
		$this->ourEventId = $ourEventId;
	}
	public function unsetCurrentOurEventId() {
		$this->ourEventId = null;
	}
		
	public function setLogToFile() {
		$this->loggingTarget = "file";
	}

	public function setLogToDb($database) {
		$this->database = $database;
		$this->loggingTarget = "db";
	}
	
	/* LOGGING */

	public function info($message) {
		$this->log("INFO", $message);
	}

	public function warning($message, $exception = "") {
		$this->log("WARNING", $message, $exception);
	}

	public function error($message, $exception = "") {
		$this->log("ERROR", $message, $exception);
	}
	
	public function debug($message){
		global $config;
		if ($config['debugMode']) {
			$this->log("DEBUG", $message);
		}
	}

	public function log($level, $message, $exception = "") {
		global $config;
                
		$level = strtoupper($level);
		
                switch ( $config['logLevel'] ) {
                        case "OFF":
                                break;
                        case "ERROR":
                                if ($level == "ERROR")
                                        $this->doLog($level, $message, $exception);
                                break;
                        case "WARN":
                                if ($level == "ERROR" || $level == "WARNING")
                                        $this->doLog($level, $message, $exception);
                            	break;
                        case "INFO":
                                $this->doLog($level, $message, $exception);
                                break;
                }
	}
	
	
	/* PRIVATE */
        
        private function doLog($level, $message, $exception) {
             if ($this->loggingTarget == "db") {
			$this->logToDb($level, $message, $exception);
		} else {
			$this->logToFile($level, $message);
		}   
        }
        
	private function logToFile($level, $message) {
		global $config;
		
		$currentIP  = $_SERVER["REMOTE_ADDR"];
		$currentTime = strftime("%Y-%m-%d--%H:%M:%S");
		$logLine = $currentTime . " - " . $level . " - " . $currentIP . " - " . str_replace("\n", "", str_replace("\r", "", $message));
		
		$logFile = $config['logFile'];
		
		// First check if there exists a log file
		if (!file_exists($logFile)) {
			touch($logFile);
		}

		// Then check if the size of it is too big
		if (filesize($logFile) > $config['logMaxFileSize']) {
			//copy($current_logfile, $old_logfile);
			unlink($logFile);
			touch($logFile);
		}
		
		// Now write line into the file
		$fh = fopen($logFile, "a");
		fwrite($fh, trim($logLine) . "\n");
		fflush($fh);
		fclose($fh);
	}

	private function logToDb($level, $message, $exception = "") {
		$currentIP  = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
		
		if ($exception == ""){
			$debugInfo = "";
		} else {
			$message .= " - " . $exception->getMessage();
			$debugInfo = "File " . $exception->getFile() . ", Line " . $exception->getLine() . ", Trace " . $exception->getTraceAsString();
		}
		
		try {
			$this->database->insertOrUpdateLog($level, $message, $debugInfo, $currentIP, time(), $this->currentFbUserId, $this->currentSubId, $this->ourEventId);
		} catch(Exception $e) {
			$this->logToFile($level, $message);
			$this->logToFile("ERROR", "Could not log to db, have logged to file instead. ".$e->getMessage() );
		}
	}

}

?>
