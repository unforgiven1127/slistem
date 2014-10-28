<?php

require_once('common/lib/model.class.php5');

class CCustomfieldsModel extends CModel
{

  public function __construct()
  {
    parent::__construct();
    $this->_initMap();
    return true;
  }


  protected function _initMap()
  {
    $this->_tableMap['customfield']['customfieldpk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['customfield']['label'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['customfield']['description'] = array ('controls' => array());
    $this->_tableMap['customfield']['fieldtype'] = array ('controls' => array());
    $this->_tableMap['customfield']['defaultvalue'] = array ('controls' => array());
    $this->_tableMap['customfield']['can_be_empty'] = array ('controls' => array('is_integer(%)'));

    $this->_tableMap['customfield_link']['customfield_linkpk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_link']['customfieldfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_link']['cp_uid'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['customfield_link']['cp_action'] = array('controls' => array());
    $this->_tableMap['customfield_link']['cp_type'] = array('controls' => array());
    $this->_tableMap['customfield_link']['cp_pk'] = array('controls' => array('is_integer(%)'));

    $this->_tableMap['customfield_option']['customfield_optionpk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_option']['customfieldfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_option']['label'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['customfield_option']['value'] = array ('controls' => array());

    $this->_tableMap['customfield_value']['customfield_valuepk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_value']['customfieldfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['customfield_value']['itemfk'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['customfield_value']['value'] = array ('controls' => array());
  }

}