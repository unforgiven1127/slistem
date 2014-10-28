<?php
require_once(__DIR__.'/template.tpl.class.php5');

class CTemplateCtRow extends CTemplate
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

    $sHTML = '';
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListRow'));

    $nCount = 0;
    foreach($pasField as $sFieldName)
    {

      //get the uniq column id from the column param for js sort features
      $asOption = array();
      if(isset($pasColumnParam[$nCount]['tag']))
      {
        $sClass = $pasColumnParam[$nCount]['tag'];

        //inherit the column style/clas
        $asOption = array('class' => $sClass);

        /*if(isset($pasColumnParam[$nCount]['width']) && !empty($pasColumnParam[$nCount]['width']))
          $asOption['style'] = 'width: '.$pasColumnParam[$nCount]['width'].'; ';*/
      }


      if(!isset($pasData[$sFieldName]))
      {
        $sHTML.= $oDisplay->getBloc('', '', $asOption);
      }
      else
      {
        $sValue = $pasData[$sFieldName];
        $sHTML.= $oDisplay->getBloc('', $sValue, $asOption);
      }

      $nCount++;
    }

    $sHTML.= $oDisplay->getBlocEnd();
    return $sHTML;
  }
}
?>
