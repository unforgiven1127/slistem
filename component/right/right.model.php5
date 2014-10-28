<?php

require_once('common/lib/model.class.php5');

class CRightModel extends CModel
{
  public function __construct()
  {
    $this->oDB = CDependency::getComponentByName('database');
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['right']['rightpk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right']['label'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['right']['description'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['right']['type'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['right']['cp_uid'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['right']['cp_action'] = array ('controls' => array());
    $this->_tableMap['right']['cp_type'] = array ('controls' => array());
    $this->_tableMap['right']['cp_pk'] = array ('controls' => array('is_integer(%)'));


    $this->_tableMap['right_tree']['right_treepk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right_tree']['rightfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right_tree']['parentfk'] = array ('controls' => array('is_integer(%)'));


    $this->_tableMap['right_user']['right_userpk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right_user']['rightfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right_user']['loginfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['right_user']['callback'] = array ('controls' => array());
    $this->_tableMap['right_user']['callback_params'] = array ('controls' => array());
  }
}
