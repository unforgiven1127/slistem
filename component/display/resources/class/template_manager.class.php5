<?php

class CTemplateManager
{
  protected $casTemplate = array();
  protected $cavData = null;
  protected $cbLoaded = false;
  protected $csTemplateName = '';
  protected $coTemplate = null;
  protected $caoTemplate = array();
  protected $casTplUid = array();
  protected $csClassName = '';
  protected $casParams = array();


  /**
  * Conatins the list of all the "generic" templates
  */
  public function __construct()
  {
    //contains the liost of all the generic templates
    $this->casTemplate = array
    (
      'CTemplatePage' => array('path' => __DIR__.'/template/page.tpl.class.php5', 'class' => 'CTemplatePage'),
      'CTemplatePageList' => array('path' => __DIR__.'/template/pagelist.tpl.class.php5', 'class' => 'CTemplatePageList'),

      'CTemplateList' => array('path' => __DIR__.'/template/list.tpl.class.php5', 'class' => 'CTemplateList'),
      'CTemplateListTabs' => array('path' => __DIR__.'/template/listtabs.tpl.class.php5', 'class' => 'CTemplateListTabs'),

      'CTemplateRow' => array('path' => __DIR__.'/template/row.tpl.class.php5', 'class' => 'CTemplateRow'),

      'CTemplatePager' => array('path' => __DIR__.'/template/pager.tpl.class.php5', 'class' => 'CTemplatePager'),

      //here for testing only, this is the less generic ever
      'CTemplateCtRow' => array('path' => __DIR__.'/template/ct_row.tpl.class.php5', 'class' => 'CTemplateCtRow'),
      'CTemplateCtBloc' => array('path' => __DIR__.'/template/ct_bloc.tpl.class.php5', 'class' => 'CTemplateCtBloc'),
      'CTemplateCpRow' => array('path' => __DIR__.'/template/cp_row.tpl.class.php5', 'class' => 'CTemplateCpRow')
    );
  }

  /* ***************************************************************************** */
  /* ***************************************************************************** */
  //Accessors

  public function setData($psLabel, $pvData)
  {
    if(!assert('!empty($pvData)'))
      return false;

    $this->cavData[$psLabel] = $pvData;
  }

  public function getData()
  {
    return $this->cavData;
  }

  public function getParams()
  {
    return $this->casParams;
  }

  public function setTemplateParams($psTemplate, $pasParams, $pnOccurence = 0)
  {
    if(!assert('!empty($psTemplate) && is_array($pasParams) && !empty($pasParams)'))
      return false;

    $sUid = $this->casTplUid[$psTemplate][$pnOccurence];
    $this->caoTemplate[$sUid]->setParams($pasParams);

    return true;
  }

  public function getTemplateParams($psTemplate, $pnOccurence = 0)
  {
    if(!assert('!empty($psTemplate) && is_integer($pnOccurence)'))
      return false;

    if(!isset($this->casTplUid[$psTemplate][$pnOccurence]))
      return false;

    $sUid = $this->casTplUid[$psTemplate][$pnOccurence];
    return $this->caoTemplate[$sUid];
  }


  public function getTemplateConfig($psTemplate, $pnOccurence = 0)
  {
    if(!assert('!empty($psTemplate) && is_integer($pnOccurence)'))
      return false;

    $sUid = $this->casTplUid[$psTemplate][$pnOccurence];
    $oConfig = $this->caoTemplate[$sUid]->getConfig();

    return $oConfig;
  }






  /* ***************************************************************************** */
  /* ***************************************************************************** */
  //Real deal methods

  /**
   * Initialize the requested template and all its sub templates recursively
   * + store params and data for later use
   *
   * Note: prefer passing the list/displayed data in the getDisplay function, to reduce the size of stored data
   *
   * @param string $psTemplate  : template name or path
   * @param array $pasParams    : template(s) params
   * @param mixed $pvData       : data stored in the manager, accessible by all templates
   * @return boolean
  */
  public function initTemplate($psTemplate, $pasParams = array(), $pvData = null)
  {
    if(!assert('!empty($psTemplate) && is_array($pasParams)'))
      return false;

    if(!isset($pasParams['sub_template']))
      $pasParams['sub_template'] = array();

    $this->casParams = $pasParams;
    $this->cavData['data'] = $pvData;

    //include files and instanciate class
    $avTemplate = $this->loadTemplate($psTemplate);

    if(!isset($avTemplate['class']) || empty($avTemplate['class']))
    {
      assert('false; // could not load the template ['.$psTemplate.']');
      return false;
    }

    //save the instanciate template for later use
    $this->coTemplate = $avTemplate['object'];


    //save attribute in current class
    $this->csClassName = $avTemplate['class'];
    $this->cbLoaded = true;

    return true;
  }

  /**
  *  Check template name/path availibility
  * Include required files and instanciate the template.
  * Finally save the template in an array
  *
  * @param string $psTemplate path
  * @return array containing the class name and a pointer towards the object
  */
  public function loadTemplate($psTemplate)
  {
    if(!assert('!empty($psTemplate)'))
      return array();

    if(isset($this->casTemplate[$psTemplate]))
    {
      $sClassName = $this->casTemplate[$psTemplate]['class'];
      $sPath = $this->casTemplate[$psTemplate]['path'];
    }
    else
    {
      if(!is_file($psTemplate))
      {
        assert('false; // template file not found at ['.$psTemplate.']');
        return array();
      }

      $sPath = $psTemplate;
      $sName = basename($psTemplate, '.tpl.class.php5');
      $sClassName = 'C'.ucfirst($sName);
    }


    $bInclude = include_once($sPath);
    if(!$bInclude)
    {
      assert('false; // could not include the template file ['.$sPath.']');
      return array();
    }

    //instanciate the requested template so it can manage its dependencies and sub templates
    //Passing a pointer towrd the manager o it can request data and params when needed
    $sTemplateUid = uniqid();
    $this->casTplUid[$sClassName][] = $sTemplateUid;
    $nOccurence = count($this->casTplUid[$sClassName])-1;

    $oTemplate = new $sClassName ($this, $sTemplateUid, $this->casParams, $nOccurence);


    //store the list of all instanciated templates, giving them an ID
    $this->caoTemplate[$sTemplateUid] = $oTemplate;

    return array('class' =>$sClassName, 'object' => &$this->caoTemplate[$sTemplateUid]);
  }


  /**
  * Check if the template is ready
  * then call getDisplay() on the requested template with all received parameters
  *
  * @return string
  */
  public function getDisplay()
  {
    if(!$this->cbLoaded)
    {
      assert('false; // template not initialized properly.');
      return '';
    }

    //return $this->coTemplate->getDisplay($this->cvData);
    return call_user_func_array(array($this->coTemplate, 'getDisplay'), func_get_args());
  }
}

?>