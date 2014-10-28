-- stef 27-05-2013: system history with partitions
-- the system will never work with a table bigger than 250000 rows

CREATE TABLE IF NOT EXISTS `login_system_history` (
  `login_system_historypk` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userfk` int(11) NOT NULL,
  `action` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `component` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `uri` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`login_system_historypk`),
  KEY `userfk` (`userfk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1

PARTITION BY RANGE( login_system_historypk ) (
    PARTITION p0 VALUES LESS THAN (250000),
    PARTITION p1 VALUES LESS THAN (500000),
    PARTITION p2 VALUES LESS THAN (750000),
    PARTITION p3 VALUES LESS THAN (1000000),
    PARTITION p4 VALUES LESS THAN (1250000),
    PARTITION p5 VALUES LESS THAN (1500000),
    PARTITION p6 VALUES LESS THAN MAXVALUE
);


CREATE TABLE IF NOT EXISTS `settings_user` (
  `settings_userpk` int(11) NOT NULL AUTO_INCREMENT,
  `loginfk` int(11) NOT NULL,
  `settingsfk` int(11) NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`settings_userpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT= 1 ;



-- stef: create a version table to know what sql we have to run when updating one of the platforms
CREATE TABLE IF NOT EXISTS `version` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `version` (`version`) VALUES
('2.5.0');