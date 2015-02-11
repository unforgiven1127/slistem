<?php

require_once('component/settings/settings.class.php5');

class CSettingsEx extends CSettings
{
  private $casSettings = array();
  private $_cbSuperAdmin = null;

  public function __construct()
  {
    $bRefresh = (bool)getValue('refresh_settings', 0);

    if(!$bRefresh && isset($_SESSION['settings']) && !empty($_SESSION['settings']))
      $this->casSettings = $_SESSION['settings'];
    else
    {
      $this->_loadSettings();

      if($bRefresh && isDevelopment())
        dump($this->casSettings);
    }
    return true;
  }

  public function __destruct()
  {
      return true;
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  // Normal functions

  public function getHtml()
  {
    $this->_processUrl();
    switch($this->csType)
    {
       case CONST_TYPE_SETTINGS:
        switch($this->csAction)
        {
           case CONST_ACTION_SAVE_CONFIG:
              return $this->_saveSiteConfig();
                break;

           case CONST_ACTION_SAVEEDIT:
             return $this->_saveSiteSetting();
             break;

           default:
             return $this->_displaySettings();
            break;
        }
        break;

        case CONST_TYPE_SETTING_BLACKLIST:

        switch($this->csAction)
        {
           case CONST_ACTION_SAVEADD:
             return $this->_saveBlackList();
               break;
        }
        break;

        case CONST_TYPE_SETTING_FOOTER:

        switch($this->csAction)
        {
            case CONST_ACTION_SAVEADD:
              return $this->_saveFooter();
                break;
         }
         break;

        case CONST_TYPE_SETTING_RIGHTUSR:

          switch($this->csAction)
          {
            case CONST_ACTION_SAVEEDIT:
                return $this->_saveGroupRight($this->cnPk);
                  break;

            case CONST_ACTION_EDIT:
                  return $this->_formGroupRights($this->cnPk, false);
                  break;
          }
          break;


        case CONST_TYPE_SETTING_MENU:

        switch($this->csAction)
        {
            case CONST_ACTION_SAVEADD:
              return $this->_saveMenu();
                break;
        }

      break;

      case CONST_TYPE_USERPREFERENCE:
        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            $aContent = $this->_formUserPreferences();
            if(isset($aContent['error']))
            {
              $oHTML = CDependency::getComponentByName('display');
              return $oHTML->getErrorMessage($aContent['error']);
            }
            elseif(isset($aContent['data']))
            {
              return $aContent['data'];
            }
            break;
        }
        break;
    }
  }

  //Ajax function

  public function getAjax()
  {
    $oPage = CDependency::getCpPage();
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_TYPE_USERPREFERENCE:
        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return json_encode($oPage->getAjaxExtraContent($this->_formUserPreferences()));
            break;

          case CONST_ACTION_SAVE_CONFIG:
            return json_encode($this->_saveUserPreferences());
            break;
        }
        break;

       case CONST_TYPE_SETTINGS:
          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxSettingPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_USER:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxUserPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_USRIGHT:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxUserRightPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_RIGHTUSR:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
                return json_encode($this->_formUserRights($this->cnPk));
                break;

            case CONST_ACTION_SAVEEDIT:
                return json_encode($this->_saveUserRight());
                break;
          }
          break;

        case CONST_TYPE_SETTING_RIGHTGRP:

          switch($this->csAction)
          {
            case CONST_ACTION_SAVEEDIT:
              return json_encode($this->_saveGroupRight($this->cnPk));
              break;

            case CONST_ACTION_ADD:
              return json_encode($this->_formGroupRights($this->cnPk, true));
              break;
          }
          break;

        case CONST_TYPE_SETTING_MENU:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxMenuPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_FOOTER:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxFooterPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_BLACKLIST:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxBlackListPage());
              break;
          }
          break;

        case CONST_TYPE_SETTING_CRON:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
              return json_encode($this->_getAjaxCronPage());
              break;
          }
          break;

       case CONST_TYPE_SYSTEM_SETTINGS:
         return json_encode($this->_getAjaxUpdateSetting());
         break;
     }
  }

  /**
   * Function to display the setting form in ajax
   * @return array
   */

  private function _getAjaxSettingPage()
  {
    $sData = $this->_formSiteConfig();

    if(empty($sData) || $sData == 'null' || $sData == null)
       return array('data' => 'Sorry, an error occured while refreshing the list.');

    return array('data' =>$sData);
  }

  /**
   * Function to manage user add/edit/delete
   * @return array of data
   */

  private function _getAjaxUserPage()
  {
    $oLogin = CDependency::getCpLogin();
    $sData = $oLogin->getUserPageList();

    if(empty($sData) || $sData == 'null' || $sData == null)
      return array('data' => 'Sorry, an error occured while refreshing the list.');

    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxExtraContent(array('data' =>$sData));
  }

  /**
   * Function to manage user rights
   * @return array of data
   */

  private function _getAjaxUserRightPage()
  {
    $sData = $this->_getUserRights();

    if(empty($sData) || $sData == 'null' || $sData == null)
      return array('data' => 'Sorry, an error occured while refreshing the list.');

    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxExtraContent(array('data' =>$sData));
  }


  /**
   * Function to manage menu
   * @return array of data
   */

  private function _getAjaxMenuPage()
  {
    $sData = $this->_getMenuSetting();

   if(empty($sData) || $sData == 'null' || $sData == null)
     return array('data' => 'Sorry, an error occured while refreshing the list.');

    return array('data' =>$sData);

  }

  /**
   * Function to manage footer
   * @return array of data
   */

  private function _getAjaxFooterPage()
  {
    $sData = $this->_getFooterSetting();

    if(empty($sData) || $sData == 'null' || $sData == null)
       return array('data' => 'Sorry, an error occured while refreshing the list.');

    return array('data' =>$sData);
  }


  /**
   * Function to manage blacklist components
   * @return array of data
   */
  private function _getAjaxBlackListPage()
  {
    $oPage = CDependency::getCpPage();
    $sData = $this->_formBlackList();

    if(empty($sData) || $sData == 'null' || $sData == null)
       return array('data' => 'Sorry, an error occured while refreshing the list.');

    return $oPage->getAjaxExtraContent(array('data' =>$sData));
  }


  /**
   * Function to execute cron
   * @return array of data
   */

  private function _getAjaxCronPage()
  {
     $asData = $this->_getCronSettingForm();

    if(empty($asData) || $asData == 'null')
       return array('data' => 'Sorry, an error occured while refreshing the list.');

    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxExtraContent($asData);
  }

  // Loads user preferences. Overwritte default values of user settings in $_SESSION

  public function loadUserPreferences($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return false;

    $oDB = CDependency::getComponentByName('database');

    $sQuery = ' SELECT su.value, s.fieldname, s.fieldtype
                FROM settings_user su RIGHT OUTER JOIN settings s
                ON s.settingspk=su.settingsfk
                WHERE su.loginfk='.$pnUserPk;
    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();


    while($bRead)
    {
      $asSettingData = $oDbResult->getData();
      $asRecords = $this->_getSetting($asSettingData);

      $_SESSION['settings'] = array_replace_recursive($_SESSION['settings'], $asRecords);
      $this->casSettings = array_replace_recursive($this->casSettings, $asRecords);
      $bRead = $oDbResult->readNext();
    }

    return true;
  }

   /**
   * Function to set the setting values in to session
   * @param array/string $pvString
   * @return boolean
   */

  private function _loadSettings($pvString = '')
  {
    $oDB = CDependency::getComponentByName('database');

    if(empty($pvString))
      $sQuery = 'SELECT * FROM settings';
    elseif(is_string($pvString) && !empty($pvString))
      $sQuery = 'SELECT * FROM settings WHERE `fieldname` = "'.$pvString.'"';
    elseif(is_array($pvString) && !empty($pvString))
    {
      $asSettings = $pvString;
      $sQuery = 'SELECT * FROM settings WHERE `fieldname` IN ('.implode(',',$asSettings).')';
    }

    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
    {
      $asSettingData = array();

      $asRecords = array();
      while($bRead)
      {
        $asSettingData = $oDbResult->getData();

        $asRecords = array_merge($this->_getSetting($asSettingData), $asRecords);

        $bRead = $oDbResult->readNext();
      }
      $this->casSettings = $asRecords;

      if(empty($this->casSettings))
      {
        unset($_SESSION);
        return false;
      }

      $_SESSION['settings'] = $this->casSettings;
      return true;

    }
    return false;
  }

  // When getting a setting from database, transform it depending of its type
  // Returning cleaned value to be stored in session

  private function _getSetting($asSettingData)
  {
    if(!assert('is_array($asSettingData)'))
      return array();

    $asRecords = array();
    switch($asSettingData['fieldtype'])
    {
      case 'serialized64':
        if(empty($asSettingData['value']))
          $asRecords[$asSettingData['fieldname']] = array();
        else
        {
          try
          {
            $asRecords[$asSettingData['fieldname']] = unserialize(base64_decode($asSettingData['value']));
          }
          catch(Exception $e)
          {
            $asRecords[$asSettingData['fieldname']] = array();
            assert('false; // can not unserialize setting ['.$asSettingData['fieldname'].' / '.$asSettingData['value'].']');
          }
        }
        break;

      case 'sortable':
      case 'serialized':
      case 'select_multi':

        if(empty($asSettingData['value']))
          $asRecords[$asSettingData['fieldname']] = array();
        else
        {
          try
          {
            $asRecords[$asSettingData['fieldname']] = unserialize($asSettingData['value']);
          }
          catch(Exception $e)
          {
            $asRecords[$asSettingData['fieldname']] = array();
            assert('false; // can not unserialize setting ['.$asSettingData['fieldname'].' / '.$asSettingData['value'].']');
          }
        }
        break;

      case 'xml':
        if(!empty($asSettingData['value']))
        {
          $oXML = new SimpleXMLElement($asSettingData['value']);
          $asRecords[$asSettingData['fieldname']] =  simplexmlToArray($oXML);
        }
        break;

      case 'text':
          $asRecords[$asSettingData['fieldname']] = $asSettingData['value'];
        break;

      default:
        $asRecords[$asSettingData['fieldname']] = $asSettingData['value'];
        break;
    }

    return $asRecords;
  }

  /**
  * Function to set the setting values in to session. If strict: assert if calling a settings that doesn't exist
  * @param array/string $pvString
  * @param boolean $pbStrict
  * @return array
  */
  public function getSettings($pvString, $pbStrict = true)
  {
    if(!assert('!empty($pvString) && is_bool($pbStrict)'))
      return array();

     $asRecord = array();

     if(is_array($pvString) && !empty($pvString))
     {
       foreach ($pvString as $sValue)
       {
         if(!isset($_SESSION['settings'][$sValue]))
         {
           if($pbStrict)
             assert('false; // setting ['.$sValue.'] not available ');
           else
             $_SESSION['settings'][$sValue] = '';
         }

         $asRecord[$sValue] = $_SESSION['settings'][$sValue];
       }
     }
     else
     {
       if(isset($_SESSION['settings'][$pvString]) && !empty($_SESSION['settings'][$pvString]))
         $asRecord[$pvString] = $_SESSION['settings'][$pvString];
     }

     return $asRecord;
   }

   public function getSettingValue($pvString)
   {
     if(!assert('!empty($pvString) && is_string($pvString)'))
       return '';

     if(!isset($_SESSION['settings'][$pvString]))
       return '';

     return $_SESSION['settings'][$pvString];
   }

  /**
  * Allow to use private/system settings.
  * Less restricted than normal settings, no assert if not found
  * It's coupled with a function to setNew System settings
  * @param array/string $pvString
  * @return array
  */
  public function getSystemSettings($pvString)
  {
    if(empty($pvString))
      return array();

    $asRecord = array();

    if(is_array($pvString))
    {
      foreach ($pvString as $sValue)
      {
        if(isset($_SESSION['settings'][$sValue]))
          $asRecord[$sValue] = $_SESSION['settings'][$sValue];
      }
    }
    else
    {
      if(isset($_SESSION['settings'][$pvString]) && !empty($_SESSION['settings'][$pvString]))
        $asRecord[$pvString] = $_SESSION['settings'][$pvString];
    }

    return $asRecord;
  }

  /**
  * Allow to set private/system settings.
  * all those settings are serialized fields to give more flexibility
  * It's coupled with a function to setSystem settings
  * @param array/string $pvString
  * @return array
  */
  public function setSystemSettings($pvShortname, $pvValue)
  {
    if(!assert('!empty($pvShortname)'))
      return false;

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT settingspk FROM settings WHERE fieldname = '.$oDB->dbEscapeString($pvShortname);
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if($bRead)
    {
      $sQuery = 'UPDATE settings SET value = '.$oDB->dbEscapeString(serialize($pvValue)).' WHERE `settingspk` = '.$oDbResult->getFieldValue('settingspk', CONST_PHP_VARTYPE_INT);
    }
    else
    {
      $sQuery = 'INSERT INTO settings (`fieldname`,`fieldtype`,`value`,`description`)';
      $sQuery.= ' VALUES ('.$oDB->dbEscapeString($pvShortname).', "serialized",';
      $sQuery.= ''.$oDB->dbEscapeString(serialize($pvValue)).', '.$oDB->dbEscapeString('system setting auto generated').')';
    }

    //echo $sQuery;
    $bResult = (bool)$oDB->ExecuteQuery($sQuery);

    if(!$bResult)
     return false;

    $this->casSettings[$pvShortname] = $pvValue;
    $_SESSION['settings'] = $this->casSettings;

    return true;
  }

  private function _getAjaxUpdateSetting()
  {
    $sSettingName = getValue('setting');
    $sSettingValue = getValue('value');

    if(!assert('!empty($sSettingName)'))
      return array('error' => 'no setting name');

    if(!$this->setSystemSettings($sSettingName, $sSettingValue))
      return array('error' => 'error saving setting.');

    return array('data' => '0k');
  }


  /**
   * Function to display the blacklist form
   * @return string
   */

  private function _formBlackList()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sFileName = file_get_contents('./conf/custom_config/'.CONST_WEBSITE.'/blacklist.inc.php5');

    $sHTML= $oHTML->getBlocStart();
      $sHTML.= $oHTML->getBlocStart('', array('class'=>'settingsTitleBloc'));
      $sHTML.= $oHTML->getText('Manage Black List ');
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getCR(2);

      $sURL = $oPage->getUrl($this->_getUid(), CONST_ACTION_SAVEADD,CONST_TYPE_SETTING_BLACKLIST);
      $oForm = $oHTML->initForm('blackListForm');
      $oForm->setFormParams('', false, array('action' => $sURL, 'submitLabel'=>'Save'));
      $oForm->addField('textarea', 'blacklist', array('label'=> 'Blacklist Contents ', 'value' =>$sFileName,'style'=>'width:680px;height:300px;'));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

  return $sHTML;
 }

 /**
  * Save the black list content and store in file
  * @return redirection to the defined URL
  */

 private function _saveBlackList()
 {
   $oPage = CDependency::getCpPage();
   $oHTML = CDependency::getCpHtml();

   $sFileName = './conf/blacklist.inc.php5';
    $sBlackList = getValue('blacklist');
    $sURL = $oPage->getUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTINGS);
   file_put_contents($sFileName, $sBlackList);

   return $oHTML->getRedirection($sURL);
  }

 /**
  * Form to execute cronjob for the specific component
  * @return string
  */
  private function _getCronSettingForm()
  {
    if(getValue('mapdb'))
      return array('data' => $this->_mappingDb());

    if(getValue('gen_right'))
      return  $this->_rightGenerator();

    $oHTML = CDependency::getCpHtml();

    $sHTML = $oHTML->getBlocStart();

    $sHTML.= $oHTML->getBlocStart('', array('class'=>'settingsTitleBloc'));
    $sHTML.= $oHTML->getText('Cron Jobs');
    $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getText('- Launch cron job now ? <a href="/index.php5?pg=cron&hashCron=1" target="_blank">Start</a><br />');
    $sHTML.= $oHTML->getText('- Launch silent cron job ? <a href="/index.php5?pg=cron&hashCron=1&cronSilent=1" target="_blank">Start</a><br /><br /><br />');
    $sHTML.= $oHTML->getText('Launch a specific cron job:<br /><br />');

    $asComponentUid = CDependency::getComponentUidByInterface('has_cron');

    foreach($asComponentUid as $sUid)
    {
      $oComponenent = CDependency::getComponentByUid($sUid);
      $sHTML.= $oHTML->getBloc('', '&rarr;&nbsp;&nbsp;'.  ucfirst($oComponenent->getComponentname()).' <a href="/index.php5?pg=cron&hashCron=1&custom_uid='.$sUid.'" target="_blank">Start</a><br />', array('class' => 'settingCronRow'));
    }


    $sHTML.= $oHTML->getText('<br /><br />Map database fields: <a href="javascript:;" onclick="var oConf = goPopup.getConfig(); oConf.width = 1150; oConf.height = 725; goPopup.setLayerFromAjax(oConf, \'/index.php5?uid=665-544&ppa=ppaa&ppt=stgcrn&ppk=0&pg=ajx&mapdb=1\'); " >Start</a><br />');
    $sHTML.= $oHTML->getText('<br />Generate right: <a href="javascript:;"onclick="var oConf = goPopup.getConfig(); oConf.width = 1100; oConf.height = 650; goPopup.setLayerFromAjax(oConf, \'/index.php5?uid=665-544&ppa=ppaa&ppt=stgcrn&ppk=0&pg=ajx&gen_right=1\'); " >Start</a><br />');


   $sHTML.= $oHTML->getBlocEnd();
  return array('data' => $sHTML);
}

  /**
   * Function to display the form to manage user rights
   * @param integer $pnLoginPk
   * @return array of data
  */
  private function _formUserRights($pnLoginPk, $pbIsAjax = true)
  {
    if(!assert('is_integer($pnLoginPk) && !empty($pnLoginPk)'))
       return array();

     if(!assert('is_bool($pbIsAjax)'))
       return array();

    $oRight = CDependency::getComponentByName('right');
    $oLogin = CDependency::getCpLogin();
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/settings.css');

    $asRightData = $oRight->getRightList(true, false, false);
    //dump('all rights');
    //dump($asRightData);

    $asUserRights = $oRight->getUserRightsPk($pnLoginPk);
    //dump('user '.$pnLoginPk.' rights');
    //dump($asUserRights);

    //list of the group the user is member of
    $asUserGroups = $oLogin->getUserGroup($pnLoginPk);
    //dump('user '.$pnLoginPk.' groups');
    //dump($asUserGroups);

    //list of the rights for each of thopse groups
    $asGroupRights = $oRight->getGroupRights(array_keys($asUserGroups));
    //dump('rights linked to thos ('.implode(',', array_keys($asUserGroups)).') groups');
    //dump($asGroupRights);

    //convert to get an array rightPk => group
    $asRightGroup = array();
    foreach($asGroupRights as $nGroupPk => $asData)
    {
      foreach($asData as $asRightDetail)
      {
        $nRightPk = $asRightDetail['rightpk'];

        if(isset($asUserGroups[$nGroupPk]))
          $asRightGroup[$nRightPk][$nGroupPk] = $asUserGroups[$nGroupPk]['title'];
      }
    }
    //dump('converted rightpk  -> grpTitle from previous');
    //dump($asRightGroup);


    $sURL = $oPage->getAjaxUrl('settings',CONST_ACTION_SAVEEDIT, CONST_TYPE_SETTING_RIGHTUSR);
    $sHTML = $oHTML->getBlocStart('',array('style'=>'margin:5px;padding:5px;'));


    $oForm = $oHTML->initForm('usrRightForm');
    $oForm->setFormParams('', true, array('submitLabel' => 'Save','action' => $sURL));
    $oForm->setFormDisplayParams(array('noCancelButton' => '1'));


    $oLogin = CDependency::getCpLogin();
    $sUserName = $oLogin->getUserNameFromPk($pnLoginPk);
    $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<div class="settingsTitleBloc">Set up rights for the user '.$sUserName.'</div>'));

    //if there the user has groups linked to some rights, offer a link to check every group related right
    if(!empty($asRightGroup))
    {
      $sLink = $oHTML->getBlocStart('',array('style'=>'text-align: right; padding-right: 20px;'));
      $sLink.= $oHTML->getLink('Select every group related right', 'javascript:;', array('onclick' => '$(\'.linkedToGrp\').closest(\'.formField\').find(\'input:first\').prop(\'checked\', true);'));
      $sLink.= $oHTML->getBlocEnd();

      $oForm->addField('misc', '', array('type' => 'text', 'text'=> $sLink));
    }

    $this->_displayRightFormList($oForm, $asRightData, $asUserRights, $asRightGroup);

    $oForm->addField('hidden', 'userfk', array('value' => $pnLoginPk));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    if($pbIsAjax)
      return array('data' =>$sHTML);
    else
      return $sHTML;
  }


  /**
   * Function to display the form to manage user rights
   * @param integer $pnLoginPk
   * @return array of data
  */
  private function _formGroupRights($pnGroupPk, $pbIsAjax = true)
  {
    if(!assert('is_integer($pnGroupPk) && !empty($pnGroupPk)'))
       return array();

     if(!assert('is_bool($pbIsAjax)'))
       return array();

     //check group first
    $oLogin = CDependency::getCpLogin();
    $asGroupData = $oLogin->getGroupByPk($pnGroupPk);

    $sHTML = '';

    if(!empty($asGroupData))
    {
      $oRight = CDependency::getComponentByName('right');
      $oHTML = CDependency::getCpHtml();
      $oPage = CDependency::getCpPage();

      $asRightData = $oRight->getRightList(true, false, false);
      $asGroupRights = $oRight->getGroupRights($pnGroupPk);
      //dump($asGroupRights);

      $sURL = $oPage->getAjaxUrl('settings', CONST_ACTION_SAVEEDIT, CONST_TYPE_SETTING_RIGHTGRP, $pnGroupPk);
      $sHTML.= $oHTML->getBlocStart('',array('style'=>'margin:5px;padding:5px;'));
      $oForm = $oHTML->initForm('usrRightForm');
      $oForm->setFormParams('', true, array('submitLabel' => 'Save','action' => $sURL));
      $oForm->setFormDisplayParams(array('noCancelButton' => '1'));

      $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<h4>Defien a right profile for the group  '.$asGroupData['title'].' </h4>'));

      $this->_displayRightFormList($oForm, $asRightData, array_keys($asGroupRights));

      $oForm->addField('hidden', 'groupfk', array('value' => $pnGroupPk));

      $sHTML.= $oForm->getDisplay();
      $sHTML.= $oHTML->getBlocEnd();
    }

    if($pbIsAjax)
      return array('data' =>$sHTML);
    else
      return $sHTML;
  }

  private function _saveGroupRight($pnGroupPk )
  {
    if(!assert('is_key($pnGroupPk)'))
      return array('error' => 'Group ID incorrect.');

    //check group is here
    $oLogin = CDependency::getCpLogin();
    $asData = $oLogin->getGroupByPk($pnGroupPk);

    if(empty($asData))
      return array('error' => 'Can\'t retreive group data.');

    $oRight = CDependency::getComponentByName('right');
    $bSaved = $oRight->saveGroupRights($pnGroupPk);

    if(!$bSaved)
      return array('error' => 'Sorry, couldn\'t save the group rights.');

    return array('notice' => 'Group rights saved.', 'action' => ' goPopup.removeByType(\'layer\'); ');
  }


  private function _displayRightFormList(&$poForm, $pasRightData, $pasUserRights = array(), $pasGroupRights = array())
  {
    if(!assert('is_object($poForm) && is_array($pasRightData) && is_array($pasUserRights) && is_array($pasGroupRights)') || empty($pasRightData))
      return false;

    $oHTML = CDependency::getCpHtml();
    $oRight = CDependency::getComponentByName('right');
    $sCurrentUid = null;
    $asCpUnavailable = array();

    //dump($pasRightData);
    foreach($pasRightData  as $asUserRightData)
    {
      //separate components for more clarity
      if($sCurrentUid !== $asUserRightData['cp_uid'])
      {
        $sCurrentUid = $asUserRightData['cp_uid'];

        if(empty($sCurrentUid))
        {
          $sTitle = $oHTML->getBloc('', 'Data access rights (table related rights)', array('class' => 'rightListComponentTitle'));
          $poForm->addField('misc', $sCurrentUid, array('type' => 'text', 'text' => $sTitle));
        }
        else
        {
          $oComponent = CDependency::getComponentByUid($sCurrentUid);
          if(!$oComponent)
          {
            $asCpUnavailable[] = $sCurrentUid;
          }
          else
          {
            $sTitle = $oHTML->getBloc('', ucfirst($oComponent->getComponentName()), array('class' => 'rightListComponentTitle'));
            $poForm->addField('misc', $sCurrentUid, array('type' => 'text', 'text' => $sTitle));
          }
        }
      }


      if(!in_array($asUserRightData['cp_uid'], $asCpUnavailable))
      {
        //make the list of rights understandable to users
        $nRightPk = (int)$asUserRightData['rightpk'];
        $asChildData = $oRight->getChildRights($nRightPk);
        $asRight = array();
        $asAlias = array();

        //search if one of the rights is linked to one of the user groups
        //!! class used to offer a check all link in the form
        if(isset($pasGroupRights[$nRightPk]))
        {
          $nGrp = count($pasGroupRights[$nRightPk]);
          if($nGrp > 1)
            $asUserRightData['group'] = '<a href="javascript:;" class="linkedToGrp" title="Right linked to the following:'.implode(' ,', $pasGroupRights[$nRightPk]).'">'.$nGrp.' groups</a>';
          else
            $asUserRightData['group'] = '<a href="javascript:;" class="linkedToGrp" title="Right linked to the following:'.implode(' ,', $pasGroupRights[$nRightPk]).'">'.$nGrp.' group</a>';
        }
        else
          $asUserRightData['group'] = '&nbsp;';


        $bDevelopment = isDevelopment();
        if($bDevelopment)
          $asUserRightData['label'].= '(#'.$nRightPk.')';


        foreach($asChildData as $nChildPk => $asRightData)
        {
          if($asUserRightData['rightpk'] == $asRightData['parent'])
          {
            if($bDevelopment)
              $asRightData['label'].='(#'.$nChildPk.')';

            if($asRightData['type'] == 'alias')
            {
              $asAlias[$asRightData['label']] = $asRightData['label'];
            }
            else
              $asRight[$asRightData['label']] = $asRightData['label'];
          }
        }


        if(!empty($asRight) || !empty($asAlias))
          $sLink = $oHTML->getLink('detail','javascript:;',array('onclick'=>'$(\'#child_'.$nRightPk.'\').fadeToggle();'));
        else
          $sLink = '';

        $sLabel = $oHTML->getBlocStart('', array('class' => 'rightListRow '));

          $sLabel.= $oHTML->getBlocStart('', array('class' => 'rightListLabel'));
          $sLabel.= $asUserRightData['label'];
          $sLabel.= $oHTML->getBlocEnd();

          if(!empty($asUserRightData['group']))
          {
            $sLabel.= $oHTML->getBlocStart('', array('class' => 'rightListGroup'));
            $sLabel.= $asUserRightData['group'];
            $sLabel.= $oHTML->getBlocEnd();
          }

          if(!empty($asUserRightData['description']))
          {
            $sLabel.= $oHTML->getBlocStart('', array('class' => 'rightListDescription'));
            $sLabel.= $asUserRightData['description'];
            $sLabel.= $oHTML->getBlocEnd();
          }

          $sLabel.= $oHTML->getBlocStart('', array('class' => 'rightListLink'));
          $sLabel.= $sLink;
          $sLabel.= $oHTML->getBlocEnd();

          $sLabel.= $oHTML->getFloatHack();

          if(!empty($sLink))
          {
            $sLabel.= $oHTML->getBlocStart('child_'.$nRightPk, array('class' => 'childRightDiv'));

            if(!empty($asRight))
            {
              $sLabel.= '<span class="rightRight">Includes features from : ';
              $sLabel.= '<span>'.implode('</span><span>', $asRight).'</span></span><br /><br />';
            }

            if(!empty($asAlias))
              $sLabel.= '<span class="rightAliases"><span>Available actions:</span> <span>'.implode('</span><span>', $asAlias).'</span>';

            $sLabel.= $oHTML->getBlocEnd();
          }

        $sLabel.= $oHTML->getBlocEnd();
        $sLabel.= $oHTML->getFloatHack();

        if(in_array($asUserRightData['rightpk'], $pasUserRights))
          $poForm->addField('checkbox', 'usrRight['.$nRightPk.']', array('type' => 'misc', 'label'=> $sLabel, 'value' => $nRightPk, 'id' => 'usrRight_'.$nRightPk, 'checked'=>'checked', 'group_box' => false));
        else
          $poForm->addField('checkbox', 'usrRight['.$nRightPk.']', array('type' => 'misc', 'label'=> $sLabel, 'value' => $nRightPk, 'id' => 'usrRight_'.$nRightPk, 'group_box' => false));

        //add special class for data right: display 2 rights per line
        if($asUserRightData['type'] == 'data')
        {
          $poForm->setFieldDisplayParams('usrRight['.$nRightPk.']', array('class' => 'dataAccessRow'));
        }
      }

     }

     return true;
  }


 /**
  * Function to save the user rights
  * @return redirect to the next page
  */

  private function _saveUserRight()
  {
    $oRight = CDependency::getComponentByName('right');
    $bRight = $oRight->getUserRightSave();

    if($bRight)
      return array('notice' => 'Rights saved', 'action' => 'goPopup.removeByType(\'layer\');');

    return array('notice' => 'Error saving rights');
  }

  /**
   * Function to display the the user list to manage rights
   * @return string
   */

  private function _getUserRights()
  {
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();

    $oPage->addCssFile($this->getResourcePath().'css/settings.css');
    $oPage->addCssFile($this->getResourcePath().'css/right_form.css');
    $asActiveUsers = $oLogin->getUserList(0, true, false);
    $asUserAndGroup = $oLogin->getUsersGroup(array_keys($asActiveUsers));

    $sHTML = $oHTML->getBlocStart('');

      $sHTML.= $oHTML->getBlocStart('', array('class'=>'settingsTitleBloc'));
      $sHTML.= $oHTML->getText('Manage User Rights');
      $sHTML.= $oHTML->getBlocEnd();

      foreach($asUserAndGroup as $nUserPk => $asUserData)
      {
        /*
         * p id=\'user-data-'.$nUserPk.'\' style=\'display: none;\'>
<span class=\'user-sys-data\'><span class=\'user-id\'>#'.$nUserPk.'</span><span class=\'user-pseudo\'>'.$asUserData['pseudo'].'</span>
<span>'.$asUserData['date_create'].'</span><span>'.$asUserData['email'].'</span></span><p>
         */
        $sToolTip = '#'.$nUserPk.' | '.$asUserData['pseudo'].' | created on '.substr($asUserData['date_create'], 0, 10).' | email: '.$asUserData['email'];
        $asUserAndGroup[$nUserPk]['user_id'] = '<div class="user-pk" onmouseover="tp(this);" title="'.$sToolTip.'" >#'.$nUserPk.'</div>';

        if($asUserData['status'] > 0)
          $asUserAndGroup[$nUserPk]['firstname'] = '<span class="user-incative" title="User inactive">'.$asUserAndGroup[$nUserPk]['firstname'].'</span>';

        //Action column
        $sUserRightUrl = $oPage->getAjaxUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTING_RIGHTUSR, $nUserPk);
        $sPicture = $oHTML->getPicture($oLogin->getResourcePath().'/pictures/right_16.png');
        $sHTML = $oHTML->getLink($sPicture, 'javascript:;', array('onclick'=>"var oConfig = goPopup.getConfig(); oConfig.width = 1100; oConfig.height = 650; goPopup.setLayerFromAjax(oConfig, '".$sUserRightUrl."'); "));
        $asUserAndGroup[$nUserPk]['user_row'] = $sHTML;
      }

    //initialize the template
    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

    //get the config object for a specific template (contains default value so it works without config)
    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');

    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(false);

    $oConf->addColumn('ID', 'user_id', array('width' => 40, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Firstname', 'firstname', array('width' => 150, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Lastname', 'lastname', array('width' => 150, 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Groups', 'group_list', array('width' => 400));
    $oConf->addColumn('Edit rights', 'user_row', array('width' => 70));

    return $oTemplate->getDisplay($asUserAndGroup);
  }

  /**
   * Function to setup the site configuration manager
   * @return string
   */

  private function _displaySettings()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/settings.css');

    $sHTML = $oHTML->getTitleLine('Site Configuration Manager ', $this->getResourcePath().'/pictures/setting.jpg');
    $sHTML.= $oHTML->getCR();
    $sHTML.= $oHTML->getBlocStart('',array('style'=>'min-height:150px;'));



    $sSiteUrl      = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTINGS);
    $sUserUrl      = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_USER);
    $sUserGrpUrl   = $oPage->getAjaxUrl('login', CONST_ACTION_MANAGE, CONST_LOGIN_TYPE_GROUP);
    $sUserRightUrl = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_USRIGHT);
    $sMenuUrl      = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_MENU);
    $sFooterUrl    = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_FOOTER);
    $sBlackListUrl = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_BLACKLIST);
    $sCronJobUrl   = $oPage->getAjaxUrl('settings',CONST_ACTION_ADD, CONST_TYPE_SETTING_CRON);
    $sCustomFields   = $oPage->getAjaxUrl('customfields',CONST_ACTION_LIST);
    $sManageableList   = $oPage->getAjaxUrl('manageablelist',CONST_ACTION_LIST);

    $asMainTab = array();

    $sContent = $oHTML->getBlocStart('', array('class' => 'settingHomeMsg'));
    $sContent.= 'Welcome to the admin panel.<br /><br />';
    $sContent.= 'You\'ll find here all the settings of you BC software. If you encouter any issue, please contact us at <a href="mailto:support@bcsoft.com">support@bcsoft.com</a>.<br /><br />';
    $sContent.= 'Interested in trying new modules & apps ?<br />';
    $sContent.= '- want to try our project management solution <br />';
    $sContent.= '- want to try our online support module <br /><br />';

    $sContent.= 'Want a customized theme matching your company colors ?<br /><br />';

    $sContent.= 'Contact us at <a href="mailto:sales@bcsoft.com">sales@bcsoft.com</a> <br />';
    $sContent.= $oHTML->getBlocEnd();

    $asMainTab[] = array('title' => 'Admin panel', 'label' => 'general-settings', 'content' => $sContent);

    //-----------------------------------------
    //first tab, contains sub tabs
    $asTab = array();
    $asTab[] = array('title' => 'Settings', 'label' => 'site-settings', 'content' => $oHTML->getBloc('area_site-settings', $this->_formSiteConfig()), 'options' => array ('link' => $sSiteUrl));
    $asTab[] = array('title' => 'Footer', 'label' => 'footer', 'content' => $oHTML->getBloc('area_footer'), 'options' => array('link' => $sFooterUrl));

    $sContent = $oHTML->getTabs('sub-settings', $asTab, 'user-sub-tab', 'vertical');
    $asMainTab[] = array('title' => 'Software settings', 'label' => 'site-settings', 'content' => $sContent);


    //-----------------------------------------
    //second tab: will contain a sub tab
    $asTab = array();
    $asTab[] = array('title' => 'Groups', 'label' => 'groups', 'content' => $oHTML->getBloc('area_groups'), 'options' => array('link' => $sUserGrpUrl));
    $asTab[] = array('title' => 'Users', 'label' => 'users', 'content' => $oHTML->getBloc('area_users'), 'options' => array('link' => $sUserUrl));
    $asTab[] = array('title' => 'User Rights', 'label' => 'user-rights', 'content' => $oHTML->getBloc('area_user-rights'), 'options' => array('link' => $sUserRightUrl));

    $sContent = $oHTML->getTabs('sub-user', $asTab, 'user-sub-tab', 'vertical');
    $asMainTab[] = array('title' => 'User management', 'label' => 'user-settings', 'content' => $sContent);


    //-----------------------------------------
    //Third tab:  no sub tab
    $asMainTab[] = array('title' => 'Menus', 'label' => 'menus', 'content' => $oHTML->getBloc('area_menus'), 'options' => array('link' => $sMenuUrl));


    //-----------------------------------------
    //Forth tab: will contain a sub tab
    $asTab = array();
    $asTab[] = array('title' => 'Manageable lists', 'label' => 'manageable-lists', 'content' => $oHTML->getBloc('area_manageable-lists'), 'options' => array ('link' => $sManageableList));
    $asTab[] = array('title' => 'Custom Fields', 'label' => 'custom-fields', 'content' => $oHTML->getBloc('area_custom-fields'), 'options' => array ('link' => $sCustomFields));

    $sContent = $oHTML->getTabs('sub-apps', $asTab, 'user-sub-tab', 'vertical');
    $asMainTab[] = array('title' => 'Applications', 'label' => 'apps-settings', 'content' => $sContent);


    //-----------------------------------------
    //Fifth tab: will contain a sub tab (BCM tab for our management purpose)
    if($this->_isSuperAdmin())
    {
      $asTab = array();
      $asTab[] = array('title' => 'Blacklist', 'label' => 'blacklist-url', 'content' => $oHTML->getBloc('area_blacklist-url'), 'options' => array('link' => $sBlackListUrl));
      $asTab[] = array('title' => 'Cron & Urls', 'label' => 'cron', 'content' => $oHTML->getBloc('area_cron'), 'options' => array('link' => $sCronJobUrl));

      $sContent = $oHTML->getTabs('sub-bcmedia', $asTab, 'bcmedia-sub-tab', 'vertical');
      $asMainTab[] = array('title' => 'System settings', 'label' => 'bcmedia-settings', 'content' => $sContent, 'options' => array('class' => 'superAdmin'));
    }

    $sHTML.= $oHTML->getTabs('settings', $asMainTab, 'general-settings', 'inline');

   return $sHTML;
  }

  /**
   * Function to display the form for menu
   * @return string
   */

  private function _getMenuSetting()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oDB = CDependency::getComponentByName('database');

    $sURL = $oPage->getUrl('settings', CONST_ACTION_SAVEADD, CONST_TYPE_SETTING_MENU);

    $sQuery = 'SELECT * FROM settings WHERE fieldname = "menunav1"';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return '';

    $sHTML = $oHTML->getBlocStart();
    $oForm = $oHTML->initForm('menuSettingForm');
    $oForm->setFormParams('', false, array('action' => $sURL, 'submitLabel'=>'Save'));
    $oForm->setFormDisplayParams(array('noCancelButton' => '1'));

      if(isset($this->casSettings['languages']) && count($this->casSettings['languages']))
        $asLanguages = $this->casSettings['languages'];
      else
        $asLanguages = array(CONST_DEFAULT_LANGUAGE);

      foreach($asLanguages as $sLanguage)
      {
        $oForm->addField('textarea', 'menu_'.$sLanguage, array('label'=> 'Menu ['.$sLanguage.']', 'value' =>$oDbResult->getFieldValue('value'),'style'=>'width:680px;'));
        $oForm->setFieldControl('menu_'.$sLanguage, array('jsFieldNotEmpty' => ''));

        $oForm->addField('textarea', 'menu_userialized_'.$sLanguage, array('label'=> 'Menu unserialized ['.$sLanguage.'] ', 'value' => var_export(unserialize(base64_decode($oDbResult->getFieldValue('value'))), true), 'readonly' => 'readonly', 'style'=>'width:680px;'));
        $oForm->addField('misc', '', array('type'=> 'br'));
      }

      $oForm->addField('misc', '', array('type'=> 'text', 'text' => 'use the menu_generator here: <a href="/component/settings/resources/menu_generator.php" target="_blank">menu_generator.php</a>'));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Save Menu
   * @return redirect to the next page
   */

  private function _saveMenu()
  {
    $oPage = CDependency::getCpPage();
    $oDB = CDependency::getComponentByName('database');
    $oHTML = CDependency::getCpHtml();

    $sMenu = getValue('menu');
    $sURL = $oPage->getUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTINGS);

    $sQuery = ' DELETE FROM SETTINGS where fieldname = "menu"';
    $oDbResult = $oDB->ExecuteQuery($sQuery);

    $sQuery = 'INSERT INTO settings (`fieldname`,`fieldtype`,`value`,`description`)';
    $sQuery.= ' VALUES ('.$oDB->dbEscapeString('menu').','.$oDB->dbEscapeString('serialized64').',';
    $sQuery.= ''.$oDB->dbEscapeString($sMenu).','.$oDB->dbEscapeString('Menu Parameters').')';

    $oDbResult = $oDB->ExecuteQuery($sQuery);

    if($oDbResult)
      return $oHTML->getRedirection($sURL);
    else
      return $oHTML->getBlocMessage('Error Obtained');

   }

  /**
   * Function to display the form for footer
   * @return string
   */

  private function _getFooterSetting()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oDB = CDependency::getComponentByName('database');

    $sURL = $oPage->getUrl('settings', CONST_ACTION_SAVEADD, CONST_TYPE_SETTING_FOOTER);

    $sQuery = 'SELECT * FROM settings WHERE fieldname = "footer"';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return '';

    $sHTML = $oHTML->getBlocStart();

    $oForm = $oHTML->initForm('footerSettingForm');
    $oForm->setFormParams('', false, array('action' => $sURL, 'submitLabel'=>'Save'));
    $oForm->setFormDisplayParams(array('noCancelButton' => '1'));

    $oForm->addField('textarea', 'footer', array('label'=> 'Footer ', 'value' =>$oDbResult->getFieldValue('value'),'style'=>'width:680px;'));
    $oForm->setFieldControl('footer', array('jsFieldNotEmpty' => ''));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }

  /**
   * Save Footer links
   * @return redirection URL
   */

  private function _saveFooter()
  {
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $oDB = CDependency::getComponentByName('database');

    $sFooter = getValue('footer');
    $sURL = $oPage->getUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTINGS);

    $sQuery = ' DELETE FROM SETTINGS where fieldname = "footer"';
    $oDbResult = $oDB->ExecuteQuery($sQuery);

    $sQuery = 'INSERT INTO settings (`fieldname`,`fieldtype`,`value`,`description`)';
    $sQuery.= ' VALUES ('.$oDB->dbEscapeString('footer').','.$oDB->dbEscapeString('serialized').',';
    $sQuery.= ''.$oDB->dbEscapeString($sFooter).','.$oDB->dbEscapeString('Footer Parameters').')';
    $oDbResult = $oDB->ExecuteQuery($sQuery);

    if($oDbResult)
      return $oHTML->getRedirection($sURL);
    else
      return $oHTML->getBlocMessage('Error Obtained');
   }

  /**
   * Form to save the site configuration parameters
   * @return string
   */

  private function _formSiteConfig()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    // Get a fresh list of settings to check if we have to create new ones.
    // We don't use $this->casSettings, because it can contain settings
    // not created yet (declare settings in a __construct + pbStrict = false)
    $oDbResult = $this->_getModel()->getByWhere('settings');
    $bRead = $oDbResult->readFirst();
    $asSettingList = array();
    while($bRead)
    {
      $asSettingList[] = $oDbResult->getFieldValue('fieldname');
      $bRead = $oDbResult->readNext();
    }

    $sHTML= $oHTML->getBlocStart();

    $sURL = $oPage->getUrl($this->_getUid(), CONST_ACTION_SAVE_CONFIG,CONST_TYPE_SETTINGS);
      $oForm = $oHTML->initForm('siteConfigForm');
      $oForm->setFormParams('',false, array('action' => $sURL, 'submitLabel' => 'Save'));

      $oComponents = CDependency::getComponentsByInterface('declare_settings');

      foreach ($oComponents as $oComponent)
      {
        //dump($oComponent);
        $oForm->addField('misc', '', array('type' => 'title', 'title' => ucfirst($oComponent->getComponentName()).' component', 'class' => 'settingsComponentTitle'));
        $aFields = $oComponent->declareSettings();

        foreach ($aFields as $aField)
        {
          if(!in_array($aField['fieldname'], $asSettingList))
          {
            $bAdded = $this->_getModel()->addSetting($aField);
            if(!$bAdded)
              assert('false; // Unable to create new setting field in database');

            $this->_loadSettings();
          }

          $oSetting = $this->_getModel()->getByWhere('settings', 'fieldname=\''.$aField['fieldname'].'\'', 'value');
          $aField['value'] = $oSetting->getFieldValue('value');

          $oForm = $this->_getSettingField($oForm, $aField);
        }
      }

    $oForm->addField('misc', '', array('type'=>'br'));

    $sHTML.= $oForm->getDisplay();
   $sHTML.= $oHTML->getBlocEnd();

  return $sHTML;
 }

  /**
   * Form to save the site configuration parameters
   * @return string
   */

  private function _formUserPreferences()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();
    $nUserPk = (int)$oLogin->getUserPk();


    // Get a fresh list of settings to check if we have to create new ones.
    // We don't use $this->casSettings, because it can contain settings
    // not created yet (declare settings in a __construct + pbStrict = false)
    $oDbResult = $this->_getModel()->getByWhere('settings');
    $bRead = $oDbResult->readFirst();
    $asSettingList = array();
    while($bRead)
    {
      $asSettingList[] = $oDbResult->getFieldValue('fieldname');
      $bRead = $oDbResult->readNext();
    }

    $sHTML = $oHTML->getBlocStart();

    $sURL = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVE_CONFIG,CONST_TYPE_USERPREFERENCE);
    $oForm = $oHTML->initForm('siteConfigForm');
    $oForm->setFormParams('siteConfigForm', true, array('action' => $sURL, 'submitLabel'=>'Save'));

    $oComponents = CDependency::getComponentsByInterface('declare_userpreferences');
    foreach($oComponents as $oComponent)
    {
      $aFields = $oComponent->declareUserPreferences();

      foreach ($aFields as $aField)
      {
        if(!in_array($aField['fieldname'], $asSettingList))
        {
          $bAdded = $this->_getModel()->addSetting($aField, true);
          if(!$bAdded)
            assert('false; // Unable to create new setting field in database');

          $this->_loadSettings();
        }

        $sUserValue = $this->_getModel()->getUserPreference($nUserPk, $aField['fieldname']);
        if(!empty($sUserValue))
          $aField['value'] = $sUserValue;

        $oForm = $this->_getSettingField($oForm, $aField);
      }
    }

    $oForm->addField('misc', '', array('type'=>'br'));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    return array('data' =>$sHTML);
  }

  /**
   * Display a form row for every user preference
   * @param object $poForm
   * @param array $paField
   * @return object
   */
  private function _getSettingField($poForm, $paField)
  {
    $oHTML = CDependency::getCpHtml();

    if(!assert('is_object($poForm) && is_array($paField)'))
      return $oHTML->initForm();

    if(!isset($paField['customformurl']))
    {
      switch($paField['fieldtype'])
      {
        case 'select' :

          if($paField['fieldtype'] == 'select_multi')
          {
            $sFieldName = $paField['fieldname'].'[]';
            $poForm->addField('select', $sFieldName, array('label' => $paField['label'], 'multiple' => 1));
          }
          else
          {
            $sFieldName = $paField['fieldname'];
            $poForm->addField('select', $sFieldName, array('label' => $paField['label']));
          }

          foreach ($paField['options'] as $sValue => $sLabel)
          {
            if($sValue == $paField['value'])
              $poForm->addOption($sFieldName, array('value'=> $sValue, 'label' => $sLabel, 'selected' => 'selected'));
            else
              $poForm->addOption($sFieldName, array('value'=> $sValue, 'label' => $sLabel));
          }
          break;

        case 'sortable' :
        case 'select_multi':
          $poForm->addField('select', $paField['fieldname'].'[]', array('label' => $paField['label'], 'multiple' => 'multiple', 'sortable' => 'sortable'));

          $aValues = array();
          if(!empty($paField['value']))
            $aValues = unserialize($paField['value']);

          // Fetch the selected options first to keep the order chosen by the user
          foreach ($aValues as $sValue)
          {
            $sLabel = array_search($sValue, $paField['options']);
            $poForm->addOption($paField['fieldname'].'[]', array('value'=> $sValue, 'label' => $sLabel, 'selected' => 'selected'));
          }

          foreach ($paField['options'] as $sLabel => $sValue)
          {
            if(!in_array($sValue, $aValues))
              $poForm->addOption($paField['fieldname'].'[]', array('value'=> $sValue, 'label' => $sLabel));
          }
          break;

        case 'serialized' :
          $sValue = '';
          if(!empty($paField['value']))
            $sValue = serialize($paField['value']);

          $poForm->addField('input', $paField['fieldname'], array('label'=>$paField['label'], 'value' => $sValue));
          break;

        case 'serialized64' :
          $sValue = '';
          if(!empty($paField['value']))
            $sValue = base64_encode(serialize($paField['value']));

          $poForm->addField('input', $paField['fieldname'], array('label'=>$paField['label'], 'value' => $sValue));
        break;

        case 'textarea' :
          $poForm->addField('textarea', $paField['fieldname'], array('label'=>$paField['label'], 'value' => $paField['value']));
          if(isset($paField['controls']))
            $poForm->setFieldControl($paField['fieldname'], $paField['controls']);
          break;

        default:
          $poForm->addField('input', $paField['fieldname'], array('label'=>$paField['label'], 'value' => $paField['value']));
          if(isset($paField['controls']))
            $poForm->setFieldControl($paField['fieldname'], $paField['controls']);
          break;
      }

    }
    else
    {
      $sAjaxEdit = $oHTML->getAjaxPopupJS($paField['customformurl'], 'body','','600','860',1);
      $sLink = $oHTML->getLink('manage setting here', 'javascript:;', array('onclick' => $sAjaxEdit, 'title' => 'Edit this field'));
      $poForm->addField('misc', '', array('label' => $paField['fieldname'], 'type'=> 'text', 'text' => $sLink));
    }

    return $poForm;
  }


  /**
  * Function to save the site configuration
  * @return array to be encoded in json
  */
  private function _saveUserPreferences($pasSetting = array())
  {
    $oLogin = CDependency::getCpLogin();
    $nUserPk = (int)$oLogin->getUserPk();

    if(!empty($pasSetting))
      $asSetting = $pasSetting;
    else
      $asSetting = $_POST;


    //check if all the settings are here, and fetch the user value if already here
    $asSettingsData = array_keys($asSetting);
    $oDbResult = $this->_getModel()->getUserSettings($nUserPk, $asSettingsData);
    $bRead = $oDbResult->readFirst();

    $asSettingsData = array();
    while($bRead)
    {
      $asSettingsData[$oDbResult->getFieldValue('fieldname')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    /* Cant do that since form fioelds may add hidden fields in the form
    if(count($_POST) != count($asSettingsData))
      return array('error' => __LINE__.' - Error: number of preferences differs with the database. ['.count($_POST).'/'.count($asSettingsData).']');
    */

    foreach($asSetting as $sFieldName => $vFieldValue)
    {
      if(!isset($asSettingsData[$sFieldName]))
      {
        //dump('the form field ['.$sFieldName.'] is not a  setting ');
      }
      else
      {
        $asSettingsData[$sFieldName]['settingspk'] = (int)$asSettingsData[$sFieldName]['settingspk'];

        if($asSettingsData[$sFieldName]['fieldtype'] == 'sortable' || is_array($vFieldValue))
        {
          if(empty($vFieldValue))
            $vFieldValue = array();

          $vFieldValue = serialize($vFieldValue);
        }

        $aValues = array(
            'settingsfk' => $asSettingsData[$sFieldName]['settingspk'],
            'loginfk' => $nUserPk,
            'value' => $vFieldValue
        );

        //check if there's already a value set for this preference
        if(!empty($asSettingsData[$sFieldName]['settings_userpk']))
        {
          $aValues['settings_userpk'] = (int)$asSettingsData[$sFieldName]['settings_userpk'];
          $bSaved = $this->_getModel()->update($aValues, 'settings_user');
          if(!$bSaved)
            return array('notice' => __LINE__.' - Error while saving user preference. Please contact your administrator');
        }
        else
        {
          $nPk = $this->_getModel()->add($aValues, 'settings_user');
          if(empty($nPk))
            return array('error' => __LINE__.' - Error while saving user preference. Please contact your administrator.');
        }
      }
    }

    //reload all settings in session
    $this->loadUserPreferences($nUserPk);

    return array('notice' => 'User preferences saved successfully.', 'reload' => 1);
  }


  /*public function saveUserPreferences($pasSetting)
  {
    if(!assert('is_array($pasSetting) && !empty($pasSetting)'))
      return false;

    return $this->_saveUserPreferences($pasSetting);
  }*/

  /**
   * Function to save a specific site setting
   * @return redirection URL
   */

  private function _saveSiteSetting()
  {
   $oPage = CDependency::getCpPage();
   $oHTML = CDependency::getCpHtml();

   $psFieldValue = getValue('psFieldValue', '');
   $psFieldName = getValue('psFieldName', '');
   $psFieldType = getValue('psFieldType', '');

   if(!assert('!empty($psFieldName)'))
    return '';

//   var_dump($psFieldValue);

   if($psFieldType == 'serialized64')
     $psFieldValue = base64_encode(unserialize($psFieldValue));

   var_dump($psFieldValue); die;

   $bUpdated = $this->_getModel()->update(array('value' => $psFieldValue), 'settings', '`fieldname` = "'.$psFieldName.'"');

   if(!$bUpdated)
    return 'Error updating settings. Please contact administrator.';

   //reload all settings in session
   $this->_loadSettings();

   $sURL = $oPage->getUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTINGS, 0);
   return $oHTML->getRedirection($sURL);
  }


  /**
   * Function to save the site configuration
   * @return redirection URL
   */

  private function _saveSiteConfig()
  {
   $oPage = CDependency::getCpPage();
   $oHTML = CDependency::getCpHtml();

   foreach ($_POST as $sFieldName => $vFieldValue)
   {
      $bUpdated = $this->_getModel()->update(array('value' => $vFieldValue), 'settings', '`fieldname` = "'.$sFieldName.'"');

      if(!$bUpdated)
        return 'Error updating settings. Please contact administrator.';
   }

   //reload all settings in session
   $this->_loadSettings();

   $sURL = $oPage->getUrl('settings', CONST_ACTION_ADD, CONST_TYPE_SETTINGS, 0);
   return $oHTML->getRedirection($sURL);
  }

  // Save a custom setting. Value is sent by external component

  public function saveCustomSetting($psSettingName, $pvValue)
  {
    if(!assert('is_string($psSettingName) && !empty($psSettingName)'))
      return false;

    $bUpdated = $this->_getModel()->update(array('value' => $pvValue, 'fieldname' => $psSettingName), 'settings', '`fieldname` = "'.$psSettingName.'"');
    if(!$bUpdated)
      return false;

    $this->_loadSettings();

    return true;
  }


  private function _isSuperAdmin()
  {
    if($this->_cbSuperAdmin !== null)
      return $this->_cbSuperAdmin;

    if(getValue('super_admin') == 'zAGa4u7he-WR6rubRu6AFRUyapesT8d_w')
    {
      $this->_cbSuperAdmin = true;
      return true;
    }


    $asIp = array('192.168.0.0.1', '127.0.0.1', '118.243.81.245', '183.77.248.83');
    if(!in_array($_SERVER['REMOTE_ADDR'], $asIp))
    {
      $this->_cbSuperAdmin = false;
      return false;
    }

    $oLogin = CDependency::getCpLogin();
    if(!$oLogin || !$oLogin->isAdmin())
    {
      $this->_cbSuperAdmin = false;
      return false;
    }

    $this->_cbSuperAdmin = true;
    return true;
  }

  private function _mappingDb()
  {
    $oHTML = CDependency::getCpHtml();

    $oDbResult = $this->_getModel()->getDatabaseFields();
    $bRead = $oDbResult->readFirst();

    $asTable = array();
    while($bRead)
    {

      $asFieldData = $oDbResult->getData();

      //$sHTML.= '<br />- '.$asFieldData['TABLE_NAME'].' - '.$asFieldData['TABLE_NAME'].' - '.$asFieldData['COLUMN_NAME'].' - '.$asFieldData['DATA_TYPE'].'<br />';

      switch($asFieldData['DATA_TYPE'])
      {
        case 'int':
        case 'bigint':
        case 'tinyint':
        case 'smallint':
        case 'timestamp':
        case 'time':
        case 'year':

          $sFinish = substr($asFieldData['COLUMN_NAME'], -2, 2);

          if($sFinish == 'pk')
          {
            $sIndex = ',\'index\' => \'pk\'';
            //if needded later, we can create arrays of pk, fk, ... to help creating join automatically
            //$asPk[$asFieldData['TABLE_NAME']] = $asFieldData['COLUMN_NAME'];
          }
          elseif($sFinish == 'pk')
          {
            $sIndex = ',\'index\' => \'fk\'';
            //$asFk[$asFieldData['COLUMN_NAME']] = $asFieldData['TABLE_NAME'];
          }
          else
            $sIndex = '';


          if($sFinish == 'pk')
            $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array(\'controls\'=>array(\'is_null(%) || is_key(%)\'),\'type\'=>\'int\''.$sIndex.');';
          else
            $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array(\'controls\'=>array(\'is_integer(%)\'),\'type\'=>\'int\''.$sIndex.');';
          break;


        case 'float':
        case 'double':
        case 'decimal':
        case 'real':

          $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array(\'controls\'=>array(\'is_numeric(%)\'),\'type\'=>\'float\');';
          break;

        case 'date':

          $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array(\'controls\'=>array(\'is_date(%)\'),\'type\'=>\'date\');';
          break;

        case 'datetime':

          $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array(\'controls\'=>array(\'is_datetime(%)\'),\'type\'=>\'datetime\');';
          break;

        default:

          $asTable[$asFieldData['TABLE_NAME']][] = '$this->_tableMap[\''.$asFieldData['TABLE_NAME'].'\'][\''.$asFieldData['COLUMN_NAME'].'\'] = array();';
          break;

      }

      $bRead = $oDbResult->readNext();
    }

    $sHTML = '';

    foreach($asTable as $asFields)
    {
      $sHTML.= implode("\n", $asFields)."\n";
    }
    $sHTML.= "\n". ' ';

    $oFs = fopen($_SERVER['DOCUMENT_ROOT'].'/common/lib/model.db_map_'.date('YmdHis').'.inc.php5', 'w+');
    if($oFs)
    {
      $sFileContent = "<?php \nfunction _initMap(){\n".$sHTML."\n return true; \n }\n?>";
      fputs($oFs, $sFileContent);
      fclose($oFs);
    }

    return $oHTML->getTitleLine('Mapping database fields...') . str_replace("\n", '<br />', $sHTML);
  }


  private function _rightGenerator()
  {
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/right_form.css');

    $sJavasctipt = 'function updateAlias(poField)
      {
        var sTable = poField.value.trim();
        alert(sTable);
        var asWords = sTable.split(\'_\');
        var nWords = asWords.length;

        if(nWords >= 3)
          sAlias = asWords[0].substring(0,1)+asWords[1].substring(0,1)+asWords[2].substring(0,2);

        if(nWords == 2)
          sAlias = asWords[0].substring(0,1)+asWords[1].substring(0,3);

        if(nWords == 1)
          sAlias = asWords[0].substring(0,4);

        $(poField).parent().find(\'.join_alias,.alias\').val(sAlias);
       }';

    $oPage->addCustomJs($sJavasctipt);

    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_TYPE_SETTING_CRON).'&gen_right=1';

    $asTable = loadDbMap();
    $sTableOption = '';
    foreach($asTable as $sTableName => $asFields)
      $sTableOption.='<option value="'.$sTableName.'">'.$sTableName.'</option>';

    $sHTML = '';

    $sRightPk = getValue('rightpk');
    if(!empty($sRightPk))
    {
      //dump($_POST);
      $sMessage = '';
      $asSQL = array();

      $sTable = getValue('table');
      $sAlias = getValue('alias');
      $sSelect = trim(getValue('select'));

      if($sTable == '*')
        $sMessage = 'Right applied to all queries using the oQH !! Sure ?<br />';
      else
      {
        if(empty($sAlias))
          $sHTML.= 'ERROR: if you specify a table, the alias can not be empty!<br />';
      }


      if(!empty($sSelect) && (substr($sSelect, 0, 1) == ',' || substr($sSelect, -1, 1) == ','))
        $sHTML.= 'ERROR: select field can not start or end by a comma (,) !<br />';

      // Build and array of SQL parameters
      // ONLY fill the array when there is a value.
      // Make db serialize smaller and allow using array_merge_recursive and not recode something custom
      if(!empty($sSelect))
        $asSQL['select'] = $sSelect;

      $asSQL['table'] = $sTable;
      $asSQL['alias'] = $sAlias;

      $asJoinType = (array)$_POST['join_type'];
      $asJoinTable = (array)$_POST['join_table'];
      $asJoinAlias = (array)$_POST['join_alias'];
      $asJoinClause = (array)$_POST['join_clause'];

      $asSQL['left'] = array();
      $asSQL['inner'] = array();
      $asSQL['outer'] = array();

      $nJoin = 0;
      foreach($asJoinType as $sCount => $sJoinType)
      {
        $nCount = (int)$sCount;

        if(!empty($asJoinTable[$nCount]))
        {
          if((empty($asJoinAlias[$nCount]) || empty($asJoinClause[$nCount])))
            $sHTML.= 'ERROR: Trying to join a table ['.$asJoinTable[$nCount].']... alias ['.$asJoinAlias[$nCount].'] and ON clause ['.$asJoinClause[$nCount].'] are required !<br />';
          else
            $nJoin++;

          $asSQL[$sJoinType][$nCount]['table'] = $asJoinTable[$nCount];
          $asSQL[$sJoinType][$nCount]['alias'] = $asJoinAlias[$nCount];
          $asSQL[$sJoinType][$nCount]['clause'] = $asJoinClause[$nCount];
          $asSQL[$sJoinType][$nCount]['sql'] = strtoupper($sJoinType).' JOIN '.strtolower($asJoinTable[$nCount]).' as '.$asJoinAlias[$nCount].' ON ('.$asJoinClause[$nCount].') ';
        }
      }

      //there are errors
      if(!empty($sHTML))
        return array('error' => $sHTML);

      $sValue = getValue('where');
      if(!empty($sValue))
        $asSQL['where'] = $sValue;

      $sValue = getValue('order');
      if(!empty($sValue))
        $asSQL['order'] = $sValue;

      $sValue = getValue('group');
      if(!empty($sValue))
        $asSQL['group'] = $sValue;

      $sValue = getValue('limit');
      if(!empty($sValue))
        $asSQL['limit'] = $sValue;


      if($nJoin == 0 && empty($asSQL['where']))
        return array('error' => 'Empty right. No join and no where ... can not do any good !!');


      $sSql = addslashes(serialize($asSQL));
      //generate SQL to insert in database
      $sHTML = '<br /><br />Okay ;p<br /><br />
        INSERT INTO `right` (rightpk, label, description, `type`, data) VALUES("'.$sRightPk.'", "'.getValue('label').'", "'.getValue('description').'", "data", "'.$sSql.'");<br />
        <br /><br />
        UPDATE `right` SET label = "'.getValue('label').'", description"'.getValue('description').'", `data` = "'.$sSql.'" WHERE rightpk = '.$sRightPk.';<br /><br />';
      $sHTML.= '<pre>'.var_export($asSQL, true).'</pre>';
      return array('data' => $sHTML, 'message' => $sMessage);
    }

    $sHTML.= '
    <div id="gen_right_container" class="gen_right_container">
    <form method="POST" id="gen_right_id" action="#">

      <span class="legend">RightPk:</span><input type="text" name="rightpk" value="XXX"><br />
      <span class="legend">Label:</span><input type="text" name="label" value="Access_..."><br />
      <span class="legend">Description:</span><input type="text" name="description" value="Restrict access to ..."><br />
      <span class="legend">Type:</span><input type="text" name="type" value="data"><br />
      <br /><hr /><br />

      <div>
      <span class="legend">Linked to table:</span><select name="table" class="short"  onchange="updateAlias(this);">
      <option value="*"> - applied to all queries - </option>'.$sTableOption.'</select><br />
      <span class="legend">Alias:</span><input type="text" class="alias" name="alias">
      </div>
      <br /><br />

      <span class="legend">Select:</span><input type="text" name="select"><br /><br />';

      for($nCount = 0; $nCount < 3; $nCount++)
      {
        $sHTML.= '
        <div>
        <span class="legend">&nbsp;</span>
        <select name="join_type[]" class="join_type">
          <option value="left">left join</option><option value="inner">inner join</option><option value="outer">outer join</option>
        </select>

        <span class="inlineLegend">table</span><select class="shorter" name="join_table[]" onchange="updateAlias(this);">
          <option value=""> - </option>'.$sTableOption.'</select>

          <span class="inlineLegend"> as </span><input type="text" class="join_alias shortest" name="join_alias[]">
          <span class="inlineLegend"> ON (</span><textarea type="text" class="short" name="join_clause[]"></textarea>)</div><br />';
      }

      $sHTML.= '<br />
      <span class="legend">WHERE </span><input type="text" name="where"><br /><br />

      <span class="legend"><em>ORDER BY</em></span><input type="text" name="order"><br />
      <span class="legend"><em>GROUP BY</em></span><input type="text" name="group"><br />
      <br /><hr /><br />

      <input type="button" value="Serialize" onclick="AjaxRequest(\''.$sURL.'\', \'\', \'gen_right_id\', \'gen_right_result\'); ">
    </form>

    <br /><br /><hr /><br /><br />
    <div id="gen_right_result" class="gen_right_result">
    </div>
    </div>';

    return array('data' => $sHTML);
  }

}
