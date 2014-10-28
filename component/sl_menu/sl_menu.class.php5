<?php
require_once('component/menu/menu.class.ex.php5');

class CSl_menu extends CMenuEx
{
  protected $csUid = '555-003';
  protected $csAction = '';
  protected $csType = '';
  protected $cnPk = 0;
  protected $csMode = '';
  protected $casCpValues = array();
  protected $csLanguage;

  public function __construct()
  {
    parent::__construct();
    return true;
  }

  public function getComponentUid()
  {
    return '555-003';
  }

  protected function _getUid()
  {
    return '555-003';
  }

  public function getComponentName()
  {
    return 'Slistem menu';
  }

  public function getResourcePath()
  {
    return '/component/sl_menu/resources/';
  }


  public function setLanguage($psLanguage)
  {
    $this->csLanguage = $psLanguage;
  }
}
