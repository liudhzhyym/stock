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
    `name` varchar(50),
    `value` double,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `stock_index` (`stock`,`day`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `tonghuashun`;


CREATE TABLE `tonghuashun` (
    `strategy` varchar(200),
    `day` varchar(10),
    `page` int(5),
    `result` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `tonghuashun_result` (`strategy`,`day`,`page`),
    KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `new_stock_data`;

CREATE TABLE `new_stock_data` (
    `stock` varchar(10),
    `result` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `new_stock_data_index` (`stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `income`;

CREATE TABLE `income` (
    `code` varchar(10),
    `firstDay` varchar(10),
    `lastDay` varchar(10),
    `sellDay` varchar(10),
    `keepDay` int(5),
    `volPercent` double,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `income_index` (`code`,`firstDay`,`keepDay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `strategy` (
    `id` bigint auto_increment NOT NULL,
    `strategy` varchar(200),
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `strategy_index` (`strategy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `spss`;

CREATE TABLE `spss` (
    `strategy` varchar(200),
    `result` mediumtext,
    `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    UNIQUE KEY `spss_index` (`strategy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
