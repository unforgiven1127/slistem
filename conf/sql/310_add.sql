

-- INSERT INTO `version` (`version`, date_version) VALUES ('3.1.0.a - stef', NOW());


ALTER TABLE `login_system_history` ADD `description` TEXT NULL AFTER `action`;
ALTER TABLE `login_system_history` ADD `table` TEXT NULL AFTER `description`;
INSERT INTO `version` (`version`, date_version) VALUES ('3.1.1.a - stef', NOW());