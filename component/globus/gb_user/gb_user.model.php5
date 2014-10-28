<?php

require_once('common/lib/model.class.php5');

class CGbUserModel extends CModel
{

  public function __construct()
  {
    parent::__construct();
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['gbuser']['gbuserpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser']['loginfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser']['type'] = array ('controls' => array ());
    $this->_tableMap['gbuser']['gbuser_companyfk'] = array ('controls' => array ('is_numeric(%)'));

    $this->_tableMap['gbuser_company']['gbuser_companypk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_company']['name'] = array ('controls' => array ());
    $this->_tableMap['gbuser_company']['industryfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_company']['nationalityfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_company']['active'] = array ('controls' => array ());

    $this->_tableMap['gbuser_group']['gbuser_grouppk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_group']['name'] = array ('controls' => array ());
    $this->_tableMap['gbuser_group']['gbuser_companyfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_group']['active'] = array ('controls' => array ());

    $this->_tableMap['gbuser_group_member']['gbuser_group_memberpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_group_member']['gbuserfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_group_member']['gbuser_groupfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbuser_group_member']['date_notified'] = array ('controls' => array());

  }

}