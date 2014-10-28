<?php

require_once(__DIR__.'/template.conf.class.php5');
/**
* Parent class for all templates and sub templates
*/
class CTemplate
{
  protected $csUid = '';
  protected $csTplType = '';
  protected $casTplToLoad = array();
  protected $casTplToProvide = array();
  protected $casTplProvided = array();
  protected $coConfig = null;
  protected $cavParams = array();
  protected $coTplManager = null;
  protected $caoSubTpl = array();


  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    if(!assert('is_object($poTplManager) && !empty($psUid) && is_integer($pnTemplateNumber)'))
      return false;

    $sCurrentClass = get_called_class();

    //save the pointer to the parent "factory" TemplateManager
    $this->coTplManager = $poTplManager;
    $this->csUid = $psUid;

    //fetch from the ManagerParams the class name this template requires
    foreach($this->casTplToProvide as $sType)
    {
      $asParams = $this->coTplManager->getParams();

      $vTemplate = $asParams['sub_template'][$sCurrentClass][$pnTemplateNumber][$sType];
      if(is_array($vTemplate))
        $this->casProvidedClass[$sType] = $vTemplate['class'];
      else
        $this->casProvidedClass[$sType] = $vTemplate;
    }

    //instanciate sub-templates using the factory function (manage dependencies)
    //and store a pointer towards the sub-template (original object stored in the manager)
    foreach($this->casTplToLoad as $sClassName)
    {
      $avTemplate = $poTplManager->loadTemplate($sClassName);
      if(empty($avTemplate))
      {
        assert('false;// could not load sub-template for '.$sClassName.'.');
      }
      else
        $this->caoSubTpl[$avTemplate['class']] = $avTemplate['object'];
    }


    //---------------------------------------------------------------------
    //---------------------------------------------------------------------
    //manage custom templates dependencies and parameters
    //$pnTemplateNumber is the number of time the template has been used. (0 by default)
    //for example: a page with 2 lists could call twice list-template and twice row-template with different parameters

    //check if there are params for this template
    if(isset($pasParams['sub_template'][$sCurrentClass][$pnTemplateNumber]) && !empty($pasParams['sub_template'][$sCurrentClass][$pnTemplateNumber]))
    {
      $this->cavParams = $pasParams['sub_template'][$sCurrentClass][$pnTemplateNumber];
    }


    //Check that we've got a classname/path for every dependencies
    if(count($this->casTplToProvide) != count($this->cavParams))
    {
      assert('false; // this template requires '.count($this->casTplToProvide).' sub templates, '.count($this->cavParams).' given. ');
      return false;
    }

    //for every dependency, laod the sub-template and check it's' the correct template type
    foreach($this->casTplToProvide as $sTemplateType)
    {
      $vTemplate = $this->cavParams[$sTemplateType];
      if(is_array($vTemplate))
        $sTemplate = $vTemplate['path'];
      else
        $sTemplate = $vTemplate;

      $avTemplate = $poTplManager->loadTemplate($sTemplate);

      if(empty($avTemplate['class']))
      {
        assert('false; // could not load the sub template ['.$sTemplate.']');
        return false;
      }

      $sType = $avTemplate['object']->getTemplateType();
      if($sTemplateType != $sType)
      {
        assert('false; //sub template require: '.$sTemplateType.' / provided in params  '.$sType);
        return false;
      }

      //store sub template
      $this->caoSubTpl[$avTemplate['class']] = $avTemplate['object'];
    }

    return true;
  }


  public function setParams($pasParams)
  {
    $this->cavParams = (array)$pasParams;
  }

  public function getConfig()
  {
    $oPointer = &$this->coConfig;
    return $oPointer;
  }



  /**
   * To be redifine in children classes, use as a example
   *
   * @return string
   */
  public function getDisplay($pvData1 = null, $pvData2 = null, $pvData3 = null, $pvData4 = null)
  {
    return '';

    /*
    //fetch whatever params passed in the getDisplay
    $asParams = func_get_args();

    //access sub templates
    foreach($this->caoSubTpl as $oSubTpl)
      $sHTML.= '- SUB TPL TEXT: '.$this->caoSubTpl->getDisplay().'<br />';

    //or
    $sHTML.= $this->caoSubTpl['CTemplateList']->getDisplay($pvData);


    //use data stored in manager
    $asData = $this->coTplManager->getData();
    foreach($asData['data'] as $asRow)
    {
      //do somethintg
    }
    */
  }
}
?>
