-- log as the same user used by the app, then recreate the view(s)


/*CREATE OR REPLACE VIEW `shared_login` AS
(
  select `l`.`loginpk` AS `loginpk`,`l`.`pseudo` AS `pseudo`,`l`.`birthdate` AS `birthdate`,`l`.`gender` AS `gender`,
  `l`.`courtesy` AS `courtesy`,`l`.`email` AS `email`,`l`.`lastname` AS `lastname`,
  `l`.`firstname` AS `firstname`,concat(`l`.`firstname`,' ',`l`.`lastname`) AS `fullname`,
  `l`.`phone` AS `phone`,`l`.`phone_ext` AS `phone_ext`,`l`.`status` AS `status`,
  `lg`.`login_groupfk` AS `login_groupfk`,`l`.`is_admin` AS `is_admin`,
  if((length(`l`.`pseudo`) > 0),`l`.`pseudo`,`l`.`firstname`) AS `friendly`

  from `login` `l`
  left join `login_group_member` `lg` on(`lg`.`loginfk` = `l`.`loginpk`)

  GROUP BY l.loginpk
);*/

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
