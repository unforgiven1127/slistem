<?php

require_once('component/portal/portal.class.ex.php5');

class CPortalBcmEx extends CPortalEx
{


  public function getPageNavigation($psAction = '', $psType = '', $pnPk = 0)
  {
   /* $oPage = CDependency::getCpPage();
    $asItem = array();
    $asItem['111-111']['*']['*']['*'][] = array('picture' => 'ct_list_32.png','label'=>'Home', 'url' => $oPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT), 'option' => array());
    $asItem['111-111']['*']['*']['*'][] = array('picture' => 'ct_list_32.png','label'=>'Home 2', 'url' => $oPage->getUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT));
    $asItem['111-111'][CONST_ACTION_LIST]['*']['*'][] = array('picture' => 'ct_list_32.png','label'=>'Home 2', 'url' => $oPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT));

    return $asItem;*/
    return array();
  }


  public function getHtml()
  {
    return $this->getHomePage();
  }


  //Ajax function
  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_PORTAL_OPP_USER_STAT:
        $oPage = CDependency::getCpPage();
        $sHTML = $this->_getOpportunityStatsByUsers(false, true);
        if(empty($sHTML))
          $sHTML = '<em class="portalChartEmptychart">No data for the moment</em>';

        $asData = $oPage->getAjaxExtraContent(array('data' => $sHTML, 'action' => ' initChart(); '));
         return json_encode($asData);
          break;

      case CONST_PORTAL_OPP_STAT:
        $oPage = CDependency::getCpPage();
        $sHTML = $this->_getOpportunityStats(false, true, (int)getValue('ppk',0));
        if(empty($sHTML))
          $sHTML = '<em class="portalChartEmptychart">No data for the moment</em>';

        $asData = $oPage->getAjaxExtraContent(array('data' => $sHTML, 'action' => ' initChart(); '));
         return json_encode($asData);
          break;

      case CONST_PORTAL_STAT:
        $oPage = CDependency::getCpPage();
        $sHTML = $this->_getUserStats(false, true, (int)getValue('ppk', 0), $_GET);
        if(empty($sHTML))
          $sHTML = '<em class="portalChartEmptychart">No data for the moment</em>';

        $asData = $oPage->getAjaxExtraContent(array('data' => $sHTML, 'action' => ' initChart(); '));
        return json_encode($asData);
          break;

      case CONST_PORTAL_CALENDAR:
        $oPage = CDependency::getCpPage();
        $sHTML = $this->_getUserCalendar(false, true);
        $asData = $oPage->getAjaxExtraContent(array('data' => $sHTML));
         return json_encode($asData);
          break;
    }
  }

  // Function to display the home page tabs

  public function getHomePage()
  {
    /* TODO: at some point there will be custom page or preferences  */
    /* @var $oDisplay CDisplayEx */
    $oDisplay = CDependency::getCpHtml();
    $asHomepageTab = array();

    $sHTML = $oDisplay->getBlocStart('', array('class' => 'homepageContainer'));

      $asHomepageTab[] = array('label' => 'mybcm', 'title' => 'My BCM workspace', 'content' => $this->_getUserBcmTab());
      $asHomepageTab[] = array('label' => 'allbcm', 'title' => 'What\'s new in BCM', 'content' => $this->_getAllBcmTab(), 'options' => array('onclick' => '$(\'#op_charts\').show();'));
      $asHomepageTab[] = array('label' => 'appsbcm', 'title' => 'Other apps & site', 'content' => $this->_getBcmAppsTab());

      $sHTML.= $oDisplay->getTabs('homepage_tabs',$asHomepageTab);

    $sHTML.= $oDisplay->getBlocEnd();

    return $sHTML;
  }

  /**
   * Display the latest activities in BCM and display icons
   * @return type
   */

  private function _getAllBcmTab()
  {
    $oDisplay = CDependency::getCpHtml();
    $oAddressBook = CDependency::getComponentByName('addressbook');
    $sHTML = '';

    if(!empty($oAddressBook))
    {
       $sHTML.= $oDisplay->getBlocStart('', array('style' => 'margin-bottom: 20px;'));

       $asActivity = $this->_getRecentActivity();
       if(!empty($asActivity))
        {
          $sHTML.= $oDisplay->getBlocStart('', array('style' => 'float: left; width: 61%;'));
            $sHTML.= $oDisplay->getBlocStart('homepage_activityId',array('class'=>'homepageSection'));

              $sHTML.= $oDisplay->getBlocStart('',array('class'=>'homepageSectionTitlte'));
              $sHTML.= $oDisplay->getText('Latest entries in BCM');
              $sHTML.= $oDisplay->getBlocEnd();

              $sHTML.= $oDisplay->getBlocStart('',array('class'=>'homepageSectionInner'));
              $sHTML.= implode('', $asActivity);
              $sHTML.= $oDisplay->getBlocEnd();

              $sHTML.= $oDisplay->getFloatHack();

            $sHTML.= $oDisplay->getBlocEnd();
          $sHTML.= $oDisplay->getBlocEnd();
        }

        $sHTML.= $oDisplay->getBlocStart('op_charts', array('class'=>'homepageSection', 'style' => 'float:right; width:38%; max-width: 450px; display:none; '));

          $sHTML.= $oDisplay->getBloc('',$oDisplay->getText('Global sales opportunities'),array('class'=>'homepageSectionTitlte'));
          $sHTML.= $this->_getOpportunityStats(true, false, 0);
          //$sHTML.= $this->_getOpportunityStatsByUsers(true, false);
          $sHTML.= $this->_getOpportunityStatsByUsers(false, false);

        $sHTML.= $oDisplay->getBlocEnd();

      $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $oDisplay->getFloatHack();

      $sUid = $oAddressBook->getComponentUid();
      $oRights = CDependency::getComponentByName('right');

      $oLogin = CDependency::getCpLogin();


      if($oRights->canAccess('111-111', CONST_ACTION_VIEW, 'bcm_stat'))
      {
        //get All users
        $oLogin = CDependency::getCpLogin();
        $asUsers = $oLogin->getUserList(0, true, false);
        $asSales = $oLogin->getUserByTeam(0, 'user_with_stat');
        $asSales = array_keys($asSales);

        $asChartParams = array('loadingTrigger' => '$("#show_user_stats").click', 'containerWidth' => '100%', 'chartWidth' => '360px');

        $sHTML.= $oDisplay->getCR();
        $sHTML.= $oDisplay->getLink('Show all sales stats', 'javascript:;', array('id' => 'show_user_stats', 'class' => 'homepageSectionTitlte', 'onclick' => ' $(\'#all_user_stats:not(:visible)\').fadeIn(); '));
        $sHTML.= $oDisplay->getBlocStart('all_user_stats', array('style' => 'width: 100%; position: relative; display: none;'));

        foreach($asUsers as $asUserData)
        {
          //if($asUserData['login_groupfk'] == 1)
          if(in_array($asUserData['loginpk'], $asSales))
          {
            $sHTML.= $oDisplay->getBlocStart('', array('style' => 'width: 370px; margin-right:12px; float:left;'));
            $sHTML.= $oDisplay->getText('<strong>'.$asUserData['lastname'].'</strong>');
            $sHTML.= $oDisplay->getCR();
            $sHTML.= $this->_getUserStats(true, false, (int)$asUserData['loginpk'], $asChartParams);
            $sHTML.= $oDisplay->getBlocEnd();
          }
        }

        $sHTML.= $oDisplay->getFloatHack();
        $sHTML.= $oDisplay->getBlocEnd();
      }
    }

    return $sHTML;
  }

  //Display user's bcm data about company, connection, project and task
  private function _getUserBcmTab()
  {
    $oLogin = CDependency::getCpLogin();
    $oDisplay = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oAddress = CDependency::getComponentByName('addressbook');
    $oRight = CDependency::getComponentByName('right');
    $bAccessPipeline = $oRight->canAccess('555-123', 'view-all', CONST_OPPORTUNITY);

    $sHTML = $oDisplay->getBlocStart('', array('class' => 'homepageMySpace'));

     $asAction = array();

     $sURL = $oPage->getUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY,0,array('loginpk'=>(int)$oLogin->getUserPk()));
     $asAction[] = array('label' => 'My companies', 'url' => $sURL, 'pic' => '/tmp_home_icons/company_24.png');

     $sURL = $oPage->getUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT,0,array('loginpk'=>(int)$oLogin->getUserPk()));
     $asAction[] = array('label' => 'My connections', 'url' => $sURL, 'pic' => '/tmp_home_icons/ct_view_24.png');

     $sURL = $oPage->getUrl('sharedspace', CONST_ACTION_MANAGE, CONST_SS_TYPE_DOCUMENT);
     $asAction[] = array('label' => 'My documents', 'url' => $sURL, 'pic' => '/tmp_home_icons/component.png');

     $sURL = $oPage->getUrl('project', CONST_ACTION_LIST, CONST_PROJECT_TYPE_TASK, 0, array(CONST_PROJECT_TASK_SORT_PARAM => 'project'));
     $asAction[] = array('label' => 'My tasks', 'url' => $sURL, 'pic' => '/tmp_home_icons/menu_task_add.png');

     $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageMySpaceLink'));
     $sHTML.= $oDisplay->getActionButtons($asAction, 1, 'My workspace...', array('width' => 250));
     $sHTML.= $oDisplay->getBlocEnd();

     //business opportunities
     $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageMySpaceLink flashyTitle'));

      $asAction = array();
      $sURL = $oPage->getUrl('opportunity', CONST_ACTION_LIST, CONST_OPPORTUNITY, 0);
      $asAction[] = array('label' => 'My business opportunities', 'url' => $sURL, 'pic' => '/tmp_home_icons/opportunity_24.png');

      if ($bAccessPipeline)
      {
        $sURL = $oPage->getUrl('opportunity', CONST_ACTION_LIST, CONST_OPPORTUNITY, 0);
        $asAction[] = array('label' => 'Company pipeline', 'url' => $sURL.'&globalstat=1', 'pic' => '/tmp_home_icons/opportunity_24.png');
      }

      $sURL = $oPage->getUrl('opportunity', CONST_ACTION_LIST, CONST_OPPORTUNITY, 0, array('display' => 'bbook'));
      $asAction[] = array('label' => 'Tokyo Weekender Black Book', 'url' => $sURL);

      $sHTML.= $oDisplay->getActionButtons($asAction, 1, 'Business stats...', array('width' => 250));

     $sHTML.= $oDisplay->getBlocEnd();

    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();
    $sHTML.= $oDisplay->getFloatHack();

    $sHTML.= $oDisplay->getBlocStart('', array('style' => 'width: 61%; float: left;'));


    //===================================================================
    // Things that have been done on the user's data (he is follower)
    if(!empty($oAddress))
    {
      $asContactActivity = $this->getContactRecentActivity((int)$oLogin->getUserPk());
      $oPage->addCssFile($oLogin->getResourcePath().'css/login.form.css');

      if(!empty($asContactActivity))
      {
        $sHTML.= $oDisplay->getBlocStart('homepage_contactactivityId', array('class' => 'homepageSection'));

         $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSectionTitlte','style' => 'color: #F97807;'));
         $sHTML.= $oDisplay->getText(' Recent activity on my connections');
         $sHTML.= $oDisplay->getBlocEnd();

         $asArray = array();

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSectionInner', 'style' => 'min-height: 30px;'));
          $nCount = 0;
            foreach($asContactActivity as $asActivity)
            {
              $sUser = $oLogin->getUserLink((int)$asActivity['loginfk'], true);
              if(empty($asArray[$nCount]['item']))
              {
                $asContactData = $oAddress->getContactByPk((int)$asActivity['followerfk']);
                $asArray[$nCount]['item'] = $asContactData['firstname'].' '.$asContactData['lastname'];
              }

              $asArray[$nCount]['log_link'] = $asActivity['log_link'].'#ct_tab_eventId';
              $asArray[$nCount]['log_date'] = $asActivity['log_date'];

              $asArray[$nCount]['action'] = strip_tags($sUser).' -> '.$asActivity['text'];
              $nCount++;
            }

            $sHTML.= $this->_getDisplayUserRecent(array('user_activity' => $asArray));

            $sHTML.= $oDisplay->getFloatHack();
          $sHTML.= $oDisplay->getBlocEnd();


          $sHTML.= $oDisplay->getFloatHack();
          $sHTML.= $oDisplay->getBlocEnd();
          $sHTML.= $oDisplay->getFloatHack();
        }

      $asUserActivity = $this->_getUserRecentActivity((int)$oLogin->getUserPk(), 70, 14);

      //Bloc  Recent activity
      if(empty($asUserActivity))
      {
        $sHTML.= $oDisplay->getBlocStart('homepage_contactactivityId', array('class' => 'homepageSection', 'style' => 'min-height: 300px;'));
        $sHTML.= $oDisplay->getText('Nothing done so far o_O');
        $sHTML.= $oDisplay->getBlocEnd();
      }
      else
      {
        //===================================================================
        $sHTML.= $oDisplay->getBlocStart('homepage_useractivityId');

          $sHTML.= $oDisplay->getBlocStart('homepage_activityId', array('style' => 'min-height: 450px; float: left; width:100%;'));
          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSection'));

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSectionTitlte'));
          $sHTML.= $oDisplay->getText('My recent activity');
          $sHTML.= $oDisplay->getBlocEnd();

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSectionInner'));
          $sHTML.= $this->_getDisplayUserRecent($asUserActivity);
          $sHTML.= $oDisplay->getBlocEnd();
          $sHTML.= $oDisplay->getBlocEnd();
          $sHTML.= $oDisplay->getBlocEnd();

          $sHTML.= $oDisplay->getFloatHack();
        $sHTML.= $oDisplay->getBlocEnd();
       }

     }
     $sHTML.= $oDisplay->getFloatHack();
     $sHTML.= $oDisplay->getBlocEnd();

     //------------------------------------------------------------
     //------------------------------------------------------------
     // Right blocks
     $sHTML.= $this->_getUserCalendar(true);


     $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSection', 'style' => 'width: 38%; max-width: 450px; float:right;'));
      $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSectionTitlte'));
        $sHTML.= $oDisplay->getText('My recent opportunities');
      $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $this->_getOpportunityStats(true, false, $oLogin->getUserPk());
     $sHTML.= $oDisplay->getBlocEnd();

     $sHTML.= $this->_getUserStats(true);

     $sHTML.= $oDisplay->getFloatHack();

     return $sHTML;
  }

  /**
   * Applications display function
   * @return string of HTML
   */

  private function _getBcmAppsTab()
  {
    $oLogin = CDependency::getCpLogin();
    $oDisplay = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sHTML = $oDisplay->getBlocStart('', array('class' => 'appsTabContainer'));
    $sHTML.= $oDisplay->getTitle('Our Main Websites', 'h2', true, array('onclick' => '$(\'#homepage_siteId\').fadeToggle();', 'class' => 'clickable'));

      $sHTML.= $oDisplay->getBlocStart('homepage_siteId');
      $sUrl = $oPage->getUrlEmbed('http://www.tokyoweekender.com?from=bccrm&pk='.$oLogin->getUserPk());
      $sLink = $oDisplay->getLink('Tokyo Weekender', $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');

      $sUrl = $oPage->getUrlEmbed('http://www.asiadailywire.com?from=bccrm&pk='.$oLogin->getUserPk());
      $sLink = $oDisplay->getLink('Asia Daily wire', $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');

      $sUrl = $oPage->getUrlEmbed('http://www.bulbouscell.com?from=bccrm&pk='.$oLogin->getUserPk());
      $sLink = $oDisplay->getLink('Bulbouscell', $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');

      $sUrl = $oPage->getUrlEmbed('http://jobs.slate.co.jp');
      $sLink = $oDisplay->getLink('Slate job board', $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');


      $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();

    //===================================================================
    //Bloc  Apps

    $sHTML.= $oDisplay->getTitle('Internal Apps', 'h2', true, array('onclick' => '$(\'#homepage_appsId\').fadeToggle();', 'class' => 'clickable'));
    $sHTML.= $oDisplay->getBlocStart('homepage_appsId');

      $sUrl = $oPage->getUrlEmbed('http://www.bulbouscell.com/newsletter/admin/index.php?from=bccrm&pk='.$oLogin->getUserPk());
      $sLink = $oDisplay->getLink('News letter', $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');

      $sUrl = $oPage->getUrlEmbed('http://bulbouscell.com/distribution/?from=bccrm&pk='.$oLogin->getUserPk());
      $sLink = $oDisplay->getLink('Distribution',  $sUrl);
      $sHTML.= $this->_getHomePageTitleLink($sLink, '');

      $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();

    //===================================================================
    //Bloc  partner / IT
    $sHTML.= $oDisplay->getBlocStart('', array('style' => 'float:left; width: 100%;'));

      $sHTML.= $oDisplay->getBlocStart('', array('style' => 'width:100%; float:left; '));
        $sHTML.= $oDisplay->getTitle('Partner & other apps', 'h2', true, array('onclick' => '$(\'#homepage_partnerId\').fadeToggle();', 'class' => 'clickable'));

         $sHTML.= $oDisplay->getBlocStart('homepage_partnerId', array('style' => 'float: left;'));
         $sUrl = $oPage->getUrlEmbed('http://www.slate.co.jp?from=bccrm&pk='.$oLogin->getUserPk());
         $sLink = $oDisplay->getLink('Slate.co.jp', $sUrl);
         $sHTML.= $this->_getHomePageTitleLink($sLink, '');
         $sHTML.= $oDisplay->getFloatHack();
         $sHTML.= $oDisplay->getBlocEnd();

         $sHTML.= $oDisplay->getBlocStart('homepage_itId', array('style' => 'float: left;'));
         $sUrl = $oPage->getUrlEmbed('http://www.bulbouscell.com/infrastructure/');
         $sLink = $oDisplay->getLink('Infrastructure', $sUrl);
         $sHTML.= $this->_getHomePageTitleLink($sLink, '');

         $sHTML.= $oDisplay->getBlocStart('homepage_itId', array('style' => 'float: left;'));
         $sUrl = $oPage->getUrlEmbed('http://www.talentatlas.com');
         $sLink = $oDisplay->getLink('Talentatlas (experimental job website)', $sUrl);
         $sHTML.= $this->_getHomePageTitleLink($sLink, '');

         $sHTML.= $oDisplay->getFloatHack();
        $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $oDisplay->getBlocEnd();

    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();

    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();
    return $sHTML;
  }

  /**
  * Display the Title of the page
  * @param string $psLink
  * @param string $psPicture
  * @return HTML structure
  */
  private function _getHomePageTitleLink($psLink, $psPicture)
  {
    /* @var $oDisplay CDisplayEx */
    $oDisplay = CDependency::getCpHtml();

    $sHTML = $oDisplay->getBlocStart('', array('style' => 'height:50px; float:left; margin:10px;'));
      $sHTML.= $oDisplay->getBlocStart('', array('style' => 'float:left;'));
      $sHTML.= $psPicture;
      $sHTML.= $oDisplay->getBlocEnd();

      $sHTML.= $oDisplay->getBlocStart('', array('style' => 'float:left; vertical-align:middle; margin:10px 0 0 10px;'));
      $sHTML.= $psLink;
      $sHTML.= $oDisplay->getBlocEnd();
    $sHTML.= $oDisplay->getBlocEnd();

    return $sHTML;
  }

  /**
  * Display the recent activity of all users
  * @return HTML structure
  */
  private function _getRecentActivity()
  {
    //TODO: make it differently, that s crap
    $oLogin = CDependency::getCpLogin();
    $oDB = CDependency::getComponentByName('database');
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oAddressBook = CDependency::getComponentByName('addressbook');
    $oProject = CDependency::getComponentUidByName('project');
    $oAddress = CDependency::getComponentUidByName('addressbook');
    $oSharedSpace = CDependency::getComponentUidByName('sharedspace');
    $oEvent = CDependency::getComponentUidByName('event');

    $asActivity = array();
    $asUsers = $oLogin->getUserList(0, true, true);

    if(!empty($oAddress))
    {
      $sQuery = 'SELECT * FROM addressbook_contact WHERE created_by <> '.$oLogin->getUserPk().' ORDER BY date_create DESC LIMIT 5';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $sActivityString = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow hpsAllBcm'));

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
          $sActivityString.= $oHTML->getNiceTime($oDbResult->getFieldValue('date_create'), 0, true).':';
          $sActivityString.= $oHTML->getBlocEnd();

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
          $sActivityString.= $oHTML->getPicture('/common/pictures/items/ct_16.png').' ';
          $sURL = $oPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $oDbResult->getFieldValue('addressbook_contactpk', CONST_PHP_VARTYPE_INT));
          $sActivityString.= $oHTML->getLink($oDbResult->getFieldValue('lastname').' '.$oDbResult->getFieldValue('firstname'), $sURL);

          if(isset($asUsers[$oDbResult->getFieldValue('created_by')]))
          $sActivityString.= $oHTML->getText(' - by '.$oLogin->getUserNameFromData($asUsers[$oDbResult->getFieldValue('created_by')]));
          $sActivityString.= $oHTML->getBlocEnd();

        $sActivityString.= $oHTML->getBlocEnd();
        $asActivity[] = $sActivityString;

        $bRead = $oDbResult->readNext();
      }

      $asActivity[] = $oHTML->getFloatHack();
      $asActivity[] = $oHTML->getBlocStart('', array('class' => 'hps_separator_top')).$oHTML->getBlocEnd();

      $sQuery = 'SELECT * FROM addressbook_company WHERE creatorfk <> '.$oLogin->getUserPk().' ORDER BY date_create DESC LIMIT 5 ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $sActivityString = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow hpsAllBcm'));

        $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
        $sActivityString.= $oHTML->getNiceTime($oDbResult->getFieldValue('date_create'), 0, true).':';
        $sActivityString.= $oHTML->getBlocEnd();

        $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
        $sActivityString.= $oHTML->getPicture('/common/pictures/items/cp_16.png').' ';
        $sURL = $oPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $oDbResult->getFieldValue('addressbook_companypk', CONST_PHP_VARTYPE_INT));
        $sActivityString.= $oHTML->getLink($oDbResult->getFieldValue('company_name'), $sURL);
        $sActivityString.= $oHTML->getText(' - by '.$oLogin->getUserNameFromData($asUsers[$oDbResult->getFieldValue('creatorfk')]));
        $sActivityString.= $oHTML->getBlocEnd();

        $sActivityString.= $oHTML->getBlocEnd();
        $asActivity[] = $sActivityString;

        $bRead = $oDbResult->readNext();
      }
    }

    if(!empty($oEvent))
    {
      $asActivity[] = $oHTML->getFloatHack();
      $asActivity[] = $oHTML->getBlocStart('', array('class' => 'hps_separator_top')).$oHTML->getBlocEnd();

      $sQuery = ' SELECT event.*, event_link.*, login.lastname, login.firstname FROM event';
      $sQuery.= ' INNER JOIN event_link ON (event_link.eventfk = eventpk) ';
      $sQuery.= ' INNER JOIN login ON (event.created_by = loginpk) ';
      $sQuery.= ' WHERE created_by <> '.$oLogin->getUserPk().' ORDER BY date_create DESC LIMIT 5 ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $nPk = (int)$oDbResult->getFieldValue('cp_pk');
        $sTitle = $oDbResult->getFieldValue('title').' - '.$oDbResult->getFieldValue('content');
        $sTitle = strip_tags($sTitle);
        if(strlen($sTitle) > 60)
          $sTitle = substr($sTitle, 0, 57).'...';

        $sActivityString = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow hpsAllBcm'));

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
          $sActivityString.= $oHTML->getNiceTime($oDbResult->getFieldValue('date_create'), 0, true).':';
          $sActivityString.= $oHTML->getBlocEnd();

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
            $sActivityString.= $oHTML->getPicture('/common/pictures/items/event_16.png').' ';

            if((($oDbResult->getFieldValue('cp_uid')=='777-249') || ($oDbResult->getFieldValue('cp_uid')=='addressbook')) && (!empty($oAddress)) && is_key($nPk))
            {
              $aItem = $oAddressBook->getItemDescription($nPk, $oDbResult->getFieldValue('cp_action'));
              if(!empty($aItem))
                $sActivityString.= $oHTML->getText($aItem[$nPk]['link'].': ');
            }

            $sURL = $oPage->getUrl('addressbook', $oDbResult->getFieldValue(CONST_CP_ACTION), $oDbResult->getFieldValue(CONST_CP_TYPE), $oDbResult->getFieldValue(CONST_CP_PK, CONST_PHP_VARTYPE_INT));
            $sActivityString.= $oHTML->getLink($sTitle, $sURL);
            $sActivityString.= $oHTML->getText(' - by '.$oLogin->getUserNameFromData($asUsers[$oDbResult->getFieldValue('created_by')]));

          $sActivityString.= $oHTML->getBlocEnd();

        $sActivityString.= $oHTML->getBlocEnd();
        $asActivity[] = $sActivityString;

        $bRead = $oDbResult->readNext();
      }
    }

    if(!empty($oSharedSpace))
    {
      $oSharedSpace = CDependency::getComponentByName('sharedspace');
      $asActivity[] = $oHTML->getFloatHack();
      $asActivity[] = $oHTML->getBlocStart('', array('class' => 'hps_separator_top')).$oHTML->getBlocEnd();

      $oDbResult = $oSharedSpace->getLastDocuments(3);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $sActivityString = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow hpsAllBcm'));

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
          $sActivityString.= $oHTML->getNiceTime($oDbResult->getFieldValue('date_creation'), 0, true).':';
          $sActivityString.= $oHTML->getBlocEnd();

          $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
          $sActivityString.= $oHTML->getPicture('/common/pictures/items/doc_16.png').' ';

          if($oDbResult->getFieldValue('title'))
          {
            $sTitle = $oDbResult->getFieldValue('title');
            $nPk = $oDbResult->getFieldValue('shared_documentpk', CONST_PHP_VARTYPE_INT);
          }
          else
          {
            $sTitle = $oDbResult->getFieldValue('parent_title');
            $nPk = $oDbResult->getFieldValue('parentfk', CONST_PHP_VARTYPE_INT);
          }

          if(strlen($sTitle) > 40)
            $sTitle = substr($sTitle, 0, 40).'...';

          $sURL = $oPage->getUrl('sharedspace', CONST_ACTION_SEND, CONST_SS_TYPE_DOCUMENT, $nPk);
          $sActivityString.= $oHTML->getLink(substr($sTitle, 0, 50), $sURL);

          if(isset($asUsers[$oDbResult->getFieldValue('creatorfk')]))
          $sActivityString.= $oHTML->getText(' - by '.$oLogin->getUserNameFromData($asUsers[$oDbResult->getFieldValue('creatorfk')]));
          $sActivityString.= $oHTML->getBlocEnd();

         $sActivityString.= $oHTML->getBlocEnd();
         $asActivity[] = $sActivityString;

        $bRead = $oDbResult->readNext();
      }
    }

    if(!empty($oProject))
    {
      $asActivity[] = $oHTML->getFloatHack();
      $asActivity[] = $oHTML->getBlocStart('', array('class' => 'hps_separator_top')).$oHTML->getBlocEnd();

      $sQuery = ' SELECT * FROM task as t ';
      $sQuery.= ' LEFT JOIN project_task as pt ON (pt.taskfk = t.taskpk) ';
      $sQuery.= ' WHERE creatorfk <> '.$oLogin->getUserPk().' ORDER BY date_created DESC LIMIT 3 ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $sActivityString = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow hpsAllBcm'));

        $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
        $sActivityString.= $oHTML->getNiceTime($oDbResult->getFieldValue('date_created'), 0, true).':';
        $sActivityString.= $oHTML->getBlocEnd();

        $sActivityString.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
        $sActivityString.= $oHTML->getPicture('/common/pictures/items/task_16.png').' ';
        $sURL = $oPage->getUrl('project', CONST_ACTION_VIEW, CONST_PROJECT_TYPE_PROJECT, $oDbResult->getFieldValue('projectfk', CONST_PHP_VARTYPE_INT));
        $sActivityString.= $oHTML->getLink($oDbResult->getFieldValue('title'), $sURL);
        $sActivityString.= $oHTML->getText(' - by '.$oLogin->getUserNameFromData($asUsers[$oDbResult->getFieldValue('creatorfk')]));
        $sActivityString.= $oHTML->getBlocEnd();
        $sActivityString.= $oHTML->getBlocEnd();
        $asActivity[] = $sActivityString;

        $bRead = $oDbResult->readNext();
      }
    }

    $asActivity[] = $oHTML->getBlocStart('', array('class' => 'floatHack')) . $oHTML->getBlocEnd();
    return $asActivity;
  }

  /**
   * Display the recent activities of user
   * @param array $asUserRecentData
   * @return string HTML structure
   */

  private function _getDisplayUserRecent($asUserRecentData, $psForceType = '')
  {
    if(!assert('is_array($asUserRecentData)'))
      return '';

    $oHTML = CDependency::getCpHtml();

    if(empty($asUserRecentData))
      return $oHTML->getText('Nothing to display for now :\\');

    $sHTML = '';
    $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding:2px;'));

    $sCompanyIcon = $oHTML->getPicture('/common/pictures/items/cp_16.png').' ';
    $sContactIcon = $oHTML->getPicture('/common/pictures/items/ct_16.png').' ';
    $sEventIcon = $oHTML->getPicture('/common/pictures/items/event_16.png').' ';
    $sOppIcon = $oHTML->getPicture(CONST_PICTURE_OPPORTUNITY);


    foreach($asUserRecentData  as $sType => $asRecentData)
    {

      if(!empty($asRecentData))
      {
        $sTabHTML = $oHTML->getBlocStart('', array('class' => 'homepageSectionRow homepageActivityBloc'));

          $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRow header'));
            $sTabHTML.= $oHTML->getBloc('', 'Date', array('class' => 'hpsRowDate'));
            $sTabHTML.= $oHTML->getBloc('', 'Action', array('class' => 'hpsRowData'));
            $sTabHTML.= $oHTML->getBloc('', 'On / Related to', array('class' => 'hpsRowAction'));
          $sTabHTML.= $oHTML->getBlocEnd();

        $nCount = 0;
        foreach($asRecentData as $asData)
        {
          if(empty($asData['text']))
            $asData['text'] = ' - ';

          if(empty($asData['item']))
            $asData['item'] = ' - ';

          $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRow'));

          switch($sType)
          {
            case CONST_AB_TYPE_COMPANY:

              $sTitle = 'Companies';
              $asData['text'] = $oHTML->utf8_strcut($asData['text'], 0, 38);
              $asData['item'] = $oHTML->utf8_strcut($asData['item'], 0, 38);

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
              $sTabHTML.= $oHTML->getNiceTime($asData['log_date'], 0, true, false);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
              $sTabHTML.= $sCompanyIcon . $oHTML->getLink($asData['text'], $asData['log_link']);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowAction'));
              $sTabHTML.= $oHTML->getLink($asData['item'], $asData['log_link']);
              break;

            case CONST_AB_TYPE_CONTACT:

              $sTitle = 'Connections';
              $asData['text'] = $oHTML->utf8_strcut($asData['text'], 0, 38);
              $asData['item'] = $oHTML->utf8_strcut($asData['item'], 0, 38);

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
              $sTabHTML.= $oHTML->getNiceTime($asData['log_date'], 0, true, false);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
              $sTabHTML.= $sContactIcon . $oHTML->getLink($asData['text'], $asData['log_link']);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowAction'));
              $sTabHTML.= $oHTML->getLink($asData['item'], $asData['log_link']);
              break;

            case CONST_EVENT_TYPE_EVENT:

              $sTitle = 'Activities';
              $asData['text'] = $oHTML->utf8_strcut($asData['text'], 0, 38);
              $asData['item'] = $oHTML->utf8_strcut($asData['item'], 0, 38);

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
              $sTabHTML.= $oHTML->getNiceTime($asData['log_date'], 0, true, false);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
              $sTabHTML.= $sEventIcon;

              /*dump(' - - - - - - - -  --- - -- - - - - - -  -');
              dump(mb_internal_encoding());
              dump(mb_strlen('[new]  戸田様     こちらこそ、戸田様には大変お世話になりました。 試飲'));
              dump(strlen('[new]  戸田様     こちらこそ、戸田様には大変お世話になりました。 試飲'));

              //dump(mb_strlen('[new] 明日まで仕事です'));
              //dump(strlen('[new] 明日まで仕事です'));
              dump(' - - ');

              $sCount = ' [mb '.mb_strlen($asData['text']).'
                |sub '.mb_strlen(mb_substr($asData['text'], 0, 35)).'
                |cut '.mb_strlen(mb_strcut($asData['text'], 0, 35)).'
                |trim '.mb_strlen(mb_strimwidth($asData['text'], 0, 35)).']';*/

              $sTabHTML.= $oHTML->getLink( $asData['text'], $asData['log_link']);
              $sTabHTML.= $oHTML->getBlocEnd();

              $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowAction'));
              $sTabHTML.= $oHTML->getLink($asData['item'], $asData['log_link']);

              break;

              case CONST_OPPORTUNITY:

                $sTitle = 'Opportunities';
                $asData['text'] = $oHTML->utf8_strcut($asData['text'], 0, 38);
                $asData['item'] = $oHTML->utf8_strcut($asData['item'], 0, 38);

                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
                $sTabHTML.= $oHTML->getNiceTime($asData['log_date'], 0, true, false);
                $sTabHTML.= $oHTML->getBlocEnd();

                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
                $sTabHTML.= $sOppIcon.' '.$oHTML->getLink($asData['text'], $asData['log_link']);
                $sTabHTML.= $oHTML->getBlocEnd();


                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowAction'));
                $sTabHTML.= $oHTML->getLink($asData['item'], $asData['log_link']);

              break;

              case 'user_activity':

                $sTitle = 'Last events';
                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowDate'));
                $sTabHTML.= $oHTML->getNiceTime($asData['log_date'], 0, true, false);
                $sTabHTML.= $oHTML->getBlocEnd();


                $asData['text'] = $oHTML->utf8_strcut($asData['text'], 0, 38);
                $asData['action'] = $oHTML->utf8_strcut($asData['action'], 0, 38);

                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowData'));
                $sTabHTML.= $oHTML->getLink($asData['action'], $asData['log_link']);
                $sTabHTML.= $oHTML->getBlocEnd();


                $sTabHTML.= $oHTML->getBlocStart('', array('class' => 'hpsRowAction'));
                $sTabHTML.= $oHTML->getLink($asData['item'], $asData['log_link']);
                break;
            }


            $sTabHTML.= $oHTML->getBlocEnd();
          $sTabHTML.= $oHTML->getBlocEnd();
          $nCount++;
        }

        $sTabHTML.= $oHTML->getBlocEnd();
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'floatHack')).$oHTML->getBlocEnd();

        $asTab[] = array('label' => $sType, 'title' => $sTitle.' ('.$nCount.')', 'content' => $sTabHTML);
      }
    }


    if(empty($asTab))
      $sHTML.= $oHTML->getText('no activity for the moment.', array('class' => 'light italic'));
    else
    {
      if(count($asTab) > 1)
        $sHTML.= $oHTML->getTabs('', $asTab);
      else
        $sHTML.= $asTab[0]['content'];
    }

    $sHTML.= $oHTML->getBlocStart('', array('class' => 'floatHack')).$oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }

  /**
  * Get connection recent activity
  * @param integer $pnLoginPk
  * @return array of data
  */
  public function getContactRecentActivity($pnLoginPk, $psDate = '', $pnLimit = 10)
  {
    if(!assert('is_integer($pnLoginPk) && is_integer($pnLimit)'))
      return array();

    if(empty($psDate))
      $psDate = date('Y-m-d', strtotime('-1 month')).' 00:00:00';
    else
    {
      if(!is_date($psDate) || is_datetime($psDate))
        return array();
    }

    $oDB = CDependency::getComponentByName('database');
    //$sQuery = 'SELECT * FROM login_activity WHERE status = 0 AND followerfk != 0 AND notifierfk='.$pnLoginPk.' AND loginfk != '.$pnLoginPk;
    $sQuery = 'SELECT * FROM login_activity WHERE status = 0
      AND (notifierfk='.$pnLoginPk.' OR followerfk = '.$pnLoginPk.')
      AND loginfk != '.$pnLoginPk.'
      AND log_date >= "'.$psDate.'"
      ORDER BY log_date DESC ';

    //admin list is going to grow....
    if($pnLoginPk == 1)
      $sQuery.= ' LIMIT 5 ';
    else
      $sQuery.= ' LIMIT '.$pnLimit;

    $oResult = $oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    $asContactActivities = array();
    while($bRead)
    {
      $asContactActivities[]= $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asContactActivities;
  }

  /**
  * Display the recent activity of user
  * @param integer $pnLoginPk
  * @return array
  */
  private function _getUserRecentActivity($pnLoginPk, $pnLimit = 30, $pnMaxperType = 0)
  {
    if(!assert('is_integer($pnLoginPk) && is_integer($pnLimit)'))
        return array();

    if(empty($pnMaxperType))
      $pnMaxperType = $pnLimit;

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT la.*, CONCAT(la.cp_uid, la.cp_action, la.cp_type, la.cp_pk) as sKey  FROM login_activity as la
    WHERE loginfk='.$pnLoginPk.' AND (followerfk IS NULL OR followerfk IN (0,'.$pnLoginPk.')) AND cp_type IN ("ct", "cp", "event", "opp")
    ORDER BY login_activitypk DESC LIMIT 0, 150 ';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    $asActivites = array();
    while($bRead)
    {
      if(!isset($asActivites[$oResult->getFieldValue(CONST_CP_TYPE)][$oResult->getFieldValue('sKey')]))
        $asActivites[$oResult->getFieldValue(CONST_CP_TYPE)][$oResult->getFieldValue('sKey')] = $oResult->getData();

      $bRead = $oResult->ReadNext();
    }

    $asResultData = array();
    $asResultData['cp'] = $asResultData['ct'] = $asResultData['event'] = $asResultData['opp'] = array();
    $nKey = 0;
    $nCount = 0;
    $nMax = floor($pnLimit/3);

    //limit results number prioritizing cp, ct , then events history
    while($nCount < $pnLimit && $nKey < $pnLimit)
    {
      if(!empty($asActivites['cp']) && count($asResultData['cp']) < min(array($nMax, $pnMaxperType)))
      {
        //get the next first entry of the array
        reset($asActivites['cp']);
        $vKey = key($asActivites['cp']);
        array_push($asResultData['cp'], $asActivites['cp'][$vKey]);

        //remove the element for next loop
        unset($asActivites['cp'][$vKey]);
        $nCount++;
      }

      if(!empty($asActivites['ct']) && count($asResultData['ct']) < min(array($nMax, $pnMaxperType)))
      {
        //get the next first entry of the array
        reset($asActivites['ct']);
        $vKey = key($asActivites['ct']);
        array_push($asResultData['ct'], $asActivites['ct'][$vKey]);

        //remove the element for next loop
        unset($asActivites['ct'][$vKey]);
        $nCount++;
      }

      if(!empty($asActivites['event']) && count($asResultData['event']) < min(array($nMax, $pnMaxperType)))
      {
        //get the next first entry of the array
        reset($asActivites['event']);
        $vKey = key($asActivites['event']);
        array_push($asResultData['event'], $asActivites['event'][$vKey]);

        //remove the element for next loop
        unset($asActivites['event'][$vKey]);
        $nCount++;
      }

      if(!empty($asActivites['opp']) && count($asResultData['opp']) < min(array($nMax, $pnMaxperType)))
      {
        //get the next first entry of the array
        reset($asActivites['opp']);
        $vKey = key($asActivites['opp']);
        array_push($asResultData['opp'], $asActivites['opp'][$vKey]);

        //remove the element for next loop
        unset($asActivites['opp'][$vKey]);
        $nCount++;
      }


      $nKey++;
    }

    return $asResultData;
  }

  /**
  * Get user monthly stats
  * @return array
  */
  private function _getMonthlyUserStat($pnUserPk = 0)
  {
    if(!assert('is_integer($pnUserPk)'))
      return '';

    $oDb = CDependency::getComponentByName('database');
    $anResult = array();

    if(empty($pnUserPk))
    {
      $oLogin = CDependency::getCpLogin();
      $nLoginFk = $oLogin->getUserPk();
    }
    else
      $nLoginFk = $pnUserPk;

    //init a 4 months scale array
    $asData = array();
    $asData[(int)date('m', mktime(0, 0, 0, ((int)date('m')-3), 1, date('Y')))] = 0;
    $asData[(int)date('m', mktime(0, 0, 0, ((int)date('m')-2), 1, date('Y')))] = 0;
    $asData[(int)date('m', mktime(0, 0, 0, ((int)date('m')-1), 1, date('Y')))] = 0;
    $asData[(int)date('m')] = 0;

    $sStartDate = date('Y-m-d', mktime(0, 0, 0, ((int)date('m')-3), 1, date('Y')));

    //--------------------------------------------------------------
    //count connections

    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%c\') as grp_month FROM addressbook_contact
     WHERE created_by = '.$nLoginFk.' AND date_create > "'.$sStartDate.'"
     GROUP BY grp_month ORDER BY grp_month DESC ';

    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_month')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['contact'] = $asTmp;

    //--------------------------------------------------------------
    //count Companies
    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%c\') as grp_month FROM addressbook_company ';
    $sQuery.= ' WHERE creatorfk = '.$nLoginFk.' AND date_create > "'.$sStartDate.'" ';
    $sQuery.= ' GROUP BY grp_month ORDER BY grp_month DESC ';

    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_month')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['company'] = $asTmp;

    //--------------------------------------------------------------
    //count Companies
    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%c\') as grp_month FROM event ';
    $sQuery.= ' WHERE created_by = '.$nLoginFk.' AND date_create > "'.$sStartDate.'"
      AND event.custom_type = 0 ';
    $sQuery.= ' GROUP BY grp_month ORDER BY grp_month DESC ';

    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_month')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['event'] = $asTmp;

    return $anResult;
  }

   /**
    * Get user weekly stats
    * @return array
    */

  private function _getWeeklyUserStat($pnUserPk = 0)
  {
    if(!assert('is_integer($pnUserPk)'))
      return '';

    $oDb = CDependency::getComponentByName('database');
    $anResult = array();

    if(empty($pnUserPk))
    {
      $oLogin = CDependency::getCpLogin();
      $nLoginFk = $oLogin->getUserPk();
    }
    else
      $nLoginFk = $pnUserPk;

    //init a 1 week scale array (for now)
    $asData = array();
    $asData[(int)date('W', strtotime('-3 week'))] = 0;
    $asData[(int)date('W', strtotime('-2 week'))] = 0;
    $asData[(int)date('W', strtotime('last week'))] = 0;
    $asData[(int)date('W')] = 0;

    $sStartDate = date('Y-m-d', mktime(0, 0, 0, ((int)date('m')-1), 1, date('Y')));

    //--------------------------------------------------------------
    //count connections
    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%u\') as grp_week FROM addressbook_contact ';
    $sQuery.= ' WHERE created_by = '.$nLoginFk.' AND date_create > "'.$sStartDate.'" ';
    $sQuery.= ' GROUP BY grp_week ORDER BY grp_week DESC ';
    //echo $sQuery;
    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_week')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['contact'] = $asTmp;

    //--------------------------------------------------------------
    //count Companies
    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%u\') as grp_week FROM addressbook_company ';
    $sQuery.= ' WHERE creatorfk = '.$nLoginFk.' AND date_create > "'.$sStartDate.'" ';
    $sQuery.= ' GROUP BY grp_week ORDER BY grp_week DESC ';
    //echo $sQuery;
    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_week')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['company'] = $asTmp;

    //--------------------------------------------------------------
    //count Companies
    $sQuery = 'SELECT count(*) as nCount, DATE_FORMAT(date_create, \'%u\') as grp_week FROM event ';
    $sQuery.= ' WHERE created_by = '.$nLoginFk.' AND date_create > "'.$sStartDate.'" ';
    $sQuery.= ' GROUP BY grp_week ORDER BY grp_week DESC ';

    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asTmp = $asData;
    while($bRead)
    {
      $asTmp[(int)$oDbResult->getFieldValue('grp_week')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    $anResult['event'] = $asTmp;

    return $anResult;
  }

  /**
   * Function to display the user calendar
   * @param boolean $pbLoadInAjax
   * @param boolean $pbOnlyAjaxContent
   * @return string HTML
  */
  private function _getUserCalendar($pbLoadInAjax = false, $pbOnlyAjaxContent = false)
  {
    $oZimbra = CDependency::getComponentByName('zimbra');
    if(empty($oZimbra))
      return '';

    $oDisplay = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';

    if($pbLoadInAjax)
    {
      $oPage = CDependency::getCpPage();
      $sUniqId = uniqid();
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_PORTAL_CALENDAR);

      $sHTML = $oDisplay->getBlocStart($sUniqId, array( 'class' => 'homepageSection','style' => 'width: 38%; max-width: 450px; float: right;'));

      $sHTML.= $oDisplay->getBlocStart('',array('style'=>'width: 100%; text-align:center;'));
      //$sHTML.= $oDisplay->getPicture('/common/pictures/notice_loading.gif/','Loading','');
      $sHTML.= $oDisplay->getBlocEnd();

      $sHTML.= '<script>$(document).ready(function()
       {
          setTimeout("AjaxRequest(\''.$sUrl.'\', \'\', \'\', \''.$sUniqId.'\');", 500);
        });</script>';
      $sHTML.= $oDisplay->getBlocEnd();

      return $sHTML;
    }


    if(!$pbOnlyAjaxContent)
      $sHTML.= $oDisplay->getBlocStart('', array( 'class' => 'homepageSection', 'style' => 'width: 38%; max-width: 450px; float: right;'));

    $oCal = $oZimbra->getZimbraCalendar();
    $sHTML.= $oCal->getHomepageUserCalendar();

    if(!$pbOnlyAjaxContent)
    $sHTML.= $oDisplay->getBlocEnd();

    return $sHTML;
  }

  /**
    * Display the user stats
    * @param boolean $pbLoadInAjax
    * @param boolean $pbOnlyAjaxContent
    * @return HTML
    */

  private function _getUserStats($pbLoadInAjax = false, $pbOnlyAjaxContent = false, $pnUserPk = 0, $pasParam = array())
  {
    if(!assert('is_integer($pnUserPk) && is_bool($pbLoadInAjax) && is_bool($pbOnlyAjaxContent) && is_array($pasParam) '))
      return '';

    $oDisplay = CDependency::getCpHtml();
    $oChart = CDependency::getComponentByName('charts');
    if(empty($pnUserPk))
    {
      $oLogin = CDependency::getCpLogin();
      $nUserPk = $oLogin->getUserPk();
    }
    else
      $nUserPk = $pnUserPk;


    if(isset($pasParam['containerWidth']) && !empty($pasParam['containerWidth']))
      $nContainerWidth = $pasParam['containerWidth'];
    else
      $nContainerWidth = '38%';

    if(isset($pasParam['chartWidth']) && !empty($pasParam['chartWidth']))
      $nChartWidth = $pasParam['chartWidth'];
    else
      $nChartWidth = '430px';

    if(isset($pasParam['chartHeight']) && !empty($pasParam['chartHeight']))
      $nChartHeight = $pasParam['chartHeight'];
    else
      $nChartHeight = '200px';

    $sHTML = '';

    if($pbLoadInAjax)
    {
      //load in the homepage the chart js
      $oChart->includeChartsJs();

      $oPage = CDependency::getCpPage();
      $sUniqId = uniqid();
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_PORTAL_STAT, $nUserPk);
      $sUrl.= '&containerWidth='.urlencode($nContainerWidth).'&chartWidth='.urlencode($nChartWidth).'&chartHeight='.urlencode($nChartHeight);

      $sHTML.= $oDisplay->getCR();
      $sHTML.= $oDisplay->getBlocStart($sUniqId, array( 'class' => 'homepageSection', 'style' => 'width: '.$nContainerWidth.'; max-width: 450px; float: right;'));

        $sHTML.= '<div style="width: 100%; text-align: center;"><img src="/common/pictures/notice_loading.gif" /></div>';
        $sHTML.= '<script>';

        if(isset($pasParam['loadingTrigger']) && !empty($pasParam['loadingTrigger']))
          $sHTML.= $pasParam['loadingTrigger'];
        else
          $sHTML.= '$(document).ready';

        $sHTML.= '(function()
          {
            setTimeout("AjaxRequest(\''.$sUrl.'\', \'\', \'\', \''.$sUniqId.'\');", 1000);
          });</script>';

      $sHTML.= $oDisplay->getBlocEnd();
    }
    else
    {
      if(!$pbOnlyAjaxContent)
      {
        $sHTML.= $oDisplay->getCR();
        $sHTML.= $oDisplay->getBlocStart('', array('class' => 'homepageSection', 'style' => ''));
      }

      $asStats = $this->_getMonthlyUserStat($nUserPk);
      $asWeekStat = $this->_getWeeklyUserStat($nUserPk);
      $nWeek = (int)date('W');
      $nLastWeek = (int)date('W', strtotime('last week'));

      $sHTML.= $oDisplay->getBlocStart('', array('style' => 'width: 98%; margin: 0 auto;'));

        $sHTML.= $oDisplay->getListStart('', array('class' => 'homeStatList'));
          $sHTML.= $oDisplay->getListItemStart('', array('class' => 'homeStatListTitle')) . 'This week:'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['company'][$nWeek].' companies'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['contact'][$nWeek].' connections'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['event'][$nWeek].' activities'. $oDisplay->getListItemEnd();
        $sHTML.= $oDisplay->getListEnd();

        $sHTML.= $oDisplay->getListStart('', array('class' => 'homeStatList', 'style' => ' border-bottom: 1px solid #ddd; padding-bottom: 3px;'));
          $sHTML.= $oDisplay->getListItemStart('', array('class' => 'homeStatListTitle')) . 'Last week:'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['company'][$nLastWeek].' companies'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['contact'][$nLastWeek].' connections'. $oDisplay->getListItemEnd();
          $sHTML.= $oDisplay->getListItemStart() . $asWeekStat['event'][$nLastWeek].' activities'. $oDisplay->getListItemEnd();
        $sHTML.= $oDisplay->getListEnd();

        $sHTML.= $oDisplay->getFloatHack();

        $asAxis = array();
        $nYear = (int)date('Y');
        foreach($asStats['contact'] as $sMonth => $vUseless)
        {
          $asAxis[] = date('M', mktime(0, 0, 0, (int)$sMonth, 1, $nYear));
        }

        $oChart->createChart('column', '', 'Added to bcm');
        $oChart->setChartLegendPosition('horizontal', 0, -5);
        $oChart->setChartAxis($asAxis);
        $oChart->setChartSize($nChartWidth, $nChartHeight);
        $oChart->setChartData('companies', $asStats['company'], '#F4B275');
        $oChart->setChartData('connections', $asStats['contact'], '#4572A7');
        $oChart->setChartData('activities', $asStats['event'], '#89A54E');

        //if in ajax or get the content from ajax, I have to not include highcharts.js again
        $sHTML.= $oChart->getChartDisplay(($pbLoadInAjax||$pbOnlyAjaxContent));

      $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $oDisplay->getFloatHack();
      $sHTML.= $oDisplay->getCR();

      if(!$pbOnlyAjaxContent)
        $sHTML.= $oDisplay->getBlocEnd();
    }

    return $sHTML;
  }

  private function _getOpportunityStats($pbLoadInAjax = false, $pbOnlyAjaxContent = false, $pnPk = 0)
  {
    $oDisplay = CDependency::getCpHtml();
    $oChart = CDependency::getComponentByName('charts');
    $sHTML = '';

    if($pbLoadInAjax)
    {
      //load in the homepage the chart js
      $oChart->includeChartsJs();

      $oPage = CDependency::getCpPage();
      $sUniqId = uniqid();
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_PORTAL_OPP_STAT, $pnPk);

      $sHTML.= $oDisplay->getCR();
      $sHTML.= $oDisplay->getBlocStart($sUniqId, array('style' => 'width:370px;'));

        $sHTML.= '<div style="width: 100%; text-align: center;"><img src="/common/pictures/notice_loading.gif" /></div>';
        $sHTML.= '<script>$(document).ready(function()
          {
            setTimeout("AjaxRequest(\''.$sUrl.'\', \'\', \'\', \''.$sUniqId.'\')", 1500);
          });</script>';

      $sHTML.= $oDisplay->getBlocEnd();
    }
    else
    {
      $oOpportunity = CDependency::getComponentByName('opportunity');

      $nMonth = date('m');
      $nYear = date('Y');
      $sStart = date('Y-m-d', mktime(0, 0, 0, ($nMonth-1), 1, $nYear));
      $sEnd = date('Y-m-d', mktime(0, 0, 0, ($nMonth+2), 1, $nYear));

      $asStats = $oOpportunity->getMonthlyStat($pnPk, $sStart, $sEnd);
      $asColor = $oOpportunity->getStatusColor();
      $asAxis = $asStats['asAxis'];

      $oChart->createChart('column', '', 'Sales opportunities');
      $oChart->setChartLegendPosition('horizontal', 0, -5);
      $oChart->setChartAxis($asAxis);
      $oChart->setChartSize('430px','200px');
      $oChart->setChartData('On going', $asStats['ongoing'], $asColor['ongoing']);
      $oChart->setChartData('Failed', $asStats['failed'], $asColor['failed']);
      $oChart->setChartData('Stalled', $asStats['stalled'], $asColor['stalled']);
      $oChart->setChartData('Signed', $asStats['signed'], $asColor['signed']);
      $oChart->setChartData('Projected', $asStats['projected'], $asColor['projected']);

      //if in ajax or get the content from ajax, I have to not include highcharts.js again
      $sHTML.= $oChart->getChartDisplay(($pbLoadInAjax||$pbOnlyAjaxContent));
    }

    return $sHTML;
  }

  private function _getOpportunityStatsByUsers($pbLoadInAjax = false, $pbOnlyAjaxContent = false)
  {
    $oDisplay = CDependency::getCpHtml();
    $oChart = CDependency::getComponentByName('charts');
    $sHTML = '';

    if($pbLoadInAjax)
    {
      //load in the homepage the chart js
      $oChart->includeChartsJs();

      $oPage = CDependency::getCpPage();
      $sUniqId = uniqid();
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_PORTAL_OPP_USER_STAT, 0);

      $sHTML.= $oDisplay->getCR();
      $sHTML.= $oDisplay->getBlocStart($sUniqId, array('style' => 'width:370px;'));

        $sHTML.= '<div style="width: 100%; text-align: center;"><img src="/common/pictures/notice_loading.gif" /></div>';
        $sHTML.= '<script>$(document).ready(function()
          {
            setTimeOut("AjaxRequest(\''.$sUrl.'\', \'\', \'\', \''.$sUniqId.'\');", 2000);
          });</script>';

      $sHTML.= $oDisplay->getBlocEnd();
    }
    else
    {
      $oOpportunity = CDependency::getComponentByName('opportunity');

      //show stats for the nex month
      //$nSwitchingDay = (int)date('d', strtotime('last friday of '.date('F Y'))) - 7;
      $nSwitchingDay = 31;     // cod ebelow useless then but some people change their minds endlessly

      $nMonth = (int)date('m');
      if(date('d') >= $nSwitchingDay)
      {
        $nTime = mktime(0, 0, 0, $nMonth+2, 1, date('Y'));
        $sMonth = date('M', $nTime);
        $sDatemin =  date('Y-m-d', $nTime);
        $sDatemax = date('Y-m-d', mktime(0, 0, 0, $nMonth+3, 1, date('Y')));
      }
      else
      {
        $nTime = mktime(0, 0, 0, $nMonth+1, 1, date('Y'));
        $sMonth = date('M', $nTime);
        $sDatemin =  date('Y-m-d', $nTime);
        $sDatemax = date('Y-m-d', mktime(0, 0, 0, $nMonth+2, 1, date('Y')));
      }

      $asStats = $oOpportunity->getMonthlyUsersStat($sDatemin, $sDatemax);
      $asColor = $oOpportunity->getStatusColor();

      if(isset($asStats['ongoing']))
      {
        $oChart->createChart('column', '', 'Individual sales ( '.$sMonth.' )');
        $oChart->setChartLegendPosition('horizontal', 0, -5);
        $oChart->setChartAxis($asStats['asAxis']);
        $oChart->setChartSize('430px','200px');
        $oChart->setChartData('On going', $asStats['ongoing'], $asColor['ongoing']);
        //$oChart->setChartData('Failed', $asStats['failed'], $asColor['failed']);
        $oChart->setChartData('Signed', $asStats['signed'], $asColor['signed']);
        $oChart->setChartData('Projected', $asStats['projected'], $asColor['projected']);

        //if in ajax or get the content from ajax, I have to not include highcharts.js again
        $sHTML.= $oDisplay->getCR();
        $sHTML.= $oChart->getChartDisplay(($pbLoadInAjax||$pbOnlyAjaxContent));
      }
    }

    return $sHTML;
  }

  public function declareUserPreferences()
  {
    $aSettings[] = array(
        'fieldname' => 'urlparam',
        'fieldtype' => 'text',
        'label' => 'Default page',
        'description' => 'Default page url after login',
        'value' => ''
    );
    return $aSettings;
  }

  public function declareSettings()
  {
    $aSettings[] = array(
        'fieldname' => 'urlparam',
        'fieldtype' => 'text',
        'label' => 'Default page',
        'description' => 'Default page url after login',
        'value' => ''
    );
    return $aSettings;
  }
}