

-- Paul: 25-05-2013
ALTER TABLE `settings` ADD `options` TEXT NULL AFTER `fieldtype`;
ALTER TABLE `login` ADD `date_passwd_changed` DATETIME NOT NULL;
UPDATE login SET date_passwd_changed='2013-05-22 07:00:00';




-- Paul: 06-06-2013
ALTER TABLE `login` ADD `otherloginfks` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

DROP TABLE login_preference;
DROP TABLE user_preference;

UPDATE `settings` SET `fieldtype` = 'text' WHERE `fieldname` = 'urlparam';

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(113, 'User Preference', 'Allows user to access preferences.', 'right', '665-544', 'ppal', 'usrprf', 0);

-- Stef: 11-06-2013   missing rights
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(114, 'Save User Preference', 'Allows user to save preferences.', 'alias', '665-544', 'ppasc', 'usrprf', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES(114, 113);


-- ALTER TABLE `login_activity` CHANGE `sentemail` `sentemail` INT NOT NULL DEFAULT '0';
ALTER TABLE `login_activity` ADD `sentemail` INT NOT NULL DEFAULT '0';
UPDATE `login_activity` SET `sentemail` = 1;

ALTER TABLE `customfield_value` CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `customfield_value` ADD UNIQUE (`customfieldfk` , `itemfk`);


DELETE FROM settings WHERE fieldname = 'menu';


ALTER TABLE `event` ADD `custom_type` INT NOT NULL AFTER `type` ;


