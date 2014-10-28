/* START - external tables */

INSERT INTO `folder` (`folderpk`, `parentfolderfk`, `label`, `rank`, `ownerloginfk`, `private`) VALUES
(4, 0, 'Shared space', 4, 1, 0),
(5, 4, 'Proposals', 1, 1, 0),
(6, 4, 'Mockups', 2, 1, 0),
(7, 4, 'Administrative', 3, 1, 0),
(8, 4, 'IT and technical', 4, 1, 0),
(9, 4, 'Media', 5, 1, 0),
(10, 4, 'Media kit & sales tools', 6, 1, 0);

INSERT INTO `folder_link` (`folder_linkpk`, `folderfk`, `cp_uid`, `cp_action`, `cp_type`) VALUES
(4, 4, '999-111', 'ppav', 'shdoc'),
(5, 5, '999-111', 'ppav', 'shdoc'),
(6, 6, '999-111', 'ppav', 'shdoc'),
(7, 7, '999-111', 'ppav', 'shdoc'),
(8, 8, '999-111', 'ppav', 'shdoc'),
(9, 9, '999-111', 'ppav', 'shdoc'),
(10, 10, '999-111', 'ppav', 'shdoc');

INSERT INTO `manageable_list` (`manageable_listpk`, `shortname`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `label`, `description`, `item_type`) VALUES
(10, 'doctypes', '', '', '', 0, 'Doc types', 'Types of document available for upload.', 'sortable');

INSERT INTO `manageable_list_item` (`manageable_list_itempk`, `manageable_listfk`, `label`, `value`) VALUES
(84, 10, 'Application .exe', 'application/octet-stream'),
(83, 10, 'Zip File', 'application/zip'),
(82, 10, 'Microsoft powerpoint', 'application/vnd.ms-powerpoint'),
(81, 10, 'Microsoft word', 'application/msword'),
(79, 10, 'Jpeg image file', 'image/jpeg'),
(80, 10, 'Microsoft excel', 'application/vnd.ms-excel'),
(75, 10, 'Open office text', 'application/vnd.oasis.opendocument.text'),
(76, 10, 'PDF', 'application/pdf'),
(77, 10, 'Image png', 'image/png'),
(78, 10, 'Image gif', 'image/gif');

/* DONE - external tables */

/* START - create new tables */
/* START document_link */

CREATE TABLE IF NOT EXISTS `document_link` (
  `document_linkpk` int(11) NOT NULL AUTO_INCREMENT,
  `documentfk` int(11) NOT NULL,
  `cp_uid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `cp_action` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `cp_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `cp_pk` int(11) NOT NULL,
  PRIMARY KEY (`document_linkpk`),
  KEY `eventfk` (`documentfk`),
  KEY `cp_uid` (`cp_uid`),
  KEY `cp_action` (`cp_action`),
  KEY `cp_type` (`cp_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* DONE document_link */

/* START document_notification */

  CREATE TABLE IF NOT EXISTS `document_notification` (
    `document_notificationpk` int(11) NOT NULL AUTO_INCREMENT,
    `documentfk` int(11) NOT NULL,
    `loginfk` int(11) NOT NULL,
    `date_notification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`document_notificationpk`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

  INSERT INTO document_notification (documentfk, loginfk, date_notification)
  SELECT docfk, loginfk, date
  FROM shared_document_log;

/* DONE document_notification */

/* START document_rights */

  CREATE TABLE IF NOT EXISTS `document_rights` (
    `document_rightspk` int(11) NOT NULL AUTO_INCREMENT,
    `documentfk` int(11) NOT NULL,
    `loginfk` int(11) NOT NULL,
    `rights` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    PRIMARY KEY (`document_rightspk`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

  INSERT INTO document_rights (documentfk, loginfk, rights)
  SELECT documentfk, userfk, 'edit'
  FROM shared_document_editor;

  INSERT INTO document_rights (documentfk, loginfk, rights)
  SELECT documentfk, userfk, 'read'
  FROM shared_document_user;

/* DONE document_rights */

/* START document, document_file and folder item
  All values for these three tables are stored in shared_document table
 */

 CREATE  TABLE  `document_file` (  `shared_documentpk` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
 `parentfk` int( 11  )  NOT  NULL ,
 `title` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `description` text CHARACTER  SET utf8 COLLATE utf8_general_ci,
 `mime_type` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `file_name` text CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `file_path` text CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `creatorfk` int( 11  )  NOT  NULL ,
 `is_public` int( 11  )  NOT  NULL  COMMENT  '0-private,1-public,2-custom',
 `is_edit_public` int( 11  )  NOT  NULL ,
 `date_creation` datetime NOT  NULL ,
 `date_update` datetime NOT  NULL ,
 `doc_typefk` int( 11  )  NOT  NULL DEFAULT  '0',
 PRIMARY  KEY (  `shared_documentpk`  ) ,
 KEY  `parentfk` (  `parentfk`  ) ,
 KEY  `date_update` (  `date_update`  ) ,
 KEY  `doc_typefk` (  `doc_typefk`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `document_file` SELECT * FROM `shared_document`;

 CREATE  TABLE  `document` (  `shared_documentpk` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
 `parentfk` int( 11  )  NOT  NULL ,
 `title` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `description` text CHARACTER  SET utf8 COLLATE utf8_general_ci,
 `mime_type` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `file_name` text CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `file_path` text CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `creatorfk` int( 11  )  NOT  NULL ,
 `is_public` int( 11  )  NOT  NULL  COMMENT  '0-private,1-public,2-custom',
 `is_edit_public` int( 11  )  NOT  NULL ,
 `date_creation` datetime NOT  NULL ,
 `date_update` datetime NOT  NULL ,
 `doc_typefk` int( 11  )  NOT  NULL DEFAULT  '0',
 PRIMARY  KEY (  `shared_documentpk`  ) ,
 KEY  `parentfk` (  `parentfk`  ) ,
 KEY  `date_update` (  `date_update`  ) ,
 KEY  `doc_typefk` (  `doc_typefk`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `document` SELECT * FROM `shared_document`;

ALTER TABLE `document` CHANGE `shared_documentpk` `documentpk` INT( 11 ) NOT NULL AUTO_INCREMENT;

DELETE FROM document WHERE parentfk>0;

ALTER TABLE `document` DROP `parentfk`;

ALTER TABLE `document` ADD `description_html` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `description`;

ALTER TABLE `document`
  DROP `file_name`,
  DROP `file_path`,
  DROP `is_edit_public`;

 CREATE  TABLE  `folder_item_toadd` (  `documentpk` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
 `title` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `description` text CHARACTER  SET utf8 COLLATE utf8_general_ci,
 `description_html` text CHARACTER  SET utf8,
 `mime_type` varchar( 255  )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL ,
 `creatorfk` int( 11  )  NOT  NULL ,
 `is_public` int( 11  )  NOT  NULL  COMMENT  '0-private,1-public,2-custom',
 `date_creation` datetime NOT  NULL ,
 `date_update` datetime NOT  NULL ,
 `doc_typefk` int( 11  )  NOT  NULL DEFAULT  '0',
 PRIMARY  KEY (  `documentpk`  ) ,
 KEY  `date_update` (  `date_update`  ) ,
 KEY  `doc_typefk` (  `doc_typefk`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `folder_item_toadd` SELECT * FROM `document`;

ALTER TABLE `folder_item_toadd`
  DROP `description`,
  DROP `description_html`,
  DROP `mime_type`,
  DROP `creatorfk`,
  DROP `is_public`,
  DROP `date_creation`,
  DROP `date_update`;

ALTER TABLE `folder_item_toadd` CHANGE `documentpk` `itemfk` INT( 11 ) NOT NULL;

ALTER TABLE folder_item_toadd DROP INDEX doc_typefk;

ALTER TABLE folder_item_toadd DROP PRIMARY KEY;

ALTER TABLE `folder_item_toadd` CHANGE `title` `label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `folder_item_toadd` ADD `rank` INT NOT NULL AUTO_INCREMENT ,
ADD PRIMARY KEY ( `rank` );

ALTER TABLE `folder_item_toadd` CHANGE `rank` `rank` INT( 11 ) NOT NULL;

ALTER TABLE folder_item_toadd DROP PRIMARY KEY;

ALTER TABLE `folder_item_toadd` ADD `parentfolderfk` INT NOT NULL;

UPDATE folder_item_toadd SET parentfolderfk=9 WHERE doc_typefk=1;
UPDATE folder_item_toadd SET parentfolderfk=5 WHERE doc_typefk=2;
UPDATE folder_item_toadd SET parentfolderfk=6 WHERE doc_typefk=3;
UPDATE folder_item_toadd SET parentfolderfk=10 WHERE doc_typefk=4;
UPDATE folder_item_toadd SET parentfolderfk=8 WHERE doc_typefk=5;
UPDATE folder_item_toadd SET parentfolderfk=7 WHERE doc_typefk=6;
UPDATE folder_item_toadd SET parentfolderfk=4 WHERE doc_typefk=0;

ALTER TABLE `folder_item_toadd` DROP `doc_typefk`;

INSERT INTO folder_item (itemfk, label, parentfolderfk, rank)
SELECT itemfk, label, parentfolderfk, rank
FROM folder_item_toadd;

DROP TABLE folder_item_toadd;

/* DONE folder_item */

ALTER TABLE `document` DROP `doc_typefk`;
ALTER TABLE `document` DROP `mime_type`;

ALTER TABLE `document` ADD `private` INT NOT NULL COMMENT '0:public, 1:private, 2: custom' AFTER `is_public`;
UPDATE document SET private=0 WHERE is_public=1;
UPDATE document SET private=1 WHERE is_public=0;

ALTER TABLE `document` DROP `is_public`;
ALTER TABLE `document` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `document` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;


/* DONE document */

UPDATE document_file SET parentfk=shared_documentpk WHERE parentfk=0;

ALTER TABLE `document_file` CHANGE `parentfk` `documentfk` INT( 11 ) NOT NULL;


ALTER TABLE `document_file`
  DROP `title`,
  DROP `description`,
  DROP `is_public`,
  DROP `is_edit_public`,
  DROP `date_update`,
  DROP `doc_typefk`;

ALTER TABLE `document_file` ADD `original` LONGTEXT NOT NULL ,
ADD `compressed` LONGTEXT NOT NULL ,
ADD `language` VARCHAR( 10 ) NOT NULL ,
ADD `live` INT NOT NULL;

UPDATE `document_file` SET live=1;

ALTER TABLE `document_file` CHANGE `shared_documentpk` `shared_documentpk` INT( 11 ) NOT NULL;

ALTER TABLE document_file DROP PRIMARY KEY;

ALTER TABLE `document_file` DROP `shared_documentpk`;

ALTER TABLE `document_file` ADD `document_filepk` INT NOT NULL AUTO_INCREMENT FIRST ,
ADD PRIMARY KEY ( `document_filepk` );

/* DONE document_file */

/* START Getting back address book documents */

ALTER TABLE `addressbook_document` ADD `documentpk` INT NOT NULL FIRST;
UPDATE `addressbook_document` SET documentpk=(addressbook_documentpk+1000);

ALTER TABLE `addressbook_document_info` ADD `documentfk` INT NOT NULL FIRST;
UPDATE `addressbook_document_info` SET documentfk=(docfk+1000);

INSERT INTO document (documentpk, title, description, creatorfk, private, date_creation, date_update)
SELECT documentpk, title, description, loginfk, '0', date_create, date_create
FROM addressbook_document;

INSERT INTO document_file (documentfk, file_name, file_path, creatorfk, date_creation, live)
SELECT documentpk, filename, path_name, loginfk, date_create, '1'
FROM addressbook_document;

INSERT INTO document_link (documentfk, cp_uid, cp_action, cp_type, cp_pk)
SELECT documentfk, '777-249', 'ppav', type , itemfk
FROM addressbook_document_info;

/* Cleaning */
DROP TABLE `addressbook_document`, `addressbook_document_info`, `shared_document`, `shared_document_editor`, `shared_document_log`, `shared_document_user`, `shared_doc_type`;


UPDATE document_file SET file_path = REPLACE(file_path, '/common/upload/addressbook/document/', '/common/upload/sharedspace/doc_migrated/');

/* PAUL 2013 09 13 */

INSERT INTO `manageable_list_item` (`manageable_list_itempk`, `manageable_listfk`, `label`, `value`) VALUES
(85, 10, 'Text file .txt', 'text/plain'),
(86, 10, 'CSV file', 'text/csv'),
(87, 10, 'Zip file', 'application/zip'),
(88, 10, 'BZ file', 'application/x-bzip'),
(89, 10, 'Tar file', 'application/x-tar'),
(90, 10, 'Open office Calc .ods', 'application/vnd.oasis.opendocument.spreadsheet'),
(91, 10, 'Open office Presentation .odp', 'application/vnd.oasis.opendocument.presentation');


-- stef: 02-10
ALTER TABLE `document_file` ADD `initial_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `mime_type` ;
UPDATE `document_file` SET `initial_name` = `file_name`;

ALTER TABLE `document_file` ADD `file_size` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `file_path` ;

ALTER TABLE `document_file` ADD INDEX ( `initial_name` );
ALTER TABLE `document_file` ADD INDEX ( `date_creation` );
ALTER TABLE `document_file` ADD INDEX ( `creatorfk` );
ALTER TABLE `document_file` ADD INDEX ( `documentfk` );


DROP view IF EXISTS shared_document_folder_item;

CREATE VIEW shared_document_folder_item AS
    SELECT fi.rank, fi.label, fi.parentfolderfk, d.*, df.file_path, df.file_name, df.file_size, df.mime_type, df.date_creation as date_last_revision
    FROM document d LEFT JOIN document_file df ON df.documentfk=d.documentpk
    LEFT JOIN folder_item fi ON d.documentpk = fi.itemfk
    LEFT JOIN folder_link fl ON fl.folderfk = fi.parentfolderfk
    WHERE df.live=1 AND fl.cp_uid='999-111' AND fl.cp_action = 'ppav' AND fl.cp_type = 'shdoc';