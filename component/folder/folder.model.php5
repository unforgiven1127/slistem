<?php

require_once('common/lib/model.class.php5');

class CFolderModel extends CModel
{

  public function __construct()
  {
    parent::__construct();
    $this->_initMap();
    return true;
  }


  protected function _initMap()
  {
    $this->_tableMap['folder']['folderpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder']['parentfolderfk'] = array ('controls' => array('is_int(%)'));
    $this->_tableMap['folder']['label'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['folder']['rank'] = array ('controls' => array ());
    $this->_tableMap['folder']['ownerloginfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder']['private'] = array ('controls' => array ());

    $this->_tableMap['folder_link']['folder_linkpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_link']['folderfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_link']['cp_uid'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['folder_link']['cp_action'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['folder_link']['cp_type'] = array ('controls' => array ('!empty(%)'));

    $this->_tableMap['folder_item']['folder_itempk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_item']['parentfolderfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_item']['label'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['folder_item']['rank'] = array ('controls' => array ());
    $this->_tableMap['folder_item']['itemfk'] = array ('controls' => array ('is_key(%)'));

    $this->_tableMap['folder_rights']['folder_rightspk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_rights']['folderfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['folder_rights']['loginfk'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['folder_rights']['rights'] = array ('controls' => array ('!empty(%)'));

    return true;
  }


}