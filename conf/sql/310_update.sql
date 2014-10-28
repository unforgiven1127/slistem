
ALTER TABLE `login_activity` ADD `item` TEXT NOT NULL AFTER `cp_pk` ;

INSERT INTO `version` (`version`, date_version) VALUES ('3.1.0.u - stef', NOW());


-- why ??  O_O
ALTER TABLE `login_activity` CHANGE `item` `item` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ;
--should be  ALTER TABLE `login_activity` CHANGE `item` `item` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, CHANGE `data` `data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
INSERT INTO `version` (`version`, date_version) VALUES ('3.1.1.u - stef', NOW());




INSERT INTO `manageable_list_item` (`manageable_list_itempk` , `manageable_listfk` , `label` , `value`)
VALUES
(NULL , '10', 'Bzip Archive (.bz2)', 'application/x-bzip2'),
(NULL , '10', 'Gzip Archive (.gz)', 'application/x-gzip');

ALTER TABLE document_file DROP INDEX parentfk;
ALTER TABLE `document_file` ADD INDEX (`original`(1000));
ALTER TABLE `document_file` ADD FULLTEXT (`compressed`);

INSERT INTO `version` (`version`, date_version) VALUES ('3.1.2.u - stef', NOW());



ALTER TABLE `document` CHANGE `date_update` `date_update` DATETIME NULL ;
ALTER TABLE `document_file` CHANGE `compressed` `compressed` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
INSERT INTO `version` (`version`, date_version) VALUES ('3.1.3.u - stef', NOW());



ALTER TABLE login ADD INDEX (`status`);
ALTER TABLE login ADD INDEX (`id`);
ALTER TABLE login ADD INDEX (`password`);
ALTER TABLE login ADD INDEX (`lastname`);
ALTER TABLE login ADD INDEX (`firstname`);
ALTER TABLE login ADD INDEX (`email`);



INSERT INTO `version` (`version`, date_version) VALUES ('3.1.4.u - stef', NOW());