
-- create new tables with correct names
create table addressbook_company like company;
insert addressbook_company select * from company;

create table addressbook_contact like contact;
insert addressbook_contact select * from contact;

create table addressbook_account_manager like account_manager;
insert addressbook_account_manager select * from account_manager;

create table addressbook_industry like industry;
insert addressbook_industry select * from industry;

create table addressbook_company_industry like company_industry;
insert addressbook_company_industry select * from company_industry;

create table addressbook_department like department;
insert addressbook_department select * from department;

create table addressbook_profile like profil;
insert addressbook_profile select * from profil;




create table system_city like city;
insert system_city select * from city;

create table system_country like country;
insert system_country select * from country;

create table system_language like `language`;
insert system_language select * from `language`;

create table system_nationality like nationality;
insert system_nationality select * from nationality;


-- update primary key that has to match table name
ALTER TABLE `addressbook_company` CHANGE `companypk` `addressbook_companypk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_contact` CHANGE `contactpk` `addressbook_contactpk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_account_manager` CHANGE `account_managerpk` `addressbook_account_managerpk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_industry` CHANGE `industrypk` `addressbook_industrypk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_company_industry` CHANGE `company_industry_pk` `addressbook_company_industrypk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_department` CHANGE `departmentpk` `addressbook_departmentpk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `addressbook_profile` CHANGE `profilpk` `addressbook_profilepk` INT( 11 ) NOT NULL AUTO_INCREMENT;

ALTER TABLE `system_city` CHANGE `citypk` `system_citypk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `system_country` CHANGE `countrypk` `system_countrypk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `system_language` CHANGE `languagepk` `system_languagepk` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `system_nationality` CHANGE `nationalitypk` `system_nationalitypk` INT( 11 ) NOT NULL AUTO_INCREMENT;



-- once done and checked things are ok !!
-- DROP TABLE `company`, `contact`, `account_manager`, `industry`, `company_industry`, `department`, `profil`, `city`, `country`, `language`, `nationality`;



-- remove hardecoded dependencies between event and AB
CREATE OR REPLACE VIEW shared_event AS
(
  SELECT * FROM event INNER JOIN event_link as el ON (el.eventfk = eventpk)
);