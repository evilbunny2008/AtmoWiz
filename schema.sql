DROP TABLE IF EXISTS `fujitsu`;
CREATE TABLE IF NOT EXISTS `fujitsu` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
