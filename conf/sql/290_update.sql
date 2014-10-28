

INSERT INTO `manageable_list_item` (`manageable_list_itempk`, `manageable_listfk`, `label`, `value`) VALUES
(92, 10, 'HTML & XML', 'application/xml'),
(93, 10, 'Ms-Word Docx', 'application/vnd.ms-word'),
(94, 10, 'Text file', 'text/x-c++'),
(95, 10, 'HTML document', 'text/html'),
(96, 10, 'XML document', 'application/xml-sitemap'),
(97, 10, 'text - script', 'text/x-php');


INSERT INTO `version` (`version`, date_version) VALUES ('2.9.0u', NOW());



-- 09 Oct stef: make sure the migrated files and new files are not gonna overlap
ALTER TABLE `document` AUTO_INCREMENT = 10000;
ALTER TABLE `document_file` AUTO_INCREMENT = 10000;


INSERT INTO `version` (`version`, date_version) VALUES ('2.9.1u', NOW());

-- 19 Nov Paul: Counting and loging document downloads
ALTER TABLE `document` ADD `downloads` INT NOT NULL;

CREATE TABLE IF NOT EXISTS `document_log` (
  `document_logpk` int(11) NOT NULL AUTO_INCREMENT,
  `loginfk` int(11) NOT NULL,
  `documentfk` int(11) NOT NULL,
  `document_filefk` int(11) NOT NULL,
  `date_download` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_logpk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

-- 20 Nov Paul : removing teamfk from login table to use login_group table

DELETE FROM `login_group_member` WHERE loginfk IN (SELECT loginpk FROM login);

INSERT INTO login_group_member (loginfk, login_groupfk)
SELECT loginpk, (teamfk+100) FROM login;

INSERT INTO `login_group` (`login_grouppk`, `shortname`, `title`, `system`, `visible`) VALUES
(101, 'sales', 'Sales', 1, 1),
(102, 'it', 'IT', 1, 1),
(103, 'manag', 'Manag', 1, 1),
(104, 'prod', 'Prod', 1, 1),
(105, 'admin', 'Admin', 1, 1);

ALTER TABLE `login` DROP `teamfk`;

-- 27 Nov Paul - Create right for viewing all company opportunity
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
(152, 'View all opportunities', 'Allows to access the whole company opportunities dashboard.', 'right', '555-123', 'view-all', 'opp', 0, NULL);

ALTER TABLE `opportunity` ADD `date_update` DATETIME NOT NULL;




INSERT INTO `version` (`version`, date_version) VALUES ('2.9.1u - stef', NOW());


ALTER TABLE `settings` ADD `is_user_setting` INT NOT NULL ;
UPDATE `settings` SET `is_user_setting` = 1 WHERE settingspk IN
(
  SELECT DISTINCT(settingsfk) FROM settings_user
);
INSERT INTO `version` (`version`, date_version) VALUES ('2.9.2u - stef', NOW());


-- stef 08/11/2013
ALTER TABLE `document` ADD `doc_type` VARCHAR( 255 ) NULL AFTER `title` , ADD INDEX ( `doc_type` ) ;
INSERT INTO `version` (`version`, date_version) VALUES ('2.9.3u - stef', NOW());





-- stef: 25-11-2013 indexes on event tables
ALTER TABLE `event` ADD INDEX ( `type` );
ALTER TABLE `event` ADD INDEX ( `custom_type` );
ALTER TABLE `event` ADD INDEX ( `date_create` );
ALTER TABLE `event` ADD INDEX ( `date_display` );
ALTER TABLE `event` ADD INDEX ( `created_by` );
ALTER TABLE `event` ADD INDEX ( `updated_by` );

INSERT INTO `version` (`version`, date_version) VALUES ('2.9.4u - stef', NOW());


-- 4 Dec Paul - Rights for Ajax duplicate checking
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`)
VALUES (153, 'Search duplicates', 'Ajax duplicate search.', 'alias', '777-249', 'srcdp', '', '', NULL);

INSERT INTO `right_tree` (`right_treepk`, `rightfk`, `parentfk`) VALUES
('', 153, 3);

INSERT INTO `version` (`version`, date_version) VALUES ('2.9.5u - paul', NOW());




-- Paul
-- 19-12-2013 : Black Book

ALTER TABLE `opportunity_detail` CHANGE `payed` `paid` TINYINT( 1 ) NOT NULL;
ALTER TABLE `opportunity_detail` ADD `invoiced` BOOLEAN NOT NULL;
ALTER TABLE `opportunity_detail` ADD `booked` BOOLEAN NOT NULL;

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
('', 'Tokyo Weekender Black Book', 'Tokyo Weekender opportunities listing', 'static', '555-123', 'bbk', 'opp', 0, NULL),
('', 'Product supervisor', 'Can pass a product status to booked or delivered', 'right', '555-123', 'right_op_supervisor', '', 0, NULL);

INSERT INTO `version` (`version`, date_version) VALUES ('2.9.6u - stef', NOW());

