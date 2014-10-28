<?php

class CSharedspace
{
  protected $csUid = '999-111';
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
    return '999-111';
  }

  protected function _getUid()
  {
    return '999-111';
  }

  public function getComponentName()
  {
    return 'sharedspace';
  }

  public function getDefaultType()
  {
    return CONST_SS_TYPE_DOCUMENT;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_LIST;
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

    $this->casCpValues = array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => $this->csAction, CONST_CP_TYPE => $this->csType, CONST_CP_PK => (int)$this->cnPk);

    return true;
  }

  public function getResourcePath()
  {
    return '/component/sharedspace/resources/';
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    return array();
  }


  public function getCronJob()
  {
    echo  'Shared space:  default cron done';
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

    require_once('component/sharedspace/sharedspace.model.php5');
    require_once('component/sharedspace/sharedspace.model.ex.php5');
    $this->coModel = new CSharedspaceModelEx();

    return $this->coModel;
  }
}
