<?php

require_once('component/folder/folder.class.ex.php5');

class CSl_folder extends CFolderEx
{
  protected $csUid = '555-002';
  protected $csAction = '';
  protected $csType = '';
  protected $cnPk = 0;
  protected $csMode = '';
  protected $casCpValues = array();
  protected $csLanguage = '';
  protected $casText = array();

  public function __construct()
  {
    parent::__construct();
    $this->csUid = '555-002';
  }

  public function getComponentUid()
  {
    return '555-002';
  }

  protected function _getUid()
  {
    return '555-002';
  }

  public function getComponentName()
  {
    return 'Slistem folder';
  }


  public function getResourcePath()
  {
    return '/component/sl_folder/resources/';
  }


  public function setLanguage($psLanguage)
  {
    $this->csLanguage = $psLanguage;
  }

}
