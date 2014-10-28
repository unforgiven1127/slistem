<?php

require_once('common/lib/model.class.php5');

class CAddressBookModel extends CModel
{

  public function __construct()
  {
    $this->oDB = CDependency::getComponentByName('database');
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['addressbook_company']['addressbook_companypk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_company']['company_name']= array ('controls' => array ('!empty(%)'));
    $this->_tableMap['addressbook_company']['email']= array ('controls' => array ('isValidEmail(%, false)'));
    $this->_tableMap['addressbook_company']['cityfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_company']['countryfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_company']['parentfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_company']['followerfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_company']['creatorfk']= array ('controls' => array ('is_key(%)'));


    $this->_tableMap['addressbook_contact']['addressbook_contactpk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['courtesy']= array ('controls' => array('!empty(%)'));
    $this->_tableMap['addressbook_contact']['firstname']= array ('controls' => array ('!empty(%)'));
    $this->_tableMap['addressbook_contact']['lastname']= array ('controls' => array ('!empty(%)'));
    $this->_tableMap['addressbook_contact']['email']= array ('controls' => array ('isValidEmail(%, false)'));
    $this->_tableMap['addressbook_contact']['loginfk']= array ('controls' => array ('is_key(%)'));  //not in updates
    $this->_tableMap['addressbook_contact']['followerfk']= array ('controls' => array ('is_key(%)'));

    $this->_tableMap['addressbook_contact']['birthdate']= array ('controls' => array ('is_date(%)'));
    $this->_tableMap['addressbook_contact']['address_1']= array ('controls' => array ());
    $this->_tableMap['addressbook_contact']['address_2']= array ('controls' => array ());
    $this->_tableMap['addressbook_contact']['postcode']= array ('controls' => array ());
    $this->_tableMap['addressbook_contact']['cityfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['countryfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['phone']= array ('controls' => array ());
    $this->_tableMap['addressbook_contact']['cellphone']= array ('controls' => array ());
    $this->_tableMap['addressbook_contact']['fax']= array ('controls' => array ());
    
    $this->_tableMap['addressbook_contact']['date_create']= array ('controls' => array ('isValidDate(%)'));
    $this->_tableMap['addressbook_contact']['created_by']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['date_update']= array ('controls' => array ('isValidDate(%)'));
    $this->_tableMap['addressbook_contact']['updated_by']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['relationfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['comments']= array ('controls' => array());
    $this->_tableMap['addressbook_contact']['grade']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['nationalityfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_contact']['language']= array ('controls' => array ('is_key(%)'));


    $this->_tableMap['addressbook_profile']['addressbook_profilepk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_profile']['contactfk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_profile']['companyfk']= array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['addressbook_profile']['position']= array ('controls' => array ());
    $this->_tableMap['addressbook_profile']['industryfk']= array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['addressbook_profile']['email']= array ('controls' => array ('isValidEmail(%, false)'));
    $this->_tableMap['addressbook_profile']['cityfk']= array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['addressbook_profile']['countryfk']= array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['addressbook_profile']['date_end']= array ('controls' => array ('is_datetime(%)'));
    $this->_tableMap['addressbook_profile']['phone']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['fax']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['department']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['address_1']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['address_2']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['postcode']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['comment']= array ('controls' => array());
    $this->_tableMap['addressbook_profile']['date_update']= array ('controls' => array ('is_datetime(%)'));


    $this->_tableMap['addressbook_account_manager']['account_managerpk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_account_manager']['companyfk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_account_manager']['contactfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_account_manager']['loginfk']= array ('controls' => array ('is_key(%)'));

    $this->_tableMap['addressbook_document']['addressbook_documentpk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_document']['title']= array ('controls' => array());
    $this->_tableMap['addressbook_document']['description']= array ('controls' => array());
    $this->_tableMap['addressbook_document']['loginfk']= array ('controls' => array ('is_key(%)'));
    $this->_tableMap['addressbook_document']['date_create']= array ('controls' => array ('isValidDate(%)'));
    $this->_tableMap['addressbook_document']['filename']= array ('controls' => array ());
    $this->_tableMap['addressbook_document']['path_name']= array ('controls' => array ());
    $this->_tableMap['addressbook_document']['content']= array ('controls' => array ());


    $this->_tableMap['addressbook_document_info']['addressbook_document_infopk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_document_info']['type']= array ('controls' => array ());
    $this->_tableMap['addressbook_document_info']['itemfk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_document_info']['docfk']= array ('controls' => array('is_key(%)'));


    $this->_tableMap['addressbook_department']['addressbook_departmentpk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_department']['department_name']= array ('controls' => array('!empty(%)'));

    $this->_tableMap['addressbook_industry']['addressbook_industrypk']= array ('controls' => array('is_key(%)'));
    $this->_tableMap['addressbook_industry']['industry_name']= array ('controls' => array('!empty(%)'));
    $this->_tableMap['addressbook_industry']['industry_desc']= array ('controls' => array());

  }

}