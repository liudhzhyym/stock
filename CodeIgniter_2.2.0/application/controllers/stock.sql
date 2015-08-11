DROP TABLE IF EXISTS `stock`;


CREATE TABLE `stock` (
    `strategy` varchar(200),
    `day` varchar(10),
    `page` int(5),
    `result` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `strategy_result` (`strategy`,`day`,`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
