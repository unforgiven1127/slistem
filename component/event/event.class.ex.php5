<?php

require_once('component/event/event.class.php5');


class CEventEx extends CEvent
{
  protected $coCalendar = null;

  public function __construct()
  {
    $this->coCalendar = CDependency::getComponentByName('zimbra');
    return true;
  }

  public function getDefaultType()
  {
    return CONST_EVENT_TYPE_EVENT;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_LIST;
  }

  //====================================================================
  //  accessors
  //====================================================================

  //====================================================================
  //  interface
  //====================================================================

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_EVENT_TYPE_EVENT:
        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:

            $oPage = CDependency::getCpPage();
            $asData = array('data' => $this->_getEventForm($this->cnPk));
            return json_encode($oPage->getAjaxExtraContent($asData));
              break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_getEventSave($this->cnPk));
              break;

          case CONST_ACTION_DELETE:
           return json_encode($this->_getEventDelete($this->cnPk));
             break;

        }
        break;

      case CONST_EVENT_TYPE_REMINDER:
        switch($this->csAction)
        {
          case CONST_ACTION_DELETE:
           return json_encode($this->_getReminderDelete($this->cnPk));
             break;
        }
        break;
    }
  }

  public function getHtml()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_EVENT_TYPE_EVENT:
        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return $this->getEventList($this->cnPk, getValue(CONST_CP_UID), getValue(CONST_CP_ACTION), getValue(CONST_CP_TYPE), getValue(CONST_CP_PK, 0));
             break;

          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return $this->_getEventForm($this->cnPk);
             break;
        }
        break;
    }
  }


  public function getCronJob()
  {
    echo 'Event cron  <br />';

    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // specific to Addressbook / BCM
    //send the reminders linked to events
    $oAB = CDependency::getComponentByName('addressbook');
    if($oAB)
      $this->_sendReminders();


    //fetch email from a specific mail box to create notes from it
    $this->_fetchMailEvents();

    return '';
  }



  //====================================================================
  //  Component core
  //====================================================================


  /**
   * Search for activity using keywords
   * @param string $psSearchWord
   * @return array
   */

  public function search($psSearchWord)
  {
    if(!assert('!empty($psSearchWord)'))
      return array();

    if(strlen($psSearchWord) < 3)
      return array('Search query is too short.');

    $asSearchWord = explode(' ', trim($psSearchWord));
    $asWhere = array();
    foreach($asSearchWord as $sSearchWord)
    {
      if(strlen($sSearchWord) >= 2)
      {
        $asWhere[] = '(title LIKE "%$psFilter%" OR content LIKE "%$psFilter%" ) ';
      }
    }

    if(empty($asWhere))
      return array('nb' => 0, 'data' => array());

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT COUNT(*) as nCount FROM event WHERE '.implode(' AND ', $asWhere).' LIMIT 100 ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    return array('nb' => $oDbResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT), 'data' => array());
  }

  //Not Implemented function
  private function _getSearchEventList($psFilter)
  {
    return array('nb' => 0, 'data' => array());
  }



  /**
   * Get the SQL realted to activities for connection of  addressbook component
   * @return array of records
   */

  public function getActivitySql()
  {
    $asResult = array();

    $asResult['select'] = 'event.title as title,event.content as content,event.date_display as date_display';
    $asResult['join'] = 'LEFT JOIN event_link AS evel ON (evel.cp_pk = ct.addressbook_contactpk and evel.cp_type = "ct") LEFT JOIN event AS event ON (event.eventpk = evel.eventfk) ';

    return $asResult;
  }


  public function getCompanyActivitySql()
  {

    $asResult['select'] = 'evel2.cp_pk as itempk,evel2.cp_type as itemtype,event.title as title,event.content as content,event.date_display as date_display,event2.title as title2,event2.content as content2,event2.date_display as date_display2';
    $asResult['join'] = ' LEFT JOIN event_link AS evel ON (evel.cp_pk = cp.addressbook_companypk AND evel.cp_type = "cp") LEFT JOIN event AS event ON (event.eventpk = evel.eventfk)';
    $asResult['join'].= ' LEFT JOIN event_link AS evel2 ON (evel2.cp_pk = prf.contactfk AND evel2.cp_type = "ct")  LEFT JOIN event AS event2 ON (event2.eventpk = evel2.eventfk) ';

    return $asResult;

  }

  /**
   * Delete the event
   * @param integer $pnPK
   * @return array with notice
   */

  protected function _getEventDelete($pnPK)
  {
    if(!assert('is_key($pnPK)'))
      return array('error' => __LINE__.' - No activity identifier.');

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * FROM `event` WHERE eventpk = '.$pnPK.' ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => __LINE__.' - No event to delete.');

    $sQuery = 'DELETE FROM event WHERE eventpk = '.$pnPK.' ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);

    $sQuery = 'DELETE FROM event_link WHERE eventfk = '.$pnPK.' ';
    $oResult = $oDB->ExecuteQuery($sQuery);

    if(!$oResult)
      return array('error' => __LINE__.' - Couldn\'t delete the activity');

    return array('notice' => 'Activity deleted successfully.','reload' =>1);
  }


  /**
  * Function to list the events
  * @param integer eventpk $pnPk
  * @param string component $psUid
  * @param string action $psAction
  * @param string type $psType
  * @param integer value $pnKey
  * @return string HTML
  */
  public function getEventList($paValues, $pnPk = 0)
  {
    if(!assert('is_cpValues($paValues) && is_integer($pnPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/event.css');

    $sExtraSelect = $sExtraSql = '';
    if($this->coCalendar)
    {
      $sExtraSelect = ' , calLink.cp_params as calUserFk ';
      $sExtraSql = ' LEFT JOIN event_link as calLink ON (calLink.eventfk = ev.eventpk AND calLink.cp_type = "'.CONST_ZCAL_EVENT.'") ';
    }

    if(is_key($pnPk))
    {
      $oEventList = $this->_getModel()->getEventsFromPk($pnPk);
    }
    elseif($paValues[CONST_CP_TYPE] == CONST_AB_TYPE_CONTACT)
    {
      $oEventList = $this->_getModel()->getEventsFromContact($paValues, $sExtraSelect, $sExtraSql);
    }
    elseif($paValues[CONST_CP_TYPE] == CONST_AB_TYPE_COMPANY)
    {
      $oEventList = $this->_getModel()->getEventsFromCompany($paValues, $sExtraSelect, $sExtraSql);
    }

    // Initializing tab content
    $asTabs = array();

    $bRead = $oEventList->readFirst();
    if(!$bRead)
      return '';

    $sCurrentType = $oEventList->getFieldValue('type');
    $sCurrentCustomType = '';
    $bDisplayedCustomType = (bool)$oEventList->getFieldValue('custom_type');
    $sContent = '';
    $nCount = $nType = 0;
    $nCountLines = 1;
    $asAllTabContent = array();
    $nCountLines = 1;
    $sAllTabContent = '';

    while($bRead)
    {
      $sEventType = $oEventList->getFieldValue('type');
      $sLine = $this->_getEventRow($oEventList);
      if($sEventType != $sCurrentType)
      {
        //generate type entry after generating the content
        //(using stored sCurrentType  and $sCurrentCustomType from the previous type)

        //separate standard event types and custom/system ones
        if(!$bDisplayedCustomType && $sCurrentCustomType == 1)
        {
          $asOption = array('class' => 'eventSeparator eventCustomType');
          $bDisplayedCustomType = true;
        }
        elseif($sCurrentCustomType == 1)
          $asOption = array('class' => 'eventCustomType');
        else
          $asOption = array();

        $asTabs[$nCountLines] = array ('label' => $sCurrentType, 'title' => $sCurrentType.' ('.$nCount.')', 'content' => $sContent, 'options' => $asOption);

        $sContent = '';
        $nCount = 0;
        $nCountLines++;
        $sCurrentType = $sEventType;
        $sCurrentCustomType = $oEventList->getFieldValue('custom_type');
        $nType++;
      }

      $nCount++;
      $sContent.= $sLine;

      //will sort the array by date, but we don't want 2 events created at the same time to deseapear so uniqId
      $asAllTabContent[$oEventList->getFieldValue('date_display'). uniqid()] = $sLine;

      $bRead = $oEventList->readNext();
    }

    //once again for the last row
    if(!$bDisplayedCustomType && $sCurrentCustomType == 1)
    {
      $asOption = array('class' => 'eventSeparator eventCustomType');
    }
    elseif($sCurrentCustomType == 1)
    {
      $asOption = array('class' => 'eventCustomType');
    }
    else
      $asOption = array();

    $asTabs[$nCountLines] = array ('label' => $sCurrentType, 'title' => $sCurrentType.' ('.$nCount.')', 'content' => $sContent, 'options' => $asOption);

    //Should we display the "aal" tab ? if yes, sort the entries
    if($nType > 0)
    {
      krsort($asAllTabContent);
      array_unshift($asTabs, array ('label' => 'all', 'title' => 'All', 'content' => implode('', $asAllTabContent)));
      $sDefaultTab = 'all';
    }
    else
      $sDefaultTab = $sCurrentType;

    return $oHTML->getTabs('eventlist_tabs', $asTabs, $sDefaultTab, 'vertical divlist', false);
  }

  public function getCount($asValues)
  {
    return $this->_getModel()->getCountFromCpValues($asValues);
  }


  /**
   * Get the Event Rows
   * @param array $pasEventData
   * @return string HTML
   */

  private function _getEventRow($poEventData)
  {
    if(!assert('is_object($poEventData) && !empty($poEventData)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();

    $oAddressBook = CDependency::getComponentByName('addressbook');
    $nUserPk = $oLogin->getUserPk();

    $sHTML = $oHTML->getBlocStart('', array('class' => 'columns divlist-item'));

      //Date and Creator
      $sHTML.= $oHTML->getBlocStart('', array('style' => 'width:15%; padding:10px;'));
      $sHTML.= $oHTML->getText($poEventData->getFieldValue('date_display'));
      $sHTML.= $oHTML->getCR();
      $sHTML.= $oHTML->getText('by '.$oLogin->getUserNameFromPk((int)$poEventData->getFieldValue('created_by')));
      $sHTML.= $oHTML->getCR();
      if($poEventData->getFieldValue(CONST_CP_TYPE)==CONST_AB_TYPE_CONTACT)
      {
        $nContactPk = (int)$poEventData->getFieldValue('contactfk');
        if(is_key($nContactPk))
        {
          $aContact = $oAddressBook->getContactDataByPk($nContactPk);
          $sHTML.= $oHTML->getText('on '.$aContact['firstname'].' '.$aContact['lastname'].' profile');
        }
      }

      if($this->coCalendar && ($poEventData->getFieldValue('calUserFk') == $nUserPk))
      {
        $sHTML.= $oHTML->getCR().$oHTML->getSpace(3);
        $sHTML.= $oHTML->getPicture($this->coCalendar->getResourcePath().'pictures/calendar_16.png');
        $sHTML.= $oHTML->getText(' <i>in my calendar</i>');
      }
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'eventListReminder', 'style' => 'padding:10px;'));
        if($poEventData->getFieldValue('reminder_recipient'))
        {
          $asRecipient = explode(',', $poEventData->getFieldValue('reminder_recipient'));
          if(in_array($oLogin->getUserPk(), $asRecipient))
            $sHTML.= $oHTML->getPicture($this->getResourcePath().'pictures/reminder_16.png', 'You have a reminder linked to this activity', '', array('class' => 'hasLegend'));
          else
            $sHTML.= $oHTML->getPicture($this->getResourcePath().'pictures/reminder_inactive_16.png', 'Other user(s) have set reminder(s) on this activity', '', array('class' => 'hasLegend'));
        }
      $sHTML.= $oHTML->getBlocEnd();

      $sShortContent = strip_tags($poEventData->getFieldValue('content'));
      $sTitle = trim($poEventData->getFieldValue('title'));
      if(!empty($sTitle))
        $sTitle.= ':<br />';

      if(strlen($sShortContent) > 175)
      {
        $sSeeMoreContent = $poEventData->getFieldValue('content');

        $sShortContent = $sTitle.substr($sShortContent,0, 172).'...';
        $sShortContent = $this->restoreLinks($sShortContent);

        $sContent = $oHTML->getTogglingText($sShortContent, $sSeeMoreContent);
      }
      else
      {
        $sShortContent = $this->restoreLinks($sShortContent);
        $sContent = $sTitle.$sShortContent;
      }

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'eventListRow'));
      $sHTML.= $sContent;
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('', array('style' => 'float:right;', 'class' => 'button-list'));
      $sHTML.= $this->_getEventRowAction($poEventData);
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getFloatHack();

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Get Action for the Events
   * @param array $pasEventData
   * @return string HTML
   */

  private function _getEventRowAction($poEventData)
  {
    if(!assert('is_object($poEventData) && !empty($poEventData)'))
      return '';

    $oRight = CDependency::getComponentByName('right');
    $bAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_DELETE,CONST_EVENT_TYPE_EVENT,0);

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();


    $sUrl = $oPage->getAjaxUrl('event', CONST_ACTION_EDIT, CONST_EVENT_TYPE_EVENT, $poEventData->getFieldValue('eventpk'), array(CONST_CP_UID => $poEventData->getFieldValue(CONST_CP_UID), CONST_CP_ACTION => $poEventData->getFieldValue(CONST_CP_ACTION), CONST_CP_TYPE => $poEventData->getFieldValue(CONST_CP_TYPE), CONST_CP_PK => $poEventData->getFieldValue(CONST_CP_PK)));
    $sAjax = 'var oConf = goPopup.getConfig();
              oConf.height = 700;
              oConf.width = 920;
              oConf.modal = true;
              goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

    $sHTML = $oHTML->getPicture($this->getResourcePath().'pictures/edit_event_16.png', 'Edit event', 'javascript:;', array('title'=>'Edit activity', 'onclick' => $sAjax));
    $sHTML.= $oHTML->getSpace(2);

    if($bAccess)
    {
      $sURL = $oPage->getAjaxUrl('event', CONST_ACTION_DELETE, CONST_EVENT_TYPE_EVENT, $poEventData->getFieldValue('eventpk'));
      $sPic = $oHTML->getPicture($this->getResourcePath().'pictures/delete_event_16.png','Delete event');
      $sHTML.= ' '.$oHTML->getLink($sPic, 'javascript:;', array('onclick' => 'goPopup.setPopupConfirm(\'Delete this activity ?\', \' AjaxRequest(\\\''.$sURL.'\\\'); \')'));
    }

    return $sHTML;
  }

  /**
  * Display the event form
  * @param integer  $pnPk
  * @return string HTML
  */
  private function _getEventAddForm($pnPk)
  {
    if(!assert('is_integer($pnPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();

    //Fetch the data from the calling component
    $sCp_Uid = getValue(CONST_CP_UID, 0);
    if(empty($sCp_Uid))
      return $oHTML->getBlocMessage(__LINE__.' - Oops, missing some informations to create an activity.');

    $sCp_Action = getValue(CONST_CP_ACTION);
    $sCp_Type = getValue(CONST_CP_TYPE);
    $nCp_Pk = (int)getValue(CONST_CP_PK, 0);

    if($sCp_Type == CONST_AB_TYPE_COMPANY)
    {
      $nCompanyPk = $nCp_Pk;
      $nContactPk = 0;
    }
    else
    {
      $nCompanyPk = 0;
      $nContactPk = $nCp_Pk;
    }

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(array($this->getResourcePath().'/css/event.css'));

    $oLogin = CDependency::getCpLogin();
    $oDB = CDependency::getComponentByName('database');
    $oAddressBook = CDependency::getComponentByName('addressbook');
    $sABookUid = $oAddressBook->getComponentUid();

    $nUser = $oLogin->getUserPk();
    $asLinkedItems = array();

    //If editing the contact
    if(!empty($pnPk))
    {
      $sQuery = 'SELECT * FROM event as ev ';
      $sQuery.= 'INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND el.eventfk = '.$pnPk.') ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return __LINE__.' - The contact doesn\'t exist.';
      while($bRead)
      {
        $asEventLink = $oDbResult->getData();
        $asLinkedItems[] = $asEventLink[CONST_CP_UID].'&'.$asEventLink[CONST_CP_ACTION].'&'.$asEventLink[CONST_CP_TYPE].'&'.$asEventLink[CONST_CP_PK];
        $bRead = $oDbResult->readNext();
      }

      $asReminder = $this->getEventReminderByPk($pnPk);
    }
    else
    {
      $oDbResult = new CDbResult();
      $asReminder = array();
    }

    if($oPage->getActionReturn())
      $sURL = $oPage->getAjaxUrl('event', CONST_ACTION_SAVEADD, CONST_EVENT_TYPE_EVENT, $pnPk, array(CONST_URL_ACTION_RETURN => $oPage->getActionReturn()));
    else
      $sURL = $oPage->getAjaxUrl('event', CONST_ACTION_SAVEADD, CONST_EVENT_TYPE_EVENT, $pnPk);

    $sHTML= $oHTML->getBlocStart();

    /* @var $oForm CFormEx */
    $oForm = $oHTML->initForm('evtAddForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'class' => 'fullPageForm','submitLabel'=>'Save'));
    $oForm->setFormDisplayParams(array('noCancelButton' => 1));

    $oForm->addField('input', CONST_CP_UID, array('type' => 'hidden', 'value' => $sCp_Uid));
    $oForm->addField('input', CONST_CP_ACTION, array('type' => 'hidden', 'value' => $sCp_Action));
    $oForm->addField('input', CONST_CP_TYPE, array('type' => 'hidden', 'value' => $sCp_Type));
    $oForm->addField('input', CONST_CP_PK, array('type' => 'hidden', 'value' => $nCp_Pk));

    $sEventItemName = $oAddressBook->getItemName(getValue(CONST_CP_TYPE), (int)getValue(CONST_CP_PK));
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add an activity on : '.$sEventItemName));


    $asEvent = getEventTypeList();
    $sEventType = $oDbResult->getFieldValue('type');

    if(!empty($sEventType) && !isset($asEvent[$sEventType]))
    {
      //a type that is not available for user selection
      //$oForm->addField('misc', '', array('type' => 'text', 'text' => $sEventType.' (can not be changed)', 'style' => 'position: absolute; right: 0;'));
      $oForm->addField('input', '', array('label'=>'Activity type', 'value' => $sEventType.'     (can\'t be changed)', 'readonly' => 'readonly', 'style' => 'width: 250px; background-color: #efefef; font-style: italic;'));
      $oForm->addField('hidden', 'event_type', array('value' => $sEventType));
      $oForm->addField('hidden', 'custom_type', array('value' => 1));
    }
    else
    {
      $oForm->addField('select', 'event_type', array('label'=>'Activity type'));
      $oForm->setFieldControl('event_type', array('jsFieldNotEmpty' => ''));
      $oForm->addOption('event_type', array('value'=> '', 'label' => 'Select', 'group' => ''));
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

    $oForm->addField('input', 'date_event', array('type' => 'datetime', 'label'=>'Date', 'value' => $sDate));


    $oForm->addField('input', 'title', array('label'=>'Activity title', 'value' => $oDbResult->getFieldValue('title')));
    $oForm->setFieldControl('title', array('jsFieldMinSize' => '2','jsFieldMaxSize' => 255));

    $oForm->addField('textarea', 'content', array('label'=>'Description', 'value' => $oDbResult->getFieldValue('content'), 'isTinymce' => 1));
    $oForm->setFieldControl('content', array('jsFieldMinSize' => '2','jsFieldMaxSize' => 4096));


    if($sABookUid == $sCp_Uid)
    {
      $asEmployees = $oAddressBook->getEmployeeList($nCompanyPk, $nContactPk, true);

      if(!empty($asEmployees))
      {
        $oForm->addField('select', 'link_to[]', array('label'=> 'Also involved', 'multiple' => 'multiple'));

        //Put compani(es) first (holding / child)
        $anCompanyTreated = array();
        foreach($asEmployees as $nPk => $asEmployeeData)
        {
          if(!empty($asEmployeeData['companyfk']) && $asEmployeeData['companyfk'] != $nCompanyPk && !in_array($asEmployeeData['companyfk'], $anCompanyTreated))
          {
            $sValue = $sABookUid.'&'.CONST_ACTION_VIEW.'&'.CONST_AB_TYPE_COMPANY.'&'.$asEmployeeData['companyfk'];
            $sLabel = '&copy; '.$asEmployeeData['company_name'].' (#'.$asEmployeeData['companyfk'].')';

            if(in_array($sValue, $asLinkedItems))
              $oForm->addOption('link_to[]', array('value'=> $sValue, 'label' => $sLabel, 'class' => 'bsmOptionCompany', 'selected' => 'selected'));
            else
              $oForm->addOption('link_to[]', array('value'=> $sValue, 'label' => $sLabel, 'class' => 'bsmOptionCompany'));

            $anCompanyTreated[] = $asEmployeeData['companyfk'];
          }
        }

        $bDisplayCpName = (count($anCompanyTreated) > 1);
        foreach($asEmployees as $nPk => $asEmployeeData)
        {
          if($asEmployeeData['addressbook_contactpk'] != $nContactPk)
          {
            $sValue = $sABookUid.'&'.CONST_ACTION_VIEW.'&'.CONST_AB_TYPE_CONTACT.'&'.$asEmployeeData['addressbook_contactpk'];
            $sLabel = $oAddressBook->getContactNameFromData($asEmployeeData);
            if($bDisplayCpName)
              $sLabel.= '&nbsp;&nbsp;&nbsp;&nbsp;('.$asEmployeeData['company_name'].')';

            if(in_array($sValue, $asLinkedItems))
              $oForm->addOption('link_to[]', array('value'=> $sValue, 'label' => $sLabel, 'class' => 'bsmOptionContact', 'selected' => 'selected'));
            else
              $oForm->addOption('link_to[]', array('value'=> $sValue, 'label' => $sLabel, 'class' => 'bsmOptionContact'));
          }
        }
      }
    }

    $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<br />'));
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Notification & reminder'));

    //===================================================================
    // Notification section
    $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('team' => 1));
    $oForm->addField('selector', 'notify', array('label'=> 'Share this event with ', 'url' => $sURL, 'nbresult' => 12));

    $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<div class="eventFormSeparator">&nbsp;</div>'));


    //===================================================================
    // Reminder section
    if(!empty($asReminder) )
    {
      $asPreviousReminder = array();
      $asUsers = $oLogin->getUserList(0, false);

      foreach($asReminder as $asData)
      {
        if(isset($asUsers[$asData['loginfk']]))
        {
          $sReminder = 'To '.$asUsers[$asData['loginfk']]['firstname'].' on the '.$asData['date_reminder'];

          if((int)$asData['loginfk'] == $nUser)
          {
            $sUid = uniqid('evt_del_');
            $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_EVENT_TYPE_REMINDER, $asData['event_reminderpk'], array('html_uid' => $sUid));
            $sReminder = '<span id="'.$sUid.'">'.$sReminder.'&nbsp;&nbsp; <a href="javascript:;" onclick="if(window.confirm(\'Delete this reminder ?\')){ AjaxRequest(\''.$sURL.'\', \'body\'); }">XXX</a></span>';
          }

          $sReminder.= '<br />';
          $asPreviousReminder[] = $sReminder;
        }
      }

      if(count($asPreviousReminder) > 0)
      {
        $sReminderInfo = $oHTML->getBlocStart('', array('class' => 'previousReminderBloc '));
          $sReminderInfo.= $oHTML->getLink(count($asPreviousReminder).' existing reminder(s)', 'javascript:;', array('onclick' => '$(this).parent().find(\'.previousReminders\').fadeToggle();'));

          $sReminderInfo.= $oHTML->getBlocStart('', array('class' => 'previousReminders hidden'));
          $sReminderInfo.= implode('', $asPreviousReminder);
          $sReminderInfo.= $oHTML->getBlocEnd();
        $sReminderInfo.= $oHTML->getBlocEnd();

        $oForm->addField('misc', '', array('type'=> 'text', 'text' => $sReminderInfo));
      }
    }

    $oForm->addField('input', 'reminder_date', array('type' => 'datetime', 'label'=>'Reminder date', 'keepNextInline'=> 1));
    $oForm->setFieldDisplayParams('reminder_date', array('class' => 'eventFieldInline'));

    $oForm->addField('select', 'reminder_before', array('label'=>'Reminder sent', 'keepNextInline'=> 1));
    $oForm->setFieldDisplayParams('reminder_before', array('class' => 'eventFieldInline'));

      $oForm->addOption('reminder_before', array('value'=> '1h', 'label' => '1 hour before'));
      $oForm->addOption('reminder_before', array('value'=> '2h', 'label' => '2 hours before'));
      $oForm->addOption('reminder_before', array('value'=> 'halfday', 'label' => 'half a day before'));
      $oForm->addOption('reminder_before', array('value'=> 'fullday', 'label' => 'a full day before'));

    $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0);
    $oForm->addField('selector', 'reminder_user', array('label'=> 'Recipient', 'url' => $sURL, 'nbresult' => 1));
    $oForm->addOption('reminder_user', array('value'=> $nUser, 'label' => $oLogin->getCurrentUserName(true)));

    $oForm->addField('textarea', 'reminder_message', array('label'=>'Message'));
    $oForm->setFieldControl('content', array('jsFieldMinSize' => '2','jsFieldMaxSize' => 4096));

    //===================================================================
    // Calendar section
    /*  TODO: to restaure. Removed until the feature is improbved
     * if($this->coCalendar)
    {
      $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<div class="eventFormSeparator">&nbsp;</div>'));

      $sUrl = $oPage->getAjaxUrl('zimbra', CONST_ACTION_ADD, CONST_ZCAL_EVENT, 0);
      $oForm->addField('checkbox', 'addCalendar', array('label'=> 'Add to my calendar', 'value' => 1, 'textbefore' => 1, 'onchange' => 'if(this.checked){ goPopup.setPopupFromAjax(null, \''.$sUrl.'\', true); } '));
      $oForm->setFieldDisplayParams('addCalendar', array('class' => 'addCalendarBox'));
      $oForm->addField('misc', '', array('type'=> 'br'));
    }*/

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }


  /**
   * Display the event form
   * @param integer  $pnPk
   * @return string HTML
   */

  private function _getEventForm($pnPk)
  {
    if(!assert('is_integer($pnPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();

    //Fetch the data from the calling component
    $sCp_Uid = getValue(CONST_CP_UID);
    if(empty($sCp_Uid))
     return $oHTML->getBlocMessage(__LINE__.' - Oops, missing some informations to create an activity.');

    $oPage = CDependency::getCpPage();
    $oDB = CDependency::getComponentByName('database');

    //If editing the contact
    if(!empty($pnPk))
    {
      $sQuery = 'SELECT * FROM event as ev ';
      $sQuery.= 'INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND el.eventfk = '.$pnPk.') ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return __LINE__.' - The contact doesn\'t exist.';
    }
    else
      $oDbResult = new CDbResult();

    if($oPage->getActionReturn())
      $sURL = $oPage->getAjaxUrl('event', CONST_ACTION_SAVEADD, CONST_EVENT_TYPE_EVENT, $pnPk, array(CONST_URL_ACTION_RETURN => $oPage->getActionReturn()));
    else
      $sURL = $oPage->getAjaxUrl('event', CONST_ACTION_SAVEADD, CONST_EVENT_TYPE_EVENT, $pnPk);

    $sHTML= $oHTML->getBlocStart();
      $sHTML.= $oHTML->getBlocStart('', array('class' =>'bottom_container'));
      $sHTML.= $this->_getEventAddForm($pnPk);
      $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Save the activity
   * @param integer $pnPk
   * @return type array
   */

  protected function _getEventSave($pnPk = 0, $pasEventData = array())
  {
    if(!assert('is_integer($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter');

    $oLogin = CDependency::getCpLogin();

    //component calling event
    $asEvent = array();
    if(!empty($pasEventData))
    {
      $asEvent = $pasEventData;
    }
    else
    {
      $asEvent['item_uid'] = getValue(CONST_CP_UID);
      $asEvent['item_action'] = getValue(CONST_CP_ACTION);
      $asEvent['item_type'] = getValue(CONST_CP_TYPE);
      $asEvent['item_pk'] = (int)getValue(CONST_CP_PK, 0);

      $asEvent['date'] = getValue('date_event');
      $asEvent['date'] = date('Y-m-d H:i:s',strtotime($asEvent['date']));

      $asEvent['type'] = getValue('event_type');
      $asEvent['title'] = getValue('title');
      $asEvent['content'] = getValue('content');
      $asEvent['coworker'] = (array)getValue('link_to', array());
      $asEvent['notify'] = getValue('notify');
      $asEvent['add_calendar'] = getValue('addCalendar', 0);
      $asEvent['custom_type'] = (int)getValue('custom_type', 0);

      $asEvent['reminder_date'] = getvalue('reminder_date');
      $asEvent['reminder_time'] = strtotime($asEvent['reminder_date']);
      $asEvent['reminder_before'] = getvalue('reminder_before');
      $asEvent['reminder_user'] = (int)getvalue('reminder_user', 0);
      $asEvent['reminder_message'] = getvalue('reminder_message');

      if(empty($asEvent['type']) || empty($asEvent['content']))
        return array('error' => __LINE__.' - Can not create empty events.');
    }

    /*if (!$oLogin->isAdmin() && $asEvent['type'] == 'cp_history')
      return array('error' => __LINE__.' - Sorry you are not allowed to edit company history');*/


    //load related item data to make sure it's available
    $oComponent = CDependency::getComponentByUid($asEvent['item_uid']);
    $asItemData =  $oComponent->getItemDescription($asEvent['item_pk'], $asEvent['item_action'], $asEvent['item_type']);
    if(empty($asItemData))
    {
      assert('false; // add event but no item to link ');
      return array('error' => __LINE__.' - bad parameters. No item found .');
    }

    $asItemData = $asItemData[$asEvent['item_pk']];


    if(!isset($asEvent['loginfk']) || empty($asEvent['loginfk']))
      $asEvent['loginfk'] = $oLogin->getUserPk();

    if(empty($asEvent['title']) && empty($asEvent['content']))
      return array('alert' =>'Enter activity title or content.');

    if(empty($asEvent['date']) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $asEvent['date']))
      return array('error' => __LINE__.' - Date is invalid.');

    if(empty($asEvent['title']) && empty($asEvent['content']))
      return array('error' => __LINE__.' - Fill title or description.');

    if(!empty($asEvent['reminder_date']) || !empty($asEvent['reminder_message']))
    {
      if(empty($asEvent['reminder_user']))
        return array('error' => __LINE__.' - You need to specify who the reminder recipient is.');

      if(empty($asEvent['reminder_before']))
        return array('error' => __LINE__.' - The delay before the reminder is sent is required.');

      if(!$asEvent['reminder_time'] || $asEvent['reminder_time'] < (time()+3600))
        return array('error' => __LINE__.' - Reminder should be set at least 1 hour ahead of now.');
    }


    $oDB = CDependency::getComponentByName('database');
    $oPage = CDependency::getCpPage();

    if(empty($pnPk))
    {
      $sFts = strip_tags($asEvent['title'].' '.$asEvent['content']);

      if(isCJK($sFts))
      {
        $oSharedSpace = CDependency::getComponentByName('sharedspace');
        if($oSharedSpace)
        $sFts = $oSharedSpace->tokenizeCjk($sFts, true);
      }


      $sQuery = 'INSERT INTO `event` (`type`, `title`, `content`, `date_create`, `date_display`, `created_by`, `custom_type`, `_fts`) ';
      $sQuery.= ' VALUES ('.$oDB->dbEscapeString($asEvent['type']).', '.$oDB->dbEscapeString($asEvent['title']).', '.$oDB->dbEscapeString($asEvent['content']).'';
      $sQuery.= ', NOW(), '.$oDB->dbEscapeString($asEvent['date']).', '.(int)$asEvent['loginfk'].', '.$asEvent['custom_type'].', '.$oDB->dbEscapeString($sFts).') ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array('error' => __LINE__.' - Sorry, could not save the activity. ['.var_export($oDbResult, true).']');

      $oDbResult->readFirst();
      $nEventfk = (int)$oDbResult->getFieldValue('pk');

      //link the event to the uid/action/type/pk from the url
      $asLink = array();
      $asLink[] = ' ('.$oDB->dbEscapeString($nEventfk).', '.$oDB->dbEscapeString($asEvent['item_uid']).', '.$oDB->dbEscapeString($asEvent['item_action']).', '.$oDB->dbEscapeString($asEvent['item_type']).', '.$oDB->dbEscapeString($asEvent['item_pk']).') ';

      if(!empty($asEvent['coworker']))
      {
        //link to this event the connections the user has selected
        foreach($asEvent['coworker'] as $sLinkParam)
        {
          $asLinkData = explode('&', $sLinkParam);

          if(count($asLinkData) != 4)
            return array('error' => __LINE__.' - link parameters incorrect.');

            $asLink[] = ' ('.$oDB->dbEscapeString($nEventfk).', '.$oDB->dbEscapeString($asLinkData[0]).', '.$oDB->dbEscapeString($asLinkData[1])
                  .', '.$oDB->dbEscapeString($asLinkData[2]).', '.$oDB->dbEscapeString($asLinkData[3]).') ';
        }
      }

      $sQuery = 'INSERT INTO `event_link` (`eventfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES '.implode(',', $asLink);
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array('error' => __LINE__.' - Sorry, could not save the activity.');

      $oMail = CDependency::getComponentByName('mail');
      $oHTML = CDependency::getCpHtml();

      if(!empty($asEvent['add_calendar']) && is_numeric($asEvent['add_calendar']))
      {
        if($this->coCalendar)
        {
          $sQuery= 'INSERT INTO `event_link` (`eventfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `cp_params`)';
          $sQuery.= ' VALUES ('.$oDB->dbEscapeString($nEventfk).', '.$oDB->dbEscapeString($this->coCalendar->getComponentUid()).',
                              '.$oDB->dbEscapeString(CONST_ACTION_VIEW).','.$oDB->dbEscapeString(CONST_ZCAL_EVENT).',
                              '.$oDB->dbEscapeString((int)$asEvent['add_calendar']).', '.$oDB->dbEscapeString($oLogin->getUserPk()).') ';
          $oDbResult = $oDB->ExecuteQuery($sQuery);
          if(!$oDbResult)
            return array('error' => __LINE__.' - Sorry, could not save the activity.');
        }
      }

      /*$sTitleEvent = $asEvent['title'].' '.substr(strip_tags($asEvent['content']), 0, 100);
      $sTitleEvent = trim($sTitleEvent);*/
      $sTitleEvent = $asItemData['label'];

      $sUrl = $oPage->getUrl($asEvent['item_uid'], $asEvent['item_action'], $asEvent['item_type'], $asEvent['item_pk']);
      $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, $this->getAction(), CONST_EVENT_TYPE_EVENT, $asEvent['item_pk'], 'New activity ['.$asEvent['type'].']', $sTitleEvent, $sUrl);

      if($asEvent['item_type'] == 'ct')
      {
        $asManager = $oComponent->getAccountManager($asEvent['item_pk'], 'addressbook_contact');
        foreach($asManager as $nManagerFk)
        {
          $oLogin->logUserActivity($oLogin->getUserPk(), $this->_getUid(),$this->getAction(),CONST_EVENT_TYPE_EVENT, $nEventfk, 'New activity ['.$asEvent['type'].']', $sTitleEvent, $sUrl, $asEvent['item_pk'], $nManagerFk);
        }
      }

      //Section to send notification about the activity
      if(!empty($asEvent['notify']))
      {
        $asRecipients = explode(',', $asEvent['notify']);
        $asEventType = getEventTypeList();

        $sItemDesc = ' An activity related to <strong>'.$asItemData['label'].'</strong> has been shared with you.<br/> ';
        $sItemDesc.=  $asItemData['description'];

        $sMailTitle = CONST_APP_NAME.' (ACT/'.$asEventType[$asEvent['type']]['label'].') - ';
        if(isset($asItemData['company_name']))
          $sMailTitle.= $asItemData['company_name'];

        if(isset($asItemData['name']))
          $sMailTitle.= ' / '.$asItemData['name'];


        foreach($asRecipients as $nLoginfk)
        {
          $asUserData = $oLogin->getUserDataByPk((int)$nLoginfk);
          $asCreator = $oLogin->getUserDataByPk($oLogin->getUserPk());

          $sMailContent = ' <strong> Hello '.$oLogin->getUserNameFromData($asUserData).',</strong> <br/><br/>';
          $sMailContent.= $sItemDesc;

          $sMailContent.= $oHTML->getCR(2);
          $sMailContent.= ' <strong> Activity Detail </strong> <br/> <br/>';
          $sMailContent.= $oHTML->getBlocStart('',array('style'=>'border:1px solid #CECECE;margin:5px;padding:5px;'));
          $sMailContent.= ' Created by : '. $oLogin->getUserNameFromData($asCreator).'<br/> <br/>';
          $sMailContent.= ' Date :'.$asEvent['date'].'<br/> <br/>' ;
          $sMailContent.= ' <strong> Title </strong> :'.$asEvent['title'] .' <br/>' ;
          $sMailContent.= ' <strong> Description </strong> :'.$asEvent['content'].' <br/>' ;
          $sMailContent.= $oHTML->getBlocEnd();

          // Get the latest 3 events and remove the first because it is already displayed
          $sMailContent.= $oHTML->getCR(4);
          $asEvents = $this->getEventDetail('',$asEvent['item_pk'], $asEvent['item_type'], 3);
          array_shift($asEvents);

          $sMailContent.= '<strong> Previous Activities </strong> <br/>';

          foreach($asEvents as $asEventDetail)
          {
            $sMailContent.= $oHTML->getBlocStart('',array('style'=>'border:1px solid #CECECE;padding:5px;margin-top:5px;'));
            $sMailContent.= ' Date :'.$asEventDetail['date_display'].'<br/> <br/>' ;
            $sMailContent.= ' <strong> Title </strong> :'.$asEventDetail['title'] .' <br/>' ;
            $sMailContent.= ' <strong> Description </strong> :'.$asEventDetail['content'].' <br/>' ;
            $sMailContent.= $oHTML->getBlocEnd();

          }
          $sMailContent.= ' Enjoy BCM <br/> <br/>';
          $sMailContent.= '<a href="'.$sUrl.'#'.$asEvent['item_type'].'_tab_eventId'.'"> Click here to view all the activities</a>';

          $sMailContent.= $oHTML->getBlocEnd();

          $oMail-> sendRawEmail(CONST_PHPMAILER_EMAIL, $asUserData['email'], $sMailTitle, $sMailContent);
        }
      }
    }
    else
    {
      $sQuery = 'UPDATE `event` SET `type` = '.$oDB->dbEscapeString($asEvent['type']).', `title` = '.$oDB->dbEscapeString($asEvent['title']).',';
      $sQuery.= '`content` = '.$oDB->dbEscapeString($asEvent['content']).', `date_display` = '.$oDB->dbEscapeString($asEvent['date']).', ';
      $sQuery.= '`date_update` = NOW(), `updated_by` = '.$oLogin->getUserPk().' WHERE eventpk = '.$pnPk;

      $nEventfk = $pnPk;
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if($oDbResult)
      {
        /*$sTitleEvent = $asEvent['title'].' '.substr(strip_tags($asEvent['content']), 0, 100);
        $sTitleEvent = trim($sTitleEvent);*/
        $sTitleEvent = $asItemData['label'];

        $sUrl = $oPage->getUrl($asEvent['item_uid'], $asEvent['item_action'], $asEvent['item_type'], $asEvent['item_pk']);
        $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, CONST_ACTION_SAVEEDIT, CONST_EVENT_TYPE_EVENT, $pnPk, 'Activity updated ['.$asEvent['type'].']', $sTitleEvent, $sUrl);
      }

      //------------------------------------------
      //Delete existing links and add the potential new ones
      $asLink = array();
      $asLink[] = ' ('.$oDB->dbEscapeString($pnPk).', '.$oDB->dbEscapeString($asEvent['item_uid']).', '.$oDB->dbEscapeString($asEvent['item_action']).', '.$oDB->dbEscapeString($asEvent['item_type']).', '.$oDB->dbEscapeString($asEvent['item_pk']).') ';

      if(!empty($asEvent['coworker']))
      {
        //link to this event the connections the user has selected
        foreach($asEvent['coworker'] as $sLinkParam)
        {
          $asLinkData = explode('&', $sLinkParam);

          if(count($asLinkData) != 4)
            return array('error' => __LINE__.' - link parameters incorrect.');

            $asLink[] = ' ('.$oDB->dbEscapeString($pnPk).', '.$oDB->dbEscapeString($asLinkData[0]).', '.$oDB->dbEscapeString($asLinkData[1])
                  .', '.$oDB->dbEscapeString($asLinkData[2]).', '.$oDB->dbEscapeString($asLinkData[3]).') ';
        }
      }

      $sQuery = 'DELETE FROM event_link WHERE eventfk = '.$pnPk;
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array('error' => __LINE__.' - Sorry, could recreate links with connections and companies.');

      $sQuery = 'INSERT INTO `event_link` (`eventfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES '.implode(',', $asLink);
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array('error' => __LINE__.' - Sorry, could not save the activity.');
    }

    if(!$oDbResult)
      return array('error' => __LINE__.' - Oops. couldn\'t save the activity.');

    if(!empty($asEvent['reminder_date']))
    {
      $bSaved = $this->_saveReminder($nEventfk, $asEvent['reminder_date'], $asEvent['reminder_before'], $asEvent['reminder_user'], $asEvent['reminder_message']);
      if(!$bSaved)
        assert('false; // Adding event: reminder could not be saved. ');
    }

    $sUrl = $oPage->getUrl($asEvent['item_uid'], $asEvent['item_action'], $asEvent['item_type'], $asEvent['item_pk'], '', $asEvent['item_type'].'_tab_eventId');

    if(empty($pnPk))
      return array('notice' => 'Activity saved successfully.', 'timedUrl' => $sUrl);

    return array('notice' => 'Activity updated successfully.', 'timedUrl' => $sUrl);
  }

  /**
   * Create a new event
   * @param string $psEventType
   * @param string $psTitle
   * @param string $psContent
   * @param string $psGuid
   * @param string $psType
   * @param string $psAction
   * @param integer $pnPk
   * @return integer eventpk
  */
  public function quickAddEvent($psEventType, $psTitle, $psContent, $psGuid, $psType = '', $psAction = '', $pnPk = 0, $pbForceType = false)
  {
    if(!assert('!empty($psEventType) && !empty($psContent) && !empty($psGuid) && is_integer($pnPk)'))
      return 0;

    $asEvent = getEventTypeList(true);
    if(!in_array($psEventType, $asEvent))
      $nCustomType = 1;
    else
      $nCustomType = 0;

    if(!$pbForceType && $nCustomType == 1)
    {
      assert('false; // Activity type does not exist');
      return 0;
    }

    $oLogin = CDependency::getCpLogin();
    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'INSERT INTO `event` (`type`, `title`, `content`, `date_create`, `date_display`, `created_by`, `custom_type`) ';
    $sQuery.= ' VALUES ('.$oDB->dbEscapeString($psEventType).', '.$oDB->dbEscapeString($psTitle).', '.$oDB->dbEscapeString($psContent).'';
    $sQuery.= ', NOW(), NOW(), '.$oLogin->getUserPk().', '.$nCustomType.') ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    if(!$oDbResult)
      return 0;

    $nEventfk = $oDbResult->getFieldValue('pk');

    $sQuery= 'INSERT INTO `event_link` (`eventfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`)';
    $sQuery.= ' VALUES ('.$oDB->dbEscapeString($nEventfk).', '.$oDB->dbEscapeString($psGuid).', '.$oDB->dbEscapeString($psAction).',';
    $sQuery.= ''.$oDB->dbEscapeString($psType).', '.$oDB->dbEscapeString($pnPk).') ';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return 0;

    return $nEventfk;
  }

  /**
   * Return an array with all the event datas matching the passed parameters
   * @param string $psUid
   * @param string $psType
   * @param string $psAction
   * @param integer $pnPk
   * @param string $psEventType
   * @return array
   */
  public function getEventInformation($psUid, $psAction, $psType, $psEventType = '')
  {
    if(!assert('!empty($psUid)'))
      return array();

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * FROM `event_link` as el ';
    if(!empty($psEventType))
      $sQuery.= ' INNER JOIN event as ev ON (ev.eventpk = el.eventfk AND type = "'.$psEventType.'")';
    else
      $sQuery.= ' INNER JOIN event as ev ON (ev.eventpk = el.eventfk)';

    $sQuery.= ' WHERE cp_uid = "'.$psUid.'" AND cp_action = "'.$psAction.'" AND cp_type="'.$psType.'"';
    $sQuery.= ' GROUP BY cp_pk ORDER BY ev.date_display desc ';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return array();

    $asResult = array();
    while($bRead)
    {
      $asResult[$oResult->getFieldValue(CONST_CP_PK, CONST_PHP_VARTYPE_INT)] = $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asResult;
  }

  /**
   * Get the detail of the event with matching parameters
   * @param string $psEventType
   * @param integer $pnItemPk
   * @param string $psType
   * @param integer $pnLimit
   * @return array of data
   */

  public function getEventDetail($psEventType='', $pnItemPk, $psType, $pnLimit=0)
  {
    if(!assert('!empty($pnItemPk) && is_integer($pnItemPk)&& is_integer($pnLimit)'))
      return 0;

    $oDB = CDependency::getComponentByName('database');

    if(!empty($psEventType))
    {
     if($psEventType == 'other')
      $sEvent = ' AND ev.type <> "email"';
     else
      $sEvent = ' AND ev.type = "'.$psEventType.'"';
     }
    else
      $sEvent = '';

    $sQuery = 'SELECT ev.*,evel.* FROM event AS ev,event_link AS evel WHERE evel.eventfk = ev.eventpk AND evel.cp_type="'.$psType.'" '.$sEvent.' and evel.cp_pk='.$pnItemPk.' ORDER by ev.date_create desc';
    if($pnLimit==0)
      $sQuery.= ' limit 1';
    else
      $sQuery.= ' limit '.$pnLimit.'';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

    if(!$bRead)
      return array();
    $asEvents = array();
    while($bRead)
    {
      $asEvents[] = $oResult->getData();
      $bRead = $oResult->readNext();
    }
    return $asEvents;
  }

  /**
  * Get the latest activity related to the company
  * @param integer $pnItemPk
  * @return array of activity data
  */
  //public function getLatestConnectionEvent($pnItemPk)
  public function getEvents($psUid, $psAction, $psType = '', $pnPk = 0, $pasItem = array(), $pnLimit = 200)
  {

    $oDB = CDependency::getComponentByName('database');

    $sCondition = '(el.cp_uid = "'.$psUid.'" AND el.cp_action = "'.$psAction.'" ';

    if(empty($pasItem))
    {
      if(!empty($psType))
        $sCondition.= ' AND el.cp_type = "'.$psType.'" ';

      if(!empty($pnPk))
        $sCondition.= ' AND el.cp_pk = "'.$pnPk.'" ';
    }
    else
    {
      $asCondition = array();
      $sCondition.= ' AND ( ';
      foreach($pasItem as $asItem)
      {
        $asCondition[] = ' (el.cp_type = "'.$asItem['type'].'" AND el.cp_pk = "'.$asItem['pk'].'") ';
      }
      $sCondition.= implode(' OR ', $asCondition).' )';
    }

    $sCondition.= ') ';

    $sQuery = ' SELECT ev.*,el.* from event as ev ';
    $sQuery.= ' INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND '.$sCondition.') ';
    $sQuery.= ' ORDER BY ev.date_display desc LIMIT '.$pnLimit;
    //echo $sQuery;

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    $asEvent = array();

    while($bRead)
    {
      $asEvent[] = $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return  $asEvent;
  }

  public function getEventDataByPk($pnItemPk)
  {
    if(!assert('!empty($pnItemPk) && is_integer($pnItemPk)'))
      return 0;

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * from event WHERE eventpk = '.$pnItemPk.'';
    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

    if(!$bRead)
      return array();
    else
      return  $oResult->getData();

  }

  public function setLanguage($psLanguage)
  {
    require_once('language/language.inc.php5');
    if(isset($gasLang[$psLanguage]))
      $this->casText = $gasLang[$psLanguage];
    else
      $this->casText = $gasLang[CONST_DEFAULT_LANGUAGE];
  }

  /**
   * Return an array containing the reminders data
   *
   * @param integer $pnEventPk
   * @param integer $pnReminderPk
   * @return array
   */
  public function getEventReminderByPk($pnEventPk = 0, $pnReminderPk = 0)
  {
    if(!assert('is_integer($pnEventPk) && is_integer($pnReminderPk)'))
      return array();

    if(empty($pnEventPk) && empty($pnReminderPk))
    {
      assert('false; // at least one pk has to be passed. ');
      return array();
    }

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM event_reminder WHERE ';

    if(!empty($pnEventPk))
      $sQuery.= 'eventfk = '.$pnEventPk;
    else
      $sQuery.= 'event_reminderpk = '.$pnReminderPk;

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return array();

    //return an array of reminders
    $asReminder = array();
    while($bRead)
    {
      $asReminder[]= $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asReminder;
  }


  private function _saveReminder($nEventfk, $sReminderDate, $sReminderBefore, $nReminderUser, $sReminderMsg = '')
  {
    if(!assert('is_integer($nEventfk) && is_integer($nReminderUser) && !empty($sReminderDate) && !empty($sReminderBefore)'))
      return false;

    $nTime = strtotime($sReminderDate);
    if($nTime === false)
    {
      assert('false; // date not valid');
      return false;
    }

    if($nTime < (time()+3600))
    {
      assert('false; // can\'t send a reminder in less than an hour ');
      return false;
    }

    if(!in_array($sReminderBefore, array('1h', '2h', 'halfday', 'fullday')))
    {
      assert('false; // delay before the reminder is sent doesn\'t exist.');
      return false;
    }

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'INSERT INTO event_reminder (eventfk, date_created, date_reminder, notify_delay, loginfk, message, sent) ';
    $sQuery.= ' VALUES ('.$oDB->dbEscapeString($nEventfk).', '.$oDB->dbEscapeString(date('Y-m-d H:i:s')).', ';
    $sQuery.= $oDB->dbEscapeString(date('Y-m-d H:i:s', $nTime)).',  '.$oDB->dbEscapeString($sReminderBefore).', ';
    $sQuery.= $oDB->dbEscapeString($nReminderUser).', '.$oDB->dbEscapeString($sReminderMsg).', 0 ) ';

    return (bool) $oDB->ExecuteQuery($sQuery);
  }


  /**
   * Send reminders to users
   *
   * @return boolean
   */
  private function _sendReminders()
  {

    $oDB = CDependency::getComponentByName('database');

    $sDate = date('Y-m-d H:i:s', strtotime('+2 days'));

    $sQuery = 'SELECT evr.*, ev.*, evl.*, IF(ct.addressbook_contactpk IS NULL, cp.company_name, CONCAT(ct.firstname, " ", ct.lastname)) as item_label ';
    $sQuery.= ' FROM event_reminder evr  ';
    $sQuery.= ' INNER JOIN event as ev ON (ev.eventpk = evr.eventfk) ';
    $sQuery.= ' INNER JOIN event_link as evl ON (evl.eventfk = ev.eventpk) ';
    $sQuery.= ' LEFT JOIN addressbook_contact as ct ON (evl.cp_type = "ct" AND evl.cp_pk = ct.addressbook_contactpk) ';
    $sQuery.= ' LEFT JOIN addressbook_company as cp ON (evl.cp_type = "cp" AND evl.cp_pk = cp.addressbook_companypk) ';
    $sQuery.= ' WHERE sent = 0 AND date_reminder < "'.$sDate.'" ';
    $sQuery.= ' GROUP BY evr.eventfk ORDER BY date_reminder ';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return true;

    $oMail = CDependency::getComponentByName('mail');
    $oLogin = CDependency::getCpLogin();
    $asUsers = $oLogin->getUserList();

    $asGroupedReminders = array();
    $nTime = (int)date('H');
    $nYear = (int)date('Y');
    $nMonth = (int)date('m');
    $nDay = (int)date('d');

    while($bRead)
    {
      $asReminder = $oResult->getData();

      switch($asReminder['notify_delay'])
      {
        case '1h': $sDateRef = date('Y-m-d H:i:s', strtotime('+ 1 hour 30 minutes')); break;
        case '2h': $sDateRef = date('Y-m-d H:i:s', strtotime('+ 2 hour 30 minutes')); break;

        case 'halfday':

          if($nTime > 12)
            $sDateRef = date('Y-m-d H:i:00', mktime(12, 0, 0, $nMonth, $nDay+1, $nYear));
          else
            $sDateRef = date('Y-m-d H:i:00', mktime(23, 59, 59, $nMonth, $nDay, $nYear));
          break;

        default:
           $sDateRef = date('Y-m-d H:i:00', mktime(23, 59, 59, $nMonth, $nDay+1, $nYear));
      }

      //echo $asReminder['notify_delay'].' ==> '.$asReminder['date_reminder'].' <= '.$sDateRef;

      if(isset($asUsers[$asReminder['loginfk']]) && !empty($asUsers[$asReminder['loginfk']]) && $asReminder['date_reminder'] <= $sDateRef)
      {
        $asReminder['email'] = $asUsers[$asReminder['loginfk']]['email'];
        if(!empty($asReminder['email']))
        {
          $asReminder['name'] = $oLogin->getUserNameFromData($asUsers[$asReminder['loginfk']], true);
          $asGroupedReminders[(int)$asReminder['loginfk']][] = $asReminder;
        }
      }

      $bRead = $oResult->readNext();
    }

    if(empty($asGroupedReminders))
      return true;

    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $asTreatedReminder = array();

    foreach($asGroupedReminders as $asReminders)
    {
      $oMail->createNewEmail();
      $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);

      $asFirstReminder = current($asReminders);
      $oMail->addRecipient($asFirstReminder['email'], $asFirstReminder['name']);

      $sSubject = 'BCM reminder - '.count($asReminders).' event(s) approaching ';
      $sContent = '';

      foreach($asReminders as $asData)
      {
        $sEventUrl = $oPage->getUrl($asData[CONST_CP_UID], $asData[CONST_CP_ACTION], $asData[CONST_CP_TYPE], $asData[CONST_CP_PK], $asData['cp_params']);
        $asTreatedReminder[] = (int)$asData['event_reminderpk'];


        $sContent.= $oHTML->getBlocStart('', array('style' => 'border-bottom: 1px solid #666; margin: 5px 5px 10px 5px; padding: 5px 5px 15px 20px; font-family: arial; font-size: 12px;  '));
          $sContent.= $oHTML->getText('You\'ve requested a reminder:');
          $sContent.= $oHTML->getCR(2);

          $sContent.= $oHTML->getBlocStart('', array('style' => 'border-left: 3px solid #aaa; padding: 3px 0 3px 5px; background-color: #ececec;'));

            $sContent.= $oHTML->getText('Reminder date: '.$asReminder['date_reminder']);
            $sContent.= $oHTML->getCR();

            if(!empty($asReminder['message']))
              $sContent.= $oHTML->getText('Reminder message: <br /><br />'.$asReminder['message']);
            else
              $sContent.= $oHTML->getText('no message', array('style' => 'font-style: italic; color: #888;'));

            $sContent.= $oHTML->getCR(2);

          $sContent.= $oHTML->getBlocEnd();

          $sContent.= $oHTML->getCR();

          if($asData[CONST_CP_TYPE] == 'ct')
            $sContent.= $oHTML->getText('This reminder is related to the connection');
          else
            $sContent.= $oHTML->getText('This reminder is related to the company');

          $sContent.= $oHTML->getLink(' #'.$asData[CONST_CP_PK].': '.$asData['item_label'], $sEventUrl);
          $sContent.= $oHTML->getCR(2);

          $sContent.= $oHTML->getBlocStart('', array('style' => 'border-left: 3px solid #aaa; padding: 3px 0 3px 5px; background-color: #ececec;'));

            $sContent.= $oHTML->getText('Event date: '.$asData['date_display']);
            $sContent.= $oHTML->getCR();
            $sContent.= $oHTML->getText('Event Type: '.ucfirst($asData['type']));
            $sContent.= $oHTML->getCR();
            $sContent.= $oHTML->getText('Content: ');
            $sContent.= $oHTML->getCR();
            $sContent.= $oHTML->getText($asData['content'], array('style' => 'font-style: italic;'));
            $sContent.= $oHTML->getCR();

          $sContent.= $oHTML->getBlocEnd();

        $sContent.= $oHTML->getBlocEnd();

      }

      $oResult = $oMail->send($sSubject, $sContent, strip_tags($sContent));

      if($oResult)
      {
        echo '--> reminder email sent to '.$asFirstReminder['email'].' - '.$asFirstReminder['name'].' with '.count($asReminders).' reminders <br />';
      }
    }

    if(!empty($asTreatedReminder))
    {
      $sQuery = 'UPDATE event_reminder SET sent=1 WHERE event_reminderpk IN ('.implode(',', $asTreatedReminder).')';
      $oResult = $oDB->ExecuteQuery($sQuery);
    }

    return true;
  }

  private function _getReminderDelete($pnReminderPk)
  {
    if(!assert('is_integer($pnReminderPk) && !empty($pnReminderPk)'))
      return array();

    $sHtmlElementId = getValue('html_uid');
    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'DELETE FROM event_reminder WHERE event_reminderpk = '.$pnReminderPk;

    if(!$oDB->ExecuteQuery($sQuery))
      return array('error' => 'Sorry, we could not delete the reminder.');

    return array('data' => 'ok', 'action' => '$("#'.$sHtmlElementId.' a").remove(); $("#'.$sHtmlElementId.'").attr(\'style\', \'text-decoration: line-through;\'); ');
  }

  public function listenerNotification($psUid, $psAction, $psType, $pnPk, $psActionToDo)
  {
    $avCpValues = array(CONST_CP_UID => $psUid, CONST_CP_ACTION => $psAction, CONST_CP_TYPE => $psType, CONST_CP_PK => $pnPk);

    if(!assert('is_CpValues($avCpValues)'))
      return false;

    switch($psActionToDo) {
      case CONST_ACTION_DELETE :
        $this->_getModel()->deleteFromCpValues($avCpValues);
        break;
    }

    return true;
  }

  /**
   *find url in a string, and replace those by a link
   * @param string $psText
   * @param string $psLinkTarget
   *
   * @return string
   */
  public function restoreLinks($psText, $psHtmlText = '')
  {
    if(empty($psText) || strlen($psText) < 5)
      return $psText;

    $asLink =  array();
    $psText = ' '.$psText.' ';

    if(empty($psHtmlText))
    {
      preg_match_all('~(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~', $psText, $asLink);
      $nUrlKey = 1;
      $nTextKey = 1;
    }
    else
    {
      //search links in the html (safer)
      preg_match_all('/<a\s[^>]*href\s*=\s*(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $psHtmlText, $asLink);
      $nUrlKey = 2;
      $nTextKey = 3;
    }

    $nResult = count($asLink[$nUrlKey]);
    for($nCount = 0; $nCount < $nResult; $nCount++)
    {
      $sLink = '<a href="'.$asLink[$nUrlKey][$nCount].'" target="_blank">'.$asLink[$nTextKey][$nCount].'</a>';
      $psText = preg_replace('#([^a-z0-9>="\'])'.$asLink[$nTextKey][$nCount].'([^a-z0-9<"\'])#i', '$1'.$sLink.'$2', $psText);
    }

    return trim($psText);
  }

  /**
   * check the dedicated mail box to look for events to import
   */
  private function _fetchMailEvents()
  {

    if(isDevelopment())
    {
      dump('!!! never fetch email from local version, real db will miss data !!!');
      return false;
    }


    echo 'Look for emails... ';

    if(!CONST_PHPMAILER_SMTP_LOGIN)
      return true;

    imap_timeout(IMAP_OPENTIMEOUT, 5);
    $oMailBox = imap_open ('{'.CONST_PHPMAILER_SMTP_HOST.':'.CONST_MAIL_IMAP_PORT.'/imap/ssl/novalidate-cert}inbox', CONST_PHPMAILER_SMTP_LOGIN, CONST_PHPMAILER_SMTP_PASSWORD);
    if($oMailBox === false)
    {
      assert('false; // could not connect to '.CONST_PHPMAILER_SMTP_HOST.' / '.CONST_PHPMAILER_SMTP_LOGIN);
      return false;
    }

    $oBoxInfo = imap_mailboxmsginfo($oMailBox);
    if(!$oBoxInfo)
    {
      assert('false; // could not fetch mailbox info to '.CONST_EVENT_SYNC_SERVER.' / '.CONST_EVENT_SYNC_MAILBOX);
      imap_close($oMailBox);
      return false;
    }

    dump($oBoxInfo);

    if(empty($oBoxInfo->Unread))
    {
      echo 'no new mail to import';
      imap_close($oMailBox);
      return true;
    }


    //$oFiltered = imap_search ($oMailBox, 'FROM "slistem@slistem.com" SINCE "'.date('Y-m-d', strtotime('-7 days')).'"' , int $options = SE_FREE [, string $charset = NIL ]] )
    //$asFiltered = imap_search($oMailBox, 'ALL', SE_FREE, 'utf-8');
    $asFiltered = imap_search ($oMailBox, 'UNSEEN', SE_FREE, 'utf-8');

    if($asFiltered == false)
    {
      echo 'All messages seen, no new email to import (or error ?)';
      imap_close($oMailBox);
      return true;
    }

    $sMessageIds = implode(',', $asFiltered);
    $asFiltered = imap_fetch_overview($oMailBox, $sMessageIds);
    if(empty($asFiltered))
    {
      echo 'could not load email overview ';
      imap_close($oMailBox);
      return false;
    }
    //dump($asFiltered); exit();
    echo('<hr />'.count($asFiltered).' found in the mailbox<hr />');



    //====================================================================
    //====================================================================
    //Initialize variables before the look into the mailbox
    $asEmail = CDependency::getCpLogin()->getUserEmailList();

    $asEmail = array_flip($asEmail);

    // Developer filter exclusion
    $asEmail['dcepulis@slate.co.jp'] = 468;
    $asEmail['dcepulis@slate-ghc.com'] = 468;
    // Fake researcher/consultant emails
    $asEmail['ewright@bcmj.biz'] = 1;
    $asEmail['rhayashi@bcmj.biz'] = 1;
    $asEmail['ksimon@bcmj.biz'] = 1;
    $asEmail['jcartwright@bcmj.biz'] = 1;
    $asEmail['jbrown@bcmj.biz'] = 1;

    $asAliases = explode(',', CONST_EVENT_SYNC_ALIASES);
    foreach($asAliases as $nKey => $sPatern)
    {
      $asAliases[$nKey] = explode('=', $sPatern);
    }



    //===========================================================================
    //===========================================================================
    //treat emails

    $asCatchAll = explode('@', CONST_MAIL_IMAP_CATCHALL_ADDRESS);
    $sCatchAllDomain = $asCatchAll[1];

    if(!CONST_MAIL_IMAP_CATCHALL_ADDRESS)
    {
      $sAllowedDomain = $sCatchAllDomain;
    }
    else
      $sAllowedDomain = CONST_MAIL_IMAP_ACCEPTED_DOMAIN;

    $asAllowedDomain = explode(',', $sAllowedDomain);
    dump($asAllowedDomain);

    $nCount = 1;
    foreach($asFiltered as $oEmail)
    {
      //default folder the email will be moved to after treated
      $sMovedTo = 'error';

      //--------------------------------------------
      //1. check the sender is a slate email address
      echo('<hr />Found an unread email<br />');

      //From can be formatted as such  "Stephane Boudoux <sboudoux@slate.co.jp>", so we need to clean it

      $sFrom = $oEmail->from;
      if(strpos($sFrom, '<') !== false)
      {
        $asFrom = explode('<', $sFrom);
        $sFrom = trim(str_replace('>', '', $asFrom[1]));
      }

      dump('from: '.$sFrom);
      $asFrom = explode('@', $sFrom);
      $bFoundUser = isset($asEmail[$sFrom]);

      //check if there's an alternative allowed domain
      if(!$bFoundUser)
      {
        $sTestEmail = trim(str_replace($asAllowedDomain, 'slate.co.jp', $sFrom));
        echo '  ==> not a known email address, test alternative domains:  '.$sFrom.' ==> '.$sTestEmail.'<br /><br />';

        if(isset($asEmail[$sTestEmail]))
        {
          $bFoundUser = true;
          echo 'Found alternative user email ['.$sTestEmail.'] for ['.$sFrom.']... keep going <br />';
          $sFrom = $sTestEmail;
          break;
        }
      }


      if(!$bFoundUser)
      {
        echo 'Sender is not a slate email address, ignored (!!! should be removed FROM inbox !!!)<br />';
      }
      else
      {
        //dump($oEmail);
        $sHeader = imap_fetchheader($oMailBox, $oEmail->msgno);
        //dump($sHeader);

        //--------------------------------------------
        //2. We need to look into the header for the specific email address it was bcc to
        //smaller item being "ct1" -> 3 char
        $asMatches = array();
        preg_match_all('/(cc:|bcc:|to:) ([a-z0-9_ \-]{3,})@'.$sCatchAllDomain.'/i', $sHeader, $asMatches);
        if(empty($asMatches[2]))
        {
          echo 'No catchAll address in the header [/(cc:|bcc:|to:) ([a-z0-9_ \-]{3,})\@'.$sCatchAllDomain.'/i]<br />';
        }
        else
        {
          $asToAddress = $asMatches[2];
          foreach($asToAddress as $sTo)
          {
            //--------------------------------------------------
            //look into the email addresses if there's an item pattern
            $asItem = array();

            if(preg_match('/[0-9]{3}-[0-9]{3}__[a-z]{0,10}__[a-z]{0,10}__[0-9]{1,10}$/i', $sTo) === 1)
            {
              dump('found an item std format ['.$sTo.']');
              $asItem = explode('__', $sTo);
            }
            else
            {
              //look for a custom/user pattern ct15555   or   #ct15555   or cp65444 ...
              foreach($asAliases as $asPatern)
              {
                $asPatern[0] = addslashes($asPatern[0]);
                if(preg_match('/^'.$asPatern[0].'[0-9]{1,9}$/i', $sTo) === 1)
                {
                  dump('found an item using the pattern  '.$asPatern[0].' in ['.$sTo.']');
                  $sTo = preg_replace('/'.$asPatern[0].'([0-9]{1,9})$/i', $asPatern[1].'__$1', $sTo);
                  $asItem = explode('__', $sTo);
                  break;
                }
              }
            }
          }


          if(count($asItem) < 4)
          {
            echo 'not an uid__act__typ__pk format <br />';
            dump($asItem);
          }
          else
          {
            echo 'email match an item: '.$sTo;

            //--------------------------------------------------
            //3. exctract the mail content and create a note

            $asNote = array('item_uid' => $asItem[0], 'item_action' => $asItem[1], 'item_type' => $asItem[2], 'item_pk' => (int)$asItem[3],
              'date' => date('Y-m-d H:i:s', $oEmail->udate),  'type' => 'email_sent', 'title' => '', 'content' => '',
              'coworker' => array(), 'notify' => 0, 'add_calendar' => 0, 'custom_type' => 1,
              'reminder_date' => '', 'reminder_time' => '', 'reminder_before' => '', 'reminder_user' => '',  'reminder_message' => '',
              'loginfk' => (int)$asEmail[$sFrom]);

            $asSubject = imap_mime_header_decode($oEmail->subject);
            if(empty($asSubject))
            {
              $asNote['title'] = 'Email to '.$oEmail->to;
            }
            else
              $asNote['title'] = $this->_decodeMailContent($asSubject[0]->text, $asSubject[0]->charset);

            /*0: header
            echo '----------------------- body part 0';
            $sBody = imap_fetchbody($oMailBox, $oEmail->msgno, 0); dump($sBody);*/

            //body part 1 => mail content
            $sBody = imap_fetchbody($oMailBox, $oEmail->msgno, 1);

            /*2 html, attachements, all the dirty crap
            echo '-----------------------  body part 2';
            $sBody = imap_fetchbody($oMailBox, $oEmail->msgno, 2); dump($sBody);*/

            $asBody = imap_mime_header_decode($sBody);
            if(empty($asBody))
            {
              $asNote['body'] = 'Email to <'.$oEmail->to.'>';
            }
            else
            {
              $asNote['body'] = $this->_decodeMailContent($asBody[0]->text, $asBody[0]->charset);
              $asNote['body'] = str_replace("\n", '<br />', $asNote['body']);
            }



            $asNote['content'] = strip_tags($asNote['body'], '<br><br/><p><span>');
            //dump('ready to create a note ... ');
            //dump($asNote);

            $asResult = $this->_getEventSave(0, $asNote);
            if(isset($asResult['error']))
              assert('false; /* could not create the note '.$asResult['error'].' */');
            else
            {
              dump('Note inserted ... ');
              $sMovedTo = 'imported';
            }

          }
        }

        $nCount++;
        if($nCount > 100)
          break;
      }

      if($sMovedTo == 'imported')
      {
        imap_delete($oMailBox, $oEmail->msgno);
      }
      else
      {
        $bMoved = imap_mail_move($oMailBox, $oEmail->msgno, 'inbox/'.$sMovedTo);

        if(!$bMoved)
          assert('false; /* email could not be moved ['.$oEmail->msgno.' , inbox/'.$sMovedTo.'] */ ');
        else
          dump('Email #'.$oEmail->msgno.' moved to '.$sMovedTo);
      }
    }

    imap_setflag_full($oMailBox, $sMessageIds, '\\Seen', ST_UID);
    imap_close($oMailBox, CL_EXPUNGE);
    return true;
  }


   private function _decodeMailContent($psText, $psFormat)
   {
      switch($psFormat)
      {
        case 2:
          $psText = imap_binary($psText);
          break;
        case 3:
          $psText = imap_base64($psText);
          break;
        case 4:
          $psText = imap_qprint($psText);
          break;

        default:
          break;
      }
      return $psText;
    }

}
