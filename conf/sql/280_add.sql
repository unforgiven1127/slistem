-- Paul / Rights for new view in opportunity

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
(137, 'My opportunities history listing', 'My opportunities history listing', 'alias', '555-123', 'ppafl', 'opp', 0, NULL);

INSERT INTO `right_tree` (`right_treepk`, `rightfk`, `parentfk`) VALUES
('', 137, 104),
('', 137, 98);

-- Paul / Rights for new view in address book

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
(138, 'List my connections', 'My connections', 'alias', '777-249', 'ppafl', 'ct', 0, NULL),
(139, 'List my companies', 'My companies', 'alias', '777-249', 'ppafl', 'cp', 0, NULL);

INSERT INTO `right_tree` (`right_treepk`, `rightfk`, `parentfk`) VALUES
('', 138, 3),
('', 139, 3);

INSERT INTO `version` (`version`, date_version) VALUES ('2.8.0a', NOW());

