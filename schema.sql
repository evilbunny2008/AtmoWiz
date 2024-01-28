CREATE DATABASE IF NOT EXISTS `sensibo`;
USE `sensibo`;

CREATE TABLE IF NOT EXISTS `sensibo` (
  `whentime` datetime NOT NULL,
  `uid` varchar(20) NOT NULL DEFAULT '',
  `temperature` float NOT NULL DEFAULT 0,
  `humidity` int(11) NOT NULL DEFAULT 0,
  `feelslike` float NOT NULL DEFAULT 0,
  `rssi` int(11) NOT NULL DEFAULT 0,
  `airconon` tinyint(1) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `targettemp` float NOT NULL,
  `fanlevel` varchar(20) NOT NULL,
  `swing` varchar(20) NOT NULL,
  `horizontalswing` varchar(20) NOT NULL,
  PRIMARY KEY (`whentime`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB;

ALTER TABLE `devices` DROP PRIMARY KEY;
ALTER TABLE `devices` DROP INDEX `uid`;
ALTER TABLE `commands` ADD PRIMARY KEY (`whentime`), ADD KEY `uid` (`uid`);

CREATE TABLE IF NOT EXISTS `commands` (
  `whentime` datetime NOT NULL,
  `uid` varchar(20) NOT NULL,
  `reason` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `airconon` tinyint(1) NOT NULL,
  `mode` varchar(20) NOT NULL
) ENGINE=InnoDB;

ALTER TABLE `devices` DROP PRIMARY KEY;
ALTER TABLE `devices` DROP INDEX `uid`;
ALTER TABLE `commands` ADD PRIMARY KEY (`whentime`), ADD KEY `uid` (`uid`);

CREATE TABLE IF NOT EXISTS `devices` (
  `uid` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB;

ALTER TABLE `devices` DROP PRIMARY KEY;
ALTER TABLE `devices` DROP INDEX `name`;
ALTER TABLE `devices` ADD PRIMARY KEY (`uid`), ADD KEY `name` (`name`);

COMMIT;

CREATE USER IF NOT EXISTS `sensibo`@`localhost` IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `sensibo`.* TO `sensibo`@`localhost`;
