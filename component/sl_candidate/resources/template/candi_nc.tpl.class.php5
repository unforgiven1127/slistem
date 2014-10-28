<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CCandi_nc extends CTemplate
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

    $oPage = CDependency::getCpPage();
    $oDisplay = CDependency::getCpHtml();

    $sHTML = '';
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListRow tplCandiRow'));


    //get the uniq column id from the column param for js sort features
    //inherit the column style/class
    set_array($pasColumnParam[0]['tag'], '');
    $asOption = array('class' => $pasColumnParam[0]['tag']);
    $sHTML.= $oDisplay->getBloc('', '<input name="listBox[]" value="'.$pasData['sl_candidatepk'].'" id="listBox_'.$pasData['sl_candidatepk'].'" class="listBox" type="checkbox" onchange="listBoxClicked(this);" />', $asOption);


    set_array($pasColumnParam[1]['tag'], '');
    $asOption = array('class' => $pasColumnParam[1]['tag'].' tplCandiRow_small');
    $sHTML.= $oDisplay->getBloc('', '<label class="list_item_draggable" for="listBox_'.$pasData['sl_candidatepk'].'" data-ids="'.$pasData['sl_candidatepk'].'" data-type="candi" data-title="'.$pasData['sl_candidatepk'].' - '.$pasData['g'].'">'.$pasData['sl_candidatepk'].'</label>', $asOption);


    set_array($pasColumnParam[2]['tag'], '');
    $asOption = array('class' => $pasColumnParam[2]['tag'].' tplCandiRow_continuous');
    $asOption['sort_value'] = rand(0,1);

    if((int)$pasData['sex'] == 1)
      $asOption['class'].= ' tplCandi_man';
    else
      $asOption['class'].= ' tplCandi_woman';

    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$pasData['sl_candidatepk']);
    $sLink = $oDisplay->getLink($pasData['lastname'], 'javascript:;', array('onclick' => 'view_candi(\''.$sURL.'\');'));
    $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


    set_array($pasColumnParam[3]['tag'], '');
    $asOption = array('class' => $pasColumnParam[3]['tag']);

    $sLink = $oDisplay->getLink($pasData['firstname'], 'javascript:;', array('onclick' => 'view_candi(\''.$sURL.'\');'));
    $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


    set_array($pasColumnParam[4]['tag'], '');
    $asOption = array('class' => $pasColumnParam[4]['tag']);
    if(!empty($pasData['h']))
    {
      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$pasData['sl_companypk']);
      $sLink = $oDisplay->getLink($pasData['h'], 'javascript:;', array('onclick' => 'view_comp(\''.$sURL.'\');'));
    }
    else
      $sLink = '';

    $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);

    set_array($pasColumnParam[5]['tag'], '');
    $asOption = array('class' => $pasColumnParam[5]['tag']);
    if(rand(0,1))
      $sHTML.= $oDisplay->getBloc('', $pasData['contact_detail'], $asOption);
    else
      $sHTML.= $oDisplay->getBloc('', '', $asOption);


    $asItem = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pasData['sl_candidatepk']);
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'rowActionContainer'));

    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasData['sl_candidatepk'], $asItem);
    $sHTML.= '<a onclick="var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " href="javascript:;">
                <img title="Edit candidate" src="/component/sl_candidate/resources/pictures/edit_24.png">
              </a>';


    $sURL = $oPage->getAjaxUrl('sl_event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);

    $sHTML.= '<a onclick="var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " href="javascript:;">
                <img title="Add a note" src="/component/sl_candidate/resources/pictures/tabs/note_24.png">
              </a>';

    $sHTML.= $oDisplay->getBlocEnd();


    /*$nCount = 0;
    foreach($pasField as $sFieldName)
    {
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
    }*/

    $sHTML.= $oDisplay->getBlocEnd();
    return $sHTML;
  }
}
?>
