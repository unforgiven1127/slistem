<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CCompany_sl3 extends CTemplate
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


  public function getDisplay($pasData, $panPositionStatus = array(), $pnApplicant = 0)
  {
    /*$nCount = 0;
    $oCandidate = CDependency::getComponentByName('sl_candidate');

    $asStatus = $oCandidate->getVars()->getCandidateStatusList();
    $asGrade = $oCandidate->getVars()->getCandidateGradeList();

    $asLocation = $oCandidate->getVars()->getLocationList();
    $asNationality = $oCandidate->getVars()->getNationalityList();
    $asLanguage = $oCandidate->getVars()->getlanguageList();*/


    $pasData['sl_companypk'] = (int)$pasData['sl_companypk'];


    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile('/component/sl_candidate/resources/css/slistem3.css');
    //$asItem = array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_COMP, 'cp_pk' => $pasData['sl_companypk']);


    //start first section
    $sHTML = $this->coDisplay->getBlocStart('', array('class' => 'candiTopSection'));

      $sHTML.= $this->coDisplay->getBloc('', 'Company data &nbsp;&nbsp;&nbsp;&nbsp;[ #<span >'.$pasData['sl_companypk'].'</span> ]', array('class' => 'candi_detail_title'));

       if($pasData['is_client'])
       {
         $sClass = ' candi_client ';
         $sTag = '<div class="candi_status_icon important" style="position: absolute; top: 2px; right: 0; margin: 0;">client</div> ';
         $sTitle = 'Be careful, this is a client !';
       }
       else
       {
         $sClass = $sTag = $sTitle = '';
       }

       if(!$pasData['is_nc_ok'])
       {
         $sTag = '<div class="candi_status_icon important" style="position: absolute; top: 2px; right: 0; margin: 0;">no Name Collect</div> ';
       }

       $sJavascript = ' if(!$(this).hasClass(\'initialized\')){  initDrag(\'#candi_'.$pasData['sl_companypk'].'\');  $(this).addClass(\'initialized\') } ';
       $sValue = '<span id="candi_'.$pasData['sl_companypk'].'" class="list_item_draggable " data-title="'.$pasData['sl_companypk'].' - '.$pasData['name'].'" data-type="comp" data-ids="'.$pasData['sl_companypk'].'" >'.$pasData['name'].'</span>';

       $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'.$sClass, 'title' => $sTitle, 'style' => 'width: 490px;'));
          $sHTML.= $this->coDisplay->getBloc('', 'Name', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', '<span style="float: left;">'.$sValue.'</span>'.$sTag, array('class' => 'candi_detail_value', 'style' => 'position: relative; width: 415px;', 'onmouseover' => $sJavascript));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        if(!empty($pasData['industry']))
          $sValue = implode(', ', $pasData['industry']);
        else
          $sValue = '';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row', 'style' => 'width: width: 490px;'));
          $sHTML.= $this->coDisplay->getBloc('', 'industry', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value', 'style' => 'position: relative; width: 415px;'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'HQ', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['hq'], array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'Japan HQ', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['hq_japan'], array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'level', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', chr(64+ (int)$pasData['level']), array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        if($pasData['is_client'])
          $sValue = '<span style="color: red;">Yes</span>';
        else
          $sValue = 'No';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'client', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sValue, array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row cp-row-desc'));
          $sHTML.= $this->coDisplay->getBloc('', 'Desciption', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', mb_strimwidth($pasData['description'], 0, 120), array('class' => 'candi_detail_value', 'onmouseover' =>'tp(this);', 'title' => addslashes($pasData['description'])));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();


      $sHTML.= $this->coDisplay->getBlocEnd();




      //start second data Employees
      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candiTopSection'));

        $sHTML.= $this->coDisplay->getBloc('', 'Structure & Employees', array('class' => 'candi_detail_title'));
        $sHTML.= $this->coDisplay->getFloatHack();

        if($pasData['nb_employee'] > 0)
        {
          $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('data_type' => 'candi', 'company' => $pasData['sl_companypk']));
          $sLink =  $this->coDisplay->getLink($pasData['nb_employee'].' employee(s)', 'javascript:;', array('onclick' => '
            var asContainer = goTabs.create(\'candi\');
            AjaxRequest(\''.$sURL.'\', \'body\', \'\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); \');
            goTabs.select(asContainer[\'number\']);
            '));
        }
        else
        {
          $sLink = '<span class="light italic"> no employees</span>';
        }

        //dump($pasData);

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'registered', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sLink, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        //dump($pasData);
        $nDepartment = count($pasData['department']);
        if($nDepartment > 0)
          $sLink = '<a href="javascript:;" onclick="$(this).closest(\'.topCandidateSection\').find(\'#tabLink7\');">'.$nDepartment.' department(s)</a>';
        else
          $sLink = '';

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'working in ', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $sLink, array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', '# branch Jp', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['num_branch_japan'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', '# emp. jp', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['num_employee_japan'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', '# branch wld', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['num_branch_world'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', '# emp. wld', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['num_employee_world'], array('class' => 'candi_detail_value'));
        $sHTML.= $this->coDisplay->getBlocEnd();


      $sHTML.= $this->coDisplay->getBlocEnd();




      //start third data misc
      $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candiTopSection last'));
      $sHTML.= $this->coDisplay->getBloc('', 'Status', array('class' => 'candi_detail_title'));
      $sHTML.= $this->coDisplay->getFloatHack();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'created', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['date_created'], array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'created by', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $oLogin->getUserLink((int)$pasData['created_by']), array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row'));
          $sHTML.= $this->coDisplay->getBloc('', 'last update', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $pasData['date_updated'], array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();

        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row right'));
          $sHTML.= $this->coDisplay->getBloc('', 'owner', array('class' => 'candi_detail_label'));
          $sHTML.= $this->coDisplay->getBloc('', $oLogin->getUserLink((int)$pasData['ownerfk']), array('class' => 'candi_detail_value'));
          $sHTML.= $this->coDisplay->getFloatHack();
        $sHTML.= $this->coDisplay->getBlocEnd();


        $sHTML.= $this->coDisplay->getBlocStart('', array('class' => 'candi_detail_row last'));
          $sHTML.= $this->coDisplay->getBloc('', 'Status', array('class' => 'candi_detail_label', 'style' => 'height: 30px;'));
          $sHTML.= $this->coDisplay->getBloc('', $this->_getStatusBar($pasData, $panPositionStatus, $pnApplicant), array('class' => 'candi_detail_value', 'style' => 'width: 425px;  height: 30px;'));
        $sHTML.= $this->coDisplay->getBlocEnd();


      $sHTML.= $this->coDisplay->getBlocEnd();


    return $sHTML;
  }



  private function _getStatusBar($pasCandidateData, $panPositionStatus = array(), $pnApplicant)
  {

    $sHTML = $this->coDisplay->getBlocStart('', array('class' => 'candi_status_bar'));

     if($pnApplicant)
       $sHTML.= '<div class="candi_status_icon in_play" onclick="$(\'#tabLink10\').click();" title="3 candidates in play"><a href="javascript:;">'.$pnApplicant.' employee(s) in play</a></div>';

     if(!empty($panPositionStatus))
     {
       if(!empty($panPositionStatus['critical']))
       {
         $sHTML.= '<div class="candi_status_icon important" onclick="$(\'#tabLink9\').click();" title="'.$panPositionStatus['critical'].' position(s) to fill"><a href="javascript:;">'.$panPositionStatus['critical'].' old position(s)</a></div>';

         $nPosition = ($panPositionStatus['open'] - $panPositionStatus['critical']);
         if($nPosition > 0)
           $sHTML.= '<div class="candi_status_icon " onclick="$(\'#tabLink9\').click();" title="'.$nPosition.' position(s) to fill"><a href="javascript:;">'.$nPosition.' recently open position(s)</a></div>';
       }
       else
       {
        if(!empty($panPositionStatus['open']))
        {
          $sHTML.= '<div class="candi_status_icon meeting_set" onclick="$(\'#tabLink9\').click();" title="'.$panPositionStatus['open'].' position(s) "><a href="javascript:;">'.$panPositionStatus['open'].' closed position(s)</a></div>';
        }
        elseif(!empty($panPositionStatus['close']))
          $sHTML.= '<div class="candi_status_icon meeting_set" onclick="$(\'#tabLink9\').click();" title="'.$panPositionStatus['close'].' position(s) "><a href="javascript:;">'.$panPositionStatus['close'].' closed position(s)</a></div>';
       }
     }

    $sHTML.= $this->coDisplay->getBlocEnd();
    return $sHTML;
  }
}