CREATE TABLE IF NOT EXISTS `login_group` (
  `login_grouppk` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `system` int(11) NOT NULL,
  `visible` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`login_grouppk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `login_group_member`
--

CREATE TABLE IF NOT EXISTS `login_group_member` (
  `login_group_memberpk` int(11) NOT NULL AUTO_INCREMENT,
  `login_groupfk` int(11) NOT NULL,
  `loginfk` int(11) NOT NULL,
  PRIMARY KEY (`login_group_memberpk`),
  UNIQUE KEY `login_groupfk` (`login_groupfk`,`loginfk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;




-- new table to manage event created with external email addresses
CREATE TABLE IF NOT EXISTS `zimbra_external_account` (
  `zimbra_external_accountpk` int(11) NOT NULL AUTO_INCREMENT,
  `loginfk` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`zimbra_external_accountpk`),
  KEY `loginfk` (`loginfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

INSERT INTO `zimbra_external_account` (`zimbra_external_accountpk`, `loginfk`, `email`) VALUES
(1, 5, 'sboudoux@gmail.com'),
(2, 5, 'sboudoux@slate.co.jp'),
(3, 26, 'ohade6@gmail.com');
