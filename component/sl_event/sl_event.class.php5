<?php
require_once('component/event/event.class.ex.php5');

class CSl_event extends CEventEx
{
  protected $csUid = '555-004';
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
    return '555-004';
  }

  protected function _getUid()
  {
    return '555-004';
  }

  public function getComponentName()
  {
    return 'Slistem notes';
  }

  public function getResourcePath()
  {
    return '/component/sl_event/resources/';
  }


  public function setLanguage($psLanguage)
  {
    $this->csLanguage = $psLanguage;
  }
}
