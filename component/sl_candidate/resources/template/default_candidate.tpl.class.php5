<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CDefault_candidate extends CTemplate
{
  protected $coDisplay = null;

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'bloc';
    $this->casTplToLoad = array();
    $this->casTplToProvide = array();

    $this->coDisplay = CDependency::getCpHtml();

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile('/component/sl_candidate/resources/css/sl_candidate.css');

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


  public function getDisplay($pasCandidateData, $pasDisplayParams)
  {
    $oCandidate = CDependency::getComponentByName('sl_candidate');

    $asStatus = $oCandidate->getVars()->getCandidateStatusList();
    $asGrade = $oCandidate->getVars()->getCandidateGradeList();


    if($pasCandidateData['sex'] == 1)
       $sGenderClass = 'man';
     else
       $sGenderClass = 'woman';

    $pasCandidateData['sl_candidatepk'] = (int)$pasCandidateData['sl_candidatepk'];


    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile('/component/sl_candidate/resources/css/slistem3.css');
    $oPage->addCssFile('/component/sl_candidate/resources/css/slistem2.css');
    //$asItem = array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $pasCandidateData['sl_candidatepk']);


    //start a data section
    $sHTML = $this->coDisplay->getBlocStart('', array('class' => 'candiTopSection default_theme'));


      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_title'));
      $sHTML.=  '<span class="mainTitle"> Candidate information</span> &nbsp;&nbsp;#<span class="refID">'.$pasCandidateData['sl_candidatepk'].'</span>';

      $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_CANDI, 0, array('duplicate' => $pasCandidateData['sl_candidatepk']));
      $sHTML.=  '<div class="top_action"><a href="javascript:;" onclick="
        var oConf = goPopup.getConfig();
        oConf.width = 1080;
        oConf.height = 725;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " title="duplicate the candidate" >duplicate</a>';

      $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasCandidateData['sl_candidatepk']);
      $sHTML.=  '&nbsp;&nbsp;-&nbsp;&nbsp;
        <a href="javascript:;" onclick="
        var oConf = goPopup.getConfig();
        oConf.width = 1080;
        oConf.height = 725;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " title="edit candidate profile" >edit</a>
        </div>';

      $sHTML.= $this->coDisplay->getBlocEnd();



      $sValue = $this->_getShortenText($pasCandidateData['company_name'], 35);
      if(!empty($sValue))
      {
        $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$pasCandidateData['companyfk']);
        $sValue = $this->coDisplay->getlink($sValue, 'javascript:;', array('onclick' => 'view_comp(\''.$sURL.'\')'));
      }

      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
        $sHTML.= $this->coDisplay->getBloc('', 'company', array('class' => 'candi_detail_label'));
        $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
      $sHTML.= $this->coDisplay->getBlocEnd();



      $sValue = $this->_getShortenText($pasCandidateData['department'], 24);//26, 25
      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
        $sHTML.= $this->coDisplay->getBloc('', 'department', array('class' => 'candi_detail_label'));
        $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
      $sHTML.= $this->coDisplay->getBlocEnd();




       if($pasCandidateData['is_client'])
       {
         $sClass = ' candi_client ';
         $sTag = '<div class="candi_status_icon important" style="position: absolute; top: 2px; right: 0; margin: 0;">client</div> ';
         $sTitle = 'Be careful, this is a client !';
       }
       else
       {
         $sClass = $sTag = $sTitle = '';
       }

       $sValue = '<span style="font-size: 12px;">'.$pasCandidateData['lastname'].'</span>&nbsp;&nbsp;<span style="font-size: 10.5px;color: #5b5b5b">'.$pasCandidateData['firstname'].'</span>';
       if($pasCandidateData['sex'] == 1)
         $sValue = '<span class="man">Mr</span>&nbsp;'.$sValue;
       else
         $sValue = '<span class="woman">Ms</span>&nbsp;'.$sValue;

       $sValue = '<span id="candi_'.$pasCandidateData['sl_candidatepk'].'" data-title="'.$pasCandidateData['sl_candidatepk'].' - '.$pasCandidateData['lastname'].' '.$pasCandidateData['firstname'].'" data-type="candi" data-ids="'.$pasCandidateData['sl_candidatepk'].'" class="list_item_draggable '.$sGenderClass.'">'.$sValue.'</span> ';
       $sJavascript = ' if(!$(this).hasClass(\'initialized\')){  initDrag(\'#candi_'.$pasCandidateData['sl_candidatepk'].'\');  $(this).addClass(\'initialized\') } ';

       $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'.$sClass, 'title' => $sTitle, 'onmouseover' => $sJavascript));
          $sHTML.= $this->coDisplay->getBloc('', 'candidate', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', '<span style="float: left;">'.$sValue.'</span>'.$sTag, array('class' => 'candi_detail_value', 'style' => 'position: relative;'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sValue = $this->_getShortenText($pasCandidateData['title'], 35);
        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'title', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();





        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'occupation', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['occupation'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'industry', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['industry'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();






        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'nationality', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['nationality'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'language', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['language'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();




        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'generated', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', '', array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();


        $nCPA = (int)$pasCandidateData['cpa'];
        $nMBA = (int)$pasCandidateData['mba'];
        $nCollab = (int)$pasCandidateData['is_collaborator'];
        $sText = '';

        if($nMBA)
          $sText.= '<div class="candi_status_icon low_priority" title="Own a MBA">MBA</div>';
        else
          $sText.= '<div class="candi_status_icon inactive" title="Doesn\'t own a MBA" >MBA</div>';

        if($nCPA)
          $sText.= '<div class="candi_status_icon low_priority" title="Own a CPA" >CPA</div>';
        else
          $sText.= '<div class="candi_status_icon inactive" title="Doesn\'t own a MBA" >CPA</div>';

        if($nCollab)
          $sText.= '<div class="candi_status_icon low_priority" title="Is a collborator" >Collab</div>';
        else
          $sText.= '<div class="candi_status_icon inactive" title="Isn\'t a collaborator" >Collab</div>';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', '-', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sText, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();








        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'resides', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['location'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();


        $nSalary = round($pasCandidateData['salary'] /1000000, 2);
        $nBonus = round($pasCandidateData['bonus'] /1000000, 2);
        $sSalary = (round($nSalary+$nBonus, 1)).'M&yen;';
        $sSalary.= '&nbsp;&nbsp;('.$nSalary.'M¥ + '.$nBonus.'M¥)';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'salary', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sSalary, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();





        if(!empty($pasCandidateData['_sys_redirect']) || !empty($pasCandidateData['_sys_status']))
        {
          $sStatusLabel = '[Merged / deleted] &nbsp;&nbsp;&nbsp;';
        }
        else
          $sStatusLabel = $asStatus[$pasCandidateData['statusfk']];

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'status', array('class' => 'candi_detail_label'));

          if($pasCandidateData['statusfk'] >= 101)
            $sHTML.= $this->coDisplay->getBloc('', $sStatusLabel, array('class' => 'candi_detail_value text_alert'));
           else
            $sHTML.= $this->coDisplay->getBloc('', $sStatusLabel, array('class' => 'candi_detail_value'));

          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'Target sal.', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', '- - - ', array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();




        $sValue = (int)date('Y') - (int)date('Y', strtotime($pasCandidateData['date_birth']));
        if($pasCandidateData['is_birth_estimation'])
          $sValue = $pasCandidateData['date_birth'].' (~'.$sValue.' yrs)';
        else
          $sValue = $pasCandidateData['date_birth'].' ('.$sValue.' yrs)';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'birth', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'grade', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $asGrade[$pasCandidateData['grade']], array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();



        if($pasCandidateData['_in_play'])
          $sStatusLabel= '<span class="text_alert">In play</span>';
        else
          $sStatusLabel = 'No';


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'in play', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sStatusLabel, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $nRm = count($pasCandidateData['rm']);
        if($nRm == 0)
        {
          $sManager = 'no active rm';
        }
        elseif($nRm == 1)
        {
          $nRmLoginPk = (int)array_first_key($pasCandidateData['rm']);
          $sManager = $oLogin->getuserLink($nRmLoginPk);
        }
        else
        {
          $sManager = $nRm.' active rm';
        }

        $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_RM, $pasCandidateData['sl_candidatepk']);
        $sManager = '<a id="rm_link_id" href="javascript:;" onclick="
          var oConf = goPopup.getConfig();
          oConf.width = 600;
          oConf.height = 400;
          goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); " title="duplicate the candidate" >'.$sManager.'</a>';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'rm[+]', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sManager, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();






        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING, $pasCandidateData['sl_candidatepk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width= 800; oConf.height = 550; goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');';

        if(empty($pasCandidateData['nb_meeting']))
        {
          $sValue = '<span style="font-style:italic; color: #666; font-size: 10px; float: left;">never met</span>';
        }
        elseif(empty($pasCandidateData['last_meeting']))
          $sValue = '<span style="font-style:italic; color: #666; font-size: 10px; float: left;">scheduled</span>';
        else
          $sValue = '';

        $sValue.= '<div class="candi_status_icon inactive meeting_history" style="float: right;" title="Meeting history">
            <a href="javascript:;" class="floatRight">#'.$pasCandidateData['nb_meeting'].'</a>
              </div>';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'date met', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', '<a href="javascript:;" style="float: left;">'.substr($pasCandidateData['last_meeting'], 0, 10).'</a>'.$sValue, array('class' => 'candi_detail_value clickable', 'onclick' => $sJavascript));
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'reference id', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['uid'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();




        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'created', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasCandidateData['date_added'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'updated', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', ' - ', array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();




        $oLogin = CDependency::getCpLogin();
        $sValue = $oLogin->getUserLink((int)$pasCandidateData['creatorfk']);

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'consultant', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();


        $nRating = (int)$pasCandidateData['profile_rating'];
        if(empty($nRating))
          $nRating = (int)$pasCandidateData['rating'];

        if($nRating > 85)
          $sRating = '<div style="color: green;">'.$nRating.'%</span>';
        elseif($nRating > 70)
          $sRating = '<div style="color: blue;">'.$nRating.'%</span>';
        else
          $sRating = '<div style="color: #F78F6A;">'.$nRating.'%</span>';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'quality', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sRating, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();



        $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

      $sHTML.= $this->coDisplay->getBlocEnd();


      //Not in a section // absolute at bottom
      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_skill_bar default_theme candi_skill_bar_'.$pasCandidateData['grade']));
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">AG<br /> '.(($pasCandidateData['skill_ag'])? $pasCandidateData['skill_ag']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">FX<br /> '.(($pasCandidateData['skill_fx'])? $pasCandidateData['skill_fx']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">AP<br /> '.(($pasCandidateData['skill_ap'])? $pasCandidateData['skill_ap']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">CH<br /> '.(($pasCandidateData['skill_ch'])? $pasCandidateData['skill_ch']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">AM<br /> '.(($pasCandidateData['skill_am'])? $pasCandidateData['skill_am']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">ED<br /> '.(($pasCandidateData['skill_ed'])? $pasCandidateData['skill_ed']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">MP<br /> '.(($pasCandidateData['skill_mp'])? $pasCandidateData['skill_mp']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">PL<br /> '.(($pasCandidateData['skill_pl'])? $pasCandidateData['skill_pl']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">IN<br /> '.(($pasCandidateData['skill_in'])? $pasCandidateData['skill_in']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">E<br /> '.(($pasCandidateData['skill_e'])? $pasCandidateData['skill_e']: '-').'</span>');
        $sHTML.= $this->coDisplay->getBloc('', '<span style="">EX<br /> '.(($pasCandidateData['skill_ex'])? $pasCandidateData['skill_ex']: '-').'</span>');
      $sHTML.= $this->coDisplay->getBlocEnd();

    return $sHTML;
  }

  private function _getShortenText($psString, $pnLength = 20)
  {
    if(!assert('is_key($pnLength) && $pnLength > 3'))
      return $psString;

    if(strlen($psString) <= $pnLength)
      return $psString;

    $oPage = CDependency::getCpPage();

    $sJavascript = ' setTimeout(\'setViewTooltip(); \', 750); ';
    $oPage->addCustomJs($sJavascript);

    $sLink = substr($psString, 0, ($pnLength-3)).' ';
    $sLink.= '<a href="javascript:;" onclick="$(\'#myTooltip\').tooltip(\'open\');" title="'.str_replace('"', '\'', $psString).'" class="openTooltip">
      <span class="candi_text_shorten">&nbsp;&nbsp;&nbsp;&nbsp;</span></a>';

    return $sLink;
  }


}