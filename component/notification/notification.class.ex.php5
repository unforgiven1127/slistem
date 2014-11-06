<?php

/*
* DB info:    notification.delivered = 1 when:
* - mail sent successfully and no naggy
* - naggy link has been clicked
* - all naggy emails have been sent
* - if 2 errors occured (delivered = -1 + new error)
* - delivered = -2 if cancelled by user
*
*/

require_once('component/notification/notification.class.php5');

class CNotificationEx extends CNotification
{
  private $casCpParam = array();
  private $casComponent = array();
  private $coLogin = null;
  private $casNagFreq = array(null, '1h', '2h', '3h', '0.5d', '1d', '2d', '3d', '1w', '2w', '1m', '2m');
  private $casFormNagFreq = array('1h' => '1 hour', '2h' => '2 hours', '3h' => '3 hours', '0.5d' => 'half a day', '1d' => '1 day', '2d' => '2 days', '3d' => '3 days', '1w' => '1 week', '2w' => '2 weeks', '1m' => '1 month', '2m' => '2 months');
  private $casInitId = array();
  private $casSetting = array();

  public function __construct()
  {
    parent::__construct();

    $this->coLogin = CDependency::getCpLogin();

    if(!isset($_SESSION['notification_cp_list']))
    {
      $this->casComponent = CDependency::getComponentUidByInterface('notification_item');
      $_SESSION['notification_cp_list'] = $this->casComponent;
    }
    else
      $this->casComponent = $_SESSION['notification_cp_list'];


    return true;
  }

  //****************************************************************************
  //****************************************************************************
  // Interfaces and component settings
  //****************************************************************************
  //****************************************************************************


  public function getHtml()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_NOTIFY_TYPE_NAG:
        return $this->_getCancelNagMessage($this->cnPk);
        break;

      case CONST_NOTIFY_TYPE_NOTIFICATION:

        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return $this->_getReminderList();
            break;

          case CONST_ACTION_VIEW:
            return $this->_getReminderView();
            break;

        }
        break;
    }
    return '';
  }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_NOTIFY_TYPE_NOTIFICATION:

        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            $oPage = CDependency::getCpPage();
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getReminderList())));
            break;

          case CONST_ACTION_ADD:
            $oPage = CDependency::getCpPage();
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getReminderForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_getReminderSave($this->cnPk));
            break;

          case CONST_ACTION_VIEW:
            return json_encode($this->_displayReminder($this->cnPk));
            break;
       }
       break;


     case CONST_NOTIFY_TYPE_MESSAGE:

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
            $oPage = CDependency::getCpPage();
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getReminderForm($this->cnPk, true))));
            break;
       }
       break;

     case CONST_NOTIFY_TYPE_NAG:
       return json_encode($this->_cancelNotification($this->cnPk));
       break;
    }

    return ;
  }

  public function getCronJob()
  {
    $this->_executeCronAction();
    return '';
  }



  public function getUserNotification($pnPk = 0, $psDateStart = '', $psDateEnd = '', $pbDelivered = null, $psOrderByDistance = true, $pnLimit = 0, $pasFilter = array())
  {
    return $this->_getModel()->getUserNotification($pnPk, $psDateStart, $psDateEnd, $pbDelivered, $psOrderByDistance, $pnLimit, $pasFilter);
  }


  /**
   * Request an id to let you use the notification
   * $pasOption can coontains internal option such as type 'email'/'reminder'/'message' (as software only)
   *
   * @param array $pasSrcComponent
   * @param array $pasOption
   * @return string
   */
  public function initNotifier($pasSrcComponent = array(), $pasOption = array())
  {
    if(!assert('!empty($pasSrcComponent) && is_array($pasOption)'))
      return '';

    // TODO: check how many emails waiting
    // Load templates ...
    // connect to external soft/platform...
    // lots of options can be added

    $sId = uniqid();
    $this->casInitId[$sId] = $pasSrcComponent;
    $this->casSetting[$sId] = $pasOption;

    return $sId;
  }

  /**
   * dummy fct for testing purpose
   * @return boolean
   */
  public function sendNotification()
  {
    $oMail = CDependency::getComponentByName('mail');

    $oMail->createNewEmail();
    $oMail->setFrom(CONST_CRM_MAIL_SENDER, 'Slistem notification');

    $oMail->addRecipient('dcepulis@slate-ghc.com', 'stef');

    $oMail->send('Component "notification" ', 'a notification html', 'a notification txt');
    return true;
  }



  /**
   * Re-use  "addItemReminder", just changing the notification type on the way for the content of the email to be a bit different
   * @param type $psId
   * @param type $pnRecipientfk
   * @param type $pasItem
   * @param type $psMessage
   * @param type $psTitle
   * @return int
   */
  public function addItemMessage($psId, $pvRecipientfk, $pasItem, $psMessage = '', $psTitle = '', $pnNaggy = 0, $psNagFreq = '')
  {
    if(!isset($this->casInitId[$psId]) || empty($this->casInitId[$psId]))
    {
      assert('false; // adding item without initializing the id');
      return 0;
    }

    $this->casSetting[$psId]['type'] = 'email';
    $this->casInitId[$psId]['user_msg'] = $psMessage;
    return $this->addItemReminder($psId, $pvRecipientfk, $pasItem, $psMessage, $psTitle, date('Y-m-d H:i:s'), $pnNaggy, $psNagFreq);
  }

  public function addMessage($psId, $pvRecipientfk, $psMessage = '', $psTitle = '', $pnNaggy = 0, $psNagFreq = '')
  {
    if(!isset($this->casInitId[$psId]) || empty($this->casInitId[$psId]))
      return 0;

    $this->casSetting[$psId]['type'] = 'email';
    $this->casInitId[$psId]['user_msg'] = $psMessage;
    return $this->addReminder($psId, $pvRecipientfk, $psMessage, $psTitle, date('Y-m-d H:i:s'), $pnNaggy, $psNagFreq);
  }




  /**
   * manage the item links and content, then call addReminder below
   *
   * @param type $psId
   * @param type $pvRecipientfk
   * @param type $psMessage
   * @param type $psTitle
   * @param string $psDate
   * @param type $pnNaggy
   * @param type $psNagFreq
   * @param type $pbIsHtml
   * @return int
   */
  public function addItemReminder($psId, $pvRecipientfk, $pasItem, $psMessage = '', $psTitle = '', $psDate = null, $pnNaggy = 0, $psNagFreq = '')
  {
    if(!assert('!empty($psId) && (is_key($pvRecipientfk) || is_arrayOfInt($pvRecipientfk))'))
    {
      assert('false; /* debug =>  id: '.var_export($psId, true).', recipient: '.var_export($pvRecipientfk, true).' */');
      return 0;
    }

    if(!isset($this->casInitId[$psId]) || empty($this->casInitId[$psId]))
      return 0;

    if(!assert('is_cpValues($pasItem)'))
      return 0;

    $sUid = $pasItem[CONST_CP_UID];
    if(!in_array($sUid, $this->casComponent))
    {
      assert('false; //component ['.$sUid.'] doesn\'t have the notification interface.');
      return 0;
    }

    $oComponent = CDependency::getComponentByUid($sUid);
    $asItemData = $oComponent->getItemDescription($pasItem[CONST_CP_PK], $pasItem[CONST_CP_ACTION], $pasItem[CONST_CP_TYPE]);
    if(!assert('!empty($asItemData)'))
      return 0;

    if(!isset($asItemData[$pasItem[CONST_CP_PK]]))
    {
      assert('false; /* item description is invalid '.var_export($asItemData, true).' */');
      return 0;
    }

    $asItemData = $asItemData[$pasItem[CONST_CP_PK]];

    $sSender = $this->coLogin->getUserLink($this->coLogin->getuserPk());
    if(empty($sSender))
    {
      assert('false; //unrecognized sender.');
      return 0;
    }

    $this->casInitId[$psId]['user_msg'] = $psMessage;

    $sMessage = '<div style="border-left: 1px solid #888888; padding: 2px 10px; margin: 5px 0 15px 0; line-height: 20px;" >';
    $sMessage.= 'This concerns "'.$asItemData['link'].'"<br /><br />';

    if(!empty($asItemData['description']))
    {
      $sMessage.= $asItemData['description'];
    }

    $sMessage.= '</div>';


    //add a separator
    $sMessage.= '<div style="height: 12px; background-color: #ffffff;">&nbsp;</div>';

    if(!empty($psMessage))
    {
      $sMessage.= '<div style="border-left: 1px solid #888888; padding: 2px 10px; margin: 5px 0; line-height: 20px;" >';
      $sMessage.= '<b>Message</b>:<br/>'.$psMessage;
      $sMessage.= '</div>';
    }

    if(empty($asItemData['description']) && empty($psMessage))
    {
      $sMessage.= '<div style="border-left: 1px solid #888888; padding: 5px 10px; margin: 5px 0;" >';
      $sMessage.= '<br/><am>No message<em>';
      $sMessage.= '</div>';
    }

    $nNotificationPk = $this->addReminder($psId, $pvRecipientfk, $sMessage, $psTitle, $psDate, $pnNaggy, $psNagFreq);
    if(!assert('!empty($nNotificationPk)'))
    {
      assert('false; /* add reminder return ['.var_export($nNotificationPk, true).'] for  ['."$psId, $pvRecipientfk, (msg) , (title), $psDate, $pnNaggy, $psNagFreq".'] */ ');
      return 0;
    }
    //save notification link
    $asAdd = array_merge($pasItem, array('notificationfk' => $nNotificationPk, 'linked_to' => 'item'));
    $oDbResult = $this->_getModel()->add($asAdd, 'notification_link');
    if(!$oDbResult)
    {
      assert('false; /* add reminder link failed */ ');
      return 0;
    }

    return $nNotificationPk;
  }


  /**
   * !! Actually the generic method to send all kind of mail based notification (reminder/email/naggy messages)
   * Core method that sends emails to recipients
   *
   * @param type $psId
   * @param type $pvRecipientfk
   * @param type $psMessage
   * @param type $psTitle
   * @param string $psDate
   * @param type $pnNaggy
   * @param type $psNagFreq
   * @param type $pbIsHtml
   * @return int
   */
  public function addReminder($psId, $pvRecipientfk, $psMessage, $psTitle = '', $psDate = null, $pnNaggy = 0, $psNagFreq = null, $pbIsHtml = false)
  {
    if(!isset($this->casInitId[$psId]) || empty($this->casInitId[$psId]))
    {
      assert('false; // no reminder ID... ');
      return 0;
    }

    if(!assert('is_key($pvRecipientfk) || is_arrayOfInt($pvRecipientfk)'))
      return 0;

    if(!assert('!empty($psMessage)'))
      return 0;

    if(!assert('is_null($psDate) || is_datetime($psDate)'))
      return 0;

    if(!assert('is_integer($pnNaggy) && $pnNaggy >= 0 && in_array($psNagFreq, $this->casNagFreq)'))
      return 0;

    if(empty($psDate))
    {
      $psDate = date('Y-m-d', strtotime('+1 day')).' 08:00:00';
    }
    else
    {
      if(!CONST_DEV_SERVER && $psDate < date('Y-m-d H:i:s', strtotime('-1 hour')))
        return 0;
    }

    //check the reminder recipient
    $pvRecipientfk = (array)$pvRecipientfk;
    $asRecipient = $this->coLogin->getUserList($pvRecipientfk, false, true);
    if(empty($asRecipient) || count($pvRecipientfk) != count($asRecipient))
    {
      assert('false; //trying to set a reminder on an unknown user(s). ['.$pvRecipientfk.']');
      return 0;
    }

    if(!$pbIsHtml)
    {
      $psMessage = nl2br($psMessage);
    }


    //----------------------------------------------------------
    //Add entry in table notification
    set_array($this->casInitId[$psId]['user_msg'], '');

    $asAdd = array('date_created' => date('Y-m-d H:i:s'), 'creatorfk' => $this->coLogin->getUserPk(), 'date_notification' => $psDate,
        'content' => $this->casInitId[$psId]['user_msg'], 'message' => $psMessage, 'title' => $psTitle, 'message_format' => 'html', 'type' => 'reminder', 'delivered' => 0);

    if(!empty($this->casSetting[$psId]))
    {
      if(isset($this->casSetting[$psId]['type']) && !empty($this->casSetting[$psId]['type']))
        $asAdd['type'] = $this->casSetting[$psId]['type'];

      if(isset($this->casSetting[$psId]['format']) && !empty($this->casSetting[$psId]['format']))
        $asAdd['message_format'] = $this->casSetting[$psId]['format'];

      // TODO: add options form messages
    }

    $nNotificationPk = $this->_getModel()->add($asAdd, 'notification');
    if(!$nNotificationPk)
    {
      assert('false; // failed to create the notification.');
      return 0;
    }


    $sNotifType = $asAdd['type'];

    //Add a reference to the source component
    $asAdd = array_merge($this->casInitId[$psId], array('notificationfk' => $nNotificationPk, 'linked_to' => 'source'));
    $oDbResult = $this->_getModel()->add($asAdd, 'notification_link');
    if(!$oDbResult)
    {
      assert('false; // could save the source reference of the reminder.');
      return 0;
    }


    //Add entry in table notification_recipient
    $nNotificationPk = (int)$nNotificationPk;
    foreach($asRecipient as $asRecipeintData)
    {
      $asAdd = array('notificationfk' => $nNotificationPk, 'loginfk' => (int)$asRecipeintData['loginpk'], 'email' => $asRecipeintData['email']);
      $nPk = $this->_getModel()->add($asAdd, 'notification_recipient');
      if(!$nPk)
      {
        assert('false; // failed to create the notification recipient.');
        return 0;
      }
    }


    //Add entry in table notification_action
    $asAdd = array('notificationfk' => $nNotificationPk, 'type' => $sNotifType, 'naggy' => $pnNaggy,
        'naggy_frequency' => $psNagFreq, 'number_sent' => 0, 'date_last_action' => null, 'status' => 1);

    $nPk = $this->_getModel()->add($asAdd, 'notification_action');
    if(!$nPk)
    {
      assert('false; // failed to create the notification_action.');
      return 0;
    }

    //if the reminder is schedule in the next half hour, we don't wait for the cron and laucnh it now'
    if($psDate < date('Y-m-d H:i:s', strtotime('+ 30 minutes')))
      $this->_executeCronAction($nNotificationPk, true);

    return $nNotificationPk;
  }


  private function _executeCronAction($pnPk = 0, $pbManual = false)
  {
    /*
     * DB info:    notification.delivered = 1 when:
     * - mail sent successfully and no naggy
     * - naggy link has been clicked
     * - all naggy emails have been sent
     * - if 2 errors occured (delivered = -1 + new error)
     * - delivered = -2 if cancelled by user
     *
    */

    //We'd rather be 15 minutes early than 15minute late, right ?
    $sDate = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    //$sDate = date('Y-m-d H:i:s', strtotime('+1day +15 minutes'));
    $sNow = date('Y-m-d H:i:s');


    $oDbResult = $this->_getModel()->getNotificationDetails($pnPk, $sDate);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
    {
      if($pbManual)
        return false;
    }

    /*dump($sDate);
    dump($oDbResult->getAll());
    exit('....');*/


    $oMail = CDependency::getComponentByName('mail');
    $asUsers = $this->coLogin->getUserList(0, true, true);

    $message_array = array();

    //we have actions to check
    while($bRead)
    {
      $asData = $oDbResult->getData();
      $nActionPk = (int)$asData['notification_actionpk'];
      $bLastNaggy = false;
      $bExec = 1;

      // dump($asData);

      //check naggy actions
      // Naggy in progress: we have to "re-execute" the action, and update the entry (naggy -1 & last_update)
      if(!empty($asData['naggy']) && !empty($asData['date_last_action']) && $asData['date_last_action'] != '0000-00-00 00:00:00')
      {
        if(!$pbManual)
          echo 'Notification sent, nags in progress => last action: '.$asData['date_last_action'].' <br />';

        $sNagDate = $this->_getNextNagDate($asData);

        if($sNagDate < $sNow)
        {
          if(!$pbManual)
            echo 'Time for a nag !! Action to be executed on the '.$sNagDate.' <br />';

          if((int)$asData['naggy'] == 1)
            $bLastNaggy = true;

          //------------------------------------------
          //We've nagged the user, we log it in the db
          if($bExec)
          {
            $sUpdate = 'UPDATE notification_action
              SET date_last_action = "'.date('Y-m-d H:i:s').'", number_sent = (number_sent+1), naggy = (naggy-1), status = '.(int)$bExec.'
              WHERE notification_actionpk = '.$nActionPk;
            $this->_getModel()->ExecuteQuery($sUpdate);
          }
        }
        else
        {
          if(!$pbManual)
            echo ('#'.$nActionPk.' -  last action executed on: '.$asData['date_last_action'].' / nage freq: '.$asData['naggy_frequency'].' / next nag should occur on the '.$sNagDate);
        }
      }
      else
      {
        if(!$pbManual)
          echo 'Not a naggy message or first nag -> exec action now !! <br />';

        // not a naggy action OR first action of a naggy serie

        if($bExec != null)
        {
          //  execute action, then update (notification "delivered" [if no error of course])
          $sUpdate = 'UPDATE notification_action
            SET date_last_action = "'.date('Y-m-d H:i:s').'", number_sent = number_sent+1, status = '.(int)$bExec.'
            WHERE notification_actionpk = '.$nActionPk;
          $this->_getModel()->ExecuteQuery($sUpdate);
        }
      }


      // -------------------------------------------------------
      //manage errors (careful, $bExec initialized at null)
      if($bExec === false)
      {
        if((int)$asData['delivered'] == 0)
          $sUpdate = 'UPDATE notification SET delivered = -1 WHERE notificationpk = '.(int)$asData['notificationfk'];
        else
          $sUpdate = 'UPDATE notification SET delivered = 999 WHERE notificationpk = '.(int)$asData['notificationfk'];

        $this->_getModel()->ExecuteQuery($sUpdate);
      }

      // -------------------------------------------------------
      //check if we need to stop the action:
      if($bExec === true && (empty($asData['naggy']) || $bLastNaggy))
      {
        $sUpdate = 'UPDATE notification SET delivered = 1 WHERE notificationpk = '.$nActionPk;
        $this->_getModel()->ExecuteQuery($sUpdate);
      }

      $message_array[] = $asData;

      if(!$pbManual)
        echo '<hr />';

      $bRead = $oDbResult->readNext();
    }

    $bExec = $this->_executeAction($message_array, $oMail, $asUsers);
  }

  /**
   * Calculate the date of the next nag based of last action and frequency
   * @param type $pasAction
   * @return string
   */
  private function _getNextNagDate($pasAction)
  {
    //Check if it s time to relaunch a naggy mail
    $sLastAction = $pasAction['date_last_action'];
    if(empty($sLastAction) || $sLastAction == '0000-00-00 00:00:00')
      return '0000-00-00 00:00:00';

    $nLastActionTime = strtotime($sLastAction);

    $sFreq = $pasAction['naggy_frequency'];
    if(!in_array($sFreq, $this->casNagFreq))
    {
      assert('false; // nag freq is not valid');
      return (date('Y').+10).'-01-01';
    }


    //----------------------------------------------------------------
    //specific case strtotime can not manage
    if($sFreq == '0.5d')
    {
      // if last action was in the nmorning, next one will be at noon the same day
      if(date('H', $nLastActionTime) < 8)
        return date('Y-m-d', $nLastActionTime).' 11:45:00';

      // if last action was at noon, next one will be the mornong of the next day
      return date('Y-m-d', strtotime('+1 day', $nLastActionTime)).' 07:00:00';
    }


    //----------------------------------------------------------------
    // When frequency is hourly based, we remove 15minutes to the action time.
    // better receiving the email 15 minutes early than late
    if(in_array($sFreq, array('1h', '2h', '3h')))
    {
      $sFreq = str_replace('h', ' hour', $sFreq);
      $nTime = strtotime('+'.$sFreq, strtotime($sLastAction));
      $nTime = ($nTime - 900);

      return date('Y-m-d H', $nTime).':00:00';
    }


    //----------------------------------------------------------------
    // Other cases: receive message in the morning of the day/week/month
    $sFreq = str_replace('d', ' day', $sFreq);
    $sFreq = str_replace('w', ' week', $sFreq);
    $sFreq = str_replace('m', ' month', $sFreq);

    return date('Y-m-d', strtotime('+'.$sFreq, strtotime($sLastAction))).' 07:00:00';
  }


  private function _executeAction($pasAction, $poMail, $pasUsers)
  {
    if(!assert('is_array($pasAction) && !empty($pasAction) && !empty($poMail) && !empty($pasUsers)'))
      return null;

    if(empty($pasAction[0]['loginfk']))
    {
      assert('false; //no recipient for this action. ['.$pasAction[0]['loginfk'].']');
      return null;
    }

    $sRecipient = $this->coLogin->getUserNameFromData($pasUsers[$pasAction[0]['loginfk']], false, false);
    $sEmail = $pasUsers[$pasAction[0]['loginfk']]['email'];
    if(empty($sRecipient) || empty($sEmail))
    {
      assert('false; //no correct recipient found. ['.$action['loginfk'].' / '.$sRecipient.' / '.$sEmail.']');
      return false;
    }

    $sMessage = '<div style="font-family: verdana; font-size: 12px;">Dear ' . $sRecipient . ',<br /><br />';

    $oPage = CDependency::getCpPage();

    foreach ($pasAction as $action) {

      if(!isset($pasUsers[$action['loginfk']]))
      {
        dump('recipient inactive. ['.$action['loginfk'].']. We need to cancel the reminders.');

        $asUpdate = array('status' => -2, 'date_last_action' => date('Y-m-d H:i:s'));
        $this->_getModel()->update($asUpdate, 'notification_action', 'notification_actionpk = '.(int)$action['notification_actionpk']);

        /*TODO
        $asUpdate = array('status' => -2, 'date_last_action' => date('Y-m-d H:i:s'));
        $this->_getModel()->update($asUpdate, 'notification_action', 'notification_actionpk = '.(int)$pasAction['notification_actionpk']);*/
        return true;
      }

      $nNaggy = (int)$action['naggy'];


      //--------------------------------------------------------
      //--------------------------------------------------------
      //start creating the mail content

      //-------------------------------
      // build the message
      if($action['type'] == 'email')
      {
        $sSubject = CONST_APP_NAME;

        if($action['creatorfk'] != $action['loginfk'])
        {
          $sUser = $this->coLogin->getUserLink((int)$action['creatorfk']);

          $sDate = date('l jS F', strtotime($action['date_notification']));
          $sTime = date('H:i', strtotime($action['date_notification']));
          $sMessage.= $sUser.' has sent you a request on '.$sDate.' at '.$sTime.'.<br /><br />';
        }

        $sMessage.= '<div style="padding: 10px; border: 1px solid #f0f0f0; line-height: 20px; background-color: #f2f2f2;">';
        $sMessage.= $action['message'].'</div>';
      }
      else
      {
        //reminder
        $sSubject = CONST_APP_NAME.' reminder';

        //dump($pasAction['creatorfk']);
        //dump($pasAction['loginfk']);
        if($action['creatorfk'] == $action['loginfk'])
        {
          $sMessage.= 'You\'ve set a reminder for ';
          $sUser = '';
        }
        else
        {
          $sUser = $this->coLogin->getUserLink((int)$action['creatorfk']);
          $sMessage.= $sUser.' has set a reminder for you for ';
        }

        $nNotif = strtotime($action['date_notification']);
        $sToDay = date('Y-m-d');
        $sYesterday = date('Y-m-d', strtotime('-1 day'));
        $sDay = date('Y-m-d', $nNotif);

        if($sDay == $sToDay)
          $sMessage.= '&nbsp;&nbsp;&nbsp;<b>today</b>';
        elseif($sDay == $sYesterday)
          $sMessage.= '&nbsp;&nbsp;&nbsp;<b>yesterday</b>';
        else
          $sMessage.= 'the&nbsp;&nbsp;&nbsp;<b>'.date('jS \o\f F', strtotime($action['date_notification'])).'</b>';

        $sMessage.= '&nbsp;&nbsp;at&nbsp;&nbsp;<b>'.date('H:i a', $nNotif).'</b>.';

        $sDate = date('Y-m-d \a\t H:i', strtotime($action['date_created']));
        $sMessage.= '<br /><span style="font-style: italic; color:#666;">Reminder created on the '.$sDate.'</span>.';

        $sMessage.= '<br /><br />';
        $sMessage.= '<div style="padding: 10px; border: 1px solid #f0f0f0; line-height: 20px; background-color: #f2f2f2;">';


        //sending the message in html, so if its not a html format i convert it
        if($action['message_format'] != 'html')
        {
          $action['message'] = nl2br($action['message'], true);
        }

        $sMessage.= $action['message'].'</div>';
      }


      if(!empty($action['title']))
        $sSubject.= ' - '.mb_strimwidth($action['title'], 0, 65, '...');


      //-------------------------------
      // add a message for naggy messages
      if($nNaggy > 1)
      {
        $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_DELETE, CONST_NOTIFY_TYPE_NAG, (int)$action['notificationpk']);
        $sMessage.= '<br /><br /><span style="font-size: 10px; font-style: italic;">This is a "naggy" message. It means this email will be sent to you again '.$nNaggy.' more time'.(($nNaggy > 1)? 's' : '').' ('.$action['naggy_frequency'].' interval).<br />  ';
        $sMessage.= 'To confirm you\'ve been notified and stop receiving this message, please click <a href="'.$sURL.'">here</a>.</span> <br /><br />';
      }
      elseif($nNaggy == 1)
      {
        //last action
        $sSubject.= '  --  Last notice --';
        $sMessage.= '<br /><br /><br /><span style="font-size: 10px; font-style: italic; color: #555555;"><b>Last notice : </b> you\'ve been reminded '.((int)$action['number_sent']+1).' time(s), this is the last email you\'ll receive.</span>';
      }

      $sMessage .= '<div style="width: 100%; height: 40px;"></div>';
      $sMessage .= ' ';

    }

    $sMessage.= '</div>';
    //-------------------------------
    //send the email
    $poMail->createNewEmail();

    //add a reply-to if the reminder comes from someone else
    if(!empty($sUser))
    {
      $sReply = $pasUsers[$pasAction[0]['creatorfk']]['email'];
      $poMail->setReplyTo($sReply, $this->coLogin->getUserNameFromData($pasUsers[$pasAction[0]['creatorfk']], false, true));
    }

    //We manage the replyTo above, so we don't add the sender automatically
    $poMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM, false);
    $poMail->addRecipient($sEmail, $sRecipient);

    $nSent = $poMail->send($sSubject, $sMessage, strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), "\n", $sMessage)));
    if($nSent)
      return true;

    assert('false; /* could not sent a email to ['.$sRecipient.', '.$sEmail.'] '.$poMail->getErrors(true).' */');
    return false;
  }


  private function _getCancelNagMessage($pnNotificationPk)
  {
    $oHTML = CDependency::getCpHtml();

    $sHTML = $oHTML->getCR(2);
    $sHTML.= $oHTML->getTitle('Cancel reminder', 'h2', true);
    $sHTML.= $oHTML->getCR(2);

    if(!assert('is_key($pnNotificationPk)'))
      return $sHTML.$oHTML->getBlocMessage('Sorry, it looks like some parameters are missing');

    $oDbResult = $this->_getModel()->getNotificationDetails($pnNotificationPk, '', null);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return $sHTML.$oHTML->getBlocMessage('It looks like this reminder has been deleted.');


    // ===============================================================
    // Check that the logged user is the reminder recipient
    $nRecipient = (int)$oDbResult->getFieldValue('loginfk');
    if(!assert('is_key($nRecipient)'))
      return $sHTML.$oHTML->getBlocMessage(__LINE__.' - Sorry, it looks like something went wrong.');

    $oLogin = CDependency::getCpLogin();
    if($nRecipient != $oLogin->getUserPk())
      return $sHTML.$oHTML->getBlocMessage(__LINE__.' - Sorry, you can\'t access this page.');
    // ===============================================================


    $nDelivered = (int)$oDbResult->getFieldValue('delivered');

    $sHTML.= 'Reminder details :';

    $sHTML.= $oHTML->getBlocStart('', array('style' => 'margin: 10px 0 25px 25px; padding: 15px 5px; border: 1px solid #ddd; line-height: 20px; width: 600px;'));
    $sHTML.= 'Original notification: '.$oDbResult->getFieldValue('date_notification');
    $sHTML.= $oHTML->getCR();
    $sHTML.= 'Notifications: '.$oDbResult->getFieldValue('number_sent').' time(s)';
    $sHTML.= $oHTML->getCR();
    $sHTML.= 'Message: '.$oDbResult->getFieldValue('message');
    $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocStart('', array('style' => 'margin: 10px 0 25px 25px; padding: 15px 5px; border: 1px solid #ddd; line-height: 20px; width: 600px;'));


    if($nDelivered == 1)
      return $sHTML.'This reminder has <span style="font-style: italic; font-weight: bold;">already been cancelled</span>. You won\'t receive any more emails about it.'.$oHTML->getBlocEnd();


    $bUpdated = $this->_getModel()->update(array('delivered' => 1), 'notification',  'notificationpk = '.$pnNotificationPk);
    if($bUpdated)
    {
      return $sHTML.'<b style="color: #2A6991;">Reminder updated successfully</b>. You won\'t receive any more emails about it.'.$oHTML->getBlocEnd();
    }


    return $sHTML.'Sorry, we couldn\'t update the reminder.'.$oHTML->getBlocEnd();
  }

  private function _cancelNotification($pnNotificationPk)
  {
    if(!assert('is_key($pnNotificationPk)'))
      return array('error' => 'Invalid parameters.');

    $oDbResult = $this->_getModel()->getNotificationDetails($pnNotificationPk, '', null);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => 'Could not find the message(s).');


    $bUpdated = $this->_getModel()->update(array('delivered' => -2), 'notification',  'notificationpk = '.$pnNotificationPk);
    if($bUpdated)
      return array('notice' => 'All notifications cancelled');

    return array('error' => 'Could not cancel the message(s).');
  }


  /**
   * Cancel the reminders linked to a specic item described in $pasItem (notification_link "source")
   *
   * @param array $pasItem
   * @return integer
   */
  public function cancelNotification($pasItem)
  {
    if(!assert('is_cpValues($pasItem)'))
      return 0;

    $sWhere = 'linked_to = "source" AND cp_uid = "'.$pasItem[CONST_CP_UID].'" AND cp_action = "'.$pasItem[CONST_CP_ACTION].
            '" AND cp_type = "'.$pasItem[CONST_CP_TYPE].'" AND cp_pk = "'.$pasItem[CONST_CP_PK].'" ';

    $oDbResult = $this->_getModel()->getByWhere('notification_link', $sWhere, 'notificationfk');
    if(!$oDbResult)
      return 0;

    $bRead = $oDbResult->readFirst();
    $anNotification = array();
    while($bRead)
    {
      $anNotification[] = (int)$oDbResult->getFieldvalue('notificationfk');
      $bRead = $oDbResult->readNext();
    }

    if(empty($anNotification))
      return 0;

    $sPks = implode(',', $anNotification);

    $bResult = $this->_getModel()->update(array('delivered' => 2), 'notification', 'notificationpk IN ('.$sPks.') AND delivered  < 1');
    if(!assert('$bResult === true'))
      return 0;

    $bResult = $this->_getModel()->update(array('status' => 2), 'notification_action', 'notificationfk IN ('.$sPks.') AND status < 1');
    if(!assert('$bResult === true'))
      return 0;

    return count($anNotification);
  }






  // =====================================================================================================
  // =====================================================================================================
  // user interface

  /**
   * Display the list of reminders in 3 tabs
   * @param boolean $pbInJax
   * @return string html
  */
  private function _getReminderList($pbInJax = true)
  {
    $sNow = date('Y-m-d H:i:s');
    $sToday = date('Y-m-d');

    $sFilterDate = getValue('filter_date');
    if(!empty($sFilterDate) && is_date($sFilterDate))
    {
      $sDate = date('Y-m-d', strtotime('-1 weeks', strtotime($sFilterDate)));
      $sDateEnd = date('Y-m-d', strtotime('+3 weeks', strtotime($sFilterDate)));
      $sDatePicker = $sFilterDate;
      $bScrollTopDate = true;
    }
    else
    {
      $sDate = date('Y-m-d', strtotime('-6 weeks'));
      $sDateEnd = date('Y-m-d', strtotime('+6 weeks'));
      $sDatePicker = $sToday;
      $bScrollTopDate = false;
    }

    $sTonight = $sToday.' 23:59:59';
    $sToday = $sToday.' 00:00:00';
    $sTomorrow = date('Y-m-d', strtotime('+1 day')).'23:59:59';
    $sSoon = date('Y-m-d H:i:s', strtotime('+3 hours'));
    $nCurrentuser = $this->coLogin->getUserPk();
    $bSaveTodaysReminder = (!isset($_SESSION['reminder_today']));


    if(!$pbInJax)
    {
      $sMainMax = '800px';
      $sSideMax = '240px';
    }
    else
    {
      $sMainMax = '';
      $sSideMax = '';
    }

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssfile($this->getResourcePath().'css/notification.css');



    $sDate.= ' 00:00:00';
    $sDateEnd.= ' 23:59:59';

    //search and filters management
    $asSearchWord = array();
    $sSearchWord = getValue('filter_content');

    $sWord = preg_replace('/[^0-9]/', '', $sSearchWord);
    if(is_numeric($sWord))
      $asSearchWord['pk'] = (int)$sWord;
    else
      $asSearchWord['content'] = $sSearchWord;

    if(!empty($this->cnPk))
    {
      //find the date of the passed notification
      //$asSearchWord['pk'] = $this->cnPk;

      $oDbResult = $this->_getModel()->getNotificationDetails($this->cnPk);
      $bRead = $oDbResult->readFirst();
      if($bRead)
      {
        $nTime = strtotime($oDbResult->getFieldValue('date_notification'));
        $sDate = date('Y-m-d', $nTime);
        $sDateEnd = date('Y-m-d', strtotime('+1 week', $nTime)).' 23:59:59';
      }
    }


    $sHTML = $oHTML->getBlocStart('', array('class' => 'reminderListContainer'));
    $sHTML.= $oHTML->getTitle('Reminders list between <span>'.substr($sDate, 0, 10).'</span> and <span>'.substr($sDateEnd, 0, 10).'</span>', 'h3', true);
    $sHTML.= $oHTML->getCR(2);

    $oDbResult = $this->_getModel()->getUserNotification($nCurrentuser, $sDate, $sDateEnd, null, true, 0, $asSearchWord);
    if(!$oDbResult)
      return $sHTML.$oHTML->getBlocmessage('No reminders found.');

    $bRead = $oDbResult->readFirst();

    $asReminder = array('mine' => array(), 'created' => array(), 'important' => array());
    while($bRead)
    {
      $asTmp = $oDbResult->getData();
      $bCreator = ($asTmp['creatorfk'] == $nCurrentuser);
      $bMyReminder = ($asTmp['loginfk'] == $nCurrentuser);

      //formatting data
      $asTmp['date_notification'] = substr($asTmp['date_notification'], 0, 16);

      //trying to find a reminder on the date searched by the user to stick an anchor on it
      if(substr($asTmp['date_notification'], 0, 10) == $sDatePicker)
      {
        $asTmp['date_notification'] = '<span class="date_anchor">'.$asTmp['date_notification'].'</span>';
      }

      //---------------------------------------
      //build the row title base on notification title, original content or generated email content
      if(empty($asTmp['title']))
        $sSeparator = ' ';
      else
        $sSeparator = ' - ';

      if(strlen($asTmp['title']) < 50)
      {
        if(empty($asTmp['content']))
          $asTmp['title'].= $sSeparator.$asTmp['message'];
        else
          $asTmp['title'].= $sSeparator.$asTmp['content'];
      }


      $asTmp['title'] = mb_strimwidth(trim(strip_tags($asTmp['title'])), 0, 60, '...');

      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_NOTIFY_TYPE_NOTIFICATION, (int)$asTmp['notificationpk']);
      $asTmp['title'] = $oHTML->getLink($asTmp['title'], 'javascript:;', array('class' => 'reminder_view', 'onclick' => '
        var oConf = goPopup.getConfig();
        oConf.width = 800; oConf.height = 600;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); '));

      $asTmp['title'].= $oHTML->getLink('&nbsp', 'javascript:;', array('class' => 'reminder_view_link', 'onclick' => '
        var oConf = goPopup.getConfig();
        oConf.width = 800; oConf.height = 600;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); '));


      //---------------------------------------

      $asTmp['status'] = $asTmp['delivered'].'|'.$asTmp['status'].'|'.$asTmp['number_sent'].'/'.$asTmp['naggy'].'*'.$asTmp['naggy_frequency'];
      switch($asTmp['delivered'])
      {
        case 0: $asTmp['status'] = 'scheduled'; break;
        case 1: $asTmp['status'] = 'done'; break;
        case -2: $asTmp['status'] = 'canceled'; break;
        case -1: $asTmp['status'] = 'error'; break;

        default: $asTmp['status'] = ''; break;
      }



      unset($asTmp['message']);


      //ordering reminders in an array
      if($bMyReminder)
      {
        if($bCreator)
          $asTmp['created'] = 'me, the '.date('d-M-y', strtotime($asTmp['date_created']));
        else
          $asTmp['created'] = $oLogin->getUserLink((int)$asTmp['creatorfk'], true).' the '.date('d-M-y', strtotime($asTmp['date_created']));

        if($asTmp['date_notification'] > $sNow)
        {
          $asReminder['mine']['future'][] = $asTmp;
          //dump('reminder for me... date['.$asTmp['date_notification'].'] saved in FUTURE');
        }
        else
        {
          $asReminder['mine']['past'][] = $asTmp;
          //dump('reminder for me... date['.$asTmp['date_notification'].'] saved in PAST');
        }

        //saving the important ones:
        //saving tomorrow's, today's and "soon" reminders to be displayed on the right side
        if($bSaveTodaysReminder && !$asTmp['delivered'] && $asTmp['date_notification'] >= $sNow && $asTmp['date_notification'] <= $sTomorrow)
        {
          $sId = $asTmp['date_notification'].'#'.$asTmp['notificationfk'];

          $sClass = '';
          if($asTmp['date_notification'] > $sTonight)
            $sClass = 'tomorrow';
          elseif($asTmp['date_notification'] > $sNow && $asTmp['date_notification'] <= $sSoon)
            $sClass = 'soon';
          else
            $sClass = 'today';

          $asReminder['important'][$sClass][$sId] = '<span class="'.$sClass.'">'.date('H:i', strtotime($asTmp['date_notification'])).' - '.strtolower(mb_strimwidth($asTmp['title'], 0, 25, '...')).'</span>';
        }
      }
      else
      {
        $asTmp['created'] = $oLogin->getUserLink((int)$asTmp['loginfk'], true).' the '.date('d-M-y', strtotime($asTmp['date_created']));
        $asReminder['created'][] = $asTmp;
      }


      $bRead = $oDbResult->readNext();
    }

    //----------------------------------------------------------------------------
    //First time the page is loaded (on today's date) we save the data in an array
    // to keep it all the time there.
    if($bSaveTodaysReminder)
    {
      $_SESSION['reminder_today'] = $asReminder['important'];
    }
    else
    {
      $asReminder = array_merge_recursive($asReminder, $_SESSION['reminder_today']);
    }
    //----------------------------------------------------------------------------


    //----------------------------------------------------------------------------
    //Prepare the array for get Tabs
    $asTabs = array();
    $sTabSelected = '';

    //incoming reminders
    if(!empty($asReminder['mine']['future']) || (empty($asReminder['mine']['past']) && empty($asReminder['created'])))
      $sTabSelected = 'incoming';

    if(!empty($asReminder['mine']['future']))
    {
      $sTabContent = $this->_getReminderTabList($asReminder['mine']['future']);
    }
    else
    {
      $sTabContent = $oHTML->getBlocMessage('No reminder found.');
    }
    $asTabs[] = array('label' => 'incoming', 'title' => 'Incoming reminders', 'content' => $sTabContent);


    //past reminders
    if(empty($sTabSelected) && !empty($asReminder['mine']['past']))
      $sTabSelected = 'past';

    if(!empty($asReminder['mine']['past']))
    {
      $sTabContent = $this->_getReminderTabList($asReminder['mine']['past']);
    }
    else
    {
      $sTabContent = $oHTML->getBlocMessage('No reminder found.');
    }
    $asTabs[] = array('label' => 'past', 'title' => 'Past reminders', 'content' => $sTabContent);


    //reminders created for others.
    if(empty($sTabSelected) && !empty($asReminder['created']))
      $sTabSelected = 'created';

    if(!empty($asReminder['created']))
    {
      $sTabContent = $this->_getReminderTabList($asReminder['created'], false);
    }
    else
    {
      $sTabContent = $oHTML->getBlocMessage('No reminder found.');
    }
    $asTabs[] = array('label' => 'other', 'title' => 'Created for others', 'content' => $sTabContent);


    //---------------------------------------------------------------------------
    //---------------------------------------------------------------------------
    //reminder formatted and sorted, start creating the page

    //split the page in 2:
    //left section containing 3 tabs (incoming, past, the one i've created for others
    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminderListLeft', 'style' => 'float: left; width: 77%; min-height:450px;  max-width: '.$sMainMax.';'));
    $sHTML.= $oHTML->getTabs('reminder_tabs', $asTabs, $sTabSelected);
    $sHTML.= $oHTML->getBlocEnd();


    //Right section containing filtering, notification and actions
    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminderListRight', 'style' => 'max-width: '.$sSideMax.'px; '));


    //a calendar reloading the popup based on the selected date
    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_NOTIFY_TYPE_NOTIFICATION);
    $sId = 'datepicker_'.uniqid();
    $sHTML.= '<script>
    $("#'.$sId.'").datepicker(
    {
      dateFormat: "yy-mm-dd",
      defaultDate: "'.$sDatePicker.'",
      onSelect: function(sDate, oPicker)
      {
        var sUrl = "'.$sURL.'"+ "&filter_date="+sDate;
        var oConf = goPopup.getConfig();
        oConf.width = 1080;
        oConf.height = 725;

        goPopup.removeLastByType(\'layer\');
        goPopup.setLayerFromAjax(oConf, sUrl);
      }
     }); ';

    if($bScrollTopDate)
    {
      $sHTML.= '
        if($(\'.date_anchor:visible\').length)
        {
          /*alert($(\'.date_anchor:visible:first\').offset().top);
          alert($(\'#reminder_tabs_contents div.tplListContainer:visible\').offset().top);*/

          fTop = $(\'.date_anchor:visible:first\').offset().top - $(\'#reminder_tabs_contents div.tplListContainer:visible\').offset().top - 25;
          //alert(fTop);
          $(\'#reminder_tabs_contents div.tplListContainer:visible\').scrollTop(fTop);
          $(\'.date_anchor:visible:first\').click();
        }';
    }

    $sHTML.= ' </script>';

    if(!empty($sSearchWord) || !empty($sFilterDate))
    {
       $sHTML.= '<br />
        <a href="javascript:;" style="margin: 0 0 15px 0; display: block;" onclick="
        var sUrl = \''.$sURL.'\'+ \'&filter_content=&filter_date=\';
        var oConf = goPopup.getConfig();
        oConf.width = 1080;
        oConf.height = 725;

        goPopup.removeLastByType(\'layer\');
        goPopup.setLayerFromAjax(oConf, sUrl);
        ">Clear filter</a>';
      }


      if(empty($sSearchWord))
        $sSearchWord = 'search...';

      $sHTML.= $oHTML->getBloc('', '<input id="reminder_filter" type="text" default-value="search..." value="'.$sSearchWord.'" style="font-style: italic; color: #999;"
        onfocus="if($(this).val() == $(this).attr(\'default-value\')){  $(this).val(\'\'); }"
        onblur=" if($(this).val().trim().length == 0){  $(this).val($(this).attr(\'default-value\')); }"
        />
        <a href="javascript:;"
        onclick="
          var sSearchTerm = $(\'#reminder_filter\').val();
          sSearchTerm = sSearchTerm.trim();

          var sDefault = $(\'#reminder_filter\').attr(\'default-value\');

          if(sSearchTerm == sDefault)
          {
            alert(\'Please input the search string. (min 2 characters) \');
            return false;
          }

          var sUrl = \''.$sURL.'\'+ \'&filter_content=\'+encodeURI(sSearchTerm);
          var oConf = goPopup.getConfig();
          oConf.width = 1080;
          oConf.height = 725;

          goPopup.removeLastByType(\'layer\');
          goPopup.setLayerFromAjax(oConf, sUrl);
          "><b>Go</b></a><br /><br />');


      //add the date picker here
      $sHTML.= $oHTML->getBloc($sId, '', array('style' => 'width: 235px; overflow: hidden;'));

      $sHTML.= '<br /><br />';

      //Today and coming soon reminder
      if(isset($asReminder['important']))
      {
        $nDisplayed = 0;
        $nMaxDisplayed = 8;
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'incoming_reminder'));

        if(isset($asReminder['important']['soon']))
        {
          $sHTML.= '<div class="reminderListRight_title soon">SOON</div><div class="reminder_bloc">';
          ksort($asReminder['important']['soon']);
          foreach($asReminder['important']['soon'] as $sReminder)
          {
            if($nDisplayed >= $nMaxDisplayed)
              break;

            $sHTML.= $sReminder.'<br />';
            $nDisplayed++;
          }
          $sHTML.= '</div>';
        }

        if(isset($asReminder['important']['today']) && $nDisplayed < $nMaxDisplayed)
        {
          if(isset($asReminder['important']['soon']))
            $sHTML.= '<div class="reminderListRight_title today">LATER TODAY</div><div class="reminder_bloc">';
          else
            $sHTML.= '<div class="reminderListRight_title today">TODAY</div><div class="reminder_bloc">';

          ksort($asReminder['important']['today']);
          foreach($asReminder['important']['today'] as $sReminder)
          {
            if($nDisplayed >= $nMaxDisplayed)
              break;

            $sHTML.= $sReminder.'<br />';
            $nDisplayed++;
          }
          $sHTML.= '</div>';
        }

        if(isset($asReminder['important']['tomorrow']) && $nDisplayed < $nMaxDisplayed)
        {
          $sHTML.= '<div class="reminderListRight_title tomorrow">TOMORROW</div><div class="reminder_bloc">';
          ksort($asReminder['important']['tomorrow']);
          foreach($asReminder['important']['tomorrow'] as $sReminder)
          {
            if($nDisplayed >= $nMaxDisplayed)
              break;

            $sHTML.= $sReminder.'<br />';
            $nDisplayed++;
          }
          $sHTML.= '</div>';
        }

        $sHTML.= $oHTML->getBlocEnd();

        $sHTML.= '<br /><br />';
      }

      //actions
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_NOTIFY_TYPE_NOTIFICATION);
      $sHTML.= $oHTML->getBlocStart();
      $sHTML.= ' - <a href="javascript:;" onclick="
        var oConf = goPopup.getConfig();
        oConf.height = 500;
        oConf.width = 850;
        oConf.modal = true;
        goPopup.removeLastByType(\'layer\');
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">Add a new reminder</a><br />';
      $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }


  private function _getReminderTabList($pasReminder, $pbMine = true)
  {
    if(empty($pasReminder))
      return '';

    $oHTML = CDependency::getCpHtml();

    //initialize the template
    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

    //get the config object for a specific template (contains default value so it works without config)
    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');

    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(false);

    $oConf->addColumn('Reminder date', 'date_notification', array('width' => 125, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Description', 'title', array('width' => 430));
    if($pbMine)
      $sTitle = 'Created by';
    else
      $sTitle = 'Created for';

    $oConf->addColumn($sTitle, 'created', array('width' => 125, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Status', 'status', array('width' => 95, 'sortable'=> array('javascript' => 1)));


    return $oTemplate->getDisplay($pasReminder);
  }



  private function _displayReminder($pnReminderPk)
  {
    if(!assert('is_key($pnReminderPk)'))
      return array('error' => __LINE__.' - Could not find data');


    $oDbResult = $this->_getModel()->getNotificationDetails($pnReminderPk, '', null);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => __LINE__.' - Could not find data');

    $oLogin = CDependency::getCpLogin();
    $oHTML = CDependency::getCpHtml();
    $asData = $oDbResult->getData();

    $sHTML = $oHTML->getTitle('Reminder / messsge details');
    $sHTML.= $oHTML->getCR();


    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
      $sHTML.= $oHTML->getBloc('', 'Scheduled', array('class' => 'reminder_detail_label'));
      $sHTML.= $oHTML->getBloc('', 'on the '.$asData['date_notification'], array('class' => 'reminder_detail_value'));
    $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
      $sHTML.= $oHTML->getBloc('', 'Recipient', array('class' => 'reminder_detail_label'));
      $sHTML.= $oHTML->getBloc('', $oLogin->getUserLink((int)$asData['loginfk']), array('class' => 'reminder_detail_value'));
    $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
      $sHTML.= $oHTML->getBloc('', 'Created by', array('class' => 'reminder_detail_label'));
      $sHTML.= $oHTML->getBloc('', $oLogin->getUserLink((int)$asData['creatorfk']).'on the '.$asData['date_created'], array('class' => 'reminder_detail_value'));
    $sHTML.= $oHTML->getBlocEnd();


    if(!empty($asData['cp_uid']))
    {
      $oComponent = CDependency::getComponentByUid($asData['cp_uid']);
      $asItem = $oComponent->getItemDescription((int)$asData['cp_pk'], $asData['cp_action'] = '', $asData['cp_type']);

      if(!empty($asItem))
      {
        $asItem = @$asItem[$asData['cp_pk']];

        if(!empty($asItem['link_popup']))
        {
          $sLink = $asItem['link_popup'];
        }
        elseif(!empty($asItem['link']))
        {
          $sLink = $asItem['link'];
        }
        else
          $sLink = $oHTML->getLink($asItem['label'], $asItem['url'].'&pg=normal', array('target' => '_blank'));

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
        $sHTML.= $oHTML->getBloc('', 'Related to', array('class' => 'reminder_detail_label'));
        $sHTML.= $oHTML->getBloc('', $sLink, array('class' => 'reminder_detail_value'));
        $sHTML.= $oHTML->getBlocEnd();
      }
    }


    if($asData['naggy'] || $asData['number_sent'])
    {
      $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
        $sHTML.= $oHTML->getBloc('', 'Sticky', array('class' => 'reminder_detail_label'));
        $sHTML.= $oHTML->getBloc('', $asData['naggy'].' / '.$asData['naggy_frequency'].' / '.$asData['number_sent'], array('class' => 'reminder_detail_value'));
      $sHTML.= $oHTML->getBlocEnd();
    }

    if(!empty($asData['title']))
    {
      $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
        $sHTML.= $oHTML->getBloc('', 'Title', array('class' => 'reminder_detail_label'));
        $sHTML.= $oHTML->getBloc('', $asData['title'], array('class' => 'reminder_detail_value'));
      $sHTML.= $oHTML->getBlocEnd();
    }

    $sMessage = str_ireplace(array('<br />', '<br/>', '<br>'), "\n", $asData['message']);
    $sMessage = str_ireplace('<a ', '<b><a ', $sMessage);
    $sMessage = str_ireplace('</a>', '</a></b>', $sMessage);
    $sMessage = strip_tags($sMessage, '<b></b>');
    $sMessage = nl2br($sMessage, true);

    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row reminder_detail_message_row'));
      $sHTML.= $oHTML->getBloc('', 'Message', array('class' => 'reminder_detail_label'));
      $sHTML.= $oHTML->getBloc('', $sMessage.$oHTML->getFloatHack(), array('class' => 'reminder_detail_value reminder_detail_message'));
    $sHTML.= $oHTML->getBlocEnd();

    if($asData['delivered'] == 0 && ($this->coLogin->isAdmin() || $asData['loginfk'] == $this->coLogin->getUserPk()))
    {
      $sId = uniqid();
      $sURL =  CDependency::getCpPage()->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_NOTIFY_TYPE_NAG, (int)$asData['notificationpk']);
      $sOnclick = $oHTML->getAjaxJs($sURL, '', '', '', '', '', ' $(\'#'.$sId.'\').remove(); ');
      $sLink = $oHTML->getLink('Cancel this reminder.', 'javascript:;', array('onclick' => 'if(window.confirm(\'All the scheduled emails will be cancel. Are you sure ?\')){'.$sOnclick.'} '));

      $sHTML.= $oHTML->getBlocStart($sId, array('class' => 'reminder_detail_row', 'style' => 'margin-top: 25px; position: relative;'));
      $sHTML.= $oHTML->getBloc('', '&nbsp;', array('class' => 'reminder_detail_label'));
      $sHTML.= $oHTML->getBloc('', 'No need to be reminded anymore? '.$sLink, array('class' => 'reminder_detail_value'));
      $sHTML.= $oHTML->getBlocEnd();
    }



    $sHTML.= $oHTML->getBlocStart('', array('class' => 'reminder_detail_row'));
      $sHTML.= $oHTML->getBlocStart('', array('class' => 'button'));
      $sHTML.= '<input type="button" value="close" />';
      $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();

    return array('data' =>$sHTML);
  }


  private function _getReminderForm($pnReminderPk = 0, $pbAsMessage = false)
  {
    if(!assert('is_integer($pnReminderPk)'))
      return array('error' => 'Sorry, an error occured.');

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/notification.css');

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $nRecipient = 0;

    //=======================================================================
    //We receive a string to describe the item to load in the cp_item_selector
    //check it and check item
    $sCpItem = getValue('cp_item_selector');
    $sCheckedValue = '';
    if(!empty($sCpItem))
    {
      $asItem = explode('|@|', $sCpItem);
      if(count($asItem) == 4)
      {
        //check the item passed in parameter exists
        $oComponent = CDependency::getComponentByUid($asItem[0]);
        if(!empty($oComponent))
        {
          $asItemData = $oComponent->getItemDescription((int)$asItem[3], $asItem[1], $asItem[2]);
          if(!empty($asItemData))
          {
            $asItemData[$asItem[3]]['label'] = preg_replace('/[^a-z0-9 \.&]/i', ' ', $asItemData[$asItem[3]]['label']);
            $sCheckedValue = $sCpItem.'|@|#'.(int)$asItem[3].' - '.$asItemData[$asItem[3]]['label'];
          }
        }
      }
    }


    /*if(!empty($pnReminderPk))
    {
      $oDbMeeting = $this->_getModel()->getByPk($pnReminderPk, 'notification');
      if(!$oDbMeeting || ! $oDbMeeting->readFirst())
        return array('error' => 'Counld not find the reminder.');

      $oForm = $oHTML->initForm('reminderAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_NOTIFY_TYPE_NOTIFICATION, $pnReminderPk);

      $oForm->setFormParams('reminderAddForm', true, array('action' => $sURL, 'submitLabel'=>'Update reminder', 'noCancelButton' => true));
      $oForm->setFormDisplayParams(array('noCancelButton' => true));

      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Edit the reminder'));
    }
    else*/
    {
      $oDbMeeting = new CDbResult();

      //Check if we have a default recipient passed in parameters
      $nRecipient = (int)getValue('loginfk');
      $sRecipient = '';
      if(!empty($nRecipient))
      {
        $asUser = $oLogin->getUserList($nRecipient, true, true);
        if(empty($asUser))
          $nRecipient = 0;
        else
        {
          $sRecipient = $oLogin->getUserNameFromData($asUser[$nRecipient]);
        }
      }

      $oForm = $oHTML->initForm('reminderAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_NOTIFY_TYPE_NOTIFICATION, 0);

      if($pbAsMessage)
      {
        $sTitle = 'Send a message';
        $sBtnLabel = 'Send';
      }
      else
      {
        $sTitle = 'Create a new reminder';
        $sBtnLabel = 'Save reminder';
      }

      $oForm->setFormParams('reminderAddForm', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel' => $sBtnLabel));
      $oForm->setFormDisplayParams(array('noCancelButton' => true));
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> $sTitle));
    }


    $sDate = $oDbMeeting->getFieldValue('date_notification');
    if(empty($sDate))
    {
      $sDate = date('Y-m-d H:i', strtotime('+1 day'));
    }

    if(!$pbAsMessage)
    {
      $oForm->addField('input', 'date_notification', array('type' => 'datetime', 'label'=> 'Reminder date', 'value' => $sDate));

      $oForm->addField('select', 'trigger', array('label'=> 'in/on'));
      $oForm->addOption('trigger', array('value' => 'morning', 'label' => 'in the morning', 'group' => 'On the reminder day'));
      $oForm->addOption('trigger', array('value' => 'half', 'label' => 'morning or noon before the date above', 'group' => 'On the reminder day'));

      $oForm->addOption('trigger', array('value' => '1h', 'label' => '1 hour before', 'group' => 'On time'));
      $oForm->addOption('trigger', array('value' => '2h', 'label' => '2 hours before', 'group' => 'On time'));

      $oForm->addOption('trigger', array('value' => '1d', 'label' => '1 day before', 'group' => 'Early'));
      $oForm->addOption('trigger', array('value' => '1w', 'label' => '1 week before', 'group' => 'Early'));

      $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));
      if(empty($nRecipient))
      {
        $nRecipient = (int)$oDbMeeting->getFieldValue('loginfk');
        if(empty($nRecipient))
        {
          $sRecipient = $oLogin->getCurrentUserName();
          $nRecipient = $oLogin->getUserPk();
        }
      }
    }
    else
    {
      $oForm->addField('input', 'date_notification', array('type'=>'hidden', 'value' => date('Y-m-d H:i')));
      $oForm->addField('input', 'trigger', array('type'=>'hidden', 'value' => 'morning'));
      $oForm->addField('input', 'email_only', array('type'=>'hidden', 'value' => '1'));

      if( empty($nRecipient) && CONST_NOTIFY_DEFAULT_RECIPIENTPK)
      {
        $sRecipient = $oLogin->getUserName(CONST_NOTIFY_DEFAULT_RECIPIENTPK);
        $nRecipient = CONST_NOTIFY_DEFAULT_RECIPIENTPK;
      }
    }

    $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
    $oForm->addField('selector', 'recipient', array('label'=>'Recipient', 'url' => $sURL, 'nbresult' => 5));
    $oForm->setFieldControl('recipient', array('jsFieldNotEmpty' => ''));
    if(!empty($nRecipient))
    {
      $oForm->addOption('recipient', array('label' => $sRecipient, 'value' => $nRecipient));
    }


    $oForm->addField('cp_item_selector', 'reminder_item', array('label'=>'Related to', 'interface' => 'notification_item', 'value' => $sCheckedValue));
    $oForm->addField('misc', '', array('type' => 'text', 'label' => '&nbsp;', 'text'=> '* Item description will automatically be added to the message.', 'class' => 'message_help'));


    $oForm->addField('textarea', 'message', array('label'=> 'Your message', 'class' => 'msgField', 'value' => $oDbMeeting->getFieldValue('description')));
    $oForm->setFieldDisplayParams('message', array('style' => 'margin: 12px 0;'));



    $oForm->sectionStart('', array('folded' => 1), 'Advanced options');

      $oForm->addField('misc', '', array('type' => 'text', 'label' => '&nbsp;', 'text'=> '<span style="font-size: 11px; colro: #666;">
        <u>Naggy message</u>:<br />make '.CONST_APP_NAME.' send multiple emails, to make sure nobody will never forget anything important.
        You\'ll be able to cancel those email at any given time.</span><br /><br /><br />'));


      $oForm->addField('select', 'naggy', array('label'=> 'Number of nags'));
      $oForm->addOption('naggy', array('label' => ' - once is enough - ', 'value' => 0));
      $oForm->addOption('naggy', array('label' => '1', 'value' => 1));
      $oForm->addOption('naggy', array('label' => '2', 'value' => 2));
      $oForm->addOption('naggy', array('label' => '3', 'value' => 3));
      $oForm->addOption('naggy', array('label' => '5', 'value' => 5));
      $oForm->addOption('naggy', array('label' => '10', 'value' => 10));
      $oForm->addOption('naggy', array('label' => 'indefinitly (until cancelled)', 'value' => 100));

      //'1h', '2h', '3h', '0.5d', '1d', '2d', '3d', '1w', '2w', '1m', '2m');
      $oForm->addField('select', 'naggy_frequency', array('label'=> 'Delay between nags'));
      foreach($this->casFormNagFreq as $sValue => $sLabel)
        $oForm->addOption('naggy_frequency', array('label' => $sLabel, 'value' => $sValue));

      $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));
      //$oForm->addField('checkbox', 'notify_recipient', array('legend' => '&nbsp;', 'label' => 'Send a copy to the recipient when saved'));

    $oForm->sectionEnd();


    return $oForm->getDisplay();
  }

  private function _getReminderSave($pnReminderPk = 0)
  {
    if(!assert('is_integer($pnReminderPk)'))
      return array('error' => 'Bad parameters.');

    //-----------------------------------------------------------
    //check form fields: start with the complex one that has the most chances to be wrogly used
    $oForm = CDependency::getComponentByName('form');
    $asItem = $oForm->getStandaloneField('cp_item_selector')->getPostedItemData('reminder_item');

    if(!empty($asItem))
    {
      if(!is_cpValues($asItem))
        return array('error' => __LINE__.' - Bad parameters: no item selected.');

      if(empty($asItem[CONST_CP_PK]))
        $asItem = array();
    }


    //Are we creating a reminder or just sending a message
    //if reminder, user select the time, message goes now !!
    $bReminder = !(bool)(int)getValue('email_only', 0);
    if(!$bReminder)
      $sTrigger = '';
    else
      $sTrigger = getValue('trigger', '');

    //-----------------------
    //check the other fields

    $asReminder = array();

    //give a few minutes delay between form date and processing date
    if(date('i') < 5)
      $sDate = date('Y-m-d H', strtotime('-1 hour')).':50:00';
    else
      $sDate = date('Y-m-d H').':00:00';

    $asReminder['date_notification'] = getValue('date_notification');

    //timepicker rounded at 15 mins, we add the seconds for proper date format
    if(strlen($asReminder['date_notification']) == 16)
      $asReminder['date_notification'].= ':00';

    switch($sTrigger)
    {
      case 'morning':
        $nTime = strtotime($asReminder['date_notification']);
        $asReminder['date_notification'] = date('Y-m-d', $nTime).' 06:00:00';
        $sErrorLabel = 'in the morning';
        break;

      case 'half':
        $nTime = strtotime($asReminder['date_notification']);
        $nHour = date('H', $nTime);
        if($nHour >= 13)
          $asReminder['date_notification'] = date('Y-m-d', $nTime).' 11:30:00';
        else
          $asReminder['date_notification'] = date('Y-m-d', $nTime).' 06:00:00';

        $sErrorLabel = 'half a day before';
        break;

      case '1h':
        $nTime = strtotime('-1 hour', strtotime($asReminder['date_notification']));
        $asReminder['date_notification'] = date('Y-m-d H:i:s', $nTime);
        $sErrorLabel = '1 hour before';
        break;

      case '2h':
        $nTime = strtotime('-2 hours', strtotime($asReminder['date_notification']));
        $asReminder['date_notification'] = date('Y-m-d H:i:s', $nTime);
        $sErrorLabel = '2 hours before';
        break;

      case '1d':
        $nTime = strtotime('-1 day', strtotime($asReminder['date_notification']));
        $asReminder['date_notification'] = date('Y-m-d ', $nTime).' 06:00:00';
        $sErrorLabel = '1 day before';
        break;

      case '1w':
        $nTime = strtotime('-1 week', strtotime($asReminder['date_notification']));
        $asReminder['date_notification'] = date('Y-m-d H:i:s', $nTime).' 06:00:00';
        $sErrorLabel = '1 week before';
        break;
    }


    if($asReminder['date_notification'] < $sDate)
    {
      $asDate = explode(' ', $asReminder['date_notification']);
      return array('error' => __LINE__.' - Can\'t create a reminder with a past date.<br />
        You are trying to set a reminder the <b>'.$asDate[0].'</b> at <b>'.$asDate[1].'</b><br /> (<b style="color: red;">'.$sErrorLabel.'</b>)');
    }


    $asReminder['recipient'] = getValue('recipient');
    if(empty($asReminder['recipient']))
      return array('error' => __LINE__.' - You need to select a recipient.');

    $asReminder['recipient'] = explode(',', $asReminder['recipient']);
    $asReminder['recipient'] = array_trim($asReminder['recipient'], true, true);

    if(empty($asReminder['recipient']) || count($asReminder['recipient']) > 5)
      return array('error' => __LINE__.' - You need to select 1 to 5 recipient recipients.');


    $oLogin = CDependency::getCpLogin();
    $asUser = array();
    foreach($asReminder['recipient'] as $sLoginPk)
    {
      $nLoginFk = (int)$sLoginPk;
      $asUser[$nLoginFk] = $oLogin->getUserDataByPk($nLoginFk, false, true);
      if(empty($asUser[$nLoginFk]))
        return array('error' => __LINE__.' - You need to select a valid recipient.');
    }

    $asReminder['message'] = getValue('message');
    if(empty($asReminder['message']))
      $asReminder['message'] = '<br /><em> -- no message -- </em>';

    $asReminder['naggy'] = (int)getValue('naggy', 0);
    $asReminder['naggy_frequency'] = getValue('naggy_frequency');
    if(empty($asReminder['naggy']))
    {
      $asReminder['naggy_frequency'] = '';
    }
    else
    {
      if(!in_array($asReminder['naggy_frequency'], $this->casNagFreq))
        return array('error' => __LINE__.' - Delay between nags is not valid.');
    }



    $asSource = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_NOTIFY_TYPE_NOTIFICATION, CONST_CP_PK => 0);
    $sId = $this->initNotifier($asSource);

    if($bReminder)
    {
      if(empty($asItem))
        $nPk = $this->addReminder($sId, $asReminder['recipient'], $asReminder['message'], '', $asReminder['date_notification'], $asReminder['naggy'], $asReminder['naggy_frequency']);
      else
        $nPk = $this->addItemReminder($sId, $asReminder['recipient'], $asItem, $asReminder['message'], '', $asReminder['date_notification'], $asReminder['naggy'], $asReminder['naggy_frequency']);
    }
    else
    {
      $sTitle = 'DBA request from '. $oLogin->getUserName(0, false);

      if(empty($asItem))
        $nPk = $this->addMessage($sId, $asReminder['recipient'], $asReminder['message'], $sTitle, $asReminder['naggy'], $asReminder['naggy_frequency']);
      else
        $nPk = $this->addItemMessage($sId, $asReminder['recipient'], $asItem, $asReminder['message'], $sTitle, $asReminder['naggy'], $asReminder['naggy_frequency']);
    }

    if(empty($nPk))
      return array('error' => __LINE__.' - Could not save the reminder. [pk:'.$nPk.' / item: '.empty($asItem).'] ');


    /*$bNotifyNow = (getValue('notify_recipient') == 'on');
    if($bNotifyNow)
    {
      $nCurrentUser = $oLogin->getUserPk();
      $sId = $this->initNotifier($asSource);

      if($asReminder['recipient'] != $nCurrentUser)
      {
        $sTitle = '';
        $sMessage = $oLogin->getCurrentUserName(). ' has just created a reminder for you. ';
        $sMessage.= 'Eventhough it\'s scheduled for the <em><b>'.$asReminder['date_notification'].'</b></em>, ';

        if(empty($asReminder['message']))
          $sMessage.= '.';
        else
          $sMessage.= ' with the following message:<br />'.$asReminder['message'];

        if(empty($asItem))
          $nPk = $this->addMessage($sId, $asReminder['recipient'], $sMessage, $sTitle);
        else
          $nPk = $this->addItemMessage($sId, $asReminder['recipient'], $asItem, $sMessage, $sTitle);

        assert('is_key($nPk)');
      }
    }*/

    return array('notice' => 'Reminder saved.', 'action' => 'goPopup.removeLastByType(\'layer\');');
  }





}
