CREATE DATABASE IF NOT EXISTS `sensibo`;
CREATE USER IF NOT EXISTS `sensibo`@`localhost` IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `sensibo`.* TO `sensibo`@`localhost`;
USE `sensibo`;

START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `sensibo`;
USE `sensibo`;

CREATE TABLE IF NOT EXISTS `commands` (
  `whentime` datetime NOT NULL,
  `uid` varchar(8) NOT NULL,
  `reason` varchar(20) NOT NULL,
  `who` varchar(30) NOT NULL,
  `status` enum('Success','Failed') NOT NULL DEFAULT 'Success',
  `airconon` tinyint(1) NOT NULL,
  `mode` enum('cool','heat','dry','auto','fan') NOT NULL DEFAULT 'cool',
  `targetTemperature` tinyint(4) NOT NULL,
  `temperatureUnit` varchar(1) NOT NULL,
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
  `targetTemperature` smallint(6) NOT NULL DEFAULT 0,
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'medium',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `cost` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`whentime`,`uid`) USING BTREE,
  KEY `cost` (`cost`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `settings` (
  `uid` varchar(20) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `onOff` enum('On','Off') NOT NULL DEFAULT 'Off',
  `targetType` enum('temperature','humidity','feelsLike') NOT NULL DEFAULT 'temperature',
  `targetOp` enum('>=','<=') NOT NULL DEFAULT '>=',
  `targetValue` tinyint(4) NOT NULL DEFAULT 30,
  `turnOnOff` enum('On','Off') NOT NULL DEFAULT 'On',
  `targetTemperature` smallint(6) NOT NULL DEFAULT 26,
  `mode` enum('Cool','Heat','Auto','Fan','Dry') NOT NULL DEFAULT 'Cool',
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'auto',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`whentime`,`uid`) USING BTREE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `weather` (
  `whentime` datetime NOT NULL,
  `temperature` float NOT NULL,
  `feelsLike` float NOT NULL,
  `humidity` float NOT NULL,
  `pressure` float NOT NULL,
  `aq` float NOT NULL,
  PRIMARY KEY (`whentime`)
) ENGINE=InnoDB;

COMMIT;
