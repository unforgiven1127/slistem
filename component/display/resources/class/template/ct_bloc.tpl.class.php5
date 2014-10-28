<?php
require_once(__DIR__.'/template.tpl.class.php5');

class CTemplateCtBloc extends CTemplate
{

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'row';
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

  public function getDisplay($pasData, $pasField, $pasColumnParam = array())
  {
    $oDisplay = CDependency::getCpHtml();

    $sHTML = $oDisplay->getBlocStart('', array('class' => 'tplListBloc'));

    $nCount = 0;
    //dump($pasField);
    foreach($pasData as $sField => $sValue)
    {
      $asOption = array('class' => $pasColumnParam[$nCount]['tag']);

      if(in_array($sField, $pasField))
      {
        $sHTML.= $oDisplay->getSpanStart('', $asOption);
        $sHTML.= $sField.' : '.$sValue.'<br />';
        $sHTML.= $oDisplay->getSpanEnd();
      }

      $nCount++;
    }

    $sHTML.= $oDisplay->getBlocEnd();
    $sHTML.= $oDisplay->getFloatHack();
    return $sHTML;
  }
}
?>
