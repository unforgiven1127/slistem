<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/conf/custom_config/'.CONST_WEBSITE.'/blacklist.inc.php5');

class CDependency
{
  static private $asInstancies;
  static private $asDependencies;
  static private $coPage = null;
  static private $asBlacklist = array();
  static private $asInterfaces = array();
  static private $coNull = null;

  //all interfaces name must start by one of the following: is_, do_, need_, has_, listen_
  static private $asComponentUid = array
  (
    '579-704' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1)),
    '777-249' => array('interfaces' => array('has_ajax' => 1,'has_menuAction' => 1, 'searchable' =>1, 'declare_userpreferences' => 1, 'has_cron' => 1, 'has_folders' => 1, 'has_customfield' => 1, 'notification_item'=> 1)),
    '668-313' => array('interfaces' => array('has_ajax' => 1)),
    '456-789' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    '999-111' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    '898-775' => array('interfaces' => array('has_ajax' => 1, 'has_menuGlobalAction' => 1, 'custom_menu_item' => 1, 'declare_settings' => 1)),
    '007-770' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1, 'listen_777-249' => 1)),
    '009-724' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1)),
    '665-544' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1)),
    '210-482' => array('interfaces' => array('has_ajax' => 1)),
    '100-603' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1)),
    '150-163' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1, 'has_homepage' => 1, 'has_menuAction' => 1)),
    '153-160' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1, 'has_homepage' => 1, 'has_menuAction' => 1)),
    '654-321' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    '180-290' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'listen_777-249' => 1)),
    '400-650' => array('interfaces' => array('has_ajax' => 1,'has_cron' => 1)),
    '111-111' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'has_navigationpath'=> 1)),
    '222-222' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1)),
    '555-123' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1, 'listen_777-249' => 'listen_777-249', 'has_charts' => 1)),
    '008-724' => array('interfaces' => array('do_sendmail' => 'do_sendmail')),
    '569-741' => array('interfaces' => array('do_html' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1)),
    '185-963' => array('interfaces' => array('display_menu' => 1, 'declare_settings' => 1, 'has_ajax' => 1)),
    '134-685' => array('interfaces' => array('has_ajax' => 1)),
    '486-125' => array('interfaces' => array('has_ajax' => 1, 'custom_menu_item' => 1, 'user_account_tab' => 1, 'manage_folder' => 1)),
    '333-333' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'send_notification' => 1, 'display_notification' => 1)),

    '555-003' => array('interfaces' => array('display_menu' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'has_menuAction' => 1, 'has_ajax' => 1)), /* slistem custom menu */
    '555-002' => array('interfaces' => array('has_ajax' => 1, 'user_account_tab' => 1, 'manage_folder' => 1, 'set_custom_container' => 1)),
    '555-001' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'searchable' =>1, 'notification_item'=> 1)),
    '555-004' => array('interfaces' => array('has_ajax' => 1)),
    '555-005' => array('interfaces' => array('has_ajax' => 1, 'notification_item'=> 1, 'has_cron' => 1)),
    '555-006' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1)),

    '196-001' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'notification_item'=> 1)),
    '196-002' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'notification_item'=> 1))
  );


  static private $asComponentName = array
  (
    'login' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'declare_settings' => 1,  'declare_userpreferences' => 1)),
    'addressbook' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'searchable' =>1, 'declare_userpreferences' => 1, 'has_cron' => 1, 'has_folders' => 1, 'has_customfield' => 1, 'notification_item'=> 1)),
    'form' => array('interfaces' => array('has_ajax' => 1)),
    'project' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    'sharedspace' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    'search' => array('interfaces' => array('has_ajax' => 1, 'has_menuGlobalAction' => 1, 'custom_menu_item' => 1, 'declare_settings' => 1)),
    'event' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1, 'listen_777-249' => 1)),
    'webmail' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1)),
    'settings' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1)),
    'querybuilder' => array('interfaces' => array('has_ajax' => 1)),
    'taaggregator' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1)),
    'talentatlas' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1, 'has_homepage' => 1, 'has_menuAction' => 1)),
    'jobboard' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1, 'has_homepage' => 1, 'has_menuAction' => 1)),
    'jobboard_user' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1)),
    'customfields' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'listen_777-249' => 1)),
    'zimbra' => array('interfaces' => array('has_ajax' => 1,'has_cron' => 1)),
    'portal' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'has_navigationpath'=> 1)),
    'charts' => array('interfaces' => array('has_ajax' => 1, 'has_publicContent' => 1)),
    'opportunity' => array('interfaces' => array('has_ajax' => 1, 'has_menuAction' => 1, 'has_cron' => 1, 'listen_777-249' => 1, 'has_chart' => 1)),
    'mail' => array('interfaces' => array('do_sendmail' => 1)),
    'display' => array('interfaces' => array('do_html' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1)),
    'menu' => array('interfaces' => array('display_menu' => 1, 'declare_settings' => 1, 'has_ajax' => 1)),
    'manageablelist' => array('interfaces' => array('has_ajax' => 1)),
    'folder' => array('interfaces' => array('has_ajax' => 1, 'custom_menu_item' => 1, 'user_account_tab' => 1, 'manage_folder' => 1)),
    'notification' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'send_user_notification' => 1)),

    'sl_menu' => array('interfaces' => array('display_menu' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'has_menuAction' => 1, 'has_ajax' => 1)), /* slistem custom menu */
    'sl_folder' => array('interfaces' => array('has_ajax' => 1, 'user_account_tab' => 1, 'manage_folder' => 1, 'set_custom_container' => 1)),
    'sl_candidate' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'declare_settings' => 1, 'declare_userpreferences' => 1, 'searchable' =>1, 'notification_item'=> 1)),
    'sl_event' => array('interfaces' => array('has_ajax' => 1)),
    'sl_position' => array('interfaces' => array('has_ajax' => 1, 'notification_item'=> 1, 'has_cron' => 1)),
    'sl_stat' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1)),

    'gb_user' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'notification_item'=> 1)),
    'gb_test' => array('interfaces' => array('has_ajax' => 1, 'has_cron' => 1, 'notification_item'=> 1))
   );

  //allow a specific platform to add interfaces for a component
  //(Paul made globus use a custom layout set in portal, to move it accross to globus component
  // we need to let Globus platform to set portal has a set_custom_layout
  public function addComponentInterface($psUid, $psName, $psInterface)
  {
    self::$asComponentUid[$psUid]['interfaces'][$psInterface] = 1;
    self::$asComponentUid[$psName]['interfaces'][$psInterface] = 1;
  }

  // list here the interfaces that needs functions to  be implemented in the component .class and .classEx
  // has_menuAction, has_menuGlobalAction, all the listen_XXX-XXX,

  public static function initialize()
  {
    self::$asInstancies = array();
    self::$asDependencies = array();

    global $gasComponentBlackList;
    self::$asBlacklist = $gasComponentBlackList;

    //generate a list of uid by interface name
    foreach(self::$asComponentUid as $sUid => $asComponentParam)
    {
      if(isset($asComponentParam['interfaces']))
      {
        foreach($asComponentParam['interfaces'] as $sInterface => $nOne)
        {
          if(!in_array($sUid, self::$asBlacklist))
            self::$asInterfaces[$sInterface][$sUid] = $sUid;
        }
      }
    }
  }

  static public function &getComponentByName($psComponentName, $psInterface = '')
  {
    if (!assert('!empty($psComponentName)'))
      exit('Error dependency');

    if(in_array($psComponentName, self::$asBlacklist))
    {
      /*assert('false; //Dependency error line '.__LINE__.': '.$psComponentName.' not available (Blacklisted)');
      exit();*/
      return self::$coNull;
    }

    if(!empty($psInterface) && !isset(self::$asComponentName[$psComponentName]['interface'][$psInterface]))
    {
      assert('false; //Dependency error line '.__LINE__.': '.$psComponentName.' '.$psInterface);
      exit();
    }

    //optimize compatibility
    $psComponentName = strtolower($psComponentName);

    if(isset(self::$asInstancies[$psComponentName]))
    {
      return self::$asInstancies[$psComponentName];
    }

    if(isset(self::$asInstancies['page']))
    {
      $sAction = self::$asInstancies['page']->getAction();
      $sType = self::$asInstancies['page']->getType();
      $nPK = self::$asInstancies['page']->getPk();
      $sMode = self::$asInstancies['page']->getMode();

    }
    else
    {
      $sAction = '';
      $sType = '';
      $nPK = '';
      $sMode = '';
    }

    switch($psComponentName)
    {
      case 'gb_user':
        require_once('component/globus/gb_user/gb_user.class.ex.php5');
        self::$asInstancies['gb_user'] = new CGbUserEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'gb_test':
        require_once('component/globus/gb_test/gb_test.class.ex.php5');
        self::$asInstancies['gb_test'] = new CGbTestEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'taaggregator':
        require_once('component/taaggregator/taaggregator.class.ex.php5');
        self::$asInstancies['taaggregator'] = new CTAaggregatorEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'jobboard_user':
        require_once('component/jobboard_user/jobboard_user.class.ex.php5');
        self::$asInstancies['jobboard_user'] = new CJobboarduserEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'talentatlas':
        require_once('component/talentatlas/talentatlas.class.ex.php5');
        self::$asInstancies['talentatlas'] = new CTalentatlasEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'jobboard':
        require_once('component/jobboard/jobboard.class.ex.php5');
        self::$asInstancies['jobboard'] = new CJobboardEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'customfields':
        require_once('component/customfields/customfields.class.ex.php5');
        self::$asInstancies['customfields'] = new CCustomfieldsEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'zimbra':
        require_once('component/zimbra/zimbra.class.ex.php5');
        self::$asInstancies['zimbra'] = new CZimbraEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'database':
        require_once('component/database/database.class.ex.php5');
        self::$asInstancies['database'] = new CDatabaseEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'page':
        require_once('component/page/page.class.ex.php5');
        self::$asInstancies['page'] = new CPageEx($sAction, $sType, $nPK, $sMode);

        //Page is special, it's the root component: store it as an attribute for further re use
        self::$coPage = self::$asInstancies['page'];

        self::$asInstancies['page']->init();
        return self::$asInstancies['page'];
        break;

      case 'display':
        require_once('component/display/display.class.ex.php5');
        self::$asInstancies['display'] = new CDisplayEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'form':
        require_once('component/form/form.class.ex.php5');
        self::$asInstancies['form'] = new CFormEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'login':
        require_once('component/login/login.class.ex.php5');
        self::$asInstancies['login'] = new CLoginEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'addressbook':
        require_once('component/addressbook/addressbook.class.ex.php5');
        self::$asInstancies['addressbook'] = new CAddressbookEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'project':
        require_once('component/project/project.class.ex.php5');
        self::$asInstancies['project'] = new CProjectEx($sAction, $sType, $nPK, $sMode);
        break;

       case 'sharedspace':
        require_once('component/sharedspace/sharedspace.class.ex.php5');
        self::$asInstancies['sharedspace'] = new CSharedspaceEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'mail':
        require_once('component/mail/mail.class.ex.php5');
        self::$asInstancies['mail'] = new CMailEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'search':
        require_once('component/search/search.class.ex.php5');
        self::$asInstancies['search'] = new CSearchEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'pager':
        require_once('component/pager/pager.class.ex.php5');
        self::$asInstancies['pager'] = new CPagerEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'event':
        require_once('component/event/event.class.ex.php5');
        self::$asInstancies['event'] = new CEventEx($sAction, $sType, $nPK, $sMode);
        break;

     case 'webmail':
        require_once('component/webmail/webmail.class.ex.php5');
        self::$asInstancies['webmail'] = new CWebMailEx($sAction, $sType, $nPK, $sMode);
        break;

     case 'querybuilder':
        require_once('component/querybuilder/querybuilder.class.ex.php5');
        self::$asInstancies['querybuilder'] = new CQuerybuilderEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'right':
        require_once('component/right/right.class.ex.php5');
        self::$asInstancies['right'] = new CRightEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'settings':
        require_once('component/settings/settings.class.ex.php5');
        self::$asInstancies['settings'] = new CSettingsEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'portal':
        //custom component, every website has its own specific class to manage the portal
        $sClass = strtolower(CONST_WEBSITE);
        require_once('component/portal/resources/class/portal_'.$sClass.'.class.php5');

        $sClass = 'CPortal'.ucfirst($sClass).'Ex';
        $oRefClass = new ReflectionClass($sClass);

        self::$asInstancies['portal'] = $oRefClass->newInstanceArgs(array($sAction, $sType, $nPK, $sMode));
        break;

      case 'charts':
        require_once('component/charts/charts.class.ex.php5');
        self::$asInstancies['charts'] = new CChartsEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'socialnetwork':
        require_once('component/socialnetwork/socialnetwork.class.ex.php5');
        self::$asInstancies['socialnetwork'] = new CSocialnetworkEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'opportunity':
        require_once('component/opportunity/opportunity.class.ex.php5');
        self::$asInstancies['opportunity'] = new COpportunityEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'menu':
        require_once('component/menu/menu.class.ex.php5');
        self::$asInstancies['menu'] = new CMenuEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'manageablelist':
        require_once('component/manageablelist/manageablelist.class.ex.php5');
        self::$asInstancies['manageablelist'] = new CManageablelistEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'folder':
        require_once('component/folder/folder.class.ex.php5');
        self::$asInstancies['folder'] = new CFolderEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'notification':
        require_once('component/notification/notification.class.ex.php5');
        self::$asInstancies['notification'] = new CNotificationEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_candidate':
        require_once('component/sl_candidate/sl_candidate.class.ex.php5');
        self::$asInstancies['sl_candidate'] = new CSl_candidateEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_menu':
        require_once('component/sl_menu/sl_menu.class.ex.php5');
        self::$asInstancies['sl_menu'] = new CSl_menuEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_folder':
        require_once('component/sl_folder/sl_folder.class.ex.php5');
        self::$asInstancies['sl_folder'] = new CSl_folderEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_event':
        require_once('component/sl_event/sl_event.class.ex.php5');
        self::$asInstancies['sl_event'] = new CSl_eventEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_position':
        require_once('component/sl_position/sl_position.class.ex.php5');
        self::$asInstancies['sl_position'] = new CSl_positionEx($sAction, $sType, $nPK, $sMode);
        break;

      case 'sl_stat':
        require_once('component/sl_stat/sl_stat.class.ex.php5');
        self::$asInstancies['sl_stat'] = new CSl_statEx($sAction, $sType, $nPK, $sMode);
        break;


      default:
        assert('false; //calling a component that doesn\'t exist. Dependency error line '.__LINE__.': '.$psComponentName.' '.$psInterface);
        exit();
        break;
      }

      if(!self::$coPage)
        self::getComponentByName('page');

      self::$asInstancies[$psComponentName]->setLanguage(self::$coPage->getLanguage());
      return self::$asInstancies[$psComponentName];
  }

  static public function getComponentUidByName($psComponentName)
  {
    if(!in_array($psComponentName, self::$asBlacklist))
    {
      switch($psComponentName)
      {
        case 'gb_user':
          return '160-001';
          break;

        case 'gb_test':
          return '160-002';
          break;

        case 'talentatlas':
         return '150-163';
          break;

        case 'jobboard':
         return '153-160';
          break;

        case 'jobboard_user':
          return '654-321';
            break;

         case 'customfields':
          return '180-290';
            break;

        case 'database':
          return '124-546';
            break;

        case 'page':
          return '845-187';
            break;

        case 'display':
          return '569-741';
            break;

        case 'form':
          return '668-313';
            break;

        case 'login':
          return '579-704';
            break;

        case 'addressbook':
          return '777-249';
            break;

        case 'project':
          return '456-789';
            break;

        case 'sharedspace':
          return '999-111';
            break;

        case 'search':
          return '898-775';
            break;

        case 'pager':
          return '140-510';
            break;

        case 'event':
          return '007-770';
            break;

        case 'webmail':
          return '009-724';
            break;

        case 'mail':
          return '008-724';
            break;

        case 'querybuilder':
          return '210-482';
            break;

        case 'taaggregator':
          return '100-603';
           break;

        case 'right':
          return '998-877';
            break;

        case 'settings':
          return '665-544';
            break;

        case 'zimbra':
          return '400-650';
            break;

        case 'portal':
          return '111-111';
            break;

        case 'charts':
          return '222-222';
            break;

        case 'socialnetwork':
          return '459-456';
            break;

        case 'opportunity':
          return '555-123';
            break;

        case 'menu':
          return '185-963';
          break;

        case 'manageablelist':
          return '134-685';
          break;

        case 'folder':
          return '486-125';
          break;

        case 'notification':
          return '333-333';
          break;

        case 'sl_candidate':
          return '555-001';
          break;

        case 'sl_folder':
          return '555-002';
          break;

        case 'sl_menu':
          return '555-003';
          break;

        case 'sl_event':
          return '555-004';
          break;

        case 'sl_position':
          return '555-005';
          break;

        case 'sl_stat':
          return '555-006';
          break;

        default:
          return '';
            break;
        }
     }
    return '';
  }


  static public function getComponentNameByUid($psComponentID)
  {
    if(!in_array($psComponentID, self::$asBlacklist))
    {
      switch($psComponentID)
      {
        case '196-001':
          return 'gb_user';
          break;

        case '196-002':
          return 'gb_test';
          break;

        case '150-163':
          return 'talentatlas';
            break;

        case '153-160':
          return 'jobboard';
            break;

        case '654-321':
          return 'jobboard_user';
            break;

        case '124-546':
          return 'database';
            break;

        case '845-187':
          return 'page';
            break;

        case '569-741':
          return 'display';
            break;

        case '668-313':
          return 'form';
            break;

        case '579-704':
          return 'login';
            break;

        case '777-249':
          return 'addressbook';
            break;

        case '456-789':
          return 'project';
            break;

        case '999-111':
          return 'sharedspace';
            break;

        case '898-775':
          return 'search';
            break;

        case '140-510':
          return 'pager';
            break;

       case '007-770':
        return 'event';
          break;

      case '009-724':
        return 'webmail';
          break;

      case '008-724':
        return 'mail';
          break;

      case '210-482':
        return 'querybuilder';
          break;

      case '100-603':
        return 'taaggregator';
          break;

      case '998-877':
        return 'right';
          break;

      case '665-544':
        return 'settings';
          break;

      case '400-650':
        return 'zimbra';
           break;

      case '111-111':
        return 'portal';
           break;

      case '222-222':
        return 'charts';
          break;

      case '459-456':
        return 'socialnetwork';
          break;

      case '555-123':
        return 'opportunity';
          break;

      case '185-963':
        return 'menu';
          break;

      case '134-685':
        return 'manageablelist';
          break;

      case '486-125':
        return 'folder';
        break;

      case '333-333':
        return 'notification';
        break;

      case '555-001':
        return 'sl_candidate';
        break;

      case '555-002':
        return 'sl_folder';
        break;

      case '555-003':
        return 'sl_menu';
        break;

      case '555-004':
        return 'sl_event';
        break;

      case '555-005':
        return 'sl_position';
        break;

      case '555-006':
        return 'sl_stat';
        break;


      default:
        return '';
          break;
      }
    }

    return '';
  }


  static public function getComponentByUid($psUid, $psInterface = '')
  {
    if(!empty($psInterface) && !isset(self::$asComponentUid[$psUid]['interfaces'][$psInterface]))
      return null;

    if(!in_array($psUid, self::$asBlacklist))
    {
      switch($psUid)
      {
        case '196-001':
          return self::getComponentByName('gb_user');
          break;

        case '196-002':
          return self::getComponentByName('gb_test');
          break;

        case '150-163':
          return self::getComponentByName('talentatlas');
            break;

        case '153-160':
          return self::getComponentByName('jobboard');
            break;

        case '654-321':
          return self::getComponentByName('jobboard_user');
            break;

        case '124-546':
          return self::getComponentByName('database');
            break;

        case '845-187':
          return self::getComponentByName('page');
            break;

        case '569-741':
          return self::getComponentByName('display');
            break;

        case '668-313':
          return self::getComponentByName('form');
            break;

        case '579-704':
          return self::getComponentByName('login');
            break;

        case '777-249':
          return self::getComponentByName('addressbook');
            break;

        case '456-789':
          return self::getComponentByName('project');
            break;

        case '999-111':
          return self::getComponentByName('sharedspace');
            break;

        case '008-724':
          return self::getComponentByName('mail');
            break;

        case '009-724':
          return self::getComponentByName('webmail');
            break;

        case '898-775':
          return self::getComponentByName('search');
            break;

        case '140-510':
          return self::getComponentByName('pager');
            break;

        case '007-770':
          return self::getComponentByName('event');
            break;

        case '210-482':
          return self::getComponentByName('querybuilder');
            break;

        case '100-603':
          return self::getComponentByName('taaggregator');
            break;

        case '998-877':
          return self::getComponentByName('right');
            break;

        case '665-544':
          return self::getComponentByName('settings');
            break;

        case '180-290':
          return self::getComponentByName('customfields');
            break;

        case '400-650':
          return self::getComponentByName('zimbra');
            break;

        case '111-111':
          return self::getComponentByName('portal');
            break;

        case '222-222':
          return self::getComponentByName('charts');
            break;

        case '459-456':
          return self::getComponentByName('socialnetwork');
            break;

        case '555-123':
          return self::getComponentByName('opportunity');
            break;

        case '185-963':
          return self::getComponentByName('menu');
          break;

        case '134-685':
          return self::getComponentByName('manageablelist');
          break;

        case '486-125':
          return self::getComponentByName('folder');
          break;

        case '333-333':
          return self::getComponentByName('notification');
          break;

        case '555-001':
          return self::getComponentByName('sl_candidate');
          break;

        case '555-002':
          return self::getComponentByName('sl_folder');
          break;

        case '555-003':
          return self::getComponentByName('sl_menu');
          break;

        case '555-004':
          return self::getComponentByName('sl_event');
          break;

        case '555-005':
          return self::getComponentByName('sl_position');
          break;

        case '555-006':
          return self::getComponentByName('sl_stat');
          break;

        default:
          assert('false; /* uid not available ['.$psUid.']*/');
           exit();
            break;
      }
    }
  }

  static public function hasInterfaceByUid($psUid, $psInterface)
  {
    if(empty($psUid) || empty($psInterface))
      return false;

    if(in_array($psUid, self::$asBlacklist))
      return false;

    if(!isset(self::$asComponentUid[$psUid]) || !isset(self::$asComponentUid[$psUid]['interfaces']))
      return false;

    if(!isset(self::$asComponentUid[$psUid]['interfaces'][$psInterface]))
      return false;

    return true;
  }

  static public function getComponentUidByInterface($psInterface, $pbFirstOneOnly = false)
  {
    if(empty($psInterface))
      return array();

    $asComponents = array();
    foreach(self::$asComponentUid as $sKey => $asComponentParam)
    {
      if(isset($asComponentParam['interfaces'][$psInterface]) && !in_array($sKey, self::$asBlacklist))
      {
        $asComponents[$sKey] = $sKey;
      }
    }

    if($pbFirstOneOnly)
      return current($asComponents);

    return $asComponents;
  }

  /**
   * Return a list of component having the requested interface
   * @param string $psInterface: interface name
   * @return array: of object
   */
  static public function getComponentsByInterface($psInterface)
  {
    if(!assert('!empty($psInterface)') || !isset(self::$asInterfaces[$psInterface]))
      return array();

    $aoComponent = array();
    foreach(self::$asInterfaces[$psInterface] as $sUid)
      $aoComponent[$sUid] = self::getComponentByUid($sUid);

    return $aoComponent;
  }

  /**
   *Return the first component that possess the requested interface.
   * @param string $psInterface
   * @return null or object
   */
  static public function getComponentByInterface($psInterface)
  {
    if(!assert('!empty($psInterface)') || !isset(self::$asInterfaces[$psInterface]))
      return array();

    $sUid = current(self::$asInterfaces[$psInterface]);

    return self::getComponentByUid($sUid);
  }


  /**
   *Call by components when updating their data, we notify all listening components
   * that changes are made
   *
   * @param string $psUid
   * @param string $psAction
   * @param string $psType
   * @param integer $pnPk
   * @param array $pasParam
   * @return integer: number of notified components
   */
  static public function notifyListeners($psUid, $psAction, $psType = '', $pnPk = 0, $psActionToDo = '', $pasParam = array())
  {
    if(!assert('!empty($psUid) && !empty($psAction) && is_integer($pnPk) && is_array($pasParam)'))
      return 0;

    $asComponent = self::getComponentsByInterface('listen_'.$psUid);

    if(empty($asComponent))
      return 0;

    $nCount = 0;
    foreach($asComponent as $oComponent)
    {
      if(!empty($oComponent))
      {
        $oComponent->listenerNotification($psUid, $psAction, $psType, $pnPk, $psActionToDo, $pasParam = array());
        $nCount++;
      }
    }

    return $nCount;
  }


  //------------------------------------------------
  //Aliases to simplify coding

  static public function getCpPage()
  {
    return self::getComponentByName('page');
  }

  static public function getCpLogin()
  {
    if(isset(self::$asInstancies['login']))
      return self::$asInstancies['login'];

    return self::getComponentByName('login');
  }

  static public function getCpHtml()
  {
    if(isset(self::$asInstancies['display']))
      return self::$asInstancies['display'];

    return self::getComponentByName('display');
  }



}