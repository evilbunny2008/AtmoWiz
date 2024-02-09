CREATE DATABASE IF NOT EXISTS `sensibo`;
CREATE USER IF NOT EXISTS `sensibo`@`localhost` IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `sensibo`.* TO `sensibo`@`localhost`;
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
  `changes` varchar(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `devices` (
  `uid` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL
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
  `cost` float NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `settings` (
  `uid` varchar(20) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `mode` enum('cool','heat','dry') NOT NULL DEFAULT 'cool',
  `targetType` enum('temperature','humidity','feelsLike') NOT NULL DEFAULT 'temperature',
  `onValue` float NOT NULL DEFAULT 28,
  `offValue` float NOT NULL DEFAULT 26.1,
  `targetTemperature` float NOT NULL DEFAULT 26,
  `fanLevel` enum('quiet','low','medium','high','auto') NOT NULL DEFAULT 'auto',
  `swing` enum('stopped','fixedTop','fixedMiddleTop','fixedMiddleBottom','fixedBottom','rangeFull') NOT NULL DEFAULT 'fixedTop',
  `horizontalSwing` enum('stopped','fixedLeft','fixedCenterLeft','fixedCenter','fixedCenterRight','fixedRight','rangeFull') NOT NULL DEFAULT 'fixedCenter',
  `enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

ALTER TABLE `commands` ADD PRIMARY KEY IF NOT EXISTS (`whentime`,`uid`) USING BTREE;
ALTER TABLE `devices` ADD PRIMARY KEY IF NOT EXISTS (`uid`), ADD KEY IF NOT EXISTS `name` (`name`);
ALTER TABLE `meta` ADD KEY IF NOT EXISTS `uid` (`uid`), ADD KEY IF NOT EXISTS `mode` (`mode`), ADD KEY IF NOT EXISTS `keyval` (`keyval`);
ALTER TABLE `sensibo` ADD PRIMARY KEY IF NOT EXISTS (`whentime`,`uid`) USING BTREE;
ALTER TABLE `settings` ADD PRIMARY KEY IF NOT EXISTS (`uid`,`created`);

COMMIT;
