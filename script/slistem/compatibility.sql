
UPDATE sl_candidate SET statusfk = 1 WHERE statusfk = 0;
UPDATE sl_candidate SET statusfk = 2 WHERE statusfk = 3;

UPDATE `sl_position_link` SET STATUS = 51 WHERE STATUS = 50;






-- already there:
INSERT IGNORE INTO login_group_member (loginfk, login_groupfk)
VALUES
(442,112),(382,107),(382,109),(101,2),(382,109),(421,109),(101,105),(101,111),(421,106),(301,107),(301,109),(453,107),(453,109),
(424,107),(424,109),(441,107),(441,109),(444,107),(444,109),(300,106),(447,107),(447,109),(432,107),(432,109),(418,106),(418,109),
(418,4),(360,106),(360,108),(360,103),(360,3),(241,106),(240,106),(240,112),(423,107),(423,109),(436,106),(436,109),(436,3),(302,106),
(302,108),(302,4),(289,106),(289,3),(429,105),(440,107),(440,109),(315,106),(315,109),(315,4),(407,106),(407,112),(408,107),(408,109),
(412,106),(412,108),(412,2),(443,106),(443,109),(443,3),(419,107),(419,109),(285,107),(285,109),(431,106),(431,109),(431,4),(390,107),
(390,109),(390,2),(422,107),(422,109),(422,1),(388,106),(388,108),(388,4),(274,106),(274,112),(448,106),(448,112),(298,106),(298,109),
(28,106),(28,109),(28,1),(290,107),(290,105),(266,107),(266,109),(314,106),(314,108),(314,103),(314,2),(445,107),(445,108),(445,2),
(85,106),(85,108),(85,103),(85,4),(406,106),(309,106),(309,111),(276,108),(276,4),(430,106),(430,111),(374,106),(374,109),(374,3),
(304,106),(304,109),(304,1),(354,108),(354,1),(236,107),(236,109),(451,107),(451,109),(446,107),(446,109),(343,106),(306,107),(306,109),
(366,106),(366,108),(366,1),(186,107),(186,108),(186,1),(99,106),(99,108),(99,103),(99,2),(405,106),(350,107),(350,109),(398,107),
(398,109),(395,106),(395,108),(395,3),(312,107),(312,112),(367,106),(367,105),(367,111),(215,106),(215,105),(102,106),(102,109),(102,4),
(155,107),(155,109),(155,3),(299,107),(299,109),(386,107),(386,109),(130,106),(130,109),(130,3),(293,107),(293,109),(347,106),(347,108),
(347,2),(199,106);


-- 103: manager  | 110: HK   | 113: canada  | 112: mailing lists
INSERT INTO login_group_member (loginfk, login_groupfk)
VALUES (354,103), (276,103), (458,103), (186,103), (186,110), (366,110), (354,113), (276,113), (241,112);


-- 16 June: copy active users to real users (for KPI)!!
INSERT INTO login_group_member (loginfk, login_groupfk)
(
  SELECT loginfk, 116 FROM login_group_member WHERE login_groupfk = 115
);
-- real users
DELETE FROM login_group_member WHERE login_groupfk = 116 AND loginfk IN (241,240,442,407,274,448,406,445,312,215);

-- Consultant based on mitch list
DELETE FROM login_group_member WHERE login_groupfk = 108 AND loginfk NOT IN (360,314,85,276,354,186,99,374,389,395,347,388,302,462);
UPDATE login SET position = 'Consultant' WHERE loginpk IN (360,314,85,276,354,186,99,374,389,395,347,388,302,462);

-- Researcher based on mitch list
DELETE FROM login_group_member WHERE login_groupfk = 109 AND loginfk NOT IN (382, 220, 423, 301,419,446,447,457,443,130,431);
UPDATE login SET position = 'Researcher' WHERE loginpk IN (382, 220, 423, 301,419,446,447,457,443,130,431);

-- Admin based on mitch list  (missing Chester)
DELETE FROM login_group_member WHERE login_groupfk = 105 AND loginfk NOT IN (199,343,309,260,306,462);
INSERT INTO login_group_member (loginfk, login_groupfk)
VALUES (199,105),(343,105),(309,105),(260,105),(306,105),(462,105);
UPDATE login SET position = 'Administration' WHERE loginpk IN (199,343,309,260,306,462);


-- Update tokyo
DELETE FROM login_group_member WHERE login_groupfk = 106 AND loginfk IN (430, 405, 421, 300, 418, 241, 240, 315, 407, 274, 448, 312);

-- 405 sakura --> inactive

UPDATE `login_group` SET `title` = 'Active users' WHERE `login_group`.`login_grouppk` = 116;
UPDATE `login_group` SET `title` = 'Management' WHERE `login_group`.`login_grouppk` = 103;
UPDATE `login_group` SET `title` = 'Administration' WHERE `login_group`.`login_grouppk` = 105;








-- add default industries to all the company having employees
-- INSERT INTO sl_company_industry (sl_companyfk, industryfk)
-- SELECT companyfk, industryfk FROM sl_candidate_profile WHERE companyfk <> 0 AND industryfk <> 0
-- GROUP BY companyfk, industryfk;

DROP TABLE sl_company_industry;
INSERT INTO sl_attribute (`type`, itemfk, attributefk)
SELECT 'cp_indus', companyfk, industryfk FROM sl_candidate_profile WHERE companyfk <> 0 AND industryfk <> 0
GROUP BY companyfk, industryfk;


-- TRUNCATE TABLE `sl_company` ;
-- DELETE FROM `event` WHERE `type` = 'description';
-- DELETE FROM `event_link` WHERE `cp_type` = 'comp';





-- Yuko, rossana, nic, mmallo
UPDATE login SET is_admin = 1 WHERE loginpk IN (101, 199,343,309, 260);


-- auto expire old RMs to not spam people
UPDATE sl_candidate_rm SET date_expired = date_end WHERE date_ended < '2014-04-01';




ALTER TABLE event AUTO_INCREMENT = 5000000;
ALTER TABLE sl_company AUTO_INCREMENT = 100000;
ALTER TABLE sl_candidate AUTO_INCREMENT = 500000;
ALTER TABLE sl_meeting AUTO_INCREMENT = 50000;





CREATE OR REPLACE VIEW `shared_login` AS
(
  select `l`.`loginpk` AS `loginpk`,`l`.`pseudo` AS `pseudo`,`l`.`birthdate` AS `birthdate`,`l`.`gender` AS `gender`,
  `l`.`courtesy` AS `courtesy`,`l`.`email` AS `email`,`l`.`lastname` AS `lastname`,
  `l`.`firstname` AS `firstname`,concat(`l`.`firstname`,' ',`l`.`lastname`) AS `fullname`,
  `l`.`phone` AS `phone`,`l`.`phone_ext` AS `phone_ext`,`l`.`status` AS `status`,
  NULL AS `login_groupfk`,`l`.`is_admin` AS `is_admin`,
  if((length(`l`.`pseudo`) > 0),`l`.`pseudo`,`l`.`firstname`) AS `friendly`

  from `login` `l`

);


CREATE OR REPLACE VIEW `shared_event` AS
(
  select `event`.`eventpk` AS `eventpk`,`event`.`type` AS `type`,`event`.`custom_type` AS `custom_type`,
`event`.`title` AS `title`,`event`.`content` AS `content`,`event`.`date_create` AS `date_create`,`event`.`date_display` AS `date_display`,
`event`.`created_by` AS `created_by`,`event`.`date_update` AS `date_update`,`event`.`updated_by` AS `updated_by`,
`el`.`event_linkpk` AS `event_linkpk`,`el`.`eventfk` AS `eventfk`,`el`.`cp_uid` AS `cp_uid`,`el`.`cp_action` AS `cp_action`,
`el`.`cp_type` AS `cp_type`,`el`.`cp_pk` AS `cp_pk`,`el`.`cp_params` AS `cp_params`
from (`event` join `event_link` `el` on((`el`.`eventfk` = `event`.`eventpk`)))
);


CREATE OR REPLACE VIEW `shared_document_folder_item` AS
select `fi`.`rank` AS `rank`,`fi`.`label` AS `label`,`fi`.`parentfolderfk` AS `parentfolderfk`,`d`.`documentpk` AS `documentpk`,
`d`.`title` AS `title`,`d`.`description` AS `description`,`d`.`description_html` AS `description_html`,`d`.`creatorfk` AS `creatorfk`,
`d`.`private` AS `private`,`d`.`date_creation` AS `date_creation`,`d`.`date_update` AS `date_update`,`df`.`file_path` AS `file_path`,
`df`.`file_name` AS `file_name`,`df`.`file_size` AS `file_size`,`df`.`mime_type` AS `mime_type`,
`df`.`date_creation` AS `date_last_revision`
from (((`document` `d` left join `document_file` `df` on((`df`.`documentfk` = `d`.`documentpk`)))
left join `folder_item` `fi` on((`d`.`documentpk` = `fi`.`itemfk`)))
left join `folder_link` `fl` on((`fl`.`folderfk` = `fi`.`parentfolderfk`)))
where ((`df`.`live` = 1) and (`fl`.`cp_uid` = '999-111') and (`fl`.`cp_action` = 'ppav') and (`fl`.`cp_type` = 'shdoc')
);




ALTER TABLE `login_system_history` CHANGE `uri` `uri` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;



-- =====================================================================================================
-- =====================================================================================================
 stop here !!! different database

DROP TABLE `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `access` int(11) unsigned DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;





-- =====================================================================================================
-- =====================================================================================================
-- =====================================================================================================
-- get position from master SHOW MASTER SATTUS;
-- Then
--
-- SLATE STOP;
-- CHANGE MASTER TO MASTER_HOST='172.31.29.60',  MASTER_USER='replication',  MASTER_PASSWORD='sl3!Slave#',  MASTER_LOG_FILE='mysql-bin.000004',  MASTER_LOG_POS=763454156;
-- SLAVE START;


-- =====================================================================================================
-- =====================================================================================================
-- =====================================================================================================
-- Move missing files
-- rsync -rapoz --progress root@squirrel.slate.co.jp:/home/slate/public_html/slistem/__shared_upload__/sharedspace/document /hdd/www/slistem/__upload__/sharedspace/document