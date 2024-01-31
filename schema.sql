CREATE DATABASE IF NOT EXISTS `sensibo`;
USE `sensibo`;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `commands` (
  `whentime` datetime NOT NULL,
  `uid` varchar(20) NOT NULL,
  `reason` varchar(20) NOT NULL,
  `who` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `airconon` tinyint(1) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `targetTemperature` tinyint(4) NOT NULL,
  `temperatureUnit` varchar(1) NOT NULL,
  `fanLevel` varchar(20) NOT NULL,
  `swing` varchar(20) NOT NULL,
  `horizontalSwing` varchar(20) NOT NULL,
  PRIMARY KEY (`whentime`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `devices` (
  `uid` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `meta` (
  `uid` varchar(20) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `keyval` varchar(20) NOT NULL,
  `value` varchar(20) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `sensibo` (
  `whentime` datetime NOT NULL,
  `uid` varchar(20) NOT NULL DEFAULT '',
  `temperature` float NOT NULL DEFAULT 0,
  `humidity` int(11) NOT NULL DEFAULT 0,
  `feelslike` float NOT NULL DEFAULT 0,
  `rssi` int(11) NOT NULL DEFAULT 0,
  `airconon` tinyint(1) NOT NULL DEFAULT 0,
  `mode` varchar(20) NOT NULL DEFAULT 'cool',
  `targetTemperature` float NOT NULL DEFAULT 0,
  `fanLevel` varchar(20) NOT NULL DEFAULT 'medium',
  `swing` varchar(20) NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` varchar(20) NOT NULL DEFAULT 'fixedCenter',
  `cost` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`whentime`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB;

COMMIT;

CREATE USER IF NOT EXISTS `sensibo`@`localhost` IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `sensibo`.* TO `sensibo`@`localhost`;
