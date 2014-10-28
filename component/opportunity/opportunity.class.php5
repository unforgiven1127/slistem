<?php

class COpportunity
{
  protected $csUid = '555-123';
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
    return '555-123';
  }

  protected function _getUid()
  {
    return '555-123';
  }

  public function getComponentName()
  {
    return 'opportunity';
  }

  public function getDefaultType()
  {
    return '';
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_ADD;
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
    return '/component/opportunity/resources/';
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    return array();
  }

  public function getCronJob()
  {
    echo  'Opportunity:  default cron done';
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

    require_once('component/opportunity/opportunity.model.php5');
    require_once('component/opportunity/opportunity.model.ex.php5');
    $this->coModel = new COpportunityModelEx();

    return $this->coModel;
  }

}
