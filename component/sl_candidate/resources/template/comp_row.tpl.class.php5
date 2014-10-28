<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CComp_row extends CTemplate
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
    //$oLogin = CDependency::getCpLogin();

    $sHTML = '';
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListRow tplCompRow'));


    //get the uniq column id from the column param for js sort features
    //inherit the column style/class
    set_array($pasColumnParam[0]['tag'], '');
    $asOption = array('class' => $pasColumnParam[0]['tag']);
    $sHTML.= $oDisplay->getBloc('', '<input name="listBox[]" value="'.$pasData['sl_companypk'].'" id="listBox_'.$pasData['sl_companypk'].'" class="listBox" type="checkbox" onchange="listBoxClicked(this);" />', $asOption);


    set_array($pasColumnParam[1]['tag'], '');
    $asOption = array('class' => $pasColumnParam[1]['tag'].' tplCandiRow_small');
    $sHTML.= $oDisplay->getBloc('', '<label class="list_item_draggable " for="listBox_'.$pasData['sl_companypk'].'" data-ids="'.$pasData['sl_companypk'].'" data-type="comp" data-title="'.$pasData['sl_companypk'].' - '.$pasData['name'].'">'.$pasData['sl_companypk'].'</label>', $asOption);


    set_array($pasColumnParam[2]['tag'], '');
    $asOption = array('class' => $pasColumnParam[2]['tag'].' tplCandiRow_continuous');
    if(!empty($pasData['is_client']))
    {
      $asOption['class'].= ' tplCandi_client';
      $asOption['title'] = 'Is a client company';
    }
    $sHTML.= $oDisplay->getBloc('', '', $asOption);


    set_array($pasColumnParam[3]['tag'], '');
    $asOption = array('class' => $pasColumnParam[3]['tag'].' tplCandiRow_continuous');
    if(!$pasData['is_nc_ok'])
    {
      $asOption['class'].= ' tplCandi_status_play';
      $asOption['title'] = 'Name collect not allowed';
    }
    $sHTML.= $oDisplay->getBloc('', '', $asOption);


    set_array($pasColumnParam[4]['tag'], '');
    $asOption = array('class' => $pasColumnParam[4]['tag']);
    $asOption['sort_value'] = (int)$pasData['level'];
    if($pasData['level'] == 1)
      $pasData['level'] = 'A';
    elseif($pasData['level'] == 2)
      $pasData['level'] = 'B';
    else
      $pasData['level'] = 'C';

    $sHTML.= $oDisplay->getBloc('', $pasData['level'], $asOption);


    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$pasData['sl_companypk']);
    $sLink = $oDisplay->getLink($pasData['name'], 'javascript:;', array('onclick' => 'view_comp(\''.$sURL.'\');'));
    set_array($pasColumnParam[5]['tag'], '');
    $asOption = array('class' => $pasColumnParam[5]['tag']);
    $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


    set_array($pasColumnParam[6]['tag'], '');
    $asOption = array('class' => $pasColumnParam[6]['tag']);
    $sHTML.= $oDisplay->getBloc('', $pasData['industry_list'], $asOption);


    set_array($pasColumnParam[7]['tag'], '');
    $asOption = array('class' => $pasColumnParam[7]['tag']);
    $sHTML.= $oDisplay->getBloc('', $pasData['description'], $asOption);


    /*set_array($pasColumnParam[8]['tag'], '');
    $asOption = array('class' => $pasColumnParam[8]['tag']);
    $sHTML.= $oDisplay->getBloc('', $pasData['contact'], $asOption);*/

    set_array($pasColumnParam[8]['tag'], '');
    $asOption = array('class' => $pasColumnParam[8]['tag']);
    $sHTML.= $oDisplay->getBloc('', $pasData['created_by'], $asOption);


/*    $asItem = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pasData['sl_candidatepk']);
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'rowActionContainer'));

    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasData['sl_candidatepk'], $asItem);
    $sHTML.= '<a onclick="var oConf = goPopup.getConfig(); oConf.width = 1080; oConf.height = 725;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " href="javascript:;">
                <img title="Edit candidate" src="/component/sl_candidate/resources/pictures/edit_24.png">
              </a>';


    $sURL = $oPage->getAjaxUrl('sl_event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);

    $sHTML.= '<a onclick="var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " href="javascript:;">
                <img title="Add a note" src="/component/sl_candidate/resources/pictures/tabs/note_24.png">
              </a>';
    $sHTML.= $oDisplay->getBlocEnd();*/


    $sHTML.= $oDisplay->getBlocEnd();
    return $sHTML;
  }
}