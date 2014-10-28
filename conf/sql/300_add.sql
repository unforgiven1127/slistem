
-- Reminders rights
INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES
(250, 'Add reminder', 'Add reminder', 'alias', '333-333', 'ppaa', 'msg', 0),
(251, 'Cancel nag', 'Cancel nag', 'alias', '333-333', 'ppae', 'nag', 0),
(252, 'View all user usage stats', 'View all user usage stats', 'right', '111-111', 'ppav', 'stat', 0);

INSERT INTO `right_tree` (`rightfk`, `parentfk`) VALUES (250, 143),(251, 143);


INSERT INTO `version` (`version`, date_version) VALUES ('3.0.0.a - stef', NOW());


