<?php

require_once('common/lib/model.class.php5');

class CEventModel extends CModel
{
  public function __construct()
  {
    parent::__construct();
    return $this->_initMap();
  }

  protected function _initMap()
  {
    $this->_tableMap['event']['eventpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['event']['type'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['event']['title'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['event']['date_create'] = array ('controls' => array ('is_datetime(%)'));
    $this->_tableMap['event']['date_display'] = array ('controls' => array ('is_datetime(%)'));
    $this->_tableMap['event']['date_update'] = array ('controls' => array ('is_datetime(%)'));
    $this->_tableMap['event']['created_by'] = array ('controls' => array ('is_integer(%)', '!empty(%)'));
    $this->_tableMap['event']['updated_by'] = array ('controls' => array ('is_integer(%)', '!empty(%)'));
    $this->_tableMap['event']['content'] = array ('controls' => array());
    $this->_tableMap['event']['_fts'] = array ('controls' => array());

    return true;
  }
}