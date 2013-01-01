
#CREATE DATABASE  `CalendarToFacebook` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE log (
	logId INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(logId),
	level VARCHAR(30) NOT NULL, 
	message TEXT NOT NULL,
	debugInfo TEXT,
	ip VARCHAR(40),
	timestamp INT NOT NULL,
	fbUserId BIGINT,
	subId INT,
	ourEventId INT,
	FOREIGN KEY (ourEventId) REFERENCES events(ourEventId)
		ON DELETE CASCADE,
	errorCount INT NOT NULL
) ENGINE=MyISAM;


CREATE TABLE users (
	fbUserId BIGINT NOT NULL,
	PRIMARY KEY(fbUserId),
	fbAccessToken VARCHAR(255)
) ENGINE=InnoDB;


CREATE TABLE subscriptions (
	subId INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(subId),
	subName VARCHAR(255) NOT NULL,
	fbUserId BIGINT NOT NULL,
	calUrl VARCHAR(255) NOT NULL,
	fbPageId BIGINT NOT NULL,
	imageProperty VARCHAR(255),
	lastSuccessfulImportTimestamp INT NOT NULL,
	lastSuccessfulPublishTimestamp INT NOT NULL,
	active BOOL
) ENGINE=InnoDB;

CREATE TABLE events (
	ourEventId INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(ourEventId),
	
	calUID VARCHAR(255),
	recurrenceSetUID VARCHAR(255),
	startDate INT,
	
	subId INT NOT NULL,
	FOREIGN KEY (subId) REFERENCES subscriptions(subId)
		ON DELETE RESTRICT,
	
	UNIQUE INDEX (calUID, subId),
	UNIQUE INDEX (recurrenceSetUID, startDate, subId),
	
	state ENUM('current', 'updated', 'new') NOT NULL,
	lastModifiedTimestamp INT,

	fbEventId BIGINT UNIQUE KEY,
	fbName VARCHAR(255),
	fbDescription TEXT,
	fbStartTime INT,
	fbEndTime INT,
	fbLocation VARCHAR(255),
	fbPrivacy VARCHAR(63),
	
	imageFileUrl VARCHAR(255)
) ENGINE=InnoDB;


CREATE TABLE modules (
	moduleName VARCHAR(255) NOT NULL,
	PRIMARY KEY(moduleName),
	moduleOrder INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE subscribedModules (
	module VARCHAR(255) NOT NULL,
	subId INT NOT NULL,
	FOREIGN KEY (subId) REFERENCES subscriptions(subId)
		ON DELETE CASCADE,
	FOREIGN KEY (module) REFERENCES modules(moduleName)
		ON DELETE RESTRICT,
	PRIMARY KEY(module, subId)
) ENGINE=InnoDB;