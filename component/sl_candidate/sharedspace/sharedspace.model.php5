<?php

require_once('common/lib/model.class.php5');

class CSharedSpaceModel extends CModel
{

  public function __construct()
  {
    $this->oDB = CDependency::getComponentByName('database');
    $this->_initMap();
    return true;
  }

  protected function _initMap()
  {
    $this->_tableMap['document']['document_pk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document']['creatorfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document']['title'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document']['doc_type'] = array();
    $this->_tableMap['document']['description'] = array('controls' => array());
    $this->_tableMap['document']['description_html'] = array('controls' => array());
    $this->_tableMap['document']['private'] = array('controls' => array('((% === 0) || (% === 1) || (% === 2))'));
    $this->_tableMap['document']['date_creation'] = array('controls' => array());
    $this->_tableMap['document']['date_update'] = array('controls' => array());

    $this->_tableMap['document_file']['document_filepk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_file']['documentfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_file']['mime_type'] = array('controls' => array());
    $this->_tableMap['document_file']['initial_name'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document_file']['file_name'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document_file']['file_path'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document_file']['file_size'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document_file']['creatorfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_file']['date_creation'] = array('controls' => array());
    $this->_tableMap['document_file']['live'] = array('controls' => array());
    $this->_tableMap['document_file']['original'] = array('controls' => array());
    $this->_tableMap['document_file']['compressed'] = array('controls' => array());

    $this->_tableMap['document_link']['document_linkpk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['document_link']['documentfk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['document_link']['cp_uid'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['document_link']['cp_action'] = array('controls' => array());
    $this->_tableMap['document_link']['cp_type'] = array('controls' => array());
    $this->_tableMap['document_link']['cp_pk'] = array('controls' => array('is_integer(%)'));

    $this->_tableMap['document_rights']['document_rightspk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_rights']['documentfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_rights']['loginfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['document_rights']['rights'] = array('controls' => array('!empty(%)'));

    $this->_tableMap['document_log']['document_logpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['document_log']['documentfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['document_log']['document_filefk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['document_log']['loginfk'] = array ('controls' => array ('is_key(%)'));

    $this->_tableMap['document_notification']['document_notificationpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['document_notification']['documentfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['document_notification']['loginfk'] = array ('controls' => array ('is_key(%)'));
  }

}
