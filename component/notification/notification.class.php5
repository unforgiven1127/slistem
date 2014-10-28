<?php

class CNotification
{
  protected $csUid = '333-333';
  protected $csAction = '';
  protected $csType = '';
  protected $cnPk = 0;
  protected $csMode = '';
  protected $casCpValues = array();
  protected $csLanguage;
  private $coModel = null;

  public function __construct()
  {
  }


  public function getComponentUid()
  {
    return '333-333';
  }

  protected function _getUid()
  {
    return '333-333';
  }
  protected function getDefaultAction()
  {
    return CONST_ACTION_LIST;
  }

  protected function getDefaultType()
  {
    return CONST_NOTIFY_TYPE_NOTIFICATION;
  }
  public function getComponentPublicItems($psInterface = '')
  {
    return array();
  }

  public function getComponentName()
  {
    return 'Notification';
  }

  public function getResourcePath()
  {
    return '/component/notification/resources/';
  }


  public function setLanguage($psLanguage)
  {
    $this->csLanguage = $psLanguage;
  }

  public function getCronJob()
  {
    return '';
  }

  protected function &_getModel()
  {
    if($this->coModel !== null)
      return $this->coModel;

    require_once('component/notification/notification.model.php5');
    require_once('component/notification/notification.model.ex.php5');
    $this->coModel = new CNotificationModelEx();

    return $this->coModel;
  }


  public function sendNotification()
  {
    return true;
  }

  public function displayNotification()
  {
    return '';
  }

  protected function _processUrl()
  {
    $oPage = CDependency::getCpPage();

    $this->csAction = $oPage->getAction();
    $this->csType = $oPage->getType();
    $this->cnPk = $oPage->getPk();
    $this->csMode = $oPage->getMode();

    if(empty($this->csAction))
      $this->csAction = $this->getDefaultAction();

    if(empty($this->csType))
      $this->csType = $this->getDefaultType();

    return true;
  }

}
