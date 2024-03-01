CREATE DATABASE IF NOT EXISTS `atmowiz`;
CREATE USER IF NOT EXISTS `atmowiz`@`localhost` IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `atmowiz`.* TO `atmowiz`@`localhost`;
USE `atmowiz`;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `commands` (
  `whentime` datetime NOT NULL,
  `uid` varchar(8) NOT NULL,
  `reason` varchar(30) NOT NULL,
  `who` varchar(30) NOT NULL,
  `status` enum('Success','Failed') NOT NULL DEFAULT 'Success',
  `airconon` tinyint(1) NOT NULL,
  `mode` enum('cool','heat','dry','auto','fan') NOT NULL DEFAULT 'cool',
  `targetTemperature` tinyint(4) NULL,
  `temperatureUnit` varchar(1) NULL,
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'medium',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `changes` varchar(50) NOT NULL,
  PRIMARY KEY (`whentime`,`uid`) USING BTREE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `devices` (
  `uid` varchar(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `meta` (
  `uid` varchar(20) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `keyval` varchar(20) NOT NULL,
  `value` varchar(20) NOT NULL,
  KEY `uid` (`uid`),
  KEY `mode` (`mode`),
  KEY `keyval` (`keyval`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `sensibo` (
  `whentime` datetime NOT NULL,
  `uid` varchar(8) NOT NULL DEFAULT '',
  `temperature` float NOT NULL DEFAULT 0,
  `humidity` tinyint(3) NOT NULL DEFAULT 0,
  `feelslike` float NOT NULL DEFAULT 0,
  `rssi` tinyint(3) NOT NULL DEFAULT 0,
  `airconon` tinyint(1) NOT NULL DEFAULT 0,
  `mode` enum('cool','heat','dry','auto','fan') NOT NULL DEFAULT 'cool',
  `targetTemperature` tinyint(4) NULL DEFAULT 0,
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'medium',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `cost` float NOT NULL DEFAULT 0,
  `amps` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`whentime`,`uid`) USING BTREE,
  KEY `cost` (`cost`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `settings` (
  `uid` varchar(20) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) NOT NULL DEFAULT 'Climate Setting',
  `upperTemperature` tinyint(4) NOT NULL DEFAULT 29,
  `lowerTemperature` tinyint(4) NOT NULL DEFAULT 22,
  `targetTemperature` tinyint(4) NOT NULL DEFAULT 26,
  `turnOnOff` enum('On','Off') NOT NULL DEFAULT 'On',
  `mode` enum('cool','heat','dry','auto','fan') NOT NULL DEFAULT 'cool',
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'medium',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`uid`,`created`) USING BTREE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `timesettings` (
  `created` datetime NOT NULL,
  `uid` varchar(8) NOT NULL,
  `daysOfWeek` tinyint(3) NOT NULL,
  `startTime` time NOT NULL,
  `turnOnOff` enum('On','Off') NOT NULL DEFAULT 'On',
  `mode` enum('Cool','Heat','Auto','Fan','Dry') NOT NULL DEFAULT 'Cool',
  `targetTemperature` tinyint(4) DEFAULT 26,
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'medium',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `climateSetting` datetime DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT ,
  PRIMARY KEY (`created`,`uid`) USING BTREE
) ENGINE=InnoDB;

CREATE TABLE `timers` (
  `whentime` datetime NOT NULL DEFAULT current_timestamp(),
  `uid` varchar(8) NOT NULL,
  `seconds` mediumint(5) NOT NULL DEFAULT 1200,
  `turnOnOff` enum('On','Off') NOT NULL DEFAULT 'On',
  PRIMARY KEY (`whentime`,`uid`) USING BTREE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `weather` (
  `whentime` datetime NOT NULL,
  `temperature` float NOT NULL,
  `feelsLike` float NOT NULL,
  `humidity` float NOT NULL,
  `pressure` float NOT NULL,
  `aqi` float NOT NULL,
  PRIMARY KEY (`whentime`)
) ENGINE=InnoDB;

COMMIT;
