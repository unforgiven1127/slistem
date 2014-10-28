

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


--
-- Table structure for table `notification`
--

CREATE TABLE IF NOT EXISTS `notification` (
  `notificationpk` int(11) NOT NULL AUTO_INCREMENT,
  `date_created` datetime NOT NULL,
  `creatorfk` int(11) NOT NULL,
  `date_notification` datetime NOT NULL,
  `title` text COLLATE utf8_unicode_ci,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `message_format` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `delivered` int(11) NOT NULL COMMENT '0: default status, 1: delivered, 2: cancelled, -1: failed once, 999: failed multiple times',
  PRIMARY KEY (`notificationpk`),
  KEY `creatorfk` (`creatorfk`),
  KEY `date_notification` (`date_notification`),
  KEY `type` (`type`),
  KEY `deliverd` (`delivered`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_action`
--

CREATE TABLE IF NOT EXISTS `notification_action` (
  `notification_actionpk` int(11) NOT NULL AUTO_INCREMENT,
  `notificationfk` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `naggy` int(11) NOT NULL,
  `naggy_frequency` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `naggy_confirmed` int(11) DEFAULT NULL,
  `number_sent` int(11) NOT NULL,
  `date_last_action` datetime DEFAULT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`notification_actionpk`),
  KEY `notificationfk` (`notificationfk`),
  KEY `type` (`type`),
  KEY `naggy_frequency` (`naggy_frequency`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_link`
--

CREATE TABLE IF NOT EXISTS `notification_link` (
  `notification_linkpk` int(11) NOT NULL AUTO_INCREMENT,
  `notificationfk` int(11) NOT NULL,
  `linked_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_pk` int(11) NOT NULL,
  PRIMARY KEY (`notification_linkpk`),
  KEY `notificationfk` (`notificationfk`),
  KEY `uid` (`cp_uid`),
  KEY `action` (`cp_action`),
  KEY `type` (`cp_type`),
  KEY `pk` (`cp_pk`),
  KEY `linked_to` (`linked_to`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_recipient`
--

CREATE TABLE IF NOT EXISTS `notification_recipient` (
  `notification_recipientpk` int(11) NOT NULL AUTO_INCREMENT,
  `notificationfk` int(11) NOT NULL,
  `loginfk` int(11) NOT NULL,
  `email` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`notification_recipientpk`),
  KEY `notificationfk` (`notificationfk`),
  KEY `loginfk` (`loginfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

