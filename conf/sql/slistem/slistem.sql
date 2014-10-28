

-- application rights

-- right to use the user selector
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10001, 'Standard consultant', 'Standard consultant access to candidate features', 'access', '555-001', 'ppal', 'candi', 0),
(10002, 'View candidates', 'Allow user to view candidate profile', 'alias', '555-001', 'ppav', 'candi', 0),
(10003, 'Add candidates', 'Allow user to add candidates', 'alias', '555-001', 'ppaa', 'candi', 0),
(10004, 'Edit candidates', 'Allow user to edit candidates', 'alias', '555-001', 'ppae', 'candi', 0),
(10005, 'Save new candidates', 'Allow to save add candidates', 'alias', '555-001', 'ppasa', 'candi', 0),
(10006, 'Save existing candidates', 'Allow user to  save edit candidates', 'alias', '555-001', 'ppase', 'candi', 0),

(10007, 'List companies', 'Allow user to browse and search companies list', 'alias', '555-001', 'ppal', 'comp', 0),
(10008, 'View companies', 'Allow user to view companies profile', 'alias', '555-001', 'ppav', 'comp', 0),
(10009, 'Add companies', 'Allow user to add companies', 'alias', '555-001', 'ppaa', 'comp', 0),
(10010, 'Edit companies', 'Allow user to edit companies', 'alias', '555-001', 'ppae', 'comp', 0),
(10011, 'Save new companies', 'Allow user to save add companies', 'alias', '555-001', 'ppasa', 'comp', 0),
(10012, 'Save existing companies', 'Allow user to save edit companies', 'alias', '555-001', 'ppase', 'comp', 0),

(10013, 'Advanced consultant', 'Access to advance management ', 'right', '555-001', 'ppam', 'candi', 0),
(10014, 'Delete companies', 'Allow to delete companies', 'alias', '555-001', 'ppad', 'comp', 0),
(10015, 'Delete candidates', 'Allow to delete candidates', 'alias', '555-001', 'ppad', 'candi', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10002, 10001),(10003, 10001),(10004, 10001),(10005, 10001),(10006, 10001),
(10007, 10001),(10008, 10001),(10009, 10001),(10010, 10001),(10011, 10001),(10012, 10001),
(10014, 10013),(10015, 10013) ,(10001, 10013);

-- data access rights
INSERT INTO `right` (rightpk, label, description, `type`, data)
VALUES("15001", "Restrict_old_candi", "Restrict access to old candidates (pk < 325000)", "data", "a:6:{s:5:\"table\";s:12:\"sl_candidate\";s:5:\"alias\";s:4:\"scan\";s:4:\"left\";a:0:{}s:5:\"inner\";a:0:{}s:5:\"outer\";a:0:{}s:5:\"where\";s:28:\"scan.sl_candidatepk > 325000\";}");

-- data access rights
INSERT INTO `right` (rightpk, label, description, `type`, data) VALUES("15002", "Restrict_men", "Restrict access to male candidates", "data", "a:6:{s:5:\"table\";s:12:\"sl_candidate\";s:5:\"alias\";s:4:\"scan\";s:4:\"left\";a:0:{}s:5:\"inner\";a:0:{}s:5:\"outer\";a:0:{}s:5:\"where\";s:13:\"scan.sex <> 1\";}");

-- only employed candiates
INSERT INTO `right` (rightpk, label, description, `type`, data) VALUES("15003", "Restrict_only_employed", "Restrict access to employed", "data", "a:6:{s:5:\"table\";s:12:\"sl_candidate\";s:5:\"alias\";s:4:\"scan\";s:4:\"left\";a:0:{}s:5:\"inner\";a:1:{i:0;a:4:{s:5:\"table\";s:20:\"sl_candidate_profile\";s:5:\"alias\";s:4:\"scpr\";s:6:\"clause\";s:91:\"scpr.candidatefk = scan.sl_candidatepk AND scpr.cpmanyfk IS NOT NULL AND scpr.cpmanyfk <> 0\";s:3:\"sql\";s:137:\"INNER JOIN sl_candidate_profile as scpr ON (scpr.candidatefk = scan.sl_candidatepk AND scpr.cpmanyfk IS NOT NULL AND scpr.cpmanyfk <> 0) \";}}s:5:\"outer\";a:0:{}s:5:\"where\";s:49:\" scpr.cpmanyfk IS NOT NULL AND scpr.cpmanyfk <> 0\";}");




-- right to access shared folder
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10016, 'Search folder', 'Filter folder by name or consulatant', 'right', '555-002', 'right_folder_managment', '', 0),
(10017, 'Acces folder feature', 'Acces folder feature', 'alias', '555-002', 'ppasea', 'fol', 0),
(10018, 'Search companies', 'Used by company selector', 'alias', '555-001', 'ppasea', 'comp', 0),
(10019, 'Search candidate', 'Used by candidate selector', 'alias', '555-001', 'ppasea', 'candi', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10017, 10016),(10018, 10001),(10019, 10001);






-- - - - - - - - - - - -
-- meeting management: alias for anybody accessing the candidates
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10020, 'View meeting list', 'View meeting list', 'alias', '555-001', 'ppav', 'meet', 0),
(10021, 'Edit meetings', 'Edit meetings', 'alias', '555-001', 'ppae', 'meet', 0),
(10022, 'Save meeting', 'Save meeting', 'alias', '555-001', 'ppasa', 'meet', 0),
(10023, 'Save meeting', 'Save meeting', 'alias', '555-001', 'ppase', 'meet', 0),
(10024, 'Delete meeting', 'Delete meeting', 'alias', '555-001', 'ppad', 'meet', 0),
(10025, 'Set meeting done ', 'Set meeting done', 'alias', '555-001', 'ppava', 'meet', 0),
(10026, 'Add meeting ', 'Add meeting', 'alias', '555-001', 'ppaa', 'meet', 0),
(10027, 'Meeting done', 'Meeting done', 'alias', '555-001', 'ppado', 'meet', 0),
(10028, 'Add a note', 'Add a note', 'alias', '555-004', 'ppaa', 'event', 0),
(10029, 'Add contact', 'Add contact', 'alias', '555-001', 'ppaa', 'cont', 0),
(10030, 'Add document', 'Add document', 'alias', '555-001', 'ppaa', 'doc', 0),
(10031, 'Set in play', 'Set in play', 'alias', '555-005', 'ppaa', 'link', 0),
(10032, 'Check cp rss', 'Check cp rss', 'alias', '555-001', 'ppae', 'cprss', 0),
(10033, 'Save note', 'Save note', 'alias', '555-004', 'ppasa', 'event', 0),
(10034, 'Save contact', 'Save contact', 'alias', '555-001', 'ppasa', 'cont', 0),
(10035, 'Save document', 'Save document', 'alias', '555-001', 'ppasa', 'doc', 0),
(10036, 'Save application', 'Save application', 'alias', '555-005', 'ppasa', 'link', 0),
(10037, 'Search position', 'Search position', 'alias', '555-005', 'ppasea', 'jd', 0),

(10038, 'List positions', 'List positions', 'alias', '555-005', 'ppal', 'jd', 0),
(10039, 'View positions', 'View positions', 'alias', '555-005', 'ppav', 'jd', 0),
(10040, 'Add position', 'Add position', 'alias', '555-005', 'ppaa', 'jd', 0),
(10041, 'Save position', 'Save position', 'alias', '555-005', 'ppasa', 'jd', 0),

(10042, 'Add folder', 'Add folder', 'alias', '555-002', 'ppaa', 'fol', 0),
(10043, 'Add folder', 'Add folder', 'alias', '555-002', 'ppasa', 'fol', 0),
(10044, 'Complex search', 'Complex search', 'alias', '898-775', 'ppasea', '', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10020, 10001),(10021, 10001),(10022, 10001),(10023, 10001),(10024, 10001),(10025, 10001),(10026, 10001),(10027, 10001)
,(10028, 10001),(10029, 10001),(10030, 10001),(10031, 10001),(10032, 10001)
,(10033, 10001),(10034, 10001),(10035, 10001),(10036, 10001),(10037, 10001)
,(10038, 10001),(10039, 10001),(10040, 10001),(10041, 10001),
,(10042, 10016),(10043, 10016),(10044, 10001);


-- - - - - - - - - - - -
-- note management: alias for anybody accessing the candidates 10001

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10026, 'Add note', 'View meeting list', 'alias', '555-001', 'ppav', 'meet', 0),

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10020, 10001),(10021, 10001),(10022, 10001),(10023, 10001),(10024, 10001),(10025, 10001);


-- - - - - - - - - - - -
-- missing ones 26-dec

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10045, 'Add item to folder', 'Add item to folder', 'alias', '555-002', 'ppasa', 'folitm', 0),
(10046, 'DBA request', 'DBA request', 'alias', '333-333', 'ppaa', 'msg', 0),
(10047, 'DBA request webmail', 'DBA request webmail', 'alias', '555-003', '', 'email', 0),
(10048, 'Charts', 'Charts', 'alias', '555-006', 'ppal', 'stat', 0),
(10049, 'Charts', 'Charts', 'alias', '555-006', 'ppal', 'pipe', 0),
(10050, 'Charts', 'Charts', 'alias', '555-006', 'ppav', '', 0),
(10051, 'Contact sheet', 'Contact sheet', 'alias', '555-001', 'ppav', 'usr', 0),
(10052, 'Update application status', 'Update application status', 'alias', '555-005', 'ppae', 'link', 0),
(10053, 'Update application status', 'Update application status', 'alias', '555-005', 'ppase', 'link', 0),
(10054, 'Access restricted contacts', 'Access restricted contacts', 'alias', '555-001', 'ppalog', 'candi', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10045, 10016),(10046, 10001),(10047, 10001),(10048, 10001),(10049, 10001),
(10050, 10001),(10051, 10001),(10052, 10001),(10053, 10001),(10054, 10001);



-- delete empty contact details
DELETE FROM sl_contact WHERE (`value` = '' OR `value` IS NULL) AND (`description` = '' OR `description` IS NULL);
DELETE FROM sl_contact_visibility WHERE sl_contactfk NOT IN (SELECT sl_contactpk FROM sl_contact);



-- - - - - - - - - - - -
-- missing ones 18-feb

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10055, 'View list of RM', 'View list of RM', 'alias', '555-001', 'ppav', 'rm', 0),
(10056, 'Add me as RM', 'Add me as RM', 'alias', '555-001', 'ppaa', 'rm', 0),
(10057, 'Extend RM', 'Extend RM', 'alias', '555-001', 'ppae', 'rm', 0),
(10058, 'Delete me from RM', 'Delete me from RM', 'alias', '555-001', 'ppad', 'rm', 0),

(10059, 'Refresh folder list', 'Refresh folder list', 'alias', '555-002', 'pparef', 'fol', 0),
(10060, 'Save folder', 'Save folder', 'alias', '555-002', 'ppasa', '', 0),
(10061, 'Edit folder', 'Edit folder', 'alias', '555-002', 'ppae', 'fol', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10055, 10001), (10056, 10001),(10057, 10001),(10058, 10001),
(10059, 10016),(10060, 10016),(10061, 10016);


-- - - - - - - - - - - -
-- Placement

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10062, 'Placement manager', 'Placement manager', 'right', '555-005', 'ppam', 'pla', 0),
(10063, 'View placement list', 'View placement list', 'alias', '555-005', 'ppla', 'pla', 0),
(10064, 'Add placement', 'Add placement', 'alias', '555-005', 'ppaa', 'pla', 0),
(10065, 'Edit placement', 'Edit placement', 'alias', '555-005', 'ppae', 'pla', 0),
(10066, 'Save placement', 'Save placement', 'alias', '555-005', 'ppasa', 'pla', 0),
(10067, 'Save placement', 'Save placement', 'alias', '555-005', 'ppase', 'pla', 0),
(10068, 'Delete placement', 'Delete placement', 'alias', '555-005', 'ppad', 'pla', 0),
(10069, 'Set placement paid', 'Set placement paid', 'alias', '555-005', 'ppava', 'pla', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES
(10063, 10062), (10064, 10062), (10065, 10062), (10066, 10062), (10067, 10062), (10068, 10062), (10069, 10062);



ALTER TABLE login DROP INDEX unique_email;


INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10070, 'Complex search', 'Complex search', 'alias', '898-775', 'relo', '', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10070, 10001);

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10071, 'Folder', 'Folder', 'alias', '555-002', 'ppaa', 'folitm', 0),
(10072, 'Folder', 'Folder', 'alias', '555-002', 'ppad', 'folitm', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10071, 10001), (10072, 10001);



INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10073, 'View meetings', 'View list of meetings', 'alias', '555-001', 'ppal', 'meet', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10073, 10001);



INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10074, 'View list folders', 'View list folders', 'alias', '555-002', 'ppal', '', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10074, 10001);




INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10075, 'Edit notes', 'Edit notes', 'alias', '555-004', 'ppae', '', 0),
(10076, 'Edit notes', 'Edit notes', 'alias', '555-004', 'ppase', '', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10075, 10001), (10076, 10001);




INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10077, 'Autocomplete field', 'Autocomplete field', 'alias', '555-001', 'ppasea', 'indus', 0),
(10078, 'Autocomplete field', 'Autocomplete field', 'alias', '555-001', 'ppasea', 'occu', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10077, 10001), (10078, 10001);


INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10079, 'Analyst page', 'Analyst page', 'alias', '555-006', 'ppav', 'analyst', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10079, 10001);

-- 07-May
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10080, 'Save selected menu', 'Save selected menu', 'alias', '555-003', 'ppau', 'menu', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10080, 10001);


INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10081, 'Stats', 'Stats', 'alias', '555-006', 'pparef', 'analyst', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10081, 10001);


-- https://slistem.devserv.com/index.php5?uid=555-006&ppa=ppal&ppt=pipex&ppk=0&pg=ajx
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10082, 'Stats', 'Stats', 'alias', '555-006', 'ppal', 'pipex', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10082, 10001);

-- https://slistem.devserv.com/index.php5?uid=555-006&ppa=ppal&ppt=global&ppk=0&pg=ajx
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10083, 'Stats', 'Stats', 'alias', '555-006', 'ppal', 'global', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10083, 10001);




INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10084, 'Stats', 'Stats', 'alias', '555-001', 'ppav', 'doc', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10084, 10001);


-- 03-june: edit positions
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10085, 'Edit position', 'edit position', 'alias', '555-005', 'ppae', 'jd', 0),
(10086, 'Save position', 'Save position', 'alias', '555-005', 'ppase', 'jd', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10085, 10001), (10086, 10001);


INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10087, 'Edit folder', 'Edit folder', 'alias', '555-002', 'ppase', '', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10087, 10001);



-- delete folders
-- https://slistem.devserv.com/index.php5?uid=555-002&ppa=ppad&ppt=fol&ppk=1&pg=ajx
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10088, 'Delete folder', 'Delete folder', 'alias', '555-002', 'ppad', 'fol', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10088, 10001);


-- browse logs history
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10089, 'browse logs history', 'browse logs history', 'alias', '555-001', '', 'logs', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10089, 10001);






-- data access rights

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15005", "Hide industry confidential", "Remove access to confidential", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:29:\"AND sind.sl_industrypk <> 126\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15006", "Hide industry automotive", "Remove access to automotive and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 505 AND sind.parentfk <> 505\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15007", "Hide industry CNS", "Remove access to CNS and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 500 AND sind.parentfk <> 500\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15008", "Hide industry Healthcare", "Remove access to healthcare and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 504 AND sind.parentfk <> 504\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15009", "Hide industry IT", "Remove access to IT and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 503 AND sind.parentfk <> 503\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15010", "Hide industry FIN", "Remove access to Finance and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 502 AND sind.parentfk <> 502\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15011", "Hide industry Energy", "Remove access to Energy and child industries", "data", "a:10:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:11:\"sl_industry\";s:5:\"alias\";s:4:\"sind\";s:6:\"clause\";s:36:\"sind.sl_industrypk = scpr.industryfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:54:\"AND sind.sl_industrypk <> 501 AND sind.parentfk <> 501\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`, `cp_uid`, `cp_type`)
VALUES ("15012", "Hide location Manila", "Remove access to candidates from Manila",
"data", "a:10:{s:5:\"table\";s:12:\"sl_candidate\";s:5:\"alias\";N;s:6:\"select\";N;s:4:\"left\";N;s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:24:\"AND scan.locationfk <> 4\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}", '555-001', 'candi');

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`, `cp_uid`, `cp_type`)
VALUES ("15013", "Hide location Tokyo", "Remove access to candidates from Tokyo",
"data", "a:10:{s:5:\"table\";s:12:\"sl_candidate\";s:5:\"alias\";N;s:6:\"select\";N;s:4:\"left\";N;s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:24:\"AND scan.locationfk <> 1\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}", '555-001', 'candi');


INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15100", "Hide occupation CNS", "Remove access to CNS and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 500 AND socc.parentfk <> 500\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15101", "Hide occupation Energy", "Remove access to Energy and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 501 AND socc.parentfk <> 501\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15102", "Hide occupation Finance", "Remove access to Finance and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 502 AND socc.parentfk <> 502\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15103", "Hide occupation IT", "Remove access to IT and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 503 AND socc.parentfk <> 503\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15104", "Hide occupation Healthcare", "Remove access to Healthcare and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 504 AND socc.parentfk <> 504\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15105", "Hide occupation Other", "Remove access to Other and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 505 AND socc.parentfk <> 505\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15106", "Hide occupation Automotive", "Remove access to Automotive and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 506 AND socc.parentfk <> 506\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15107", "Hide occupation Engineer", "Remove access to Engineer and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 507 AND socc.parentfk <> 507\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`) VALUES ("15108", "Hide occupation Consumer", "Remove access to Consumer and child occupation", "data", "a:10:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"select\";N;s:4:\"left\";a:1:{i:0;a:3:{s:5:\"table\";s:13:\"sl_occupation\";s:5:\"alias\";s:4:\"socc\";s:6:\"clause\";s:40:\"socc.sl_occupationpk = scpr.occupationfk\";}}s:5:\"inner\";N;s:5:\"outer\";N;s:5:\"where\";s:56:\"AND socc.sl_occupationpk <> 508 AND socc.parentfk <> 508\";s:5:\"order\";N;s:5:\"group\";N;s:5:\"limit\";N;}");





INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`, `cp_uid`, `cp_type`) VALUES ("15015", "Special Rossana", "Access only [IT manila + Finance in Tokyo]", "data", 'a:10:{s:5:"table";s:12:"sl_candidate";s:5:"alias";s:4:"scan";s:6:"select";N;s:4:"left";N;s:5:"inner";a:1:{i:0;a:3:{s:5:"table";s:11:"sl_industry";s:5:"alias";s:4:"sind";s:6:"clause";s:34:"sind.sl_industrypk=scpr.industryfk";}}s:5:"outer";N;s:5:"where";s:148:"AND((scan.locationfk=4 AND(sind.sl_industrypk=503 OR sind.parentfk=503)) OR (scan.locationfk=1 AND(sind.sl_industrypk=502 OR sind.parentfk=502)))";s:5:"order";N;s:5:"group";N;s:5:"limit";N;}', "", "");
ALTER TABLE `sl_company` ADD `ownerfk` INT NULL AFTER `updated_by`;
ALTER TABLE `sl_company` ADD INDEX (`ownerfk`);



UPDATE `right` SET `cp_uid` = '555-001' WHERE `rightpk` >= 15000;
UPDATE `right` SET `cp_type` = 'candi' WHERE `rightpk` >= 15000 AND `rightpk` < 15100;
UPDATE `right` SET `cp_type` = 'comp' WHERE `rightpk` >= 15100 AND `rightpk` < 15200;






-- switch right data from serialized to json to avoid conversion errors
DELETE FROM `right` WHERE rightpk >= 15000;

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
(15108, 'Lock occupation Consumer', 'Restrict access to Consumer and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 508 AND socc.parentfk <> 508","order":null,"group":null,"limit":null}'),
(15107, 'Lock occupation Engineer', 'Restrict access to Engineer and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 507 AND socc.parentfk <> 507","order":null,"group":null,"limit":null}'),
(15106, 'Lock occupation Automotive', 'Restrict access to Automotive and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 506 AND socc.parentfk <> 506","order":null,"group":null,"limit":null}'),
(15104, 'Lock occupation Healthcare', 'Restrict access to Healthcare and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 504 AND socc.parentfk <> 504","order":null,"group":null,"limit":null}'),
(15103, 'Lock occupation IT', 'Restrict access to IT and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 503 AND socc.parentfk <> 503","order":null,"group":null,"limit":null}'),
(15102, 'Lock occupation Finance', 'Restrict access to Finance and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 502 AND socc.parentfk <> 502","order":null,"group":null,"limit":null}'),
(15101, 'Lock occupation Energy', 'Restrict access to Energy and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 501 AND socc.parentfk <> 501","order":null,"group":null,"limit":null}'),
(15100, 'Lock occupation CNS', 'Restrict access to CNS and child occupation', 'data', '555-001', '', 'comp', 0, '{"table":"sl_occupation","alias":"socc","select":null,"left":[{"table":"sl_occupation","alias":"socc","clause":"socc.sl_occupationpk = scpr.occupationfk"}],"inner":null,"outer":null,"where":"AND socc.sl_occupationpk <> 500 AND socc.parentfk <> 500","order":null,"group":null,"limit":null}'),
(15015, 'Special Rossana', 'Access only [IT manila + Finance in Tokyo]', 'data', '555-001', '', 'candi', 0, '{"table":"sl_candidate","alias":"scan","select":null,"left":null,"inner":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"outer":null,"where":"AND ( (scan.locationfk = 4 AND (sind.sl_industrypk = 503 OR sind.parentfk = 503)) OR (scan.locationfk = 1 AND (sind.sl_industrypk = 502 OR sind.parentfk = 502)) )","order":null,"group":null,"limit":null}'),
(15013, 'Hide location Tokyo', 'Remove access to candidates from Tokyo', 'data', '555-001', '', 'candi', 0, '{"table":"sl_candidate","alias":null,"select":null,"left":null,"inner":null,"outer":null,"where":"AND scan.locationfk <> 1","order":null,"group":null,"limit":null}'),
(15012, 'Hide location Manila', 'Remove access to candidates from Manila', 'data', '555-001', '', 'candi', 0, '{"table":"sl_candidate","alias":null,"select":null,"left":null,"inner":null,"outer":null,"where":"AND scan.locationfk <> 4","order":null,"group":null,"limit":null}'),
(15011, 'Lock industry Energy', 'Restrict access to Energy and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 501 AND sind.parentfk <> 501","order":null,"group":null,"limit":null}'),
(15010, 'Lock industry FIN', 'Restrict access to Finance and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 502 AND sind.parentfk <> 502","order":null,"group":null,"limit":null}'),
(15009, 'Lock industry IT', 'Restrict access to IT and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 503 AND sind.parentfk <> 503","order":null,"group":null,"limit":null}'),
(15008, 'Lock industry Healthcare', 'Restrict access to healthcare and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 504 AND sind.parentfk <> 504","order":null,"group":null,"limit":null}'),
(15007, 'Lock industry CNS', 'Restrict access to CNS and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 500 AND sind.parentfk <> 500","order":null,"group":null,"limit":null}'),
(15006, 'Lock industry automotive', 'Restrict access to automotive and child industries', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 505 AND sind.parentfk <> 505","order":null,"group":null,"limit":null}'),
(15005, 'Lock industry confidential', 'Restrict access to confidential', 'data', '555-001', '', 'candi', 0, '{"table":"sl_industry","alias":"sind","select":null,"left":[{"table":"sl_industry","alias":"sind","clause":"sind.sl_industrypk = scpr.industryfk"}],"inner":null,"outer":null,"where":"AND sind.sl_industrypk <> 126","order":null,"group":null,"limit":null}');






ALTER TABLE `sl_position_link` ADD `in_play` INT NOT NULL AFTER `status`, ADD INDEX (`in_play`) ;


INSERT INTO `login` (`loginpk`, `id`, `password`, `pseudo`, `birthdate`, `gender`, `courtesy`, `email`, `lastname`, `firstname`, `position`, `phone`, `phone_ext`, `nationalityfk`, `status`, `is_admin`, `valid_status`, `hashcode`, `date_create`, `date_update`, `date_expire`, `date_reset`, `date_last_log`, `log_hash`, `webmail`, `webpassword`, `mailport`, `Imap`, `aliasName`, `signature`, `date_passwd_changed`, `otherloginfks`) VALUES
(500, 'jharvey', 'jh!2014#HA', 'jharvey', NULL, 0, '', 'jharvey@slate-ghc.com', 'Harvey', 'John', 'DBA', '', '1005', NULL, 1, 1, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '0000-00-00 00:00:00', NULL);

INSERT INTO `login` (`loginpk`, `id`, `password`, `pseudo`, `birthdate`, `gender`, `courtesy`, `email`, `lastname`, `firstname`, `position`, `phone`, `phone_ext`, `nationalityfk`, `status`, `is_admin`, `valid_status`, `hashcode`, `date_create`, `date_update`, `date_expire`, `date_reset`, `date_last_log`, `log_hash`, `webmail`, `webpassword`, `mailport`, `Imap`, `aliasName`, `signature`, `date_passwd_changed`, `otherloginfks`) VALUES
(501, 'rpool', 'rpo!2014#OL_', 'res-pool', NULL, 0, '', 'rpool@slate.co.jp', 'res. pool', 'R', ' - ', '', '', NULL, 1, 1, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '0000-00-00 00:00:00', NULL);


-- a field reflecting the last pos link status
ALTER TABLE `sl_candidate_profile` ADD `_pos_status` INT NOT NULL AFTER `_in_play`, ADD INDEX (`_pos_status`);




ALTER TABLE `sl_placement` ADD `date_due` DATE NOT NULL AFTER `date_start`, ADD INDEX (`date_due`) ;
ALTER TABLE `sl_placement` ADD `location` VARCHAR(64) NOT NULL AFTER `date_due`, ADD INDEX (`location`) ;



INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10090, 'View KPI list', 'View KPI list', 'right', '555-006', 'ppam', 'kpi', 0);

-- browse logs history
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10091, 'edit position', 'edit position', 'alias', '555-005', 'ppae', 'jd', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10091, 10001);


-- delete position
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(10092, 'delete position', 'delete position', 'alias', '555-005', 'ppad', 'jd', 0);
INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (10092, 10001);