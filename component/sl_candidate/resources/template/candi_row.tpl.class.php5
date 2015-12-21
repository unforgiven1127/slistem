<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CCandi_row extends CTemplate
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

  public function getDisplay($pasData, $pasField, $pasColumnParam = array(), $pasHeader = array())
  {

    $oPage = CDependency::getCpPage();
    $oDisplay = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();

    if(empty($pasData['_is_admin']) && $pasData['_sys_redirect'] > 0)
      $nCandidatePk = $pasData['_sys_redirect'];
    else
      $nCandidatePk = $pasData['sl_candidatepk'];

    $sViewURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$pasData['PK']);
    $sViewJS = 'view_candi(\''.$sViewURL.'\');';

    $sHTML = '';
    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListRow tplCandiRow'));


    //get the uniq column id from the column param for js sort features
    //inherit the column style/class
    set_array($pasColumnParam[0]['tag'], '');
    $asOption = array('class' => $pasColumnParam[0]['tag']);
    $sHTML.= $oDisplay->getBloc('', '<input name="listBox[]" value="'.$nCandidatePk.'" id="listBox_'.$nCandidatePk.'" class="listBox" type="checkbox" onchange="listBoxClicked(this);" />', $asOption);


    set_array($pasColumnParam[1]['tag'], '');
    $asOption = array('class' => $pasColumnParam[1]['tag'].' tplCandiRow_small');
    $sHTML.= $oDisplay->getBloc('', '<label class="list_item_draggable " for="listBox_'.$nCandidatePk.'" data-ids="'.$nCandidatePk.'" data-type="candi" data-title="'.$pasData['sl_candidatepk'].' - '.$pasData['lastname'].' '.$pasData['firstname'].'">'.$pasData['sl_candidatepk'].'</label>', $asOption);


    set_array($pasColumnParam[2]['tag'], '');
    $asOption = array('class' => $pasColumnParam[2]['tag'].' tplCandiRow_continuous clickable', 'onclick' => $sViewJS);
    if($pasData['cp_client'] || $pasData['is_client'])
    {
      $asOption['class'].= ' tplCandi_client';
      $asOption['title'] = 'Work for a client company';
    }
    $sHTML.= $oDisplay->getBloc('', '', $asOption);


    set_array($pasColumnParam[3]['tag'], '');
    $asOption = array('class' => $pasColumnParam[3]['tag'].' tplCandiRow_continuous clickable');

    //priority to in_ply: dynamic status, he's in play now !!
    $sValue = '';

    if(!empty($pasData['_pos_status']))
    {
      if($pasData['_pos_status'] < 101)
      {
        //$asOption['class'].= ' tplCandi_status_active tplCandi_status';
        $asOption['class'].= ' tplCandi_status';
        $asOption['title'] = 'Candidate active: pitched, CCM, offer ';
        $nValue = 4;

        switch($pasData['_pos_status'])
        {
          case 1: $sValue = ' ptchd'; $asOption['title'] = 'Pitched'; break;
          case 2: $sValue = ' ressnt'; $asOption['title'] = 'Resume sent'; $nValue = 5; break;

          case ($pasData['_pos_status'] >= 50 && $pasData['_pos_status'] < 100):
            $nWeighted = ((int)$pasData['_pos_status']-50);
            $asOption['class'].= ' tplCandi_status_50';
            $sValue = ' CCM '.$nWeighted; $asOption['title'] = $sValue; $nValue = $nWeighted+5; break;

        case 100:
            $sValue = ' offer';
            $asOption['title'] = 'Offer';
            $asOption['class'].= ' tplCandi_status_100';
            $nValue = 20;
            break;
        }
      }
      elseif($pasData['_pos_status'] == 101)
      {
        $asOption['class'].= ' tplCandi_status_placed';
        //$sValue = ' placed';
        $asOption['title'] = 'Candidate has been placed';
        $nValue = 1;
      }
      elseif($pasData['_pos_status'] == 151)
      {
        $asOption['class'].= ' tplCandi_status tplCandi_status_151';
        $asOption['title'] = 'Last action has expired';
        $sValue = ' expire';
        $nValue = 3;
      }
      else
      {
        $asOption['class'].= ' tplCandi_status tplCandi_status_inactive';
        $sValue = ' inactive';
        $asOption['title'] = 'Candidate inactive: expired, stalled, fallen';
        $nValue = 2;
      }
    }
    else
      $nValue = 0;

    /* Debug status sort
     * set_array($pasData['sort_status'], 0);
    $sValue.= $pasData['sort_status'];*/

    /*In play is now (again) automatic, so we don't really need the icon anymore
    if($pasData['_in_play'] > 0)
    {
      $sValue = strtoupper(substr(trim($sValue), 0, 3));
      $asOption['class'].= ' tplCandi_inplay';
      $sValue.= '<span class="tplCandi_status_play" title="In play">&nbsp;</span>';
      $nValue+= 5;
    }*/

    $asOption['onclick'] = 'view_candi(\''.$sViewURL.'\', \'#tabLink8\');';
    $asOption['sort_value'] = $nValue;
    $sHTML.= $oDisplay->getBloc('', $sValue, $asOption);


    set_array($pasColumnParam[4]['tag'], '');
    $asOption = array('class' => $pasColumnParam[4]['tag'].' tplCandiRow_continuous clickable');
    $asOption['sort_value'] = (int)$pasData['grade'];
    $asOption['onclick'] = 'view_candi(\''.$sViewURL.'\');';
    switch($pasData['grade'])
    {
      case 1:
        $asOption['class'].= ' tplCandi_grade_met';
        $asOption['title'] = 'Met grade candidate';
        break;

      case 2:
        $asOption['class'].= ' tplCandi_grade_low';
        $asOption['title'] = 'Low grade candidate';
        break;

      case 3:
        $asOption['class'].= ' tplCandi_grade_high';
        $asOption['title'] = 'High grade candidate';
        break;

      case 4:
        $asOption['class'].= ' tplCandi_grade_top';
        $asOption['title'] = 'Top shelf candidate';
        break;
    }
    $sHTML.= $oDisplay->getBloc('', '', $asOption);

    set_array($pasColumnParam[5]['tag'], '');
    $asOption = array('class' => $pasColumnParam[5]['tag']);
    $asOption['sort_value'] = (int)$pasData['_has_doc'];

    if($pasData['_has_doc'])
    {
      $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_DOC, (int)$pasData['sl_candidatepk']);
      $asOption['class'].= ' tplCandi_resume';
      $asOption['title'] = 'Resume';
      $asOption['onclick'] = 'window.open(\''.$sURL.'\', \'_view_res\'); ';
    }

    $sHTML.= $oDisplay->getBloc('', '&nbsp;', $asOption);


    /*if(empty($pasData['_is_admin']) && $pasData['_sys_redirect'] > 0)
    {
      set_array($pasColumnParam[6]['tag'], '');
      $asOption = array('class' => $pasColumnParam[6]['tag'].' tplCandiRow_continuous tpl_link_cell');

      if((int)$pasData['sex'] == 1)
        $asOption['class'].= ' tplCandi_man';
      else
        $asOption['class'].= ' tplCandi_woman';

      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$pasData['_sys_redirect']);
      $asOption['onclick'] = 'view_candi(\''.$sURL.'\');';

      $sLink = $oDisplay->getLink($pasData['lastname'], 'javascript:;');
      $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);

      set_array($pasColumnParam[7]['tag'], '');
      $asOption = array('class' => $pasColumnParam[7]['tag'].' tpl_link_cell');
      $asOption['onclick'] = 'view_candi(\''.$sURL.'\');';


      $sLink = $oDisplay->getLink($pasData['firstname'], 'javascript:;');
      $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);

      $asOption = array('class' => 'merged');
      $sHTML.= $oDisplay->getBloc('', 'Candidate [#'.$pasData['sl_candidatepk'].'] merged to #'.$pasData['_sys_redirect'], $asOption);
    }
    else*/
    {
      set_array($pasColumnParam[6]['tag'], '');
      $asOption = array('class' => $pasColumnParam[6]['tag'].' tplCandiRow_continuous tpl_link_cell');

      if($pasData['_sys_status'] > 0)
        $asOption['class'].= ' deleted';

      if((int)$pasData['sex'] == 1)
        $asOption['class'].= ' tplCandi_man';
      else
        $asOption['class'].= ' tplCandi_woman';

      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$pasData['sl_candidatepk']);
      $asOption['onclick'] = 'view_candi(\''.$sURL.'\');';

      $sLink = $oDisplay->getLink($pasData['lastname'], 'javascript:;');
      $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


      set_array($pasColumnParam[7]['tag'], '');
      $asOption = array('class' => $pasColumnParam[7]['tag'].' tpl_link_cell');
      $asOption['onclick'] = 'view_candi(\''.$sURL.'\');';

      if($pasData['_sys_status'] > 0)
        $asOption['class'].= ' deleted';

      $sLink = $oDisplay->getLink($pasData['firstname'].'&nbsp;', 'javascript:;');
      $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


      set_array($pasColumnParam[8]['tag'], '');
      $asOption = array('class' => $pasColumnParam[8]['tag'].' tpl_link_cell');
      if(!empty($pasData['company_name']))
      {
        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$pasData['sl_companypk']);
        $asOption['onclick'] = 'view_comp(\''.$sURL.'\');';
        $sLink = $oDisplay->getLink($pasData['company_name'], 'javascript:;');
      }
      else
        $sLink = '';

      $sHTML.= $oDisplay->getBloc('', $sLink, $asOption);


      // -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-
      // -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-  -=-
      // display field if selected in user settings

      $nColNumber = 9;

      if(isset($pasHeader['position_play_company']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag']);
        $sHTML.= $oDisplay->getBloc('', $pasData['position_play_company'], $asOption);

        $nColNumber++;
      }

      if(isset($pasData['activity']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag'].'');

        if($pasData['_in_play'] > 1)
        {
          $sValue = $pasData['_in_play'].' active positions';
          $asOption['title'] = $pasData['activity'];
          $asOption['class'].= ' clickable';
          $asOption['onclick'] = ' tp(this);';
        }
        elseif($pasData['_in_play'] < 0)
        {
          $sValue = $pasData['_in_play'].' inactive positions';
          $asOption['title'] = $pasData['activity'];
          $asOption['class'].= ' clickable';
          $asOption['onclick'] = ' tp(this); ';
        }
        else
          $sValue = $pasData['activity'];

        $sHTML.= $oDisplay->getBloc('', $sValue, $asOption);
        $nColNumber++;
      }

      if(isset($pasHeader['title']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag']);
        $sHTML.= $oDisplay->getBloc('', $pasData['title'], $asOption);

        $nColNumber++;
      }

      if(isset($pasHeader['department']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag']);
        $sHTML.= $oDisplay->getBloc('', $pasData['department'], $asOption);

        $nColNumber++;
      }

      if(isset($pasHeader['lastNote']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag']);
        $asOption['sort_value'] = (int)$pasData['lastNote'];
        if(!empty($pasData['note_date']))
        {
          $pasData['note_content'] = htmlentities($pasData['note_content']);
          $pasData['note_content'] = str_replace(array('"', '\''), '&quot;', $pasData['note_content']);

          $asOption['class'].= ' tplCandi_note';
          $asOption['title'] = '<div class=\'list_note_title\'>Last entry on the <span>'.$pasData['note_date'].'</span></div>'. $pasData['note_content'];
          $asOption['onmouseover'] = ' $(this).tooltip({content: function(){ return $(this).attr(\'title\'); }}).mouseenter(); ';
        }

        $sHTML.= $oDisplay->getBloc('', '', $asOption);
        $nColNumber++;
      }

      if(isset($pasHeader['date_birth']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag'].' alignCenter');
        $sHTML.= $oDisplay->getBloc('', $pasData['age'], $asOption);

        $nColNumber++;
      }

      //salary
      if(isset($pasHeader['salary']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag'].' list_salary_cell');
        $asOption['sort_value'] = (int)$pasData['full_salary'];
        if(empty($pasData['full_salary']))
          $sHTML.= $oDisplay->getBloc('', '', $asOption);
        else
        {
          $full_salary = (int)$pasData['full_salary']/1000000;
          $salary_unit = 'M';
          if ($full_salary < 1)
          {
            $full_salary = (int)$pasData['full_salary']/1000;
            $salary_unit = 'K';
          }

          $sHTML.= $oDisplay->getBloc('', round($full_salary, 1).$salary_unit, $asOption);
        }

        $nColNumber++;
      }

      //creator/person in charge
      if(isset($pasHeader['manager']))
      {
        set_array($pasColumnParam[$nColNumber]['tag'], '');
        $asOption = array('class' => $pasColumnParam[$nColNumber]['tag']);
        if(empty($pasData['created_by']))
          $sHTML.= $oDisplay->getBloc('', '-', $asOption);
        else
          $sHTML.= $oDisplay->getBloc('', $oLogin->getUserLink((int)$pasData['created_by'] , true), $asOption);

        $nColNumber++;
      }


      $asItem = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pasData['sl_candidatepk']);
      $sHTML.= $oDisplay->getBlocStart('', array('class' => 'rowActionContainer'));

      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasData['sl_candidatepk'], $asItem);
      $sHTML.= '<a class="candi_row_edit" title="Edit candidate profile" onclick="edit_candi(\''.$sURL.'\');" title="Edit candidate"  href="javascript:;">&nbsp;</a>';

      $sURL = $oPage->getAjaxUrl('sl_event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);
      $sHTML.= '<a class="candi_row_note" title="Add a nore or character note" onclick="add_candi_note(\''.$sURL.'\');" href="javascript:;">&nbsp;</a>';

      if(!empty($pasData['folderfk']))
      {
        $folder_obj = CDependency::getComponentByName('sl_folder');
        $folder_db = $folder_obj->getFolder((int)$pasData['folderfk']);

        $read = $folder_db->readFirst();

        $folder_owner = $folder_db->getFieldValue('ownerloginfk');
        $current_user = $oLogin->getUserPk();

        if ($folder_owner == $current_user || $oLogin->isAdmin())
        {
          $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_DELETE, CONST_FOLDER_TYPE_ITEM, 0,
            array('folderpk' => $pasData['folderfk'], 'item_type' => 'candi', 'ids' => $pasData['sl_candidatepk']));
          $sHTML.= '<a class="candi_row_folder" title="Remove candidate from the folder" onclick="if(window.confirm(\'Remove from the folder ?\')){ AjaxRequest(\''.$sURL.'\'); $(this).closest(\'li.tplListRowContainer\').remove(); }" href="javascript:;">&nbsp;</a>';
        }
      }

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
    }

    $sHTML.= $oDisplay->getBlocEnd();
    return $sHTML;
  }
}