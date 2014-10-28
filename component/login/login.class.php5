<?php

class CLogin
{
  protected $csUid = '579-704';
  protected $csAction = '';
  protected $csType = '';
  protected $cnPk = 0;
  protected $csMode = '';
  protected $casCpValues = array();
  protected $csLanguage = '';
  protected $casText = array();
  private $coModel = null;

  public function __construct()
  {

  }

  public function getComponentUid()
  {
    return '579-704';
  }

  protected function _getUid()
  {
    return '579-704';
  }

  public function getComponentName()
  {
    return 'login';
  }

  public function getDefaultType()
  {
    return '';
  }

  public function getDefaultAction()
  {
    return '';
  }
  public function getComponentPublicItems($psInterface = '')
  {
    return array();
  }

  public function getAction()
  {
    return $this->csAction;
  }
  public function setAction($psAction)
  {
    if(!assert('!empty($psAction)'))
     return '';

    return $this->csAction = $psAction;
  }


  public function getType()
  {
    return $this->csType;
  }
  public function setType($psType)
  {
    if(!assert('!empty($psType)'))
    return '';

    return $this->csType = $psType;
  }


  public function getPk()
  {
  return $this->cnPk;
  }
  public function setPk($pnPk)
  {
  if(!assert('!empty($pnPk)'))
      return '';

  return $this->cnPk = $pnPk;
  }


  public function getMode()
  {
    return $this->csMode;
  }
  public function setMode($psMode)
  {
    if(!assert('!empty($psMode)'))
    return '';

    return $this->csMode = $psMode;
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

    $this->casCpValues = array(CONST_CP_UID => getValue(CONST_CP_UID), CONST_CP_ACTION => getValue(CONST_CP_ACTION), CONST_CP_TYPE => getValue(CONST_CP_TYPE), CONST_CP_PK => (int)getValue(CONST_CP_PK));

    return true;
  }

  public function getResourcePath()
  {
    return '/component/login/resources/';
  }

  public function getCronJob()
  {
    echo  'Login:  default cron done';
    return '';
  }

  public function setLanguage($psLanguage)
  {
    $this->csLanguage = $psLanguage;
  }

  protected function &_getModel()
  {
    if($this->coModel !== null)
      return $this->coModel;

    require_once('component/login/login.model.php5');
    require_once('component/login/login.model.ex.php5');
    $this->coModel = new CLoginModelEx();

    return $this->coModel;
  }
}
