<?php

require_once('component/sl_event/sl_event.class.php5');

class CSl_eventEx extends CSl_event
{
  private $casCpParam = array();

  public function __construct()
  {
    parent::__construct();

    $sCandiUid = CDependency::getComponentUidByName('sl_candidate');
    $this->casCpParam = array(CONST_CP_UID => $sCandiUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => '', CONST_CP_PK => 0);

    return true;
  }

  //****************************************************************************
  //****************************************************************************
  // Interfaces and component settings
  //****************************************************************************
  //****************************************************************************


  public function getHtml()
  {
    return parent::getHtml();
  }

  public function getAjax()
  {
    $this->_processUrl();
    $oPage = CDependency::getCpPage();

    switch($this->csType)
    {
      case CONST_EVENT_TYPE_EVENT:

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return json_encode($oPage->getAjaxExtraContent(array('data' => self::_getNoteForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:

            $asResult = $this->_saveNote($this->csAction);

            return json_encode($oPage->getAjaxExtraContent($asResult));
            break;
        }
        break;
    }

    return parent::getAjax();
  }


  //****************************************************************************
  //****************************************************************************
  //Component core
  //****************************************************************************
  //****************************************************************************


  public function getNotes($pnItemPk, $psItemType, $psNoteType = '', $pasExcludeType = array())
  {
    if(!assert('is_key($pnItemPk) && !empty($psItemType)'))
      return false;

    $asParams = $this->casCpParam;
    $asParams[CONST_CP_TYPE] = $psItemType;
    $asParams[CONST_CP_PK] = $pnItemPk;

    return $this->_getModel()->getFromCpValues($asParams, $psNoteType, '', $pasExcludeType);
  }


  public function displayNotes($pnItemPk, $psItemType, $psNoteType = '', $pasExcludeType = array(), $pbAddLink = true, $psLinkDefaultType = '')
  {
    if(!assert('is_key($pnItemPk) && !empty($psItemType)'))
      return array();

    $asNotes = $this->getNotes($pnItemPk, $psItemType, $psNoteType, $pasExcludeType);

    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $nPriotity = 0;
    $bAddLink = false;
    $sHTML = '';

    if ($psNoteType != 'cp_history' || $oLogin->isAdmin())
    {
      $sHTML.= '<div class="tab_bottom_link">';
      $asItem = array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW,
        'cp_type' => $psItemType, 'cp_pk' => $pnItemPk, 'default_type' => $psLinkDefaultType);

      if($psLinkDefaultType == 'character')
        $sLabel = 'Add a character note';
      else
        $sLabel = 'Add a note';

      $sURL = $oPage->getAjaxUrl('sl_event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);
      $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
      $sHTML.= '<a href="javascript:;" onclick="'.$sJavascript.'">'.$sLabel.'</a>';
      $sHTML.= '</div>';
    }

    if(empty($asNotes))
    {
      $sHTML.= '<div class="entry"><div class="note_content"><em>No entry found.</em></div></div>';
    }
    else
    {
      $nCurrentUser = $oLogin->getUserPk();
      $asEventType = getEventTypeList();

      $oPage->addCssFile($this->getResourcePath().'/css/sl_event.css');
      $asUsers = $oLogin->getUserList(0, false, true);
      $sPic = $oHTML->getPicture($this->getResourcePath().'/pictures/edit_16.png');

      $dNow = date('Y-m-d H:i:s');
      $s1HourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
      $dAMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
      $dTwoMonthAgo = date('Y-m-d H:i:s', strtotime('-2 month'));

      foreach($asNotes as $asNote)
      {
        if($asNote['date_display'] > $dTwoMonthAgo)
          $nPriotity = 2;
        elseif($asNote['date_display'] > $dAMonthAgo)
          $nPriotity = 1;

        //some types (custom or not) may nopt be available for users to use (ex: cp_history)
        if($asNote['custom_type'] || !isset($asEventType[$asNote['type']]['label']))
          $sType = ucfirst(str_replace('_', ' ', $asNote['type']));
        else
          $sType = $asEventType[$asNote['type']]['label'];

        //generic class used for all types of items
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'entry'));

          $sHTML.= $oHTML->getBlocStart('', array('class' => 'note_header'));
            $sHTML.= '&rarr;&nbsp;&nbsp;'.$oHTML->getSpan('', getDateDifference($asNote['date_display'], $dNow).' ago' , array('class' => 'note_date'));
            $sHTML.= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;'.$oHTML->getSpan('', $sType, array('class' => 'note_type')).'&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;';

            $sHTML.= $oHTML->getSpanStart();

            if(empty($asUsers[$asNote['created_by']]) || $asNote['created_by'] == -1)
              $sHTML.= ' by '.$oLogin->getUserLink(-1);
            else
              $sHTML.= ' by '.$oLogin->getUserLink($asUsers[$asNote['created_by']], true);

            $sHTML.= $oHTML->getSpanEnd();

            $sHTML.= $oHTML->getSpanStart('', array('class' => 'note_chronology'));
            $sHTML.= substr($asNote['date_display'], 0,-3);
            $sHTML.= $oHTML->getSpanEnd();

          $sHTML.= $oHTML->getBlocEnd();

          if($asNote['title'] == $asNote['content'])
            $asNote['title'] = '';

          if(!empty($asNote['title']) && !empty($asNote['content']))
          {
            $sHTML.= $oHTML->getBloc('', '<span class="note_innerTitle">'.$asNote['title'].'</span><br /><span class="note_innerContent">'.
                    $asNote['content'].'</span>', array('class' => 'note_content'));
          }
          else
            $sHTML.= $oHTML->getBloc('', $asNote['title'].$asNote['content'], array('class' => 'note_content'));

          if ($psNoteType != 'cp_history' || $oLogin->isAdmin())
          {
            //Should we Display the link to edit notes
            //Right to do so or creator and note has been created a bit (allow fix typos)
            $bEdit = (($asNote['created_by'] == $nCurrentUser) && ($asNote['date_create'] > $s1HourAgo));
            if($bEdit || CDependency::getComponentByName('right')->canAccess('555-004', CONST_ACTION_MANAGE, CONST_EVENT_TYPE_EVENT, 0))
            {
              $asCpParam = array(CONST_CP_UID => '555-001',
                CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => $psItemType, CONST_CP_PK => $pnItemPk);
              $sURL = $oPage->getAjaxurl($this->csUid, CONST_ACTION_EDIT, CONST_EVENT_TYPE_EVENT, (int)$asNote['eventpk'], $asCpParam);

              $sHTML.= $oHTML->getBloc('', $sPic, array('class' => 'note_edit_link', 'onclick' => '
                var oConf = goPopup.getConfig();
                oConf.width = 950;
                oConf.height = 550;
                goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');'));
            }
          }

        $sHTML.= $oHTML->getBlocEnd();
      }
    }

    return array('content' => $sHTML, 'nb_result' => count($asNotes), 'priority' => $nPriotity);
  }


  /**
   * Display the event form
   * @param integer  $pnPk
   * @return string HTML
 */
  private function _getNoteForm($pnPk)
  {
    if(!assert('is_integer($pnPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();

    //Fetch the data from the calling component
    $sCp_Uid = getValue(CONST_CP_UID);
    if(empty($sCp_Uid))
      return $oHTML->getBlocMessage(__LINE__.' - Oops, missing some informations to create a note.');


    $sCp_Action = getValue(CONST_CP_ACTION);
    $sCp_Type = getValue(CONST_CP_TYPE);
    $nCp_Pk = (int)getValue(CONST_CP_PK, 0);

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(self::getResourcePath().'css/sl_event.css');
    $oDB = CDependency::getComponentByName('database');

    //If editing the contact
    if(!empty($pnPk))
    {
      $sQuery = 'SELECT * FROM event as ev ';
      $sQuery.= 'INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND el.eventfk = '.$pnPk.') ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return __LINE__.' - This note is linked to an item that doesn\'t exist.';

      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_EVENT_TYPE_EVENT, $pnPk);
    }
    else
    {
      $oDbResult = new CDbResult();
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_EVENT_TYPE_EVENT);
    }


    /* @var $oForm CFormEx */
    $oForm = $oHTML->initForm('evtAddForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'class' => 'fullPageForm','submitLabel'=>'Save'));
    $oForm->setFormDisplayParams(array('noCancelButton' => 1));

    $oForm->addField('input', CONST_CP_UID, array('type' => 'hidden', 'value' => $sCp_Uid));
    $oForm->addField('input', CONST_CP_ACTION, array('type' => 'hidden', 'value' => $sCp_Action));
    $oForm->addField('input', CONST_CP_TYPE, array('type' => 'hidden', 'value' => $sCp_Type));
    $oForm->addField('input', CONST_CP_PK, array('type' => 'hidden', 'value' => $nCp_Pk));
    $oForm->addField('input', 'no_candi_refresh', array('type' => 'hidden', 'value' => getValue('no_candi_refresh', 0)));
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add a note'));


    if(!empty($pnPk) && CDependency::getCpLogin()->isAdmin())
    {
      $oForm->addField('checkbox', 'delete_note', array('label'=>'Delete note ?', 'value' => $pnPk, 'textbefore' => 1));
    }


    $asEvent = getEventTypeList(false, $sCp_Type, CDependency::getCpLogin()->isAdmin());
    $sEventType = $oDbResult->getFieldValue('type');

    if(!empty($sEventType) && !isset($asEvent[$sEventType]))
    {
      //a type that is not available for user selection
      //$oForm->addField('misc', '', array('type' => 'text', 'text' => $sEventType.' (can not be changed)', 'style' => 'position: absolute; right: 0;'));
      $oForm->addField('input', '', array('label'=>'Note type', 'value' => $sEventType.'     (can\'t be changed)', 'readonly' => 'readonly', 'style' => 'width: 250px; background-color: #efefef; font-style: italic;'));
      $oForm->addField('hidden', 'event_type', array('value' => $sEventType));
      $oForm->addField('hidden', 'custom_type', array('value' => 1));
    }
    else
    {
      $oForm->addField('select', 'event_type', array('label'=>'Note type', 'onchange' => 'if($(this).val() == \'character\'){ $(this).closest(\'.ui-dialog\').find(\'.note_tip_container\').show(); } else { $(this).closest(\'.ui-dialog\').find(\'.note_tip_container\').hide(); } '));
      $oForm->setFieldControl('event_type', array('jsFieldNotEmpty' => ''));

      if(empty($sEventType))
        $sEventType = getValue('default_type', 'note');

      foreach($asEvent as $asEvents)
      {
        if($asEvents['value'] == $sEventType)
          $oForm->addOption('event_type', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group'], 'selected'=>'selected'));
        else
          $oForm->addOption('event_type', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group']));
      }
    }


    $sDate = $oDbResult->getFieldValue('date_display');
    if(empty($sDate))
      $sDate = date('Y-m-d H:i');
    else
      $sDate = date('Y-m-d H:i', strtotime($sDate));

    //$oForm->addField('input', 'date_event', array('type' => 'datetime', 'label'=>'Date', 'value' => $sDate));
   $oForm->addField('input', 'date_event', array('type' => 'hidden', 'label'=>'Date', 'value' => $sDate));


    $oForm->addField('input', 'title', array('label'=>'Note title', 'value' => $oDbResult->getFieldValue('title')));
    $oForm->setFieldControl('title', array('jsFieldMinSize' => '2','jsFieldMaxSize' => 255));

    $oForm->addField('textarea', 'content', array('label'=>'Description', 'value' => $oDbResult->getFieldValue('content'), 'isTinymce' => 1));
    $oForm->setFieldControl('content', array('jsFieldMinSize' => '2','jsFieldMaxSize' => 9000));


    $sHTML = '';

    if($sEventType == 'character')
    {
      $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING, $nCp_Pk);
      $sId = uniqid();

      $sHTML.= '<b style="padding-left: 60px;">Tips:</b><br />
        <div id="'.$sId.'" data-id="'.$sId.'" class="note_tip_container" >
        <ul class="note_tip_list">
        <li> >>&nbsp;&nbsp;<span style="color: red; font-size: inherit;">Meeting assessement</span>: use the dedicated meeting feature and set your meeting "done". [
        <a href="javascript:;" style="font-size: inherit; font-weight: bold;" onclick=" goPopup.removeLastByType(\'layer\');
        var oConf = goPopup.getConfig();
        oConf.width = 950; oConf.height = 550;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">here</a> ]</li>
        <li>What does the candidate do -what is his profession? 	</li>
        <li>What is his scope of experience in this profession? Is he specific or broad?</li>
        <li>Is the candidate intelligent and able to solve problems or not?</li>
        <li>Can the candidate manage problems or not?</li>
        <li>What does the candidate hope to accomplish before he moves to the next position?</li>
        <li>What is his vision for his future? What is the next step for him if and when he moves?</li>
        <li>How does he look? Is he confident and articulate? Does he have a powerful presence?</li>
        <li>Can he manage a team and is he ambitious?</li>
        <li>Is he met (not placeable), notable (placeable) or top shelf (Absolutely placeable)?</li>
        <li>Focus on your candidate\'s career process and placability when you are writing comments. </li>

        <ul>
        <div class="floatHack" />
        </div>
        <script>
        setTimeout(" autoscroll_'.$sId.'(0);", 7500);
        function autoscroll_'.$sId.'(pnIteration)
        {
          if(pnIteration > 10)
            return false;

          var nScroll = $(\'#'.$sId.'\').scrollTop();
          if(nScroll < 220)
          {
            $(\'#'.$sId.'\').animate({scrollTop: (nScroll+56)}, 500);
            //alert($(\'#'.$sId.'\').scrollTop());
          }
          else
            $(\'#'.$sId.'\').animate({scrollTop: 0}, 500);

          setTimeout(" autoscroll_'.$sId.'("+(pnIteration+1)+");", 4500);
        }
        </script>
        ';
    }

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }


  /**
   * Implement for the candidate form... add notes through an array of data except of using post
   * @param integer $pnItemPk
   * @param string $psType
   * @param sting $psContent
   * @return array
   */
  public function addNote($pnItemPk, $psType, $psContent, $pnLoginfk = 0)
  {
    if(!assert('is_key($pnItemPk) && !empty($psType)'))
      return array('error' => 'Could not add the note.');

    if(!assert('is_integer($pnLoginfk)'))
      return array('error' => 'Could not add the note.');

    if(empty($psContent))
      return array('error' => __LINE__.' - Can not create empty notes.');

    $asEventType = getEventTypeList();

    $asEvent = array();
    $asEvent['item_uid'] = '555-001';
    $asEvent['item_action'] = CONST_ACTION_VIEW;
    $asEvent['item_type'] = CONST_CANDIDATE_TYPE_CANDI;
    $asEvent['item_pk'] = (int)$pnItemPk;

    $asEvent['date'] = date('Y-m-d H:i:s');
    $asEvent['type'] = $psType;
    $asEvent['title'] = '';
    $asEvent['content'] = $psContent;
    $asEvent['coworker'] = array();
    $asEvent['notify'] = 0;
    $asEvent['add_calendar'] = 0;

    if(isset($asEventType[$psType]))
      $asEvent['custom_type'] = 0;
    else
      $asEvent['custom_type'] = 1;

    $asEvent['reminder_date'] = '';
    $asEvent['reminder_time'] = 0;
    $asEvent['reminder_before'] = '';
    $asEvent['reminder_user'] = 0;
    $asEvent['reminder_message'] = '';

    if(!empty($pnLoginfk))
      $asEvent['loginfk'] = $pnLoginfk;

    return parent::_getEventSave(0, $asEvent);
  }


  public function getLastEvent($panItem, $psItemUid = '', $psItemAction = '', $psItemType = '', $psEventType = '', $pasExcludeType = array())
  {
    if(!assert('is_arrayOfInt($panItem)'))
      return false;

    $sQuery = '
      SELECT MAX(elin.event_linkpk), elin.*, even.*
      FROM event_link as elin
      INNER JOIN event as even ON (even.eventpk = elin.eventfk)

      WHERE  elin.cp_uid = '.$this->_getModel()->dbEscapeString($psItemUid).'
      AND elin.cp_action = '.$this->_getModel()->dbEscapeString($psItemAction).'
      AND elin.cp_type = '.$this->_getModel()->dbEscapeString($psItemType).'
      AND elin.cp_pk IN ('.implode(',', $panItem).') ';

    if(!empty($psEventType))
      $sQuery.= ' AND even.type = '.$this->_getModel()->dbEscapeString($psEventType).' ';

    if(!empty($pasExcludeType))
      $sQuery.= ' AND even.cp_type NOT IN ("'.implode('","', $pasExcludeType).'") ';


    $sQuery.= ' GROUP BY elin.cp_uid, elin.cp_action, elin.cp_pk';
    //dump($sQuery);
    return $this->_getModel()->executeQuery($sQuery);
  }

  private function _saveNote($psAction = '')
  {
    $event_type = getValue('event_type');
    $content = getValue('content');

    if((empty($event_type) && !getValue('delete_note')) || (empty($content) && !getValue('delete_note')))
      return array('error' => __LINE__.' - Can not create empty notes.');

    $oPage = CDependency::getCpPage();
    $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, getValue(CONST_CP_TYPE), (int)getValue(CONST_CP_PK));


    if(!empty($this->cnPk) && getValue('delete_note') && CDependency::getCpLogin()->isAdmin())
    {
      $asResult = parent::_getEventDelete($this->cnPk);

      $asResult['action'] = ' view_candi("'.$sURL.'", "#tabLink1"); goPopup.removeByType(\'layer\'); ';
      unset($asResult['reload']);
      return $oPage->getAjaxExtraContent($asResult);
    }

    $asResult = parent::_getEventSave($this->cnPk);
    if(isset($asResult['error']))
      return $oPage->getAjaxExtraContent($asResult);


    $sType = getValue('event_type');
    if($sType == 'cp_history')
    {
      $oMail = CDependency::getComponentByName('mail');
      $oMail->createNewEmail();
      $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
      $oMail->addRecipient(CONST_DEV_EMAIL, CONST_DEV_EMAIL);

      if($psAction == CONST_ACTION_SAVEADD)
        $oResult = $oMail->send('Slistem - note cp_history manually created', 'Please add a cp_hidden note with the company name for '.$sURL);
      else
        $oResult = $oMail->send('Slistem - note cp_history manually updated', 'Please check the cp_hidden matches the cp_history note for '.$sURL);
    }

    set_array($asResult['action'], '');


    if((bool)getValue('no_candi_refresh', 0))
      $asResult['action'].= ' goPopup.removeLastByType(\'layer\'); ';
    else
      $asResult['action'].= ' view_candi("'.$sURL.'", "#tabLink1"); goPopup.removeByType(\'layer\'); ';

    $asResult['timedUrl'] = '';
    $asResult['url'] = '';

    return $asResult;
  }

}
