<?php
require_once(__DIR__.'/template.tpl.class.php5');

class CTemplatePageList extends CTemplate
{

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'page';
    $this->casTplToLoad = array('CTemplateList');

    parent::__construct($poTplManager, $psUid, $pasParams, $pnTemplateNumber);
  }





  public function getDisplay($pvData, $pasPageTitle)
  {
    $oDisplay = CDependency::getCpHtml();

    $sHTML = $oDisplay->getTitleLine($pasPageTitle['text'], $pasPageTitle['picture']);
    $sHTML.= $oDisplay->getCR();

    $sHTML.= $this->caoSubTpl['CTemplateList']->getDisplay($pvData);

    return $sHTML;
  }
}
?>
