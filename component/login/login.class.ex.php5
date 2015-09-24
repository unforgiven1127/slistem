<?php

require_once 'component/login/login.class.php5';
require_once 'component/sl_candidate/resources/class/slate_vars.class.php5';

class CLoginEx extends CLogin
{
  private $cbIsLogged;
  private $cbIsAdmin;
  private $casUserData;
  private $casRight;
  private $coSlateVars = null;

  public function __construct()
  {
    if(isset($_SESSION['userData']) && !empty($_SESSION['userData']))
    {
      if(!isset($_SESSION['userData']['loginTime']))
      {
        $this->cbIsLogged = false;
      }
      else
        $this->cbIsLogged = true;

      $this->casUserData = $_SESSION['userData'];
      $this->casRight = array();

      if(!isset($_SESSION['userData']['admin']) || empty($_SESSION['userData']['admin']))
        $this->cbIsAdmin = false;
      else
        $this->cbIsAdmin = true;
    }
    else
    {
      if(isset($_COOKIE['login_userdata']) && !empty($_COOKIE['login_userdata']))
      {
        $asCookieUserData = unserialize(urldecode($_COOKIE['login_userdata']));
        if(isset($asCookieUserData['pk']) && !empty($asCookieUserData['pk']))
        {
          $this->cbIsLogged = $this->_getCookieIdentification($asCookieUserData);
          return $this->cbIsLogged;
        }
      }

      $this->cbIsLogged = false;
      $this->casUserData = array();
      $this->casRight = array();
      $this->cbIsAdmin = false;
    }

    return true;
  }

  //====================================================================
  //  accessors
  //====================================================================

  public function isLogged()
  {
    return $this->cbIsLogged;
  }

  public function isAdmin()
  {
    if($this->cbIsAdmin)
      return true;

    return false;
  }

  public function getUserLogins()
  {
    if(!assert('!empty($this->casUserData)'))
      return '';

    $asLoginfks = array();
    if(!empty($this->casUserData['otherloginfks']))
    {
      if($this->casUserData['otherloginfks'] == '*')
        $asLoginfks = array_keys($this->getUserList(0, true, true));
      else
        $asLoginfks = explode(',', $this->casUserData['otherloginfks']);
    }

    $asLogins = array();
    foreach($asLoginfks as $sLoginfk)
    {
      $asLogins[$sLoginfk] = $this->getUserNameFromPk((int)$sLoginfk);
    }

    asort($asLogins);
    return $asLogins;
  }

  public function getCurrentUserName()
  {
    if(!assert('!empty($this->casUserData) && isset($this->casUserData[\'firstname\']) && isset($this->casUserData[\'lastname\'])'))
      return '';

    return $this->casUserData['firstname'].' '.$this->casUserData['lastname'];
  }

  public function getUserNameFromPk($pnPk, $pbFriendly = false, $pbFullName = false, $pbDisplayPicture = false)
  {
    $oDbResult = $this->_getModel()->getByPk($pnPk, 'login');
    if(!$oDbResult || !$oDbResult->readFirst())
      return '';

    return $this->getUserNameFromData($oDbResult->getData(), $pbFriendly, $pbFullName, $pbDisplayPicture);
  }

  // Returns a user name that matches the size limit
  public function getFormatedUserNameFromData($psFirstName, $psLastName, $pnMaxSize)
  {
    if(!assert('is_key($pnMaxSize)'))
      return '';

    if(!assert('is_string($psFirstName) && !empty($psFirstName)'))
      return '';

    if(!assert('is_string($psLastName) && !empty($psLastName)'))
      return '';

    $psFullName = $psFirstName.' '.$psLastName;
    if(strlen($psFullName)<=$pnMaxSize)
      return $psFullName;

    $psCutFirstName = $psFirstName[0].'. '.$psLastName;
    if(strlen($psCutFirstName)<=$pnMaxSize)
      return $psCutFirstName;

    $psCutBoth = $psFirstName[0].'. '.substr($psLastName, 0, $pnMaxSize-3);
    return $psCutBoth;
  }

  public function getUserNameFromData($asUserData, $pbFriendly = false, $pbFullName = false, $pbDisplayPicture = false)
  {
    if(!assert('is_array($asUserData) && !empty($asUserData) && is_bool($pbFriendly)'))
      return '';

    $oDisplay = CDependency::getCpHtml();

    if($pbFriendly)
    {
      if(!empty($asUserData['pseudo']))
        return $asUserData['pseudo'];
      else
        return $asUserData['firstname'];
    }
    else
    {
      $sFullName = '';

      if($pbDisplayPicture)
      {
        if($asUserData['gender'] == 0)
          $sFullName.= $oDisplay->getPicture($this->getResourcePath().'/pictures/girl.png');
        else
          $sFullName.= $oDisplay->getPicture($this->getResourcePath().'/pictures/boy.png');
      }

      if($pbFullName)
        $sFullName.= $asUserData['courtesy'];

      $sFullName.= $asUserData['firstname'].' '.$asUserData['lastname'];
      return $sFullName;
    }
  }

  public function getUserAccountName($psUserLastname = '', $psUserFirstname = '', $pbFriendly = false, $pbFullName = false, $pbDisplayPicture = false)
  {
    if(!assert(' is_bool($pbFriendly)'))
      return '';

    //assert('false; // crappy function from paul');

    /*if($pbFriendly)
    {
      if(!empty($asUserData['pseudo']))
        return $asUserData['pseudo'];
      else
        return $psUserFirstname;
    }
    else
    {*/
      $sFullName = '';

      /*if($pbDisplayPicture)
      {
        if($asUserData['gender'] == 0)
           $sFullName.= $oDisplay->getPicture($this->getResourcePath().'/pictures/girl.png');
        else
           $sFullName.= $oDisplay->getPicture($this->getResourcePath().'/pictures/boy.png');
      }*/

      if($pbFullName)
        $sFullName.= $asUserData['courtesy'];

      $sFullName.= $psUserFirstname.' '.$psUserLastname;
      return $sFullName;
    //}
  }

    public function getVars()
    {
      if($this->coSlateVars !== null)
        return $this->coSlateVars;

      if(empty($_SESSION['slate_vars']))
      {
        $this->coSlateVars = new CSlateVars();
        $_SESSION['slate_vars'] = serialize($this->coSlateVars);
      }
      else
      {
        $this->coSlateVars = unserialize($_SESSION['slate_vars']);
        if($this->coSlateVars == false)
          assert('false; // could not restore the var object');
      }

      return $this->coSlateVars;
    }


  public function getUserLink($pvUser = 0, $pbFriendly = false, $pbFullName = false)
  {
    if(!assert('(is_array($pvUser) || is_integer($pvUser))'))
    {
      assert('false; /* getUserLink with wrong pvUser '.  var_export($pvUser, true).' */');
      return '';
    }

    if(!assert('is_bool($pbFriendly) || is_bool($pbFullName)'))
      return '';

    //special system cases
    if(is_numeric($pvUser) && (int)$pvUser < 0)
    {
      $nUser = (int)$pvUser;

      if($nUser === -1)
        return '<a href="javascript:;" class="user_link user_system" title="Default system user when automatic tasks update the database">system</a>';

      if($nUser === -2)
        return '<a href="javascript:;" class="user_link user_unknown" title="Unknown information or former user"> -- </a>';

      return '';
    }

    if(empty($pvUser))
      $pvUser = (int)$this->casUserData['loginpk'];

    if(is_array($pvUser))
    {
      if(!isset($pvUser['loginpk']))
        return 'unknow';

      //cache: if link already generated, re-use right away
      if(isset($_SESSION['login_ULCache'][$pvUser['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName]))
        return $_SESSION['login_ULCache'][$pvUser['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName];

      $asUserData = $pvUser;
    }
    else
    {
      //cache: if link already generated, re-use right away
      if(isset($_SESSION['login_ULCache'][$pvUser.'_'.(int)$pbFriendly.(int)$pbFullName]))
        return $_SESSION['login_ULCache'][$pvUser.'_'.(int)$pbFriendly.(int)$pbFullName];

      $asUserData = $this->getUserDataByPk($pvUser);
    }

    if(empty($asUserData))
      return 'unknown';

    $oDisplay = CDependency::getCpHtml();
    $sName = $this->getUserNameFromData($asUserData, $pbFriendly, $pbFullName);


    if($pbFriendly)
      $sDescName = $asUserData['firstname'].' '.$asUserData['lastname'].' ';
    else
      $sDescName = 'consultant id: '.$asUserData['pseudo'].' ';

    if(!$asUserData['status'])
    {
      $sClass = 'una';
      if(!empty($asUserData['date_update']) && $asUserData['date_update'] != '0000-00-00 00:00:00')
      {
        $asDate = explode(' ', $asUserData['date_update']);
        $sDescription = 'Inactive user ['.$sDescName.'- deactivated the '.$asDate[0].' ]';
      }
      else
        $sDescription = 'Inactive user ['.$sDescName.']';
    }
    else
    {
      $sClass = '';
      $sDescription = 'Active user ['.$sDescName.'- extension: '.$asUserData['phone_ext'].' - email: '.$asUserData['email'].' ]';
    }

    $asOption = array('class' => 'user_link '.$sClass, 'title' => $sDescription, 'active' => (int)$asUserData['status'], 'loginfk' => $asUserData['loginpk']);

    if(CONST_LOGIN_USERLINK_CALLBACK)
      $asOption = call_user_func_array(CONST_LOGIN_USERLINK_CALLBACK, array('data' => $asOption));
    else
      $asOption['onclick'] = 'tp(this);';

      $sLink = $oDisplay->getLink($sName, 'javascript:;', $asOption);

    $_SESSION['login_ULCache'][$asUserData['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName] = $sLink;

    return $sLink;
  }

  public function getUserName($pvUser, $pbFriendly = false, $pbFullName = false)
  {
    if(!assert('(is_array($pvUser) || is_integer($pvUser)) /*'.$pvUser.'*/'))
      return '';

    if(!assert('is_bool($pbFriendly) || is_bool($pbFullName)'))
      return '';

    if(empty($pvUser))
      $pvUser = (int)$this->casUserData['loginpk'];

    if($pvUser < 0)
    {
      if($pvUser === -1)
        return 'system';

      if($pvUser === -2)
        return ' -- ';

      return '';
    }


    if(is_array($pvUser))
    {
      if(!isset($pvUser['loginpk']))
        return 'unknow';

      //cache: if link already generated, re-use right away
      if(isset($_SESSION['login_UNCache'][$pvUser['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName]))
        return $_SESSION['login_UNCache'][$pvUser['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName];

      $asUserData = $pvUser;
    }
    else
    {
      //cache: if link already generated, re-use right away
      if(isset($_SESSION['login_UNCache'][$pvUser.'_'.(int)$pbFriendly.(int)$pbFullName]))
        return $_SESSION['login_UNCache'][$pvUser.'_'.(int)$pbFriendly.(int)$pbFullName];

      $asUserData = $this->getUserDataByPk($pvUser);
    }

    if(empty($asUserData))
      return 'unknown';

    $sName = $this->getUserNameFromData($asUserData, $pbFriendly, $pbFullName);
    $_SESSION['login_UNCache'][$asUserData['loginpk'].'_'.(int)$pbFriendly.(int)$pbFullName] = $sName;

    return $sName;
  }

  public function getUserPk()
  {
    if(!isset($this->casUserData['pk']))
      return 0;

    return (int)$this->casUserData['pk'];
  }


  public function getUserEmail()
  {
    if(!assert('!empty($this->casUserData)'))
      return '';

    if(isset($this->casUserData['email']))
      return $this->casUserData['email'];

    assert('false; // user without email spotted.');
    return '';
  }

  public function getUserData()
  {
    return $this->casUserData;
  }

  //====================================================================
  //  component interfaces
  //====================================================================


  public function declareSettings()
  {
    $oPage = CDependency::getCpPage();

    $aOptions = array (
        '-100 years' => 'always',
        '-1 month' => '1 month',
        '-3 months' => '3 months',
        '-6 months' => '6 months',
        '-1 year' => '1 year'
    );

    $aSettings[] = array(
        'fieldname' => 'password_validity',
        'fieldtype' => 'select',
        'options' => $aOptions,
        'label' => 'Period of password validity',
        'description' => 'The time before passwords need to be changed',
        'value' => '+100 years'
    );

    $aSettings[] = array(
        'fieldname' => 'allowed_ip',
        'fieldtype' => 'textarea',
        'label' => 'Allowed IP',
        'description' => 'IP allowed to access the website.',
        'value' => '127.0.0.1,127.0.0.1',
        'customformurl' => $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_EDIT, CONST_TYPE_IP, 0)
    );

    return $aSettings;
  }

  public function declareUserPreferences()
  {
    $asPrefs = array();

    $asPrefs[] = array(
          'fieldname' => 'mail_client',
          'fieldtype' => 'select',
          'options' => array('local_client' => 'Computer default client (Thunderbird, Outlook...)', 'webmail' => 'Webmail application (Gmail, Yahoo!Mail, Zimbra...)', 'bcm_mail' => 'Software mail feature'),
          'label' => 'How to send emails from the software ?',
          'description' => 'You can configure the default behaviour of the sofware when clicking on email addresses.',
          'value' => ''
      );

    return $asPrefs;
  }


  public function getHtml()
  {
    $this->_processUrl();

    if($this->isLogged())
    {
      switch($this->csType)
      {
        case CONST_LOGIN_TYPE_USER:
          switch($this->csAction)
          {
            case CONST_ACTION_LIST:
            return $this->_displayList();
              break;

            case CONST_ACTION_EDIT:
            return $this->_getUserAccountDetail($this->cnPk);
              break;
          }
          break;

        default:
          //access home page
          return $this->_getHomePage();
      }
    }

    switch($this->csType)
    {
      case 'restricted':
        return $this->getRestrictedPage($this->cbIsLogged, true);
        break;

      case CONST_LOGIN_TYPE_PASSWORD:
        switch($this->csAction)
        {
         case CONST_ACTION_RESET:
              return $this->_getLoginResetPasswordForm();
          break;
        }
        break;

      case CONST_ACTION_LOGOUT:
        return $this->_getLogout();
          break;

      default:

       switch($this->csAction)
       {
          default:
            return $this->_getLoginForm();
          break;
        }
        break;
    }
  }

 public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_LOGIN_TYPE_USER:
        switch($this->csAction)
        {
          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:

            //TODO
            if((bool)getValue('credentials', false))
              return json_encode($this->_saveUserCred($this->cnPk));

            return json_encode($this->_saveUser($this->cnPk));

            break;

           case CONST_ACTION_DELETE:
             return json_encode($this->_updateUserStatus($this->cnPk));
              break;

            case CONST_ACTION_SEARCH:
              /* custom json encoding in function for token input selector */
              return $this->_getSelectorUser();
                break;

            case CONST_ACTION_MANAGE:
              return json_encode($this->_editUser($this->cnPk));
              break;

            case CONST_ACTION_ADD:
              return json_encode($this->_addUser());
              break;
          }
          break;

      case CONST_LOGIN_TYPE_EXTERNAL_USER:
        switch($this->csAction)
        {
          case CONST_ACTION_VALIDATE:
            return json_encode($this->_checkUserLogged());
              break;
        }
        break;


      case CONST_LOGIN_TYPE_PASSWORD:
        switch($this->csAction)
        {
          case CONST_ACTION_SEND:
            return json_encode($this->_getSendPassword());
              break;

          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_setNewPassword());
              break;

          case CONST_ACTION_VALIDATE:
            return json_encode($this->_getIdentification(true));
              break;

        }
        break;

      case CONST_TYPE_IP:

        switch($this->csAction)
        {
          case CONST_ACTION_EDIT:
            return json_encode($this->_formAllowedIp());
          break;

          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_saveAllowedIp());
          break;
        }

      break;

    case CONST_LOGIN_TYPE_GROUP:
    {
      switch($this->csAction)
      {
          case CONST_ACTION_ADD:
            return json_encode($this->_getGroupForm($this->cnPk));
              break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_saveGroup($this->cnPk));
            break;

          case CONST_ACTION_MANAGE:
            return json_encode($this->_getGroupList());
            break;

      }
    }

      default:
        switch($this->csAction)
        {
          case CONST_ACTION_LOGOUT:
            return json_encode($this->_getLogout(true));
              break;

          case CONST_ACTION_RELOG:
            if(!$this->isLogged())
              return json_encode(array('error' => 'Don\'t play around. O_o'));

            return json_encode($this->_reLog($this->cnPk));
            break;
        }
    }
  }

  public function getCronJob()
  {
    echo 'Login cron  <br />';

    $nHour = date('H');

    if(($nHour > 5 && $nHour < 6) || getValue('forcecron') == 'login')
    {
       $this->_cleanRecentActivity();
       $this->_checkBirthday();
       $this->_getCronEmail();
       $this->_checkPassword();
    }

    return '';
  }


  //====================================================================
  //  System features
  //====================================================================

  public function getSystemHistoryByDate($psDateStart = '', $psDateEnd = '')
  {
    if(!assert('is_date($psDate) || is_datetime($psDate)'))
      return array();

    if(!assert('is_date($psDateEnd) || is_datetime($psDateEnd)'))
      return array();

    if(empty($psDateStart) && empty($psDateEnd))
    {
      assert('false; // at least one date is required.');
      return array();
    }

    $asParams = array();
    if(!empty($psDateStart))
    {
      $asParams['date_start'] = $psDateStart;
    }

    if(empty($psDateEnd))
    {
      $asParams['date_end'] = $psDateEnd;
    }


    return $this->_getSystemHistory($asParams);
  }

  public function getSystemHistoryUser($pvLoginfk)
  {
    if(!assert('is_key($pvLoginfk) || is_arrayOfInt($pvLoginfk)'))
      return array();

    $asParams = array();

    if(is_integer($pvLoginfk))
      $asParams['loginfk'] = $pvLoginfk;
    else
      $asParams['logins'] = implode(',', $pvLoginfk);

    return $this->_getSystemHistory($asParams);
  }

  public function getSystemHistoryItem($pvItem, $psLimit = '')
  {
    if(!assert('(is_string($pvItem) || is_cpValues($pvItem)) && !empty($pvItem)'))
      return array();

    if(is_string($pvItem))
    {
      if(strlen($pvItem) < 2)
      {
        assert('false; // Item shortname must contain at least 2 characters.');
        return array();
      }

      return $this->_getSystemHistory(array('component' => $pvItem), $psLimit);
    }

    return $this->_getSystemHistory(array('cp_key' => $pvItem), $psLimit);
  }

  private function _getSystemHistory($pasParams, $psLimit = '')
  {
    if(!assert('is_array($pasParams) && !empty($pasParams)'))
      return array();

    $oDbResult = $this->_getModel()->getSystemHistory($pasParams, $psLimit);
    $bRead = $oDbResult->readFirst();

    $asHistory = array();
    while($bRead)
    {
      $asHistory[] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asHistory;

  }

  //====================================================================
  //  Component specific methods
  //====================================================================


  // Checks if one of the passwords need to be changed.
  private function _checkPassword()
  {
    $oMail = CDependency::getComponentByInterface('do_sendmail');
    $oSetting = CDependency::getComponentByName('settings');
    $sPasswordValidity = $oSetting->getSettingValue('password_validity');

    $tExpirationTimestamp = strtotime($sPasswordValidity);
    $sExpirationDate = date('Y-m-d H:i:s', $tExpirationTimestamp);
    echo $sExpirationDate;
    $sSuccess = $this->_getModel()->disableExpiredAccounts($sExpirationDate);
    echo 'Account disabled:'.$sSuccess.'<br />';

    $tReminderTimestamp = $tExpirationTimestamp+(7*24*60*60);
    $sReminderDate = date('Y-m-d H:i:s', $tReminderTimestamp);
    echo $sReminderDate;
    echo '<br />';
    $oUserToRemind = $this->_getModel()->getUsersToRemind($sReminderDate);
    $bRead = $oUserToRemind->readFirst();

    if($bRead)
    {
      $nCount = 0;
      while($bRead)
      {
        $oMail->createNewEmail();
        $oMail->setFrom('crm@bulbouscell.com', 'CRM notifyer');

        $sSubject = 'BCM reminder - Please change your password';
        $oMail->addRecipient($oUserToRemind->getFieldValue('email'), $oUserToRemind->getFieldValue('firstname').' '.$oUserToRemind->getFieldValue('lastname'));
        $sContent ='Hello '.$oUserToRemind->getFieldValue('firstname');
        $sContent.='<br />Your password has not been changed for a long time. It is required to change it often for security reasons.';
        $sContent.='<br />Please <a href=\''.CONST_CRM_DOMAIN.'\' target=\'_blank\'>log in BCM</a> and change it in \'My Account\' section';
        $nCount += $oMail->send($sSubject, $sContent, strip_tags($sContent));

        $bRead = $oUserToRemind->readNext();
      }

      echo $nCount.' reminders sent';
    }
  }

  private function _getCronEmail()
  {
    $oAddress = CDependency::getComponentUidByName('addressbook');
    if($oAddress)
    {
     $oEvent = CDependency::getComponentByName('event');
     $oAddressBook = CDependency::getComponentByName('addressbook');

     $day = date('l');
     $time = (int)date('H');

     if((($day=='Monday' || $day=='Thursday' ) && $time == 6) || getValue('forcecron') == 'login'|| getValue('custom_uid') == '579-704')
     {
      $oDB = CDependency::getComponentByName('database');

      $sQuery = 'SELECT * FROM  `login_activity` WHERE status = 0 AND followerfk!=0 AND notifierfk!=0 AND loginfk!=notifierfk ';
      $sQuery.= ' AND sentemail = 0 AND log_date > '.date('Y-m-d', mktime(0, 0 , 0, date('m'), (date('d')-5), date('Y')));

      $oResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oResult->readFirst();
      if(!$bRead)
        return false;

      $asToNotify = array();
      $asDocuments = array();

      while($bRead)
      {
        $asEmailData = $oResult->getData();
        $asRecipients = array($asEmailData['notifierfk']);

        foreach($asRecipients as $nLoginfk)
        {
          $asToNotify[$nLoginfk][] = $asEmailData['login_activitypk'];
        }

        $asEventDetail = $oEvent->getEventDataByPk((int)$asEmailData[CONST_CP_PK]);

        $sEventURL = $asEmailData['log_link'].'#ct_tab_eventId';
        $sLink = '<a href="'.$sEventURL.'"> Access the event in BCM </a>';

        $asUserData = $this->getUserDataByPk((int)$asEventDetail['created_by']);
        $sTargetName = $oAddressBook->getItemName('ct',(int)$asEmailData['followerfk']);
        $sContent = ' <h4>Activity Detail</h4> <br/>';
        $sContent.= ' <strong>Target : </strong>'.$sTargetName .'<br/>' ;
        $sContent.= ' <strong>Created on  :</strong>'.$asEventDetail['date_create'].' by '.$this->getUserNameFromData($asUserData).'<br/>';
        $sContent.= ' <strong>Type :</strong>'.ucwords($asEventDetail['type']).'<br/>';
        $sContent.= ' <strong>Title :</strong>'.$asEventDetail['title'].'<br/>';
        $sContent.= ' <strong>Description :</strong><div style="border: 1px solid #BBBBBB;border-radius: 5px 5px 5px 5px;padding: 3px; ">'.$asEventDetail['content'].'</div><br />';
        $sContent.= ''.$sLink.'<br />';
        $asDocuments[$asEmailData['login_activitypk']] = $sContent;

        $bRead = $oResult->ReadNext();
       }

      $oMail = CDependency::getComponentByName('mail');
      if(!empty($oMail))
      {
      $nSent = 0;

      foreach($asToNotify as $nEmailfk => $anEmailToNotify)
      {
       if(!empty($nEmailfk))
        {
          $asReceiverEmail =  $this->getUserDataByPk((int)$nEmailfk);

          $sContent = '<h3> Hello '.$asReceiverEmail['firstname'].' '.$asReceiverEmail['lastname'].',</h3><br />';

          if(count($anEmailToNotify) > 1)
            $sContent.= count($anEmailToNotify)." events have been created in your connection in BCM.<br />";
          else
            $sContent.= "A event has been created in your connection in BCM. Click the link below.".$sLink."<br/>";

          foreach($anEmailToNotify as $nDocumentPk)
          {
            $sQuery = 'UPDATE login_activity SET sentemail = 1 where login_activitypk ='.$nDocumentPk;
            $oResult = $oDB->ExecuteQuery($sQuery);
            $sContent.= '<br />'.$asDocuments[$nDocumentPk].'<br />';
           }
           $sContent.= "Enjoy BCM.";

          $oMail->sendRawEmail('info@bcm.com',$asReceiverEmail['email'], 'BCM - Event has been created on your connection.', $sContent);
          $nSent++;
          }
        }
        echo $nSent.' email(s) have been sent.<br />';
       }
      }
      return true;
   }
 }


  /**
   * Display the login form. If $pbIsStdLogin, it's a standard redirection or display of the login screen, else it's a access
   * to a restricted feature.
   * @param bool $pbIsLogged
   * @param bool $pbIsStdLogin
   * @return string HTML
   */
  public function getRestrictedPage($pbIsLogged = false, $pbIsStdLogin = false)
  {
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $sHTML = '';

    if(!$pbIsLogged)
    {
      if(!$pbIsStdLogin)
      {
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'restrictedAccessBloc'));
        $sHTML.= $oHTML->getBlocMessage($this->casText['LOGIN_ACCESS_RESTRICTED']);
        $sHTML.= $oHTML->getBlocEnd();
      }

      //no specific style. i fnot logged in, i ll endu on login screen
      $sHTML.= $oHTML->getBlocStart();
      $sHTML.= $this->_getLoginForm(false, '', true);
      $sHTML.= $oHTML->getBlocEnd();
    }
    else
    {
      //apply different style to the form if error or normal access
      if($oPage->getUid() == $this->csUid)
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'standardLoginSection'));
      else
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'restrictedLoginSection'));

      $sHTML.= $oHTML->getBlocMessage($this->casText['USER_ACCESS_RESTRICTED']);
      $sHTML.= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }

  /**
   * Display the user account details
   *
   * @param integer $pnLoginPk
   * @return string html
  */
  private function _getUserAccountDetail($pnLoginPk)
  {
    if(!assert('is_integer($pnLoginPk)'))
      return '';

    if(empty($pnLoginPk))
      $pnLoginPk = $this->getUserPk();

    $oHTML = CDependency::getCpHtml();

    $sHTML= $oHTML->getBlocStart();
    $sHTML = $oHTML->getTitleLine($this->casText['LOGIN_MY_ACCOUNT'], $this->getResourcePath().'/pictures/user.png');
    $sHTML.= $oHTML->getCR();
    $sHTML.= $this->_formUser($pnLoginPk);
    $sHTML.= $oHTML->getBlocEnd();

    return  $sHTML;
  }

  /**
   * Check the credentials of the user
   * @param $pnLoginPk integer
   * @param $psEmail string
   * @param $psPassword string
   * @param $pnPort string
   * @param $psIMAP string
   * @return boolean with true or false
   */

  private function _checkCredentials($pnLoginPk = 0,$psEmail,$psPassword,$pnPort,$psIMAP)
  {
    //Check if the credentials given are correct.
    if(!empty($pnLoginPk))
    {
      $asCredentials =   $this->getUserDataByPk($pnLoginPk);
      $sUserLogin =  $asCredentials['webmail'];
      $sUserPwd  =  $asCredentials['webpassword'];

      $sHost ='{'.BCMAIL_HOST.':'.BCMAIL_PORT.'/imap/ssl/novalidate-cert}SENT';
    }
    else
    {
      $sUserLogin = $psEmail;
      $sUserPwd  =  $psPassword;
      $sHost ='{'.$psIMAP.'/imap/ssl/notls/norsh/novalidate-cert}Sent';
    }

     if(empty($sUserLogin))
      return array('error' => __LINE__.' - '.$this->casText['LOGIN_EMAIL_INVALID']);

     if(empty($sUserPwd))
       return array('checked' => 0, 'error' => $this->casText['LOGIN_PASSWD_REQD']);

     $nTimeout = imap_timeout(IMAP_OPENTIMEOUT, 5);
     if($sConn = @imap_open($sHost, $sUserLogin, $sUserPwd, OP_READONLY))
     {
       imap_close($sConn);
       return array('checked' => 1);
     }
     else
       return array('checked' => 0, 'error' => $this->casText['LOGIN_COULDNOT_CONNECT'], 'detail' => $sHost.' // '.$nTimeout.' // '.  imap_last_error());
   }

   /**
    * Save the User credentials
    * @param $pnLoginPk integer
    * @return array
    */

  private function _saveUserCred($pnLoginPk)
  {
    if(!assert('is_integer($pnLoginPk) && !empty($pnLoginPk)'))
      return array('error' => $this->casText['LOGIN_NO_USER']);

    $oHTML = CDependency::getCpHtml();

    $sMail = getValue('login');
    $sPassword = getValue('password');
    $nPort = (int)getValue('port', 0);
    $sIMAP = getValue('imap');
    $sAlias = getValue('alias');
    $sSignature= getValue('signature');

    if(empty($sMail) && !filter_var($sMail, FILTER_VALIDATE_EMAIL))
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_EMAIL']));

    if(empty($sPassword) && strlen($sPassword) > 4)
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_PASSWD']));

    if(empty($nPort))
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_PORT']));

    if(empty($sIMAP))
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_IMAP']));

    if(empty($sAlias))
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_ALIAS']));

    if(empty($sSignature))
      return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_SIGN']));

    $asCredentialCheck = $this->_checkCredentials(0,$sMail, $sPassword, $nPort, $sIMAP);
    if($asCredentialCheck['checked'] == 0)
      return array('error' => __LINE__.' - '.$asCredentialCheck['error'], 'detail' => $asCredentialCheck['detail'] );

    $oDB = CDependency::getComponentByName('database');
    $oPage = CDependency::getCpPage();

    $sQuery = 'UPDATE login SET ';
    $sQuery.= ' webmail = '.$oDB->dbEscapeString($sMail).', ';
    $sQuery.= ' webpassword = '.$oDB->dbEscapeString($sPassword).', ';
    $sQuery.= ' mailport = '.$oDB->dbEscapeString($nPort).', ';
    $sQuery.= ' Imap = '.$oDB->dbEscapeString($sIMAP).', ';
    $sQuery.= ' aliasName = '.$oDB->dbEscapeString($sAlias).',';
    $sQuery.= ' signature = '.$oDB->dbEscapeString($sSignature).' ';
    $sQuery.= ' WHERE loginPk = '.$pnLoginPk;

    $oDB->ExecuteQuery($sQuery);
    $this->_getModel()->createMysqlView();

    $sURL = $oPage->getUrl('login', '', '', $pnLoginPk);
    return array('notice' => $this->casText['LOGIN_CREDENTIAL_SAVE'], 'url' => $sURL);
  }

  /**
   * Save the User Information
   * @param $pnLoginPk integer
   * @return array
   */

  private function _saveUser($pnLoginPk = 0)
  {
    $oHTML = CDependency::getCpHtml();

    $oRight = CDependency::getComponentByName('right');
    $bmanager = $oRight->canAccess($this->csUid, 'ppam');
    $asUpdate = array();

    if($bmanager)
    {
      $sEmail = getValue('email');
      if(empty($sEmail) || !isValidEmail($sEmail, FILTER_VALIDATE_EMAIL))
        return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_NO_EMAIL']));

      if($this->_getModel()->exists('email', $_POST['email'], $pnLoginPk))
        return array('error' => 'A user using the email '.$_POST['email'].' already exists. Please choose a different one.');

      if($this->_getModel()->exists('id', $_POST['id'], $pnLoginPk))
        return array('error' => 'A user using the login '.$_POST['id'].' already exists. Please choose a different one.');

      if(empty($_POST['id']) || strlen($_POST['id']) < 3)
        return array('error' => 'Login has to contains at least 4 characters.');

      if(empty($_POST['password']) || strlen($_POST['password']) < 5)
        return array('error' => 'password has to contains at least 5 characters.');

      $_POST['status'] = (int)$_POST['status'];
      if($_POST['status'] < 0 || $_POST['status'] > 1)
        return array('error' => __LINE__.' - Error.');

      if(!isset($_POST['group']))
        $asGroups = array();
      else
        $asGroups = $_POST['group'];

      if(!empty($asGroups) && !is_arrayOfInt($asGroups))
        return array('error' => $oHTML->getBlocMessage($this->casText['LOGIN_GROUP_INVALID']));

      $asUpdate['email'] = $sEmail;
      $asUpdate['id'] = getValue('id');
      $asUpdate['password'] = getValue('password');
      $asUpdate['status'] = getValue('status');
      $asUpdate['group'] = getValue('group');
    }

    if(empty($_POST['firstname']))
      return array('error' => $this->casText['LOGIN_NO_FIRSTNAME']);

    if(empty($_POST['lastname']))
      return array('error' => $this->casText['LOGIN_NO_LASTNAME']);

    if(empty($_POST['position']))
      return array('error' => $this->casText['LOGIN_NO_POSITION']);


    $asUpdate['firstname'] = getValue('firstname');
    $asUpdate['lastname'] = getValue('lastname');
    $asUpdate['pseudo'] = getValue('pseudo');
    $asUpdate['nationalityfk'] = getValue('nationality');
    $asUpdate['phone'] = getValue('phone');
    $asUpdate['phone_ext'] = getValue('phone_ext');
    $asUpdate['position'] = getValue('position');


    if(empty($pnLoginPk))
    {
      $_POST['date_passwd_changed'] = date('Y-m-d');
      $pnLoginPk = (int)$this->_getModel()->add($asUpdate, 'login');

      if(empty($pnLoginPk))
        return array('error' => 'Adding user failed.');
    }
    else
    {

      if(!assert('is_key($pnLoginPk)'))
        return array('error' => 'No User found.');

      $asUpdate['loginpk'] = $pnLoginPk;
      $bUpdated = $this->_getModel()->update($asUpdate, 'login');

      if(!$bUpdated)
        return array('error' => 'Updating user failed.');
    }

    if($bmanager)
    {
      $bSaved =  $this->_getModel()->saveUserGroups($pnLoginPk, $asGroups);
      if(!$bSaved)
        return array('error' => 'Sorry, could not save user groups.');
    }

    //re-create the shared view to let other componet access logins data
    $this->_getModel()->createMySqlView();

    return array('notice' => $this->casText['LOGIN_ACCOUNT_SAVE'], 'action' => 'goPopup.removeByType(\'layer\'); $(\'#settings li[rel=users]\').click();');
  }

  /**
   * Display the user form
   * @param  $pnPK User key
   * @return string with HTML content of form
   */

  private function _formUser($pnPK)
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/login.form.css');

    $oResult = $this->_getModel()->getByPk($pnPK, 'login');
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return $oHTML->getBlocMessage($this->casText['LOGIN_NO_RESULT']);

    $sHTML= $oHTML->getFloatHack();

    $asTabs = array();
    $sDefault = '';

    if(getValue('view_account_tab') || CONST_LOGIN_USER_MANAGE_ACCOUNT)
    {
      $sDefault = $this->casText['LOGIN_ACCOUNT'];
      $asTabs[] = array('title' => $this->casText['LOGIN_ACCOUNT'], 'label' => $this->casText['LOGIN_ACCOUNT'], 'content' =>$this->_formUserInfo($pnPK));
      $asTabs[] = array('title' => $this->casText['LOGIN_CREDENTIALS'], 'label' => $this->casText['LOGIN_CREDENTIALS'], 'content' =>$this->_formUserCredential($oResult));
    }

    $sPreferencesUrl   = $oPage->getAjaxUrl('settings', CONST_ACTION_LIST, CONST_TYPE_USERPREFERENCE, 0);
    if(empty($sDefault))
    {
      $sDefault = 'preferences';
      $asTabs[] = array('title' => 'Preferences', 'label' => 'preferences', 'content' =>
          $oHTML->getBloc('area_preferences', '<script>$(\'ul#login_tabs_links > li[rel=preferences]\').click();</script>'), 'options' => array('link' => $sPreferencesUrl));
    }
    else
      $asTabs[] = array('title' => 'Preferences', 'label' => 'preferences', 'content' => $oHTML->getBloc('area_preferences'), 'options' => array('link' => $sPreferencesUrl));


    $aoComponent = CDependency::getComponentsByInterface('user_account_tab');
    foreach($aoComponent as $oComponent)
    {
      $asTab = $oComponent->getUserAccountTabData($this->getUserPk());
      if(!empty($asTab))
      {
        //$sFoldersUrl   = $oPage->getAjaxUrl('folder', CONST_ACTION_LIST, '', $this->casUserData['pk']);
        $asTabs[] = array('title' => $asTab['title'], 'label' => $asTab['label'], 'content' => $oHTML->getBloc('area_'.$asTab['label']), 'options' => array('link' => $asTab['url']));
      }
    }

    $sHTML.= $oHTML->getTabs('login_tabs', $asTabs, $sDefault);

    return $sHTML;
  }

  /**
   * Display the form for changing allowed IP
   * @return string with HTML content of form
   */

  private function _formAllowedIp()
  {
    $oSetting = CDependency::getComponentByName('settings');
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $sAllowedIps = $oSetting->getSettingValue('allowed_ip');

    $sURL = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEEDIT, CONST_TYPE_IP, 0);

    $oForm = $oHTML->initForm('allowedIp');
    $oForm->setFormParams('allowedIp', true, array('action' => $sURL, 'label' => 'Allowed IP', 'submitLabel'=>'Save Changes'));
    $oForm->setFormDisplayParams(array('noCancelButton' => 'noCancelButton'));

    $oForm->addField('misc', '', array('type' => 'text', 'text' => 'IP address allowed to login to the software.<br /><br />Input there the list of IP addresses separated by commas. Example : 1270.0.0.1, 127.0.1.2, 198.162.0.0 ...<br /><br />'));
    $oForm->addField('textarea', 'allowed_ip', array('label' => '' , 'value'=> str_replace(',', ",\n", $sAllowedIps), 'style' => 'min-width: 600px; min-height: 120px;'));

    $sHTML = $oForm->getDisplay();

    return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
  }

  /**
   * Saves allowed IP
   * @return string with HTML content of form
   */

  private function _saveAllowedIp()
  {
    $oSetting = CDependency::getComponentByName('settings');
    $sValue = getValue('allowed_ip');

    $sValue = str_replace(array("\n", chr(10), chr(13), ' '), '', $sValue);
    $sValue = str_replace(',,', ',', $sValue);

    if(substr($sValue, -1, 1) == ',')
      $sValue = substr($sValue, 0, strlen($sValue)-1);


    $asIp = explode(',', $sValue);

    foreach($asIp as $nKey => $sIp)
    {
      $asMatches = array();
      if(!preg_match('/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/', trim($sIp), $asMatches))
        return array('error' => 'Ip address invalid / wrong format. ['.$sIp.']');

      if(count($asMatches) != 5)
        return array('error' => 'Ip address invalid / wrong format. ['.$sIp.']');

      if((int)$asMatches[1] < 1 ||(int)$asMatches[2] < 0 ||(int)$asMatches[3] < 0 || (int)$asMatches[4] < 1 ||
         (int)$asMatches[1] > 254 ||(int)$asMatches[2] > 254 || (int)$asMatches[3]> 254 || (int)$asMatches[4] > 254 )
      {
        return array('error' => 'Ip address invalid / wrong format. ['.$sIp.']');
      }

      $asIp[$nKey] = trim($sIp);
    }

    $sValue = implode(',', $asIp);
    $bUpdated = $oSetting->saveCustomSetting('allowed_ip', $sValue);
    if(!$bUpdated)
      return array('notice' => 'Unable to update the value of allowed_ip setting. Value given : '.$sValue);


    $sFilename = CONST_PATH_ROOT.'/'.CONST_PATH_HTACCESS;
    if(isDevelopment())
      $sFilename = CONST_PATH_ROOT.'/dev_'.CONST_PATH_HTACCESS;

    if(file_exists($sFilename))
    {
      $bRenameSuccess = copy($sFilename, $sFilename.'.bak');
      if(!$bRenameSuccess)
        return array('error' => 'htaccess backup failed. Htaccess file has not been updated.');
    }
    else
    {
      $sTemplate = 'RewriteEngine on
                    RewriteCond %{SERVER_PORT} !=443
                    RewriteRule (.*) https://%{SERVER_NAME}%{REQUEST_URI} [R,L]

                    AuthName "Restricted Area"
                    AuthType Basic
                    AuthUserFile '.CONST_PATH_ROOT.'/.htpasswd
                    Satisfy Any
                    order deny,allow
                    deny from all
                    require valid-user

                    #allowedip
                    ';


      $oFs = @fopen($sFilename, "w+");
      if(!$oFs)
         return array('error' => 'Can not create htaccess.['.$sFilename.']');

      fputs($oFs, $sTemplate);
      fclose($oFs);
    }

    // ---------------------------------------------
    // Chek the required tag is present in the file
    $asLines = file($sFilename);
    $nPosition = array_search_multi('#allowedip', $asLines);
    if($nPosition < 5)
      return array('error' => 'Htaccess is not formated correctly, missing #tag.');


    array_splice($asLines, $nPosition+1);
    $aAllowedIps = explode(',', $sValue);
    $sDate = "\n".'#'.date('Y-m-d H:i:s').' : from admin section '."\n";

    foreach ($aAllowedIps as $aAllowedIp)
      $asLines[]= $sDate.'allow from '.$aAllowedIp."\n"; 'allow from '.$aAllowedIp."\n";


    $oFs = fopen($sFilename, "w");

    foreach($asLines as $sLine)
    {
      $bAddLineSuccess = fwrite($oFs, $sLine);
      if(!$bAddLineSuccess)
        return array('notice' => 'There has been an error during htaccess writing. htaccess file has not been updated.');
    }

    $bCloseSuccess = fclose($oFs);
    if(!$bCloseSuccess)
      return array('notice' => 'Htaccess file could not be closed. htaccess file has not been updated.');

    return array('notice' => 'Authorized IP updated successfully.', 'action' => 'goPopup.removeByType("layer"); ');
  }


  private function _addUser()
  {
    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxExtraContent(array('data' => $this->_formUserInfo()));
  }


  /**
   * Function to display form to edit user information
   * @param integer $pnPk
   * @return string
  */
  private function _editUser($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => $this->casText['LOGIN_NO_RESULT']);

    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxExtraContent(array('data' => $this->_formUserInfo($pnPk)));
  }

  /**
   * Function to remove the user
   * @param int $pnUserPk
   * @return array
   */

  private function _updateUserStatus($pnUserPk)
  {
    if(!assert('is_integer($pnUserPk) && !empty($pnUserPk)'))
       return array('error' => $this->casText['LOGIN_USER_DELETED']);

    $oDbResult = $this->_getModel()->getByPk($pnUserPk, 'login');
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => __LINE__.' - '.$this->casText['LOGIN_CONNECTION_DELETE']);

    $aData['loginpk']=$pnUserPk;
    $aData['status'] = (int)getValue('status')==1 ? 0 : 1;

    $bUpdated = $this->_getModel()->update($aData, 'login');
    $this->_getModel()->createMysqlView();
    if(!$bUpdated)
       return array('error' => __LINE__.' - '.$this->casText['LOGIN_CANT_DELETE']);

    $oPage = CDependency::getCpPage();
    return array('notice' => $this->casText['LOGIN_STATUS_CHANGED'], 'timedUrl' => $oPage->getUrl('login', CONST_ACTION_LIST, CONST_LOGIN_TYPE_USER));
  }

  // Switchs to another account

  private function _reLog($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return ( array ('error' => 'Impossible to switch. No user recognised.'));

    if($this->_getLogout(false, false)===true)
    {
      $bLoggedIn = $this->_getIdentification(false, $pnPk, false);
      if($bLoggedIn === true)
        return (array('notice' => 'User changed successfully.', 'reload' => 1));
      else
        return (array('error' => 'Could not log in with your new account. Please contact your administrator.'.dump($bLoggedIn)));
    }
    else
      return (array('error' => 'Could not log out. Please contact your administrator.'));
  }

/**
 * Display the User Information tab
 * @param  $poUserData array
 * @return string with HTML
 */

  private function _formUserInfo($pnPk = 0)
  {
    if(!assert('is_integer($pnPk)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/login.form.css');

    $aUrlOptions = array();
    if($oPage->getActionReturn())
      $aUrlOptions [CONST_URL_ACTION_RETURN] = $oPage->getActionReturn();

    if(is_key($pnPk))
    {
      $oResult = $this->_getModel()->getByPk($pnPk, 'login');
      $bRead  = $oResult->readFirst();
      if(!$bRead)
        return $oHTML->getBlocMessage($this->casText['LOGIN_NO_RESULT']);

      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SAVEEDIT, CONST_LOGIN_TYPE_USER, $pnPk, $aUrlOptions);
      $sTitle = 'Edit user';
    }
    else
    {
      $oResult = new CDbResult();
      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SAVEADD, CONST_LOGIN_TYPE_USER, 0, $aUrlOptions);
      $sTitle = 'Add a new user';
    }

    $oRight = CDependency::getComponentByName('right');
    $bmanager = $oRight->canAccess($this->csUid, 'ppam');
    if($bmanager)
    {
      $asUserGroup = $this->_getModel()->getUserGroup(0, true);
    }

    $oForm = $oHTML->initForm('userForm');
    $oForm->setFormParams('', true, array('action' => $sURL));
    $sHTML = $oHTML->getBlocStart();

    $oForm->addField('misc', '', array('type' => 'title', 'title'=> $sTitle));

    $oMnlList = CDependency::getComponentByName('manageablelist');
    $aCourtesy = $oMnlList->getListValues('courtesy');

    if(!empty($aCourtesy))
    {
      $oForm->addField('select', 'courtesy', array('label' => 'Courtesy'));
      foreach($aCourtesy as $sLabel => $sValue)
      {
        if($sValue==$oResult->getFieldValue('courtesy'))
          $oForm->addOption('courtesy', array('value'=>$sValue, 'label' => $sLabel, 'selected' => 'selected'));
        else
          $oForm->addOption('courtesy', array('value'=>$sValue, 'label' => $sLabel));
      }
    }

    $oForm->addField('input', 'firstname', array('label'=>$this->casText['LOGIN_FIRSTNAME'], 'class' => '', 'value' => $oResult->getFieldValue('firstname')));
    $oForm->setFieldControl('firstname', array('jsFieldMinSize' => '2', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'lastname', array('label'=>$this->casText['LOGIN_LASTNAME'], 'class' => '', 'value' => $oResult->getFieldValue('lastname')));
    $oForm->setFieldControl('lastname', array('jsFieldMinSize' => '2', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'pseudo', array('label'=>$this->casText['LOGIN_PSEUDO'], 'class' => '', 'value' => $oResult->getFieldValue('pseudo')));
    $oForm->setFieldControl('pseudo', array('jsFieldMinSize' => '2', 'jsFieldMaxSize' => 255));

    $oForm->addField('select', 'nationality', array('label'=>$this->casText['LOGIN_NATIONALITY'], 'class' => ''));
    $oForm->addOptionHtml('nationality', $this->getVars()->getNationalityOption($oResult->getFieldValue('nationalityfk')));

    if($bmanager)
    {
      $oForm->addField('input', 'id', array('label'=>'Login', 'class' => '', 'value' => $oResult->getFieldValue('id')));
      $oForm->setFieldControl('id', array('jsFieldMinSize' => '2', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 15));

      $oForm->addField('input', 'password', array('type' => 'password', 'label'=>'Password', 'class' => '', 'value' => $oResult->getFieldValue('password')));
      $oForm->setFieldControl('password', array('jsFieldMinSize' => '5', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'email', array('label'=> $this->casText['LOGIN_EMAIL'], 'value' => $oResult->getFieldValue('email')));
      $oForm->setFieldControl('email', array('jsFieldTypeEmail' => '','jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));
    }

    $oForm->addField('input', 'phone', array('label'=> $this->casText['LOGIN_PHONE'], 'value' => $oResult->getFieldValue('phone')));
    $oForm->setFieldControl('phone', array('jsFieldMinSize' => 8));

    $oForm->addField('input', 'phone_ext', array('label'=> $this->casText['LOGIN_EXTENSION'], 'value' => $oResult->getFieldValue('phone_ext')));
    $oForm->setFieldControl('phone_ext', array('jsFieldMinSize' => 4, 'jsFieldTypeInteger' => 'jsFieldTypeInteger'));

    $oForm->addField('input', 'position', array('label'=> $this->casText['LOGIN_POSITION'], 'value' => $oResult->getFieldValue('position')));
    $oForm->setFieldControl('position', array('jsFieldNotEmpty' => '','jsFieldMinSize' => 2));


    //manage status: inactive by default
    if($bmanager)
    {
      $oForm->addField('select', 'status', array('label'=> $this->casText['LOGIN_ACTIVE_USER']));

      $oForm->addOption('status', array('value'=> 0, 'label' => 'No', 'selected' => 'selected'));

      if($oResult->getFieldValue('status') == 1)
        $oForm->addOption('status', array('value'=> 1, 'label' => 'Yes', 'selected' => 'selected'));
      else
        $oForm->addOption('status', array('value'=> 1, 'label' => 'Yes'));



      //group management at last (better if there's no field after the bsmselect)
      if(empty($pnPk))
        $anCurrentGroup = array();
      else
      {
        $asCurrentGroup = $this->_getModel()->getUserGroup($pnPk, false, true);
        $anCurrentGroup = array_keys($asCurrentGroup);
      }

      //dump($asUserGroup);
      //dump($asCurrentGroup);


      $oForm->addField('select', 'group[]', array('label'=> $this->casText['LOGIN_GROUP'], 'multiple' => 20));
      foreach($asUserGroup as $nGroupPk => $asGroupData)
      {
        if(in_array($nGroupPk, $anCurrentGroup))
        $oForm->addOption('group[]', array('value'=> $asGroupData['login_grouppk'], 'label' => $asGroupData['title'], 'selected' => 'selected'));
        else
          $oForm->addOption('group[]', array('value'=> $asGroupData['login_grouppk'], 'label' => $asGroupData['title']));
      }
    }


    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /**
  * Display the user email credentials information
  * @param $poUserData array
  * @return string with form details
  */

  private function _formUserCredential($poUserData)
  {
   $oHTML = CDependency::getCpHtml();
   $oPage = CDependency::getCpPage();

   if(!assert('is_object($poUserData) && !empty($poUserData)'))
     return $oHTML->getBlocMessage($this->casText['LOGIN_NO_RESULT']);

    $nLoginPk = $poUserData->getFieldValue('loginpk', CONST_PHP_VARTYPE_INT);
    $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SAVEEDIT, CONST_LOGIN_TYPE_USER, $nLoginPk, array('credentials' => 1));

    $sHTML= $oHTML->getBlocStart();
    $sHTML.= $oHTML->getBlocStart('');

    //Start the credential form
    $oForm = $oHTML->initForm('userCredentialForm');
    $oForm->setFormParams('', true, array('action' => $sURL));

    //div including the form
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> $this->casText['LOGIN_UPDATE_CREDENTIALS']));

    $oForm->addField('input', 'login', array('label'=>$this->casText['LOGIN_LOGIN'], 'class' => '', 'value' => $poUserData->getFieldValue('webmail')));
    $oForm->setFieldControl('login', array('jsFieldMinSize' => '4', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'password', array('label'=>$this->casText['LOGIN_PASSWORD'], 'type'=> 'password','class' => '', 'value' => $poUserData->getFieldValue('webpassword')));
    $oForm->setFieldControl('password', array('jsFieldMinSize' => '6', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'alias', array('label'=> $this->casText['LOGIN_ALIAS'], 'value' => $poUserData->getFieldValue('aliasName')));
    $oForm->setFieldControl('alias', array('jsFieldMinSize' => 4));

    $oForm->addField('input', 'port', array('label'=> $this->casText['LOGIN_PORT'],'readonly'=>'readonly', 'value' => '143'));
    $oForm->setFieldControl('port', array('jsFieldMaxSize' => 255));

    $oForm->addField('input', 'imap', array('label'=> $this->casText['LOGIN_IMAP'],'readonly'=>'readonly', 'value' => 'mail.bulbouscell.com'));
    $oForm->setFieldControl('imap', array('jsFieldMaxSize' => 255,'jsFieldNotEmpty' => ''));

    $oForm->addField('textarea', 'signature', array('label'=> $this->casText['LOGIN_SIGNATURE'], 'value' => $poUserData->getFieldValue('signature'), 'isTinymce' => 1));
    $oForm->setFieldControl('signature', array('jsFieldMinSize' => '2', 'jsFieldMaxSize' => 4096));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Display the login form
   * @return fom structure
   */

  private function _getLoginForm($pbWelcomeMsg = true, $psExtraClass = '', $pbRedirect = true)
  {
    if(!assert('is_bool($pbWelcomeMsg)'))
      return false;

    $oHTML = CDependency::getCpHtml();
    $oSetting = CDependency::getComponentByName('settings');
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/login.form.css');

    $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_VALIDATE, CONST_LOGIN_TYPE_PASSWORD);

    //force redirection for external identification

    $sRedirectAfterLogin = getValue('redirect');
    if(!empty($sRedirectAfterLogin))
      $sURL.= '&redirect='.urlencode($sRedirectAfterLogin);

    //fetch customization settings
    $asSettings = $oSetting->getSettings(array('loginScreenTop', 'loginScreenBottom', 'loginScreenLeft', 'loginScreenRight'), false);

    $sHTML = $oHTML->getBlocStart('', array('class' => 'loginScreenContainer '.$psExtraClass));

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'loginScreenContent loginScreenTop'));
      if(!empty($asSettings['loginScreenTop']))
        $sHTML.= $asSettings['loginScreenTop'];
      $sHTML.= $oHTML->getBlocEnd();

      //div including the form
      $sHTML.= $oHTML->getBlocStart();

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'loginScreenContent loginScreenLeft'));
        if(!empty($asSettings['loginScreenLeft']))
          $sHTML.= $asSettings['loginScreenLeft'];
        $sHTML.= $oHTML->getBlocEnd();


        $sHTML.= $oHTML->getBlocStart('loginFormId', array('class' => 'loginScreenCenter'));

          //div receiving error message after loging attempts
        if($pbWelcomeMsg)
        {
          $sHTML.= $oHTML->getBlocStart('loginMsgId', array('class' => 'notice fontError'));
          $sHTML.= $oHTML->getText(CONST_LOGIN_MESSAGE);
        }
        else
        {
          $sHTML.= $oHTML->getBlocStart('loginMsgId', array('class' => 'notice fontError hidden'));
        }

          $sHTML.= $oHTML->getBlocEnd();

          /* @var $oForm CFormEx */
          $oForm = $oHTML->initForm('loginFormData');
          $oForm->setFormParams('', true, array('submitLabel' => $this->casText['LOGIN_SIGNIN'], 'action' => $sURL));
          $oForm->setFormDisplayParams(array('noCancelButton' => 1, 'columns' => 1));

          $oForm->addField('input', 'login', array('label'=>$this->casText['LOGIN_LOGIN'], 'class' => 'loginWideField'));
          $oForm->addField('input', 'password', array('label'=>$this->casText['LOGIN_PASSWORD'], 'type'=> 'password', 'class' => 'loginWideField'));
          $sURLPswd = $oPage->getUrl('login', CONST_ACTION_RESET, CONST_LOGIN_TYPE_PASSWORD);
          $sLink = $oHTML->getLink($this->casText['LOGIN_FORGOT_PASSWORD'], $sURLPswd);
          if($pbRedirect)
            $oForm->addField('input', 'redirect', array('type'=> 'hidden', 'value' => CONST_CRM_DOMAIN.$_SERVER['REQUEST_URI']));

          $sHTML.= $oForm->getDisplay().$oHTML->getBloc('missingPwdLink', $sLink);

        $sHTML.= $oHTML->getBlocEnd();

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'loginScreenContent loginScreenRight'));
        if(!empty($asSettings['loginScreenRight']))
          $sHTML.= $asSettings['loginScreenRight'];
        $sHTML.= $oHTML->getBlocEnd();

        $sHTML.= $oHTML->getFloatHack();
        $sHTML.= $oHTML->getBlocEnd();


      $sHTML.= $oHTML->getBlocStart('', array('class' => 'loginScreenContent loginScreenBottom'));
      if(!empty($asSettings['loginScreenBottom']))
        $sHTML.= $asSettings['loginScreenBottom'];
      $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }

  /**
  * Form to reset the password
  * @return form structure
  */

  private function _getLoginResetPasswordForm()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(array($this->getResourcePath().'css/login.form.css'));

    $sHashCode = getValue('hshc');
    if(empty($sHashCode))
    {
      //Form asking to input email address
      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEND, CONST_LOGIN_TYPE_PASSWORD);
      $sHTML= $oHTML->getBlocStart();

      //div receiving error message after reset attempts
      $sHTML.= $oHTML->getBlocStart('resetMsgId', array('class' => 'notice fontError'));
      $sHTML.= $oHTML->getText($this->casText['LOGIN_STEP_FORGOT_1']);
      $sHTML.= $oHTML->getBlocEnd();

      //div including the form
      $sHTML.= $oHTML->getBlocStart('resetFormId');

      $oForm = $oHTML->initForm('resetFormData');
      $oForm->setFormParams('', false);
      $oForm->setFormDisplayParams(array('noButton' => 1, 'columns' => 1));

      $oForm->addField('input', 'email', array('label'=>$this->casText['LOGIN_EMAIL'], 'class' => 'loginWideField'));
      $oForm->addField('input', 'btn', array('type'=> 'button', 'value'=>$this->casText['LOGIN_SEND_RESETEMAIL'], 'onclick' => "setLoadingScreen('body', true); setTimeout('setLoadingScreen(\\'body\\', false);', 5000); AjaxRequest('".$sURL."', '', 'resetFormDataId', 'resetMsgId');"));

      $sHTML.= $oForm->getDisplay();

      $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getBlocEnd();
    }
    else
    {
      //Got hashcode from email, check then display form reset

      if(empty($this->cnPk))
        return __LINE__.' - '.$this->casText['LOGIN_PARAMETER_INCORRECT'];

      $oDB = CDependency::getComponentByName('database');
      $oDB->dbConnect();
      $sQuery = 'SELECT * FROM `login` WHERE loginpk = '.$this->cnPk.' AND hashcode = '.$oDB->dbEscapeString($sHashCode).' ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      if(!$bRead)
        return __LINE__.' - '.$this->casText['LOGIN_PARAMETER_INCORRECT'];

      $sResetDate = $oDbResult->getFieldValue('date_reset');
      $sMinDate = date('Y-m-d H:i:s', mktime(((int)date('H')-3), date('i'), date('s'), date('m'), date('d'), date('Y')));

      if(empty($sResetDate) || $sResetDate < $sMinDate)
        return __LINE__.' - '.$this->casText['LOGIN_REQ_EXPIRE'];

      $sHTML= $oHTML->getBlocStart();

      //div receiving error message after reset attempts
      $sHTML.= $oHTML->getBlocStart('resetMsgId', array('class' => 'notice fontError'));
      $sHTML.= $oHTML->getText($this->casText['LOGIN_STEP_FORGOT_3']);
      $sHTML.= $oHTML->getBlocEnd();

      //div including the form
      $sHTML.= $oHTML->getBlocStart('resetFormId');

      $oForm = $oHTML->initForm('resetFormData');
      $oForm->setFormParams('', false);
      $oForm->setFormDisplayParams(array('noButton' => 1, 'columns' => 1));

      $oForm->addField('input', 'hashcode', array('type'=>'hidden', 'value' => $oDB->dbEscapeString($sHashCode, '', true)));
      $oForm->addField('input', 'password', array('label'=>$this->casText['LOGIN_NEW_PASSWORD'], 'type' => 'password', 'class' => 'loginWideField'));
      $oForm->addField('input', 'confirm', array('label'=>$this->casText['LOGIN_CONFIRM_PASSWORD'], 'type' => 'password', 'class' => 'loginWideField'));

      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SAVEEDIT, CONST_LOGIN_TYPE_PASSWORD, $this->cnPk);
      $oForm->addField('input', 'btn', array('type'=> 'button', 'value'=>$this->casText['LOGIN_SAVE_PASSWORD'], 'onclick' => "AjaxRequest('".$sURL."', 'body', 'resetFormDataId', 'resetMsgId');"));

      $sHTML.= $oForm->getDisplay();
      $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getBlocEnd();
    }
    return $sHTML;
  }

  /**
   * Function to send the new password
   * @return array message after sending email
   */

  private function _getSendPassword()
  {
    $sEmailAddress = getValue('email', '', 'post');
    if(empty($sEmailAddress) || filter_var($sEmailAddress, FILTER_VALIDATE_EMAIL) === false)
    {
      return array('message' => $this->casText['LOGIN_EMPTY_EMAIL_TYPED']);
    }

    $oDB = CDependency::getComponentByName('database');

    $sHashCode = uniqid('id', true);
    //save the hash code in database + date

    $sSQL = 'SELECT loginpk FROM login WHERE email = "'.$sEmailAddress.'" ';
    $oDbResult = $oDB->ExecuteQuery($sSQL);
    $bRead = $oDbResult->ReadFirst();

    if(!$bRead)
      return array('message' => '('.__LINE__.')'.$this->casText['LOGIN_COULDNT_MATCH']);

    $nPk = (int)$oDbResult->getFieldValue('loginpk');
    if(empty($nPk))
      return array('message' => '('.__LINE__.')'.$this->casText['LOGIN_ERROR_OCCURED']);


    $sSQL = 'UPDATE login SET hashcode = "'.$sHashCode.'", date_reset = "'.date('Y-m-d H:i:s').'" WHERE loginpk = '.$nPk;
    $bRead = $oDB->ExecuteQuery($sSQL);
    if(!$bRead)
      return array('message' => '('.__LINE__.')'.$this->casText['LOGIN_ERROR_OCCURED']);

    $oPage = CDependency::getCpPage();
    $oMail = CDependency::getComponentByName('mail');

    if(!empty($oMail))
    {
      $sURL = $oPage->getUrl('login', CONST_ACTION_RESET, CONST_LOGIN_TYPE_PASSWORD, (int)$oDbResult->getFieldValue('loginpk'));
      $sURL.= '&hshc='.$sHashCode;
      $sSubject = CONST_APP_NAME.' message: Reset your password';
      $sContent = 'Reseting your password step 2/3: <br /><br />Please click on the link below to reset your password:  <br /><br />'."\n\n";
      $sContent.= '<a href="'.$sURL.'">'.CONST_CRM_DOMAIN.'</a>';
      $sContent.= '<br /><br /> Regards, BCM Administrator.';

      $bSent = $oMail->sendRawEmail(CONST_CRM_MAIL_SENDER, $sEmailAddress, $sSubject, $sContent);
    }

    if($bSent)
      return array('message' => $this->casText['LOGIN_EMAIL_SENT'].$sEmailAddress);
    else
      return array('message' => $this->casText['LOGIN_TRY_LATER']);
  }

  /**
   * Set the new password
   * @return array message after setting password
   */

  private function _setNewPassword()
  {
    $sPassword = getValue('password', '', 'post');
    $sConfirm = getValue('confirm', '', 'post');
    $sHashcode = getValue('hashcode', '', 'post');

    if(empty($this->cnPk) || empty($sHashcode))
      return array('error' => $this->casText['LOGIN_BAD_PARAMETER']);

    if(empty($sPassword) || empty($sConfirm))
      return array('error' => $this->casText['LOGIN_INPUT_NEW_PASSWORD']);

    if($sPassword != $sConfirm)
      return array('error' => $this->casText['LOGIN_PASSWORD_CONFIRM_DIFF']);

    if(strlen($sPassword) < 5 )
      return array('error' => $this->casText['LOGIN_PASSWORD_SHORT']);

    $bHasUpper = preg_match('/[A-Z]/', $sPassword);
    $bHasLower = preg_match('/[a-z]/', $sPassword);
    $bHasNumber = preg_match('/[0-9]/', $sPassword);
    $bHasSymbole = preg_match('/[^A-Za-z0-9]/', $sPassword);
    $nSecurityCheck = (int)$bHasUpper+ (int)$bHasLower+ (int)$bHasNumber+ (int)$bHasSymbole;

    if($nSecurityCheck < 3)
      return array('message' => $this->casText['LOGIN_PASSWD_EXAMPLE']);

    $oDB = CDependency::getComponentByName('database');

    $sSQL = 'UPDATE login SET password = '.$oDB->dbEscapeString($sPassword).', hashcode = NULL WHERE loginpk = '.$this->cnPk.' AND hashcode = "'.$sHashcode.'" ';
    $bRead = $oDB->ExecuteQuery($sSQL);

    if($bRead)
      return array('message' => $this->casText['LOGIN_NEW_PASSWD_CHANGED'], 'timedUrl' =>"index.php5");
    else
      return array('message' => $this->casText['LOGIN_ERROR_OCCURED']);
  }

  /**
   * Get the user identification
   * @param boolen $pbIsAjax
   * @return array
   */

  private function _getIdentification($pbIsAjax = false, $pnCookiePk = 0, $bRedirect = true)
  {
    $oDB = CDependency::getComponentByName('database');
    $oSetting = CDependency::getComponentByName('settings');

    if(!empty($pnCookiePk) && is_integer($pnCookiePk))
       $sQuery = 'SELECT * FROM `login` WHERE loginpk = '.$pnCookiePk.' AND status = 1 ';
    else
    {
      if(empty($_POST) || !isset($_POST['login']) || !isset($_POST['password']))
        return array('error' => __LINE__.' - '.$this->casText['LOGIN_PASSWORD_REQD']);

      if(empty($_POST['login']) || empty($_POST['password']))
        return array('error' => __LINE__.' - '.$this->casText['LOGIN_PASSWORD_REQD']);

      $sQuery = 'SELECT * FROM `login`  WHERE (`id` = '.$oDB->dbEscapeString($_POST['login']).' ';
      $sQuery.= ' AND BINARY `password` = '.$oDB->dbEscapeString($_POST['password']).') ';
      $sQuery.= ' OR ( `email` = '.$oDB->dbEscapeString($_POST['login']).' ';
      $sQuery.= ' AND BINARY `password` = '.$oDB->dbEscapeString($_POST['password']).') ';
    }

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array('error' => __LINE__.' -'.$this->casText['LOGIN_PASSWORD_INCORRECT']);

    $nStatus = $oDbResult->getFieldValue('status', CONST_PHP_VARTYPE_INT);
    if($nStatus == 0)
      return array('error' => __LINE__.' -'.$this->casText['LOGIN_ACCOUNT_DEACTIVATED']);

    if($nStatus == 2)
      return array('error' => __LINE__.' - '.$this->casText['LOGIN_ACCOUNT_SUSPENDED']);

    //set session
    //use the standard function. I know it s another query, but it incluses groups management and maybe other features
    $_SESSION['userData'] = $this->getUserDataByPk((int)$oDbResult->getFieldValue('loginpk'), true);

    $_SESSION['userData']['pk'] = (int)$_SESSION['userData']['loginpk'];
    $_SESSION['userData']['admin'] = (bool)$_SESSION['userData']['is_admin'];
    $_SESSION['userData']['loginTime'] = time();

    $_SESSION['userRight'] = array();

    $oSetting->loadUserPreferences($_SESSION['userData']['pk']);

    $oRight = CDependency::getComponentByName('right');
    $oRight->loadUserRights($_SESSION['userData']['pk']);

    $sHash = sha1($_SESSION['userData']['pk'].'|@|'.uniqid('cook_', true).'|@|'.rand(1000000, 1000000000));
    $sQuery = 'UPDATE login SET date_last_log = "'.date('Y-m-d H:i:s').'", log_hash = "'.$sHash.'" WHERE loginpk = '.$_SESSION['userData']['pk'];
    $oDB->ExecuteQuery($sQuery);

    //Create a 3 hour cookie (will be refresh as long as user browse pages)
    //@setcookie('login_userdata', serialize(array('pk' => $_SESSION['userData']['pk'], 'hash' => $sHash)), mktime(date('H')+3, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')), '/');
    @setcookie('login_userdata', serialize(array('pk' => $_SESSION['userData']['pk'], 'hash' => $sHash)), time()+3600*3, '/');
    //redirections

    $sRedirectUrl = getValue('redirect');
    if(!empty($sRedirectUrl))
    {
      //To connect to multiplateforms
      //manage requested redirection after login
      $asUrl = parse_url($sRedirectUrl);

      if(empty($asUrl['query']))
        $sUrl = $sRedirectUrl.'?pk='.$_SESSION['userData']['pk'];
      else
        $sUrl = $sRedirectUrl.'&pk='.$_SESSION['userData']['pk'];
    }
    elseif(!empty($_SESSION['urlRedirect']))
    {
      //manage automatic redirection after login
       $sUrl = $_SESSION['urlRedirect'];
    }
    else
    {
      //no redirection => homepage
      $oPage = CDependency::getCpPage();
      $sUrl = $oPage->getUrlHome();
    }

    if($pbIsAjax)
      return array('url' => $sUrl);

    if($bRedirect)
    {
      $this->_redirectUser($sUrl);
    }
    else
      return true;
  }

  /**
   * refresh / recreate cookie from session data.
   * @return boolean
   */
  public function rebuildCookie()
  {
    $oDB = CDependency::getComponentByName('database');

    $sHash = sha1($_SESSION['userData']['pk'].'|@|'.uniqid('cook_', true).'|@|'.rand(1000000, 1000000000));
    $sQuery = 'UPDATE login SET date_last_log = "'.date('Y-m-d H:i:s').'", log_hash = "'.$sHash.'" WHERE loginpk = '.$_SESSION['userData']['pk'];
    $oDB->ExecuteQuery($sQuery);

    @setcookie('login_userdata', serialize(array('pk' => $_SESSION['userData']['pk'], 'hash' => $sHash)), time()+3600*4, '/');

    return true;
  }


  /**
   * Emulate a standard logged user for cronjob scripts
   * @return boolean
   */
  public function setCronUser()
  {
    $this->casUserData = array();
    $this->casUserData['pk'] = -1;
    $this->casUserData['lastname'] = 'Admin';
    $this->casUserData['firstname'] = 'System';
    $this->casUserData['pseudo'] = 'Admin';
    $this->casUserData['email'] = CONST_DEV_EMAIL;
    $this->casUserData['admin'] = 1;
    $this->casUserData['loginTime'] = time();

    $this->casRight = array();
    $this->cbIsAdmin = true;
    $this->cbIsLogged = true;

    $_SESSION['userData'] = $this->casUserData;
    return true;
  }

  /**
   * Check the identification of cookie
   * @param integer $pnPK
   * @return boolean
   */

  private function _getCookieIdentification($pasCookieData)
  {
    if(!assert('is_array($pasCookieData) && !empty($pasCookieData)'))
      return false;

    $nUserPk = (int)$pasCookieData['pk'];
    $sSecurityHash = $pasCookieData['hash']; //$sHash = sha1(['pk'].'|@|'.['id'].'|@|'.['password'].'|@|'.['email']);

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `login` WHERE loginpk = '.$nUserPk.' AND status = 1 ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return false;

    $nStatus = $oDbResult->getFieldValue('status', CONST_PHP_VARTYPE_INT);
    if($nStatus != 1)
      return false;

    $sHash = $oDbResult->getFieldValue('log_hash');
    if($sHash != $sSecurityHash)
      return false;

    //lauch the standard login process passing the user pk
    $this->_getIdentification(false, $nUserPk);
    //exit(__LINE__.' - error cookie');

   return true;
  }

  /**
   * Check user logged or not
   * @return array of records
   */

  private function _checkUserLogged()
  {
    $asAllowedClient['127.0.0.1'] = '';
    $asAllowedClient['192.168.10.24'] = '32435354vf234b42n4gf2n4rt4y5r4ne3';
    $asAllowedClient['203.167.38.24'] = '32435354vf234b42n4gf2n4rt4y5r4ne3';

    $asAllowedClient['192.168.10.25'] = 'SDA354SADasd4das45a6788sa4124';
    $asAllowedClient['203.167.38.25'] = 'SDA354SADasd4das45a6788sa4124';
    $asAllowedClient['192.168.10.29'] = 'SDA354SADasd4das45a6788sa4124';
    $asAllowedClient['203.167.38.29'] = 'SDA354SADasd4das45a6788sa4124';

    $sClientIp = $_SERVER['REMOTE_ADDR'];
    $sHash = getValue('extIdHash');
    $sReturnUrl = getValue('url');

    if(!isset($asAllowedClient[$sClientIp]) || $asAllowedClient[$sClientIp] != $sHash)
      exit('DN/'.$sClientIp.'/'.$sHash);

    $sEmail = getValue('email');
    $nPk = (int)getValue('pk');
    if(empty($sEmail) && empty($nPk))
      return array('status' => -1, 'pk' => 0, 'email' => '', 'date' => '', 'url' => '', 'msg'=>'bad parameters to identify the user');

    $oDB = CDependency::getComponentByName('database');
    $oDB->dbConnect();

    if(!empty($nPk))
     $sQuery = 'SELECT * FROM `login` WHERE `loginpk` = "'.$nPk.'" ';
    else
     $sQuery = 'SELECT * FROM `login` WHERE `email` = '.$oDB->dbEscapeString($sEmail).' ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
    {
      $oPage = CDependency::getCpPage();
      $sUrl = $oPage->getUrlHome();
      return array('status' => 0, 'pk' => 0, 'email' => '', 'date' => '', 'url' => $sUrl.'&redirect='.urlencode($sReturnUrl), 'msg'=>'user unknown');
    }

    //Not identified if identified more than 4 hours ago, or if it was the day before
    $asDbDate = explode(' ', $oDbResult->getFieldValue('date_last_log'));

    if($asDbDate < date('Y-m-d') || $oDbResult->getFieldValue('date_last_log') < date('Y-m-d H:i:s', (time()-(4*3600))))
    {
      $oPage = CDependency::getCpPage();
      $sUrl = $oPage->getUrlHome();

      return array('status' => 0, 'pk' => $oDbResult->getFieldValue('loginpk'), 'email' => $oDbResult->getFieldValue('email'),
        'date' => $oDbResult->getFieldValue('loginpk'), 'url' => $sUrl.'&redirect='.urlencode($sReturnUrl), 'msg'=>'not identified ');
    }

    return array('status' => 1, 'pk' => $oDbResult->getFieldValue('loginpk'), 'email' => $oDbResult->getFieldValue('email'),
        'date' => $oDbResult->getFieldValue('loginpk'), 'url' => '', 'msg'=>'identified');
  }

  /**
   * Disconnect the user and redirect him to the login screen
   * @param boolean $pbIsAjax
   */
  private function _getLogout($pbIsAjax = false, $pbRedirect = true)
  {


    $oDb = CDependency::getComponentByName('database');
    $sQuery = 'UPDATE login SET log_hash = \'\' WHERE loginpk = '.$this->casUserData['pk'];
    $oDb->executeQuery($sQuery);

    //unset session
    session_destroy();

    //unset cookie
    setcookie('login_userdata', '', time()-360000, '/');

    //$oPage = CDependency::getCpPage();
    //$sUrl = $oPage->getUrlHome(true);
    $sUrl = CONST_CRM_DOMAIN;

    if($pbIsAjax)
      return array('url' => $sUrl); //'message' => 'login ok',

    if($pbRedirect)
      return $this->_redirectUser($sUrl);
    else
      return true;
  }

  private function _redirectUser($psUrl = '')
  {
    $oPage = CDependency::getCpPage();
    if(empty($psUrl))
    {
      //redirect the user on the portal
      $sURl = $oPage->getUrlHome();
      return $sURl = $oPage->redirect($sURl);
    }

    return $oPage->redirect($psUrl);
  }

  /**
   * Display home page contents
   * @return HTML structure
   */



  /**
  * Function to get the user list selected from the parameters
  * @param integer $pvPk
  * @param boolean $pbOnlyActive
  * @param boolean $pbIncludeRoot
  * @return array
  */
  public function getUserList($pvPk = 0, $pbOnlyActive = true, $pbIncludeRoot = false, $psSort = '')
  {
    if(!assert('is_integer($pvPk) || is_array($pvPk)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `login` l
      LEFT JOIN login_group_member lm ON l.loginpk=lm.loginfk ';

    if($pbOnlyActive)
       $sQuery.= ' WHERE l.status > 0';
    else
      $sQuery.= ' WHERE 1 ';

    if(!$pbIncludeRoot)
    {
      $sQuery.= ' AND l.is_admin <> 1 ';
    }

    if(!empty($pvPk))
    {
      if(is_integer($pvPk))
        $sQuery.= ' AND l.loginpk = '.$pvPk;
      else
        $sQuery.= ' AND l.loginpk IN ('.implode(',', $pvPk).') ';
    }

    if(empty($psSort))
      $sQuery.= ' ORDER BY l.firstname, l.lastname';
    else
      $sQuery.= ' ORDER BY '.$psSort;

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('loginpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }

  /**
  * Function to get the user list selected from the parameters
  * @param integer $pvPk
  * @param boolean $pbOnlyActive
  * @param boolean $pbIncludeRoot
  * @return array
  */
  public function getUserEmailList($pvPk = 0, $pbOnlyActive = true, $pbIncludeRoot = false)
  {
    if(!assert('is_integer($pvPk) || is_array($pvPk)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT loginpk, email FROM `login` l
      LEFT JOIN login_group_member lm ON l.loginpk=lm.loginfk ';

    if($pbOnlyActive)
       $sQuery.= ' WHERE l.status > 0';
    else
      $sQuery.= ' WHERE 1 ';

    if(!$pbIncludeRoot)
    {
      $sQuery.= ' AND l.is_admin <> 1 ';
    }

    if(!empty($pvPk))
    {
      if(is_integer($pvPk))
        $sQuery.= ' AND l.loginpk = '.$pvPk;
      else
        $sQuery.= ' AND l.loginpk IN ('.implode(',', $pvPk).') ';
    }

    $sQuery.= ' ORDER BY l.firstname, l.lastname';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('loginpk')] = $oDbResult->getFieldValue('email');
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }

  /**
  * Function to get the user list selected from the parameters
  * @param integer $pvPk
  * @param boolean $pbOnlyActive
  * @param boolean $pbOnlyExist
  * @param boolean $pbIncludeRoot
  * @return array
  */
  public function getUserByTeam($pvTeamPk = 0, $pvGroupName = '', $pbOnlyActive = true, $pbSortByStatus = true, $pbAllGroups = false)
  {
    if(!assert('is_integer($pvTeamPk) || is_array($pvTeamPk)'))
      return array();


    if(!assert('is_bool($pbOnlyActive)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `login` l
      LEFT JOIN login_group_member lm ON (l.loginpk=lm.loginfk) ';

    $sWhere = '';

    if($pbOnlyActive)
       $sWhere.= ' WHERE l.status > 0';
    else
      $sWhere.= ' WHERE 1 ';

    if(!empty($pvTeamPk))
    {
      if(is_array($pvTeamPk))
        $sWhere.= ' AND lm.login_groupfk IN ('.implode(',', $pvTeamPk).') ';
      elseif($pvTeamPk >= 0)
      {
        $sWhere.= ' AND lm.login_groupfk = "'.$pvTeamPk.'" ';
      }
    }

    if(!empty($pvGroupName))
    {
      $sQuery.= ' LEFT JOIN login_group lgro ON (lgro.login_grouppk = lm.login_groupfk) ';

      if(!$pbAllGroups)
      {
        if(is_array($pvGroupName))
          $sWhere.= ' AND lgro.shortname IN ("'.implode('", "', $pvGroupName).'") ';
        else
          $sWhere.= ' AND lgro.shortname LIKE "'.$pvGroupName.'" ';
      }
    }

    if($pbSortByStatus)
      $sQuery.= $sWhere.' ORDER BY status Desc, l.firstname';
    else
      $sQuery.= $sWhere.' ORDER BY l.firstname';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $asResult = array();

    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('loginpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }

  /**
  * Function to get the user list selected from the parameters
  * @param integer $pvPk
  * @param boolean $pbOnlyActive
  * @param boolean $pbOnlyExist
  * @param boolean $pbIncludeRoot
  * @return array
  */
  public function getGroupMembers($pvTeamPk = 0, $pvGroupName = '', $pbOnlyActive = true, $pbAddUserWithoutGrp = false)
  {
    if(!assert('is_integer($pvTeamPk) || is_array($pvTeamPk)'))
      return array();

    if(!assert('is_bool($pbOnlyActive)'))
      return array();

    if($pbAddUserWithoutGrp)
      $sJoin = ' LEFT ';
    else
      $sJoin = ' INNER ';

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT *, lgro.title as group_label FROM `login` l
      '.$sJoin.' JOIN login_group_member lm ON (l.loginpk=lm.loginfk)
      '.$sJoin.' JOIN login_group lgro ON (lgro.login_grouppk = lm.login_groupfk) ';

    $sWhere = '';

    if($pbOnlyActive)
       $sWhere.= ' WHERE l.status > 0';
    else
      $sWhere.= ' WHERE 1 ';

    if(!empty($pvTeamPk))
    {
      if(is_array($pvTeamPk))
        $sWhere.= ' AND lm.login_groupfk IN ('.implode(',', $pvTeamPk).') ';
      elseif($pvTeamPk >= 0)
      {
        $sWhere.= ' AND lm.login_groupfk = "'.$pvTeamPk.'" ';
      }
    }
    elseif(!empty($pvGroupName))
    {
      if(is_array($pvGroupName))
        $sWhere.= ' AND lgro.shortname IN ("'.implode('", "', $pvGroupName).'") ';
      else
        $sWhere.= ' AND lgro.shortname LIKE "'.$pvGroupName.'" ';
    }

    $sQuery.= $sWhere.' ORDER BY lgro.title, l.firstname';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $asResult = array();

    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('login_groupfk')][$oDbResult->getFieldValue('loginpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }

  /**
  * Function to get the user list selected from the parameters
  * @param integer $pvPk
  * @param boolean $pbOnlyActive
  * @param boolean $pbOnlyExist
  * @param boolean $pbIncludeRoot
  * @return array
  */
  public function getUserInMultiGroups($panGroupPk, $pbOnlyActive = true)
  {
    if(!assert('is_array($panGroupPk) && !empty($panGroupPk)'))
      return array();

    if(!assert('is_bool($pbOnlyActive)'))
      return array();

    if(count($panGroupPk) > 5)
    {
      assert('false; /* can not fetch user from more than 5 groups */');
      return array();
    }

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `login` as l ';

    foreach($panGroupPk as $nKey => $nGroupPk)
    {
      $sQuery.= ' INNER JOIN login_group_member lm'.$nKey.' ON (l.loginpk = lm'.$nKey.'.loginfk AND lm'.$nKey.'.login_groupfk = '.$nGroupPk.')';
    }

    if($pbOnlyActive)
       $sQuery.= ' WHERE l.status > 0';

    //dump($sQuery);
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asResult = array();

    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('loginpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }


  /**
   * Selector of the users of BCM
   * @return type
   */

  private function _getSelectorUser()
  {
    $sSearch = getValue('q');
    if(empty($sSearch))
      return json_encode(array());

    $bAllUsers = (bool)getValue('all_users', 0);
    $bDisplayTeam = (bool)getValue('team', 0);
    $bDisplayId = (bool)getValue('show_id', 1);
    $bFriendly = (bool)getValue('friendly', 0);
    $asJsonData = array();

    $oDB = CDependency::getComponentByName('database');

    if($sSearch == 'all' || $sSearch == 'more' || $sSearch == '--' || $sSearch == '**')
    {
      $sQuery = 'SELECT * FROM login WHERE 1 ';
      $sOrder = ' ORDER BY firstname, lastname ';
    }
    else
    {
      $sQuery = 'SELECT *,
        IF(lower(firstname) = '.$oDB->dbEscapeString(strtolower($sSearch)).', 1, 0) as fname_eq,
        IF(lower(firstname) LIKE '.$oDB->dbEscapeString(strtolower($sSearch).'%').', 1, 0) as fname_start,
        IF(lower(lastname) = '.$oDB->dbEscapeString(strtolower($sSearch)).', 1, 0) as lname_eq,
        IF(lower(lastname) LIKE '.$oDB->dbEscapeString(strtolower($sSearch).'%').', 1, 0) as lname_start
        FROM login
        WHERE  ((lower(lastname) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%').'
        OR lower(firstname) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%').'
        OR lower(pseudo) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%').')) ';

      $sOrder = ' ORDER BY fname_eq DESC, lname_eq DESC, fname_start DESC, lname_start DESC,  firstname, lastname ';
    }

    if(!$bAllUsers)
      $sQuery.= ' AND status = 1 ';

    $sQuery.= $sOrder;

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
    {
      while($bRead)
      {
        $asData['id'] = $oDbResult->getFieldValue('loginpk');
        $asData['name'] = '';

        if($bDisplayId)
          $asData['name'] = '#'.$asData['id'].' - ';

        $asData['name'].= $oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');

        if($bFriendly)
        {
          $asData['label'] = '';
          if($bDisplayId)
            $asData['label'] = '#'.$asData['id'].' - ';

          if($oDbResult->getFieldValue('pseudo'))
            $asData['label'].= $oDbResult->getFieldValue('pseudo');
          else
            $asData['label'].= $oDbResult->getFieldValue('firstname');
        }

        $asJsonData[] = json_encode($asData);
        $bRead = $oDbResult->readNext();
      }
    }

    if($bDisplayTeam)
    {
      $oMList = CDependency::getComponentByName('manageablelist');
      $asTeam = $oMList->getListValues('team_users_compact');
      $asAll = array();

      $asData['class_selected'] = 'login_team_selected';
      $bFirst = true;

      foreach($asTeam as $sTeamName => $sTeamMembers)
      {
        $asTeamMembers = explode(',', $sTeamMembers);
        $asAll = array_merge($asAll, $asTeamMembers);

        //add a class on the first Grp to separate with single users
        if($bFirst)
        {
          $bFirst = false;
          $asData['class_result'] = 'login_team_selector_separator';
        }
        else
          $asData['class_result'] = 'login_team_selector';

        $asData['id'] = implode(',', $asTeamMembers);
        $asData['name'] = '[Group] - '.$sTeamName;
        $asJsonData[] = json_encode($asData);
      }

      $asData['id'] = implode(',', array_unique($asAll));
      $asData['name'] = '[Group] - Everybody';
      $asJsonData[] = json_encode($asData);
    }

    echo '['.implode(',', $asJsonData).']';
  }

  /**
   * Display all information of user
   * @param integer $pnLoginPk
   * @return array
   */

  public function getUserDataByPk($pnLoginPk = 0, $pbWithGroup = false, $pbActiveOnly = false)
  {
    if(!assert('is_integer($pnLoginPk) && is_bool($pbWithGroup) && is_bool($pbActiveOnly)'))
      return array();

    if(empty($pnLoginPk))
      $pnLoginPk = $this->getUserPk();

    $oDB = CDependency::getComponentByName('database');

    if(!$pbWithGroup)
      $sQuery = 'SELECT * FROM login WHERE loginpk = '.$pnLoginPk;
    else
    {
      $sQuery = 'SELECT * FROM login ';
      $sQuery.= ' LEFT JOIN login_group_member as lgme ON (lgme.loginfk = login.loginpk AND lgme.loginfk = '.$pnLoginPk.') ';
      $sQuery.= ' LEFT JOIN login_group as lgro ON (lgro.login_grouppk = lgme.login_groupfk) ';
      $sQuery.= ' WHERE login.loginpk = '.$pnLoginPk;
    }

    if($pbActiveOnly)
      $sQuery.= ' AND login.status > 0 ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asGroup = array();
    while($bRead)
    {
      $nGoupPk = (int)$oDbResult->getFieldValue('login_grouppk');
      if(!empty($nGoupPk))
        $asGroup[$nGoupPk] = $oDbResult->getFieldValue('title');

      $bRead = $oDbResult->readNext();
    }

    $asUserData = $oDbResult->getData();
    $asUserData['group'] = $asGroup;

    return $asUserData;
  }

  /**
   * Log the user recent activity
   * @param integer $pnLoginPk
   * @param string $psItemUid
   * @param string $psItemAction
   * @param string $psItemType
   * @param string $pnItemPk
   * @param string $sText
   * @param string $sLink
   * @return boolean
  */
  public function logUserActivity($pnLoginPk, $psItemUid, $psItemAction='', $psItemType='', $pnItemPk=0, $psAction='', $psItemLabel = '', $psLink='', $pnFollowerfk=0, $pnNotifierfk=0)
  {
    if(!assert('is_integer($pnLoginPk) && !empty($psItemUid)') && !empty($pnLoginPk))
        return false;

    if(!assert('is_integer($pnItemPk) && is_integer($pnFollowerfk) && is_integer($pnNotifierfk)'))
        return false;

    if($pnLoginPk == -1)
      return true;

    $asLogData = array();
    $asLogData['text'] = $psAction;
    $asLogData['link'] = $psLink;
    $asLogData['followerfk'] = $pnFollowerfk;
    $asLogData['notifierfk'] = $pnNotifierfk;
    $asLogData['item'] = '';

    if(!empty($psItemLabel))
    {
      $asLogData['item'] = $psItemLabel;
    }
    else
    {
      //try to fetch an item description from the component
      try
      {
        $oComponent = CDependency::getComponentByUid($psItemUid);
        if(!$oComponent)
          throw new Exception();

        $asItem = $oComponent->getItemDescription();
        if(empty($asItem))
          throw new Exception();

        $asLogData['item'] = $asItem['label'];
      }
      catch (Exception $ex)
      {
        assert('false; // need to code getItemDescription in '.$psItemUid.' ');
      }

    }

    return $this->logUserAction($pnLoginPk, $psItemUid, $psItemAction, $psItemType, $pnItemPk, $asLogData);
  }

  public function logUserAction($pnLoginPk, $psItemUid, $psItemAction='', $psItemType='', $pnItemPk=0, $pasLogData = array())
  {
    if(!assert('is_key($pnLoginPk) && !empty($psItemUid)'))
        return false;

    if(!assert('is_integer($pnItemPk) && is_array($pasLogData)'))
        return false;

    if(!isset($pasLogData['text']) || empty($pasLogData['text']))
      $sText = 'NULL';
    else
      $sText = $pasLogData['text'];

    if(!isset($pasLogData['item']) || empty($pasLogData['item']))
      $sItemLabel = 'NULL';
    else
      $sItemLabel = $pasLogData['item'];

    if(!isset($pasLogData['link']) || empty($pasLogData['link']))
      $sLink = 'NULL';
    else
      $sLink = $pasLogData['link'];

    if(!isset($pasLogData['followerfk']) || empty($pasLogData['followerfk']))
      $nFollowerfk = 'NULL';
    else
      $nFollowerfk = (int)$pasLogData['followerfk'];

    if(!isset($pasLogData['notifierfk']) || empty($pasLogData['notifierfk']))
      $nNotifierfk = 'NULL';
    else
      $nNotifierfk = (int)$pasLogData['notifierfk'];

    if(!isset($pasLogData['data']) || empty($pasLogData['data']))
      $sData = 'NULL';
    else
    {
      $sData = base64_encode(serialize($pasLogData['data']));
    }

    if(!isset($pasLogData['force_log']) || empty($pasLogData['force_log']))
      $bCheckRecent = true;
    else
      $bCheckRecent = false;

    $sDate = date('Y-m-d H:i:s');
    $oDB = CDependency::getComponentByName('database');

    if($bCheckRecent)
    {
      //search any activity on this element in the last N hours
      $sQuery = 'SELECT * FROM `login_activity` WHERE loginfk="'.$pnLoginPk.'" AND followerfk= "'.$nFollowerfk.'" ';
      $sQuery.= ' AND cp_uid= "'.$psItemUid.'" AND cp_type="'.$psItemType.'" AND cp_pk="'.$pnItemPk.'" ';
      $sQuery.= ' AND TIMESTAMPDIFF(HOUR, log_date, "'.$sDate.'") < 4  ';
      $sQuery.= ' LIMIT 10 ';
      //echo $sQuery;

      $oResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oResult->readFirst();
      if($bRead)
      {
        //if action is view and we've found recent entry(ies), no need to log
        if($psItemAction == CONST_ACTION_VIEW)
        {
          //echo 'action view, but already recent view';
          return false;
        }

        // if 1 existing entry has action=VIEW, and the current one isn't,
        // we update the view by a more important one
        while($bRead)
        {
          $nActivityPk = (int)$oResult->getFieldValue('login_activitypk');

          //overwrite the view entry, by a more important one. Any other case will lead to a new entry
          if(!empty($nActivityPk) && $psItemAction != CONST_ACTION_VIEW && $oResult->getFieldValue(CONST_CP_ACTION) == CONST_ACTION_VIEW)
          {
            //echo 'update activity with : '.$psItemAction;
            $sQuery= 'UPDATE `login_activity` SET `text` = '.$oDB->dbEscapeString($sText).', `item` = '.$oDB->dbEscapeString($sItemLabel).',
              `data` = '.$oDB->dbEscapeString($sData).', `log_date` = "'.$sDate.'", `log_link` = '.$oDB->dbEscapeString($sLink).'
              WHERE login_activitypk = '.$nActivityPk;

            if($oDB->ExecuteQuery($sQuery))
              return true;

            assert('false; //could not update activity');
            return false;
          }

          $bRead = $oResult->readNext();
        }
      }
    }

    //no result or nothing updated: we insert the new avtivity log
    //echo 'No activity found or updated: create a new one ';

    $sQuery= 'INSERT INTO `login_activity`(`loginfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`,`text`, `item`, `data`, `log_date`,`log_link`,`followerfk`,`notifierfk`)
     VALUES ('.$oDB->dbEscapeString($pnLoginPk).', '.$oDB->dbEscapeString($psItemUid).', '.$oDB->dbEscapeString($psItemAction).',
    '.$oDB->dbEscapeString($psItemType).', '.$oDB->dbEscapeString($pnItemPk).', '.$oDB->dbEscapeString($sText).', '.$oDB->dbEscapeString($sItemLabel).',
    '.$oDB->dbEscapeString($sData).','.$oDB->dbEscapeString($sDate).','.$oDB->dbEscapeString($sLink).','.$oDB->dbEscapeString($nFollowerfk).',
    '.$oDB->dbEscapeString($nNotifierfk).') ';
    //echo $sQuery;

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    if($oDbResult)
    {
      $oDbResult->readFirst();
      return (int)$oDbResult->getFieldValue('pk');
    }

    return 0;
  }


  /**
   *  Retrieve the user activities
   * @param type $pnLoginPk
   * @param type $psItemUid
   * @param type $psItemAction
   * @param type $psItemType
   * @param type $pnItemPk
   * @param type $pasLogData
   * @return boolean
   */
  public function getUserActivity($pnLoginPk, $psItemUid, $pvItemAction = null, $psItemType = null, $pnItemPk = null, $pnLimit = 20)
  {
    if(!assert('is_key($pnLoginPk) && !empty($psItemUid) '))
      return array();

    if(!assert('(is_integer($pnItemPk) || $pnItemPk === null) && is_key($pnLimit)'))
      return array();

    //search any activity on this element in the last N hours
    $sQuery = 'SELECT * FROM `login_activity` WHERE loginfk="'.$pnLoginPk.'"  ';
    $sQuery.= ' AND cp_uid= "'.$psItemUid.'" ';

    if($pvItemAction !== null)
    {
      if(is_array($pvItemAction))
        $sQuery.= ' AND cp_action IN ("'.implode('","', $pvItemAction).'") ';
      else
        $sQuery.= ' AND cp_action = "'.$pvItemAction.'" ';
    }

    if($psItemType !== null)
      $sQuery.= ' AND cp_type = "'.$psItemType.'" ';

    if($pnItemPk !== null)
      $sQuery.= ' AND cp_pk = "'.$pnItemPk.'" ';

    $sQuery.= ' ORDER BY login_activitypk DESC LIMIT '.$pnLimit;
    //echo $sQuery;

    $oDB = CDependency::getComponentByName('database');
    $asResult = array();

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    while($bRead)
    {
      $asData = $oResult->getData();

      $asData['data'] = @unserialize(base64_decode($asData['data']));
      //dump($asData['data']);
      if($asData['data'] === false)
      {
        //assert('false; // login_activity data badly formatted');
        $asData['data'] = array();
      }

      $asResult[] = $asData;
      $bRead = $oResult->readNext();
    }

    return $asResult;
  }

  /**
   *  Retrieve the user activities
   * @param type $pnLoginPk
   * @param type $psItemUid
   * @param type $psItemAction
   * @param type $psItemType
   * @param type $pnItemPk
   * @param type $pasLogData
   * @return boolean
   */
  public function getUserActivityByPk($pnHistoryPk)
  {
    if(!assert('is_key($pnHistoryPk)'))
      return array();

    //search any activity on this element in the last N hours
    $sQuery = 'SELECT * FROM `login_activity` WHERE login_activitypk="'.$pnHistoryPk.'"  ';
    $oDB = CDependency::getComponentByName('database');

    $oResult = $oDB->ExecuteQuery($sQuery);
    if(!$oResult->readFirst())
      return array();

    $asData = $oResult->getData();

    if(!empty($asData['data']))
    {
      $asData['data'] = @unserialize(base64_decode($asData['data']));

      if($asData['data'] === false)
      {
        //assert('false; // login_activity data badly formatted');
        $asData['data'] = array();
      }
    }

    return $asData;
  }



  /**
   * Clean the user activity table: remove > 2months old entries
   * @return boolean indicating if entries have been removed
   */
  private function _cleanRecentActivity()
  {
    $oDB = CDependency::getComponentByName('database');

    $sDate = date('Y-m-d', mktime(0, 0, 0, (int)date('m')-2, date('d'), date('Y')));

    $sQuery = ' SELECT `login_activitypk` FROM `login_activity` WHERE `log_date` < "'.$sDate.'" ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
    {
      echo 'No activity to clean <br />';
      return true; // nothing to clean
    }

    $anToDelete = array();
    while($bRead)
    {
      $anToDelete[] = $oDbResult->getFieldValue('login_activitypk', CONST_PHP_VARTYPE_INT);
      $bRead = $oDbResult->readNext();
    }

    $sQuery = ' DELETE FROM login_activity WHERE login_activitypk IN ('.implode(',', $anToDelete).') ';
    $oDbResult = $oDB->executeQuery($sQuery);

    if(!$oDbResult)
    {
      echo 'couldn\'t delete user activity.';
      return false;
    }

    echo count($anToDelete).' activity entries have been deleted <br />';
    return true;
  }

  /**
   * Function to notify about the birthday to the users and birthday boy
   * @return boolean
   */

  private function _checkBirthday()
  {
    //TODO : Add the field called birthdate in the login table
    $oDB = CDependency::getComponentByName('database');
    $oMail = CDependency::getComponentByName('mail');

    $sMonth = date('m');
    $sTomorrow = date('d',strtotime("+1 days"));
    $sToday = date('d');

    //Sending Email to others except birthday boy

    $sQuery = 'SELECT * FROM login WHERE MONTH(birthdate) = "'.$sMonth.'" AND DAY(birthdate) = "'.$sTomorrow.'"';
    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
     {
      $asBirthdayBoys = array();
      while($bRead)
      {
        $asBirthdayBoys[$oDbResult->getFieldValue('loginpk',CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
        $bRead = $oDbResult->readNext();
      }

      //All users
      $asUserList = $this->getUserList(0,true,false);

      $asNames = array();
      foreach($asBirthdayBoys as $nKey => $asValue)
      {
         array_push($asNames, $this->getUserNameFromData($asValue));
       }

      foreach($asUserList as $asUsers)
      {
        if(!in_array(array_keys($asBirthdayBoys), $asUsers))
        {
          $sContent = 'Hello Everyone, <br/>';
          $sContent.= 'Tomorrow is birthday of '.implode(',',$asNames).'  <br/>';
          $sContent.= 'Have fun <br/>';
          $oMail->sendRawEmail('info@bcm.com',$asUsers['email'],'Birthday Tomorrow',$sContent);

          }
        }
      }

     //Send wishes to birthday person on birthdate

    $sQuery = 'SELECT * FROM login WHERE MONTH(birthdate) = "'.$sMonth.'" AND DAY(birthdate) = "'.$sToday.'"';
    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
     {
      $asBirthdayBoys = array();
      while($bRead)
      {
        $asBirthdayBoys[$oDbResult->getFieldValue('loginpk',CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
        $bRead = $oDbResult->readNext();
        }

        foreach($asBirthdayBoys as $nKey=>$asValue)
        {
          $sName = $this->getUserNameFromData($asValue);

          $sContent = 'Dear '.$sName.' , <br/>';
          $sContent.= 'Happy Birthday !!! We want to wish your a successful year ahead. <br/>';
          $sContent.= 'May all your wishes come true <br/>';
          $sContent.= 'Enjoy!!! <br/>';

          $oMail->sendRawEmail('info@bcm.com',$asValue['email'],'Happy Birthday ',$sContent);

          }
       }
       return true;
    }


   public function getCheckRecentActivity($pnItemPk)
   {
    if(!assert('!empty($pnItemPk) && is_integer($pnItemPk)'))
      return 0;

    $pnLoginPk = $this->getUserPk();
    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * FROM login_activity where cp_pk ='.$pnItemPk.' and followerfk!=0 and notifierfk ='.$pnLoginPk.'  AND status=0';
    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

     if(!$bRead)
      return 0;
     else
       return $oResult->getFieldValue('login_activitypk', CONST_PHP_VARTYPE_INT);
  }

  /**
   * Function to display userlist for other component
   * @return type
   */
  public function getUserPageList()
  {
    $sHTML = $this->_displayList(false);
    return $sHTML;
  }

  public function getUpdateRecentActivity($pnPK)
  {
    if(!assert('!empty($pnPK) && is_integer($pnPK)'))
      return false;

     $oDB = CDependency::getComponentByName('database');
     $sQuery = 'UPDATE  login_activity SET status = 1 WHERE login_activitypk ='.$pnPK;
     $oResult = $oDB->ExecuteQuery($sQuery);
     if($oResult)
       return true;
  }

  public function setLanguage($psLanguage)
  {
    $gasLang = array();
    require_once('language/language.inc.php5');

    if(isset($gasLang[$psLanguage]))
      $this->casText = $gasLang[$psLanguage];
    else
      $this->casText = $gasLang[CONST_DEFAULT_LANGUAGE];
  }

  public function getUsers($pbNoadmin = 1)
  {
    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * FROM `login`';
    if($pbNoadmin==1)
      $sQuery .= ' WHERE is_admin <> 1';
    $sQuery .= ' ORDER BY is_admin, status DESC, firstname asc';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  /**
   * Display all the users from the system.
   * "Alias" of the private function to be re-used as a contact sheet
   * @return string HTML
   */
  public function displayUserList($pbFullPage = true)
  {
    return $this->_displayList($pbFullPage);
  }


  private function _displayList($pbFullPage = true)
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(array($this->getResourcePath().'css/login.form.css'));
    $nGroupFk = (int)getValue('login_groupfk', CONST_LOGIN_DEFAULT_LIST_GRP);


    $oRight = CDependency::getComponentByName('right');
    if($oRight->canAccess($this->csUid, CONST_ACTION_MANAGE, CONST_LOGIN_TYPE_USER))
      $bAdmin = true;
    else
      $bAdmin = false;


    if($nGroupFk >= 0)
    {
      $aUserList = $this->getUserByTeam($nGroupFk);
      if($nGroupFk == 0)
        $sTitle = 'Users with no group';
      else
      {
        $aUserGroups = $this->_getModel()->getUserGroup(0, true, true);
        $sTitle = $aUserGroups[$nGroupFk]['title'];
      }
    }
    else
    {
      $sTitle = 'All Users';
      $aUserList = $this->getUserList(0, false, true, 'l.status DESC, l.firstname, l.lastname');
    }



    //Full list container
    $sHTML = $oHTML->getBlocStart('', array('style'=>'position: relative;'));
    if($pbFullPage)
    {
      $sHTML.= $oHTML->getTitleLine($sTitle, $this->getResourcePath().'/pictures/contact_48.png');
      $sHTML.= $oHTML->getCR();
    }

    if(CONST_LOGIN_DISPLAYED_GRP)
    {
      $anGroup = explode(',', CONST_LOGIN_DISPLAYED_GRP);
      $aUserGroups = $this->_getModel()->getUserGroup(0, true, false, $anGroup);
    }
    else
      $aUserGroups = $this->_getModel()->getUserGroup(0, true);

    $aActions = array();

    //Use current URL instead of component url for Slistem
    //page is loaded by another component, it has to stay there
    //$sURL = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_LOGIN_TYPE_USER, 0);
    $sURL = $oPage->getRequestedUrl();
    $sURL = preg_replace('/(&login_groupfk=)([0-9]{0,6})/', '', $sURL);


    if($pbFullPage)
    {
      $aActions[] = array('label' => 'All Users',  'url' => $sURL.'&login_groupfk=-1');
      foreach($aUserGroups as $aGroup)
        $aActions[] = array('label' => $aGroup['title'], 'url' => $sURL.'&login_groupfk='.$aGroup['login_grouppk']);

      $aActions[] = array('label' => 'No Group',  'url' => $sURL.'&login_groupfk=0');
    }
    else
    {
      //Refresh list in ajax for
      $aActions[] = array('label' => 'All Users',  'url' => 'javascript:;', 'onclick' => 'AjaxRequest(\''.$sURL.'&login_groupfk=-1\', \'body\', false, \'area_users\'); ');
      foreach($aUserGroups as $aGroup)
        $aActions[] = array('label' => $aGroup['title'], 'url' => 'javascript:;', 'onclick' => 'AjaxRequest(\''.$sURL.'&login_groupfk='.$aGroup['login_grouppk'].'\', \'body\', false, \'area_users\'); ');

      $aActions[] = array('label' => 'No Group',  'url' => 'javascript:;', 'onclick' => 'AjaxRequest(\''.$sURL.'&login_groupfk=0\', \'body\', false, \'area_users\')' );
    }

    $sHTML.= $oHTML->getActionButtons($aActions, 1, $sTitle, array('width' => 225, 'id' => 'displayUsers'));


    //create list rows
    $aData = array();
    $nInactive = 0;
    foreach($aUserList as $aUser)
    {
      $aRow = array();

      if((int)$aUser['is_admin'] == 1)
        $aRow['icon'] = $oHTML->getPicture($this->getResourcePath().'/pictures/admin_user.png');
      else
      {
        if((int)$aUser['status'] == 1)
          $aRow['icon'] = $oHTML->getPicture($this->getResourcePath().'/pictures/active_user.png');
        else
        {
          $aRow['icon'] = $oHTML->getPicture($this->getResourcePath().'/pictures/inactive_user.png');
          $aRow['class'] = 'hiddenUser';
          $nInactive++;
        }
       }

      $sURL = $oPage->getAjaxUrl('notification', CONST_ACTION_ADD, CONST_NOTIFY_TYPE_MESSAGE, 0, array('loginfk' => $aUser['loginpk']));
      $aRow['login'] = $this->getUserNamefromData($aUser, false,false,false);
      $aRow['login'] = $oHTML->getLink($aRow['login'], 'javascript:;', array('onclick' => '
          var oConf = goPopup.getConfig();
          oConf.height = 500;
          oConf.width = 850;
          goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ', 'title' => 'User #'.$aUser['loginpk'].' created on '.$aUser['date_create']));

      $aRow['position'] = $aUser['position'];
      $aRow['email'] = $oHTML->getLink($aUser['email'], 'mailto:'.$aUser['email'], array('target' => '_blank'));
      $aRow['email'] = $oHTML->getLink($aUser['email'], 'javascript:;', array('onclick' => 'window.open(\'mailto:'.$aUser['email'].'\', \'zm_mail\'); '));

      $sPhone = $aUser['phone'];
      if(trim($sPhone) == '')
        $aRow['phone'] = '&nbsp;';
      else
        $aRow['phone'] = $oHTML->getLink($sPhone, 'callto:'.$sPhone);

      if(trim($aUser['phone_ext']) == '')
        $aRow['ext'] = '&nbsp;';
      else
        $aRow['ext'] = $oHTML->getLink($aUser['phone_ext'], 'callto:'.$aUser['phone_ext']);

      if($bAdmin)
      {
        $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_MANAGE, CONST_LOGIN_TYPE_USER, (int)$aUser['loginpk']);
        $sPic = $oHTML->getPicture(CONST_PICTURE_EDIT, $this->casText['LOGIN_EDIT_USER']);
        $aRow['actions'] = $oHTML->getLink($sPic, 'javascript:;', array('onclick' => 'var oConfig = goPopup.getConfig(); oConfig.width = 900; oConfig.height = 650; goPopup.setLayerFromAjax(oConfig, \''.$sURL.'\');'));
        $aRow['actions'].= $oHTML->getSpace(2);

        if($aUser['status'] == 1)
        {
          $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_DELETE, CONST_LOGIN_TYPE_USER, (int)$aUser['loginpk'],array('status'=>$aUser['status']));
          $sPic = $oHTML->getPicture(CONST_PICTURE_DELETE,$this->casText['LOGIN_DEACTIVATE_USER']);
          $aRow['actions'].= ' '.$oHTML->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\''.$this->casText['LOGIN_DEACTIVATE'].'\')){ return false; }'));
        }
        else
        {
          $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_DELETE, CONST_LOGIN_TYPE_USER, (int)$aUser['loginpk'],array('status'=>$aUser['status']));
          $sPic = $oHTML->getPicture(CONST_PICTURE_REACTIVATE,$this->casText['LOGIN_REACTIVATE_USER']);
          $aRow['actions'] .= ' '.$oHTML->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\''.$this->casText['LOGIN_REACTIVATE'].'\')){ return false; }'));
        }

        //link to right management
        $sURL = $oPage->getAjaxUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTING_RIGHTUSR, (int)$aUser['loginpk']);
        $sPic = $oHTML->getPicture($this->getResourcePath().'pictures/right_16.png', 'Right management');
        $aRow['actions'].= $oHTML->getSpace(2);
        $aRow['actions'].= $oHTML->getLink($sPic, 'javascript:;', array('onclick' => 'var oConfig = goPopup.getConfig(); oConfig.width = 1100; oConfig.height = 650; goPopup.setLayerFromAjax(oConfig, \''.$sURL.'\');'));
      }

      $aData[] = $aRow;
   }




    if($bAdmin)
    {
      $sURL = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_ADD, CONST_LOGIN_TYPE_USER);
      $sHTML.= $oHTML->getActionButton('Add new user', '', CONST_PICTURE_ADD, array('onclick' => 'var oConfig = goPopup.getConfig(); oConfig.width = 900; oConfig.height = 650; goPopup.setLayerFromAjax(oConfig, \''.$sURL.'\');'));
      $sHTML.= $oHTML->getCR(2);

      if($nInactive > 0)
      {
        $sHTML.= $oHTML->getBlocStart();
        $sPic = $oHTML->getPicture($this->getResourcePath().'/pictures/active_user_switch.png', $this->casText['LOGIN_SHOWHIDE_USER']);
        $asOption = array(
            'onclick' =>'
             var sSrc = $(\'> img\', this).attr(\'src\');
             var sAltPic = $(this).attr(\'alternative_pic\');
             if(sSrc == sAltPic)
             {
               $(\'> img\', this).attr(\'src\', $(this).attr(\'default_pic\'));
               $(\'#userList ul > li.hiddenUser\').hide(0);
             }
             else
             {
               setCoverScreen(true);
               $(\'> img\', this).attr(\'src\', sAltPic);
               $(\'#userList ul > li.hiddenUser\').show(0);

               var oPosition = $(\'#userList ul > li.hiddenUser:first\').offset();
               $(this).closest(\'.scrollingContainer\').scrollTop(oPosition.top-100);
               setCoverScreen(false);
             }
             ',
            'default_pic' => $this->getResourcePath().'/pictures/active_user_switch.png', 'alternative_pic' => $this->getResourcePath().'/pictures/inactive_user_switch.png');

        $sHTML.= $oHTML->getLink($sPic.' Show/hide inactive users','javascript:;', $asOption);
        $sHTML.= $oHTML->getBlocEnd();
      }
    }

    $sHTML .= $oHTML->getBlocStart('userList', array('style' => 'padding-right: 2px;'));
    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
    $sAjaxUrl = '';
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam, array('sRefreshWithUrl' => $sAjaxUrl, 'sZoneToRefresh' => 'userList'));

    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');

    $oConf->addColumn(' ', 'icon', array('id' => 'icon', 'width' => '20'));
    $oConf->addColumn($this->casText['LOGIN_NAME'], 'login', array('id' => 'login', 'sortable' => array('javascript' => 1), 'width' => '27%'));
    $oConf->addColumn($this->casText['LOGIN_POSITION'], 'position', array('id' => 'position', 'sortable' => array('javascript' => 1), 'width' => '20%'));
    $oConf->addColumn($this->casText['LOGIN_EMAIL'], 'email', array('id' => 'email', 'sortable' => array('javascript' => 1), 'width' => '20%'));
    $oConf->addColumn($this->casText['LOGIN_PHONE'], 'phone', array('id' => 'phone', 'sortable' => array('javascript' => 1), 'width' => '12%'));
    $oConf->addColumn($this->casText['LOGIN_EXT'], 'ext', array('id' => 'ext',  'sortable' => array('javascript' => 1), 'width' => '5%'));

    if($bAdmin)
      $oConf->addColumn($this->casText['LOGIN_ACTION'], 'actions', array('id' => 'actions', 'width' => '9%'));

    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(false);
    //$oConf->setPagerBottom(true, 'center', $pnTotal, $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0, array_merge(array('nbTotal' => $pnTotal),$pasValues)), array('ajaxTarget' => 'documents-list', 'nb_result' => $nNbResults), 10, array(10,20,30));


   $sHTML.= $oTemplate->getDisplay($aData);
   $sHTML.=$oHTML->getBlocEnd();
   $sHTML.=$oHTML->getBlocEnd();

   return $sHTML;
  }

  public function displayList($pbFullPage = false)
  {
    return $this->_displayList($pbFullPage);
  }


  private function _getHomePage()
  {
    $oPortal = CDependency::getComponentByName('portal');

    CDependency::getCpPage()->setUid($oPortal->getComponentUid());
    return $oPortal->getHomePage();
  }


  public function getGroupByPk($pnGroupPk)
  {
    if(!assert('is_integer($pnGroupPk)'))
      return array();

    $oDbResult = $this->_getModel()->getByPk($pnGroupPk, 'login_group') ;
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    return $oDbResult->getData();
  }

  private function _getGroupForm($pnGroupPk)
  {
    if(!assert('is_integer($pnGroupPk)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(array($this->getResourcePath().'css/login.form.css'));

    if($pnGroupPk > 0)
    {
      $oDbResult = $this->_getModel()->getByPk($pnGroupPk, 'login_group') ;
    }
    else
      $oDbResult = new CDbResult();


    //div including the form
    $sHTML = $oHTML->getBlocStart();

    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_LOGIN_TYPE_GROUP, $pnGroupPk);
    $oForm = $oHTML->initForm('loginGroupForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'inajax' =>1));
    $oForm->setFormDisplayParams(array('noCancelButton' => 1, 'columns' => 1));

    if(empty($pnGroupPk))
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> '<span class="h4">Add a new group</span><hr /><br />'));
    else
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> '<span class="h4">Edit</span><hr /><br />'));

    $oForm->addField('input', 'shortname', array('label' => 'Group shortname', 'value' => $oDbResult->getFieldValue('shortname')));
    $oForm->addField('input', 'title', array('label' => 'Displayed title', 'value' => $oDbResult->getFieldValue('title')));

    $bGroupSystem = (bool)$oDbResult->getFieldValue('system');
    if(empty($pnGroupPk) || !$bGroupSystem)
    {
      $oForm->addField('select', 'system', array('label' => 'Group system'));
      $oForm->addOption('system', array('label' => 'Yes', 'value' => 1));
      $oForm->addOption('system', array('label' => 'No', 'value' => 0, 'selected' => 'selected'));
    }
    else
    {
      $oForm->addField('select', 'system', array('label' => 'Group system', 'readonly' => 'readonly'));
      $oForm->addOption('system', array('label' => 'yes', 'value' => 1, 'selected' => 'selected'));
    }

    if(!empty($pnGroupPk))
    {
      $oForm->addField('checkbox', 'delete', array('label' => 'Delete group ?', 'textbefore' => 1,  'value' => 1, 'onclick' => ''));
      $oForm->setFieldDisplayParams('delete', array('class' => 'deleteGroup'));
    }

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    return $oPage->getAjaxExtraContent(array('data' => $sHTML));
  }


  private function _saveGroup($pnGroupPk)
  {
    if(!assert('is_integer($pnGroupPk)'))
      return array();

    $asData['shortname'] = getValue('shortname');
    $asData['title'] = getValue('title');
    $asData['system'] = (int)getValue('system');


    if(strlen($asData['shortname']) < 3 || strlen($asData['shortname']) >= 255)
      return array('error' => 'Shortname size must be between 3 and 255 characters');

    if(strlen($asData['title']) < 3 || strlen($asData['title']) >= 255)
      return array('error' => 'Title size must be between 3 and 255 characters');

    if($asData['system'] < 0 || $asData['system'] > 1)
      return array('error' => 'Value for group system is invalid.');

    if(empty($pnGroupPk))
    {
      $pnGroupPk = $this->_getModel()->add($asData, 'login_group');
      if(empty($pnGroupPk))
        return array('error' => __LINE__.' - Could not save group.');
    }
    else
    {
      $asData['login_grouppk'] = (int)$pnGroupPk;

      $bUpdate = $this->_getModel()->update($asData, 'login_group');
      if(!$bUpdate)
        return array('error' => 'Could not update group data.');
    }

    return array('notice' => 'Group saved', 'action' => ' goPopup.removeByType(\'layer\'); $(\'#settings li[rel=groups]\').click(); ');
  }


  private function _getGroupList()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(array($this->getResourcePath().'css/login.form.css'));

    $asGroup = $this->_getModel()->getGroups(true, true);

    foreach($asGroup as $nKey => $asGroupData)
    {

      if($asGroupData['system'] > 0)
        $asGroup[$nKey]['title'].=  $oHTML->getLink('  (sys) ', 'javascript:;', array('title' => 'Required for the software to work. Can\'t be changed.'));


      $asGroup[$nKey]['member'] = $oHTML->getBloc('', $asGroupData['count'].' user(s)');

      $sHTML = $oHTML->getBlocStart('', array('style'=>'text-align: right; width: 95%; '));

        if($asGroupData['system'] < 1)
        {
          $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_LOGIN_TYPE_GROUP, (int)$asGroupData['login_grouppk']);
          $sPic = $oHTML->getPicture(CONST_PICTURE_EDIT, 'Edit group');
          $sHTML.= $oHTML->getLink($sPic.' Edit group', 'javascript:;', array('onclick' => 'var oConf = goPopup.getConfig(); oConf.width = 850; oConf.height = 350; goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ')).' ';
          $sHTML.= $oHTML->getSpace(8);
        }

        $sUrl = $oPage->getAjaxUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTING_RIGHTGRP, (int)$asGroupData['login_grouppk']);
        $sPic = $oHTML->getPicture($this->getResourcePath().'/pictures/right_16.png', 'Link rights to this group');
        $sHTML.= $oHTML->getLink($sPic.' Set rights', 'javascript:;', array('onclick' => 'var oConf = goPopup.getConfig(); oConf.width = 1100; oConf.height = 650; goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ')).' ';
      $sHTML.= $oHTML->getBlocEnd();

      $asGroup[$nKey]['action'] = $sHTML;
    }

    //initialize the template
    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

    //get the config object for a specific template (contains default value so it works without config)
    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');

    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(false);

    $oConf->addColumn('Group name', 'title', array('width' => 250, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('# Members in the group', 'member', array('width' => 200, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Actions', 'action', array('width' => 300));

    $sUrl = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_ADD, CONST_LOGIN_TYPE_GROUP);
    $sHTML = $oHTML->getActionButton('Add new group', $sUrl, CONST_PICTURE_ADD, array('ajaxLayer' => 1));
    $sHTML.= $oHTML->getCR(2);
    $sHTML.= $oTemplate->getDisplay($asGroup);

    return $oPage->getAjaxExtraContent(array('data' => $sHTML));
  }

  /**
   * return an atrray list user groups. If getAll = true, all group will be fetched, with loginfk = $pnUserPk in the array
   * @param integer $pnUserPk
   * @param bool $pbGetAll
   * @return array
   */
  public function getUserGroup($pnUserPk, $pbGetAll = false)
  {
    if(!assert('is_key($pnUserPk) || is_bool($pbGetAll)'))
      return array();

    return $this->_getModel()->getUserGroup($pnUserPk, $pbGetAll);
  }

  /**
   * return an array listing the users groups.
   *
   * if $pbAsText is true:    return pk => grp1, grp2, ..
   * if not:                  return pk = array( grp1, grp2,)
   * @param array of integers $pnUserPk
   * @param bool $pbAsText
   * @return array
  */
  public function getUsersGroup($panUserPk = array(), $pbGroup = true, $pbCompact = false)
  {
    if(!assert('is_array($panUserPk)'))
      return array();

    if(!assert('is_bool($pbGroup) && is_bool($pbCompact)'))
      return array();

    return $this->_getModel()->getUsersAndGroup(cast_arrayOfInt($panUserPk), $pbGroup, $pbCompact);
  }


  public function getGroupList()
  {
    return $this->_getModel()->getGroups();

  }

  // In some case (Globus) an external component may wants to interact with login tables

  public function saveFromExternalComponent($pavValues, $pbIsEdition = false)
  {
    if(!assert('is_bool($pbIsEdition)'))
      return 0;

    if(!assert('is_array($pavValues) && !empty($pavValues)'))
      return 0;

    $sFirstname = $pavValues['firstname'];
    $sLastname = $pavValues['lastname'];
    $sEmail = $pavValues['email'];

    if(!assert('is_string($sFirstname) && !empty($sFirstname)'))
      return 0;

    if(!assert('is_string($sLastname) && !empty($sLastname)'))
      return 0;

    if(!assert('isValidEmail($sEmail)'))
      return 0;


    if(!$pbIsEdition)
    {
      $pavValues['loginpk'] = 0;
      $oExists = $this->_getModel()->getByWhere('login', 'email LIKE \''.$sEmail.'\' ');
      $oExists->readFirst();

      //already at least 2 profiles
      if($oExists->numRows() > 1)
        return -1;

      //trying to create a user that already exists ? we update it instead (multi profiles => multi groups)
      if($oExists->numRows() == 1)
      {
        $pbIsEdition = true;
        $pavValues['loginpk'] = (int)$oExists->getFieldValue('loginpk');

      }
    }


    $aData = $pavValues;

    if(!$pbIsEdition)
    {
      $sPassword = $this->_generatePassword();
      $aData['password'] = $sPassword;

      if(!assert('!empty($sPassword) && is_string($sPassword)'))
        return 0;

      $sLogin = strtolower($sLastname.$sFirstname);
      $sLogin = preg_replace('/[^a-z0-9 -]+/', '', $sLogin);
      $sLogin = str_replace(' ', '-', $sLogin);
      if(strlen($sLogin)>10)
        $sLogin = substr($sLogin, 0, 10);

      $aData['id'] = $sLogin;

      $nLoginPk = $this->_getModel()->add($aData, 'login');

      if(!assert('is_key($nLoginPk)'))
        return 0;
    }
    else
    {
      if(!assert('is_key($pavValues[\'loginpk\'])'))
        return 0;

      $aData['date_update'] = date('Y-m-d H:i:s');
      $bUpdated = $this->_getModel()->update($aData, 'login');

      if(!$bUpdated)
        return 0;

      $nLoginPk = $aData['loginpk'];
    }

    return $nLoginPk;
  }

  public function saveGroupFromExternalComponent($psShortname, $pnLoginFk)
  {
    if(!assert('is_key($pnLoginFk)'))
      return false;

    $oGroup = $this->_getModel()->getByWhere('login_group', 'shortname=\''.$psShortname.'\'');
    $nGroupPk = (int)$oGroup->getFieldValue('login_grouppk');

    if(!assert('is_key($nGroupPk)'))
      return false;

    $nGroupMemberPk = $this->_getModel()->add(array('login_groupfk' => $nGroupPk, 'loginfk' => $pnLoginFk), 'login_group_member');

    return $nGroupMemberPk;
  }

  public function deleteFromExternalComponent($pnLoginPk)
  {
    if(!assert('is_key($pnLoginPk)'))
      return false;

    $this->_getModel()->deleteByFk($pnLoginPk, 'login_group_member', 'login');
    $this->_getModel()->deleteByPk($pnLoginPk, 'login');

    return true;
  }

  private function _generatePassword()
  {
    $aData = array(
        'abcdefghijklmnopqrstuwxyz',
        '0123456789',
        'ABCDEFGHIJKLMNOPQRSTUWXYZ',
        '#!-=+'
    );

    $sPass = '';

    foreach ($aData as $sData)
    {
      $nLenght = strlen($sData)-1;
      for ($i=0; $i<=3; $i++)
      {
        $n = rand(0, $nLenght);
        $sPass .= $sData[$n];
      }
    }

    return $sPass;
  }

  public function exists($psField, $pvValue, $pnLoginPk = 0)
  {
    if(!assert('!empty($psField) && is_string($psField)'))
      return false;

    return ($this->_getModel()->exists($psField, $pvValue, $pnLoginPk));
  }


  public function getUserPkFromName($psName, $pbCheckPseudo = false, $pbUseWildcard = false)
  {
    if(!assert('!empty($psName) && is_bool($pbCheckPseudo) && is_bool($pbUseWildcard)'))
      return array();

    $oDB = $this->_getModel();
    if($pbUseWildcard)
      $psName = '%'.$psName.'%';

     $sQuery = 'SELECT loginpk FROM login WHERE firstname LIKE '.$oDB->dbEscapeString($psName).'
       OR lastname LIKE '.$oDB->dbEscapeString($psName).' ';

     if($pbCheckPseudo)
       $sQuery.= ' OR pseudo LIKE '.$oDB->dbEscapeString($psName).' ';

     //dump($sQuery);
     $oDbResult = $oDB->ExecuteQuery($sQuery);
     $bRead = $oDbResult->readFirst();

     if(!$bRead)
       return array();

     $anPk = array();
     while($bRead)
     {
       $anPk[] = (int)$oDbResult->getFieldValue('loginpk');
       $bRead = $oDbResult->readNext();
     }

     return $anPk;
  }
}
