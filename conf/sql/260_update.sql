-- ------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------
-- already updated online to fix bug

-- fix customfield table. Add defaultvalue if dopes not exist or alter it
ALTER TABLE `customfield` ADD `defaultvalue` TEXT NULL;
ALTER TABLE `customfield` CHANGE `defaultvalue` `defaultvalue` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;


ALTER TABLE `customfield` DROP `uid`;
ALTER TABLE `customfield` DROP `action`;
ALTER TABLE `customfield` DROP `type`;
ALTER TABLE `customfield` DROP `pk`;


-- fixing encoding & indexes

ALTER TABLE `customfield` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `customfield` CHANGE `label` `label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE `fieldtype` `fieldtype` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE `defaultvalue` `defaultvalue` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;

ALTER TABLE `customfield_link` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `customfield_link` CHANGE `cp_uid` `cp_uid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE `cp_action` `cp_action` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
 CHANGE `cp_type` `cp_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;

ALTER TABLE `customfield_link` ADD INDEX ( `customfieldfk` ) ;
ALTER TABLE `customfield_link` ADD INDEX ( `cp_uid` ) ;
ALTER TABLE `customfield_link` ADD INDEX ( `cp_type` ) ;
ALTER TABLE`customfield_link` ADD INDEX ( `cp_pk` ) ;


ALTER TABLE `customfield_option` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `customfield_option` CHANGE `label` `label` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
 CHANGE `value` `value` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;
ALTER TABLE `customfield_option` ADD INDEX ( `customfieldfk` ) ;
ALTER TABLE `customfield_option` ADD INDEX ( `label` ) ;
ALTER TABLE `customfield_option` ADD INDEX ( `value` ) ;


ALTER TABLE `customfield_value` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `customfield_value` CHANGE `itemfk` `itemfk` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
 CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;



-- ------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------
-- Resume update for version 260

ALTER TABLE `right_user` ADD `groupfk` INT (11) NOT NULL AFTER `loginfk` , ADD INDEX ( `groupfk` );



-- change login right, it shouldn't be a general static
-- add rights type: "logged" given automatically to anybody logged in

TRUNCATE TABLE `right`;
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(1, 'Addressbook admin', 'Full access to addressbook, with access to other people data and delete features', 'right', '777-249', 'right_admin', '', 0),
(2, 'Addressbook manager', 'Can access most of the features. Can''t delete connection or companies', 'right', '777-249', 'right_manager', '', 0),
(3, 'Addressbook viewer', 'Can view amd browse connection and company lists', 'right', '777-249', 'right_viewer', '', 0),
(4, 'View connection', 'View connection ', 'alias', '777-249', 'ppav', 'ct', 0),
(5, 'List connections', 'list / search connection', 'alias', '777-249', 'ppal', 'ct', 0),
(6, 'View company', 'View company ', 'alias', '777-249', 'ppav', 'cp', 0),
(7, 'List companies', 'list / search companies', 'alias', '777-249', 'ppal', 'cp', 0),
(8, 'Login static access', 'Allow to access login', 'static', '579-704', 'ppav', '', 0),
(9, 'Project Admin', 'Full access to the project component. Can manage other people''s tasks and projects.', 'right', '456-789', 'right_admin', '', 0),
(10, 'Project Manager', 'Can access most of the features. Can not remove the projects and tasks', 'right', '456-789', 'right_manager', '', 0),
(11, 'Project Viewer', 'Can View the list of the projects and view the project information', 'right', '456-789', 'right_viewer', '', 0),
(12, 'View project', 'View Project ', 'alias', '456-789', 'ppav', 'prj', 0),
(13, 'List project', 'List project ', 'alias', '456-789', 'ppal', 'prj', 0),
(14, 'Sharedspace admin', 'Full access to sharedspace component. Can manage other people''s documents.', 'right', '999-111', 'right_admin', '', 0),
(15, 'Sharedspace manager', 'Access to most of the features but can not remove the shared document', 'right', '999-111', 'right_manager', '', 0),
(16, 'List docs', 'List Shared Documents ', 'alias', '999-111', 'ppal', 'shdoc', 0),
(17, 'Downlaod docs', 'Download Shared Documents ', 'alias', '999-111', 'ppasen', 'shdoc', 0),
(18, 'Add Company', 'Access rights to add a new company', 'alias', '777-249', 'ppaa', 'cp', 0),
(19, 'Add Connection', 'Access rights to add a new connection', 'alias', '777-249', 'ppaa', 'ct', 0),
(20, 'Edit Company', 'Access Rights to edit company', 'alias', '777-249', 'ppae', 'cp', 0),
(21, 'Edit Connection', 'Access Rights to edit connection', 'alias', '777-249', 'ppae', 'ct', 0),
(22, 'Save new company', 'Access rights to save Company', 'alias', '777-249', 'ppasa', 'cp', 0),
(23, 'Save new connection', 'Access rights to save Connection', 'alias', '777-249', 'ppasa', 'ct', 0),
(24, 'Save existing company', 'Access rights to save Edited Company', 'alias', '777-249', 'ppase', 'cp', 0),
(25, 'Save existing connection', 'Access rights to save Edited Connection', 'alias', '777-249', 'ppase', 'ct', 0),
(26, 'Change company account manager', 'Access Right to change company account manager', 'alias', '777-249', 'ppat', 'cp', 0),
(27, 'Change connection account manager', 'Access Right to change connection account manager', 'alias', '777-249', 'ppat', 'ct', 0),
(28, 'Manage company profile', 'Access right to link company', 'alias', '777-249', 'ppam', 'cp', 0),
(29, 'Manage connection profile', 'Access right to link connection', 'alias', '777-249', 'ppam', 'ct', 0),
(30, 'Save Company Account Manager', 'Access right to save company account manager', 'alias', '777-249', 'ppast', 'cp', 0),
(31, 'Save Connection Account Manager', 'Access right to save connection account manager', 'alias', '777-249', 'ppast', 'ct', 0),
(32, 'Save Link Company', 'Access right to save link company ', 'alias', '777-249', 'ppasm', 'cp', 0),
(33, 'Save Link Connection ', 'Access right to save link connection', 'alias', '777-249', 'ppasm', 'ct', 0),
(34, 'Webmail Static Access', 'Static access for the webmail component', 'logged', '009-724', '', '', 0),
(35, 'Delete Company', 'Right to remove the company', 'alias', '777-249', 'ppad', 'cp', 0),
(36, 'Delete Connection', 'Right to remove the connection', 'alias', '777-249', 'ppad', 'ct', 0),
(37, 'Add Business Profile', 'Add business profile to  connection', 'alias', '777-249', 'ppaa', 'cpr', 0),
(38, 'Save Business Profile', 'Save business profile to  connection', 'alias', '777-249', 'ppacpr', 'cpr', 0),
(39, 'Upload document', 'Access to upload document', 'alias', '777-249', 'ppaa', 'doc', 0),
(40, 'save uploaded document', 'Access to save the uploaded document', 'alias', '777-249', 'ppasa', 'doc', 0),
(41, 'Download  connection document', 'Access to download connection document', 'alias', '777-249', 'ppasen', 'ct', 0),
(42, 'Download company document', 'Access to download company document', 'alias', '777-249', 'ppasen', 'cp', 0),
(47, 'Add activities ', 'Access to add the events ', 'alias', '007-770', 'ppaa', 'event', 0),
(48, 'Edit activities ', 'Access to edit the events ', 'alias', '007-770', 'ppae', 'event', 0),
(49, 'Delete activities ', 'Access to remove the events ', 'alias', '007-770', 'ppad', 'event', 0),
(50, 'Save activities ', 'Access Right to save the events', 'alias', '007-770', 'ppasa', 'event', 0),
(51, 'Save Project', 'Access right to save project', 'alias', '456-789', 'ppasa', 'prj', 0),
(52, 'Save Edited Project', 'Access right to save edited project', 'alias', '456-789', 'ppase', 'prj', 0),
(53, 'Delete Project', 'Access right to remove the project', 'alias', '456-789', 'ppad', 'prj', 0),
(54, 'Listing Tasks', 'Access right to list task ', 'alias', '456-789', 'ppal', 'task', 0),
(55, 'Add Task', 'Access right to add task ', 'alias', '456-789', 'ppaa', 'task', 0),
(56, 'Save Added Task', 'Access right to save added task ', 'alias', '456-789', 'ppasa', 'task', 0),
(57, 'Save Edited Task', 'Access right to save edited task ', 'alias', '456-789', 'ppase', 'task', 0),
(58, 'Edit Task', 'Access right to edit task ', 'alias', '456-789', 'ppae', 'task', 0),
(59, 'Update Task', 'Access right to update task ', 'alias', '456-789', 'ppaupd', 'task', 0),
(60, 'Update Task Status', 'Access right to update task status', 'alias', '456-789', 'ppado', 'task', 0),
(61, 'Delete Task', 'Access right to remove task', 'alias', '456-789', 'ppad', 'task', 0),
(62, 'Upload attachment to project ', 'Access right to upload attachment to project', 'alias', '456-789', 'ppaa', 'attch', 0),
(63, 'Save Project Actors ', 'Access right to save project actors', 'alias', '456-789', 'ppase', 'prjacr', 0),
(64, 'View Graphical View ', 'Access right to view graphical view ', 'alias', '456-789', 'ppavd', 'prj', 0),
(65, 'View Task ', 'Access right to view task ', 'alias', '456-789', 'ppav', 'task', 0),
(66, 'Save Added Attachment for Project ', 'Access right to save added attachment for the project ', 'alias', '456-789', 'ppasa', 'attch', 0),
(67, 'Save Edited Attachment for Project ', 'Access right to save edited attachment for the project ', 'alias', '456-789', 'ppase', 'attch', 0),
(68, 'Edit Project Actors ', 'Access right to edit project actors ', 'alias', '456-789', 'ppae', 'prjacr', 0),
(69, 'Static Mail Access', 'Static access for the BCMedia email link', 'logged', '845-187', '', '', 0),
(70, 'Search Companies List ', 'Access right to search companies list in the selector ', 'alias', '777-249', 'ppasea', 'cp', 0),
(71, 'Save Business Add Profile ', 'Access to save business add profile', 'alias', '777-249', 'ppasa', 'cpr', 0),
(72, 'Save Business Edit Profile ', 'Access to save business edit profile', 'alias', '777-249', 'ppase', 'cpr', 0),
(73, 'Edit Business Profile', 'Access right to edit business profile', 'alias', '777-249', 'ppae', 'cpr', 0),
(74, 'Sharedspace management', 'Access right to edit and delete sharedspace ', 'alias', '999-111', 'ppam', 'shdoc', 0),
(75, 'Edit Sharedspace document', 'Access to edit shared space document', 'alias', '999-111', 'ppae', 'shdoc', 0),
(76, 'Save added Sharedspace document', 'Access to add shared space document', 'alias', '999-111', 'ppasa', 'shdoc', 0),
(77, 'Save edited Sharedspace document', 'Access to save shared space document', 'alias', '999-111', 'ppase', 'shdoc', 0),
(78, 'Add Sharedspace document', 'Access to add shared space document', 'alias', '999-111', 'ppaa', 'shdoc', 0),
(79, 'Delete sharedspace', 'Access right to delete shared space document', 'alias', '999-111', 'ppad', 'shdoc', 0),
(80, 'Add Project ', 'Access right to add new project', 'alias', '456-789', 'ppaa', 'prj', 0),
(81, 'Edit Project', 'Access right to edit project', 'alias', '456-789', 'ppae', 'prj', 0),
(82, 'Static Form Access', 'Static form access', 'static', '668-313', '', '', 0),
(83, 'Edit the attachment of company or connection', 'Access right to edit the attachment of company or connection', 'alias', '777-249', 'ppae', 'doc', 0),
(84, 'Delete the attachment of company or connection', 'Access right to delete attachment of company or connection', 'alias', '777-249', 'ppad', 'doc', 0),
(85, 'Customfields Admin', 'Admin right for custom field component. Can add fields for all database or delete customfields', 'right', '180-290', 'right_admin', '', 0),
(86, 'Customfields Manager', 'Can add one field at one time. Can not update the existing custom fields', 'right', '180-290', 'right_manager', '', 0),
(87, ' Add custom fields', 'Access right to add custom fields ', 'alias', '180-290', 'ppaa', 'csm', 0),
(88, ' Edit custom fields', 'Access right to edit custom fields ', 'alias', '180-290', 'ppae', 'csm', 0),
(89, ' Save custom fields', 'Access right to save added custom fields ', 'alias', '180-290', 'ppasa', 'csm', 0),
(90, ' Update custom fields', 'Access right to update  custom fields ', 'alias', '180-290', 'ppau', 'csm', 0),
(91, 'Add custom fields for all items', 'Access right to add custom field for all the entries', 'alias', '180-290', 'ppaall', 'csm', 0),
(94, 'Static right for portal', 'Static access for portal component', 'logged', '111-111', '', '', 0),
(95, 'Static right for charts', 'Static access for charts component', 'logged', '222-222', '', '', 0),
(96, 'Static right for zimbra', 'Static access for zimbra component', 'logged', '400-650', '', '', 0),
(97, 'Delete activity reminders', 'Allow to delete reminders set on events', 'alias', '007-770', 'ppad', 'evtrem', 0),
(98, 'Opportunity admin', 'Allow to manage other people''s opportunities and change the status to "paid"', 'right', '555-123', 'right_op_paiement', '', 0),
(99, 'Opportunity paiement', 'Allow to change an opportunity status to payed.', 'alias', '555-123', 'ppafe', 'pai', 0),
(100, 'Opportunity edit', 'Opportunity edition form.', 'alias', '555-123', 'ppae', '', 0),
(101, 'Opportunity add', 'Opportunity addition form. ', 'alias', '555-123', 'ppaa', '', 0),
(102, 'Alias add opportunity saving', 'Saving opportunity addition.', 'alias', '555-123', 'ppase', '', 0),
(103, 'Alias edit opportunity saving', 'Saving opportunity edition.', 'alias', '555-123', 'ppasa', '', 0),
(104, 'Opportunity manager', 'Adding and editing opportunities untill paiement.', 'right', '555-123', 'right_op_managment', '', 0),
(105, 'Public charts', 'Display public charts.', 'static', '222-222', 'ppav', '', 0),
(106, 'My opportunities listing', 'My opportunities listing', 'alias', '555-123', 'ppal', 'opp', 0),
(107, 'Delete profile', 'Alias allowing to delete connection profiles', 'alias', '777-249', 'ppad', 'cpr', 0),
(110, 'shift_wide_css', 'Allow user to shift between std or wide css', 'logged', '665-544', '', 'stgsys', 0),
(112, 'Alias AB ssearch connection ', 'Alias allowing to use the connection selector.', 'alias', '777-249', 'ppasea', 'ct', 0),
(113, 'User Preference', 'Allows user to save his preferences.', 'right', '579-704', 'ppae', 'usr', 0),
(114, 'Save User Preference', 'Allows user to save preferences.', 'alias', '579-704', 'ppase', 'usr', 0),
(115, 'Access to the contact sheet', '', 'logged', '579-704', 'ppal', 'usr', 0),
(116, 'Reset password', '', 'static', '579-704', 'ppares', 'pswd', 0),
(117, 'Redirection to login form', '', 'static', '579-704', '', 'restricted', 0),
(118, 'Logout', '', 'logged', '579-704', 'ppalgt', '', 0),
(119, 'Relog', '', 'static', '579-704', 'relog', '', 0),
(120, 'Password management', '', 'static', '579-704', 'ppasen', 'pswd', 0),
(121, 'Password management', '', 'static', '579-704', 'ppase', 'pswd', 0),
(122, 'Password management', '', 'static', '579-704', 'ppava', 'pswd', 0);



-- on the way, add missing alias for customfield
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(123, 'Fast-edit custom fields', 'Access right to edit custom fields ', 'alias', '180-290', 'ppafe', 'csm', 0);

-- give the alias to CF manager and addressbook manager
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES(123, 86), (123, 2);


ALTER TABLE `customfield` ADD `can_be_empty` INT NOT NULL;

-- bug when creating coonections: was adding a profile with contactfk = 0!!
-- now _testField checks that, code corrected, but we need to clean the ~200 profiles (since 24 of April)
DELETE FROM `addressbook_profile` WHERE `contactfk` = 0;


-- right to use the user selector
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(124, 'Allow searching users', 'Let users use the field to pickup other users', 'logged', '579-704', 'ppasea', 'usr', 0);


-- update database version
INSERT INTO `version` (`version`) VALUES ('2.6.0');






-- ohad: being able to display some nationalities in priority
ALTER TABLE `system_nationality` ADD `area` VARCHAR( 255 ) NOT NULL , ADD `priority` INT NOT NULL , ADD INDEX ( `area` , `priority` );

UPDATE `system_nationality` SET `priority` = '10' WHERE `nationality_name` = 'Japan';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'Canada';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'Israel';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'China';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'France';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'Italy';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'Switzerland';
UPDATE `system_nationality` SET `priority` = '5' WHERE `nationality_name` = 'Germany';


-- right to use the user selector
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(125, 'Delete all opportunities', 'Opp admin can delette all opportunities', 'alias', '555-123', 'ppad', 'opp', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES(125, 104);



INSERT INTO `version` (`version`) VALUES ('2.6.1');
