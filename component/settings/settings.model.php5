<?php

require_once('common/lib/model.class.php5');

class CSettingsModel extends CModel
{
  public function __construct()
  {
    $this->oDB = CDependency::getComponentByName('database');
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['settings']['settingspk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['settings']['fieldname'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['settings']['fieldtype'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['settings']['fieldoption'] = array ('controls' => array());
    $this->_tableMap['settings']['value'] = array ('controls' => array());
    $this->_tableMap['settings']['description'] = array ('controls' => array());
    $this->_tableMap['settings']['is_user_setting'] = array ('controls' => array());

    $this->_tableMap['settings_user']['settings_userpk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['settings_user']['loginfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['settings_user']['settingsfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['settings_user']['value'] = array ('controls' => array());

    $this->_tableMap['saved_search']['id'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['saved_search']['login_activitypk'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['saved_search']['loginpk'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['saved_search']['search_label'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['saved_search']['date_create'] = array ('controls'=>array(),'type'=>'date');
  }
}
