DROP TABLE IF EXISTS `stock`;


CREATE TABLE `stock` (
    `strategy` varchar(200),
    `day` varchar(10),
    `page` int(5),
    `result` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `strategy_result` (`strategy`,`day`,`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `stock_data`;


CREATE TABLE `stock_data` (
    `stock` varchar(10),
    `day` varchar(10),
    `indicator` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `stock_index` (`stock`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;