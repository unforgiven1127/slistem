<?php

require_once('common/lib/model.class.php5');

class CLoginModel extends CModel
{

  public function __construct()
  {
    parent::__construct();
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['login']['loginpk'] = array('controls' => array('empty(%) || is_key(%)'));
    $this->_tableMap['login']['id'] = array('controls' => array('strlen(%) > 2'));
    $this->_tableMap['login']['password'] = array('controls' => array('strlen(%) > 4'));
    $this->_tableMap['login']['gender'] = array('controls' => array());
    $this->_tableMap['login']['courtesy'] = array('controls' => array());
    $this->_tableMap['login']['email'] = array('controls' => array('isValidEmail(%)'));
    $this->_tableMap['login']['lastname'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['login']['firstname'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['login']['nationalityfk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['login']['position'] = array('controls' => array());
    $this->_tableMap['login']['phone'] = array('controls' => array());
    $this->_tableMap['login']['phone_ext'] = array('controls' => array());
    $this->_tableMap['login']['status'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['login']['is_admin'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['login']['valid_status'] = array('controls' => array());
    $this->_tableMap['login']['hashcode'] = array('controls' => array());
    $this->_tableMap['login']['date_create'] = array('controls' => array());
    $this->_tableMap['login']['date_update'] = array('controls' => array());
    $this->_tableMap['login']['date_expire'] = array('controls' => array());
    $this->_tableMap['login']['date_reset'] = array('controls' => array());
    $this->_tableMap['login']['date_last_log'] = array('controls' => array());
    $this->_tableMap['login']['log_hash'] = array('controls' => array());
    $this->_tableMap['login']['webmail'] = array('controls' => array());
    $this->_tableMap['login']['webpassword'] = array('controls' => array());
    $this->_tableMap['login']['mailport'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['login']['imap'] = array('controls' => array());
    $this->_tableMap['login']['aliasName'] = array('controls' => array());
    $this->_tableMap['login']['signature'] = array('controls' => array());
    $this->_tableMap['login']['pseudo'] = array('controls' => array());
    $this->_tableMap['login']['birthdate'] = array('controls' => array());
    $this->_tableMap['login']['otherloginfks'] = array('controls' => array());


    $this->_tableMap['login_group']['login_grouppk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['login_group']['shortname'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['login_group']['title'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['login_group']['system'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['login_group']['visible'] = array('controls' => array('is_integer(%)'));


    $this->_tableMap['login_group_member']['login_group_memberpk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['login_group_member']['login_groupfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['login_group_member']['loginfk'] = array('controls' => array('is_key(%)'));
  }

}