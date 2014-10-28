<?php
require_once(__DIR__.'/template.tpl.class.php5');

class CTemplatePage extends CTemplate
{

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'page';
    $this->casTplToLoad = array();
    $this->casTplToProvide = array();
    parent::__construct($poTplManager, $psUid, $pasParams, $pnTemplateNumber);
  }


  public function getTemplateType()
  {
    return $this->csTplType;
  }

  public function getRequiredFeatures()
  {
    return array('to_load' => $this->casTplToLoad, 'to_provide' => $this->casTplToProvide);
  }

  public function getDisplay()
  {

    return 'new page template: title on top + nothing ';
  }
}
?>
