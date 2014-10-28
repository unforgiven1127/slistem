-- delete empty contact details
DELETE FROM sl_contact WHERE (`value` = '' OR `value` IS NULL) AND (`description` = '' OR `description` IS NULL);
DELETE FROM sl_contact_visibility WHERE sl_contactfk NOT IN (SELECT sl_contactpk FROM sl_contact


