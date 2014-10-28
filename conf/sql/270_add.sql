CREATE TABLE IF NOT EXISTS `folder_rights` (
  `folder_rightpk` int(11) NOT NULL AUTO_INCREMENT,
  `folderfk` int(11) NOT NULL,
  `loginfk` int(11) NOT NULL,
  `rights` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`folder_rightpk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `folder_link` (
  `folder_linkpk` int(11) NOT NULL AUTO_INCREMENT,
  `folderfk` int(11) NOT NULL,
  `cp_uid` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cp_action` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cp_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`folder_linkpk`),
  KEY `eventfk` (`folderfk`),
  KEY `cp_uid` (`cp_uid`),
  KEY `cp_action` (`cp_action`),
  KEY `cp_type` (`cp_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

ALTER TABLE `folder_link` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `folder_item` (
  `folder_itempk` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `parentfolderfk` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `itemfk` int(11) NOT NULL,
  PRIMARY KEY (`folder_itempk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `folder` (
  `folderpk` int(11) NOT NULL AUTO_INCREMENT,
  `parentfolderfk` int(11) NOT NULL DEFAULT '0',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `rank` int(11) NOT NULL,
  `ownerloginfk` int(11) NOT NULL,
  `private` tinyint(1) NOT NULL,
  PRIMARY KEY (`folderpk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

