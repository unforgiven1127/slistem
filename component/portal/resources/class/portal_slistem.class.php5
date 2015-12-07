<?php

require_once('component/portal/portal.class.ex.php5');

class CPortalSlistemEx extends CPortalEx
{
  public function declareSettings()
  {
    return array();
  }

  public function getAjax()
  {
    $oPage = CDependency::getCpPage();
    return json_encode($oPage->getAjaxExtraContent(array('data' => $this->getHomePage(true))));
  }

  public function declareUserPreferences()
  {
    $asPrefs = array();

    $asPrefs[] = array(
        'fieldname' => 'home_taregt_tbl',
        'fieldtype' => 'select',
        'options' => array('1' => 'Before charts', '2' => 'After charts', '0' => 'Not displayed'),
        'label' => 'Display homepage target table',
        'description' => 'Display homepage target table',
        'value' => '0'
    );

    return $asPrefs;
  }

  public function getHomePage($pbInAjax = false)
  {
    $oLogin = CDependency::getCpLogin();
    $oDisplay = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sHTML = '';

    $nUserPk = $oLogin->getUserPk();
    if(!is_key($nUserPk))
    {
      @header('location: /index.php5?uid=579-704&ppa=ppalgt&ppt=&ppk=0&logout=1');
      $sHTML.= '<script>document.location.href = "/index.php5?uid=579-704&ppa=ppalgt&ppt=&ppk=0&logout=1&pg="; </script>';

    }


    $sHTML.= $oDisplay->getBlocStart('', array('class' => 'portalContainer'));


    $sHTML.= $oDisplay->getBlocStart('', array('style' => 'min-height: 150px;'));


      //-------------------------------------------------------------------------------------------
      //-------------------------------------------------------------------------------------------
      //if the user just connected, we set "long" animations to give time to do extra actions.
      $sMainClass = '';
      if(!isset($_SESSION['first_page']))
      {
        $sMainClass = 'hidden';
        $_SESSION['first_page'] = true;

        $sHTML.= $oDisplay->getBlocStart('firstLoadingContainer', array('style' => 'width: 600px; padding: 20px; margin: 0 auto; '));

          $sHTML.= $oDisplay->getBlocStart('', array('style' => 'width: 128px; margin: 0 auto;'));
          $sHTML.= $oDisplay->getPicture('/component/portal/resources/pictures/slistem/home_loading.gif');
          $sHTML.= $oDisplay->getBlocEnd();



          $sHTML.= $oDisplay->getBlocStart('loadingTextContainer', array('style' => 'min-height: 175px;  position: relative;'));

            $sHTML.= $oDisplay->getText('Sl[i]stem is loading your data: <br />');
            $sHTML.= $oDisplay->getText('<br /> - loading environment ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - preferences ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - recent activity ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - reminders ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - history ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - statisics ', array('class' => 'hidden loadingText'));
            $sHTML.= $oDisplay->getText('<br /> - folders', array('class' => 'hidden loadingText'));
            //$sHTML.= $oDisplay->getText($oDisplay->getPicture('/component/portal/resources/pictures/slistem/dots.gif'));

            $sHTML.= $oDisplay->getBlocStart('loadingBar', array('style' => 'position: absolute; bottom: 0; height: 5px; width: 0px; margin: 0 auto; border-top: 3px solid #BC1E1C;'));
            $sHTML.= '&nbsp;';
            $sHTML.= $oDisplay->getBlocEnd();

          $sHTML.= $oDisplay->getBlocEnd();

        $sHTML.= $oDisplay->getBlocEnd();

        $sHTML.= '<script>$(document).ready(function()
          {
            var nDelay = 450;
            var nItems = $("#loadingTextContainer .loadingText").length;
            var nWidth = (100/nItems);

            $("#loadingTextContainer .loadingText").each(function(nIndex)
            {
              nTimer = ((nIndex+1)*nDelay);
              $(this).delay(nTimer).show("slide", { direction: "right" }, 250, function()
              {
                $("#loadingBar").css("width", ((nIndex+1)*nWidth)+"%");
              });

              if(nIndex+1 == nItems)
              {
                nTimer+= nDelay;
                $("#firstLoadingContainer").delay(nTimer).fadeOut(function(){ $("#firstLoadingContainer").remove(); $("#homePageMainContainer").fadeIn(); });
              }
            });
          });</script>';
      }


      //-------------------------------------------------------------------------------------------
      //-------------------------------------------------------------------------------------------
      // Display view / search / folder  user history
      $sHTML.= $oDisplay->getBlocStart('homePageMainContainer', array('class' => $sMainClass));

      $oPage->addJsFile($this->getResourcePath().'/js/slistem.js');
      $sHTML.= $oDisplay->getBlocStart('', array('class' => 'home_history_container', 'style' => 'width: 100%; margin-bottom: 25px;  padding-bottom: 20px;'));

        //----------------------------------------------------------------------------
        //----------------------------------------------------------------------------
        //Reminders

        $oNotification = CDependency::getComponentByName('notification');
        $nDayOfTheWeek = (int)date('N');

        if($nDayOfTheWeek >= 6)
          $sDateEnd = date('Y-m-d', strtotime('next wednesday'));
        else
          $sDateEnd = date('Y-m-d', strtotime('next sunday'));

        $asFilter = array('loginfk' => $oLogin->getUserPk());

        $oDbResult = $oNotification->getUserNotification(0, date('Y-m-d'), $sDateEnd, false, false, 10, $asFilter);
        $nReminder = $oDbResult->numRows();
        if($nReminder > 0 && $nReminder < 3)
        {
          $oDbResult = $oNotification->getUserNotification(0, date('Y-m-d'), date('Y-m-d', strtotime('+4 weeks')), false, false, 10, $asFilter);
        }

        //Test
        //$oDbResult = $oNotification->getUserNotification(0, date('Y-m-d', strtotime('-4 week')), date('Y-m-d', strtotime('+4 week')), false, false);
        $bRead = $oDbResult->readFirst();

        if(!$bRead)
          $nGraphDisplayed = 2;
        else
        {
          $nGraphDisplayed = 1;
          $asReminder = array();
          $nCount = 0;
          while($bRead)
          {
            $asData = $oDbResult->getData();
            $sURL = $oPage->getAjaxUrl('notification', CONST_ACTION_LIST, CONST_NOTIFY_TYPE_NOTIFICATION, (int)$asData['notificationpk'], array('filter_date' => substr($asData['date_notification'], 0, 10)));

            $sText = $asData['title'];
            if(!empty($sText))
              $sText = ' - '.strip_tags($asData['message']);
            else
              $sText = strip_tags($asData['message']);

            /*$sText = $oDisplay->getLink(mb_strimwidth($sText, 0, 65, '...'), 'javascript:;', array('onclick' => '
              var oConf = goPopup.getConfig();
              oConf.width = 1080;
              oConf.height = 725;
              goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ')); */
            $sJavascript = 'var oConf = goPopup.getConfig();
              oConf.width = 1080;
              oConf.height = 725;
              goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';

            if( (int)$asData['naggy'] == 0)
              $asReminder[] = array('log_link' => 'javascript:;" onclick="'.$sJavascript.';', 'log_date' => $asData['date_notification'], 'text' => $sText);
            else
              $asReminder[] = array('log_link' => 'javascript:;" onclick="'.$sJavascript.';', 'log_date' => $asData['date_notification'], 'text' => $sText, 'class' => 'home_naggy');

            $bRead = $oDbResult->readNext();

            $nCount++;
            if($nCount == 12)
              break;
          }

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'home_item_container home_reminder', 'style' => 'float: left; margin-left: 20px;'));
          $sHTML.= $this->_displayActivity($asReminder, 'Reminders');
          $sHTML.= $oDisplay->getBlocEnd();
        }


        //----------------------------------------------------------------------------
        //----------------------------------------------------------------------------
        //History
        $sHTML.= $oDisplay->getBlocStart('', array('class' => 'home_item_container slider_mode', 'style'=> 'float: right; margin-right: 20px;'));
          $sHTML.= $oDisplay->getBloc('', '', array('class' => 'slider_mode_top', 'onclick' => 'scrollHome(this, \'top\');'));

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'slider_mode_inner', 'current-position' => 0));

            $asActivity = $oLogin->getUserActivity($oLogin->getUserPk(), '555-001', CONST_ACTION_VIEW, null, null, 15);
            $sHTML.= $this->_displayActivity($asActivity, 'Candidates');

            $asActivity = $oLogin->getUserActivity($oLogin->getUserPk(), '555-001',
              array(CONST_ACTION_LIST, CONST_ACTION_SEARCH), null, null, 15, 'searches');
            $sHTML.= $this->_displayActivity($asActivity, 'Searches');

            $asActivity = $oLogin->getUserActivity($oLogin->getUserPk(), '555-002', null, null, null, 15);
            $sHTML.= $this->_displayActivity($asActivity, 'Folder');

          $sHTML.= $oDisplay->getBlocEnd();

          $sHTML.= $oDisplay->getBloc('', '', array('class' => 'slider_mode_bottom', 'onclick' => 'scrollHome(this, \'bottom\');'));
        $sHTML.= $oDisplay->getBlocEnd();

        //----------------------------------------------------------------------------
        //----------------------------------------------------------------------------
        //user statistics

        $sHTML.= $oDisplay->getBlocStart('', array('class' => 'home_item_container slider_mode', 'style' => 'float: left; margin-left: 20px; position: relative;'));
          $sHTML.= $oDisplay->getBloc('', '', array('class' => 'slider_mode_top', 'onclick' => 'scrollHome(this, \'top\', '.$nGraphDisplayed.');'));

          $sHTML.= $oDisplay->getBlocStart('', array('class' => 'slider_mode_inner small_slider', 'current-position' => 0, 'style' => 'height: '.($nGraphDisplayed *275).'px; '));

          $sHTML.= $this->_getUSerStatistics($oLogin->getUserPk());

          $sHTML.= $oDisplay->getBlocEnd();
          $sHTML.= $oDisplay->getBloc('', '', array('class' => 'slider_mode_bottom', 'onclick' => 'scrollHome(this, \'bottom\', '.$nGraphDisplayed.');'));

        $sHTML.= '<span class="italic" style="position: absolute; bottom: 2px; right: 3px; color:#999; font-size: 10.5px;">* refreshed every 10 minutes</span>';
        $sHTML.= $oDisplay->getBlocEnd();

      $sHTML.= $oDisplay->getFloatHack();
      $sHTML.= $oDisplay->getBlocEnd();




    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();
    $sHTML.= $oDisplay->getBlocEnd();


    //end of homepageMainContainer
    $sHTML.= $oDisplay->getBlocEnd();


  /*
    $nResult = $oNotif->addReminder(260, 'message 2d / 3 nag', '2013-10-13 08:00:00', 3, '2d');
    $nResult = $oNotif->addReminder(135, 'message 1d / 3 nag', '2013-10-14 08:00:00', 3, '1d');

    $nResult = $oNotif->addReminder(135, 'message 1w / 3 nag', '2013-10-02 08:00:00', 3, '1w');
    $nResult = $oNotif->addReminder(135, 'message 2w / 3 nag', '2013-09-15 08:00:00', 3, '2w');

    $nResult = $oNotif->addReminder(135, 'message 1m / 3 nag', '2013-09-02 08:00:00', 3, '1m');
    $nResult = $oNotif->addReminder(135, 'message 2m / 3 nag', '2013-08-10 08:00:00', 3, '2m');

    $nResult = $oNotif->addReminder(135, 'message 0.5d / 2 nag', '2013-10-14 08:00:00', 2, '0.5d');
    $nResult = $oNotif->addReminder(135, 'message 0.5d / 2 nag', '2013-10-14 14:00:00', 2, '0.5d');
    $nResult = $oNotif->addReminder(135, 'message 0.5d / 2 nag', '2013-10-14 17:00:00', 2, '0.5d');
    $nResult = $oNotif->addReminder(135, 'message 0.5d / 2 nag', '2013-10-14 09:00:00', 2, '0.5d');

    $nResult = $oNotif->addReminder(298, 'message instant, no nag', '2013-10-14 08:00:00', 0);
    $nResult = $oNotif->addReminder(260, 'message futur, no nag', '2014-10-14 08:00:00', 0);
    $nResult = $oNotif->addReminder(array(135, 298), 'multi recipient / 0.5d / 3 nag', date('Y-m-d H:i:s', strtotime('+ 1 day')), 3, '0.5d');
*/

 /*
    $nResult = $oNotif->addReminder(135, 'Hi Stephane,
We have a dodgy Buffalo wifi router. I say dodgy as I only got wifi to work once. That said, the hardwired side worked fine. Yours if you want it.

Alternately, we could stick an extra NIC card on one of the PCs in the basement and you can use it as an SME router (technically, you could just stick an SME, pfsense or M0n0wall VM on your machine if you have two NICs and that should also work - we have enough spare switches if you need one to network in your NAS).
Happy to discuss more tomorrow or dissect your brick if you like (no promises though).', '2013-10-14 08:00:00', 2, '0.5d');

    $asItem = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => 'candi', CONST_CP_PK => rand(32000, 351000));
    $nResult = $oNotif->additemReminder(135, $asItem, 'Hi Stephane,
Low priority but did Sakura really share this document with me four times despite me never even having heard of the company it is about?

Thanks,

Reminder linked to item', '2013-10-05 08:00:00');

    $nResult = $oNotif->additemReminder(101, $asItem, 'Hi Stephane,
Low priority but did Sakura really share this document with me four times despite me never even having heard of the company it is about?

Thanks,

Reminder linked to item', '2013-10-05 08:00:00');
*/

/*
   $oNotif = CDependency::getComponentByName('notification');

    $asCp = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => 'candi', CONST_CP_PK => rand(32000, 351000));
    $sId = $oNotif->initNotifier($asCp);
    dump($sId);
    $nResult = $oNotif->addMessage($sId, 360, 'Simple message / not a reminder ', 'Look at that');
    dump($nResult);

    $asItem = array(CONST_CP_UID => '555-001',
        CONST_CP_ACTION => CONST_ACTION_VIEW,
        CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI,
        CONST_CP_PK => rand(32000, 351000));
    $nResult = $oNotif->addItemMessage($sId, 367, $asItem, 'Please do something...', 'DBA request');
    dump($nResult);

    $nResult = $oNotif->addMessage($sId, 360, 'Simple message / not a reminder ', 'Look at that', 2, '1w');
    dump($nResult);
 */

    //$sHTML.= getFontTester();

    //for everyHtml page we create (non ajax), we need to add the tab li, init tabs,
    if($pbInAjax)
      return $sHTML;
    else
    {
      $sHTML.= '<script>setTimeout("$(\'#tab_list li.selected\').addClass(\'tab_type_home\');", 1500)</script>';
      return $sHTML;
    }
  }


  private function _displayActivity($pasActivity, $psTitle = '')
  {
    $oDisplay = CDependency::getCpHtml();

    if(!assert('is_array($pasActivity)') || empty($pasActivity))
    {
      $sHTML = $oDisplay->getBlocStart('', array('class' => 'history_bloc'));

      if(!empty($psTitle))
        $sHTML.= $oDisplay->getBloc('', $psTitle, array('class' => 'home_item_title'));

      $sHTML.= '<div style="background-color:#f6f6f6; padding: 10px; min-height: 450px; font-size: 15px; color: #666;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.2) 25%, rgba(0, 0, 0, 0) 25%, rgba(0, 0, 0, 0) 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, rgba(0, 0, 0, 0) 75%, rgba(0, 0, 0, 0)) repeat scroll 0 0 / 25px 25px #f4f4f4;">
        <br /><br /><br /><br />Nothing to display for now...
        <br /><br />
        Soon, you will find here all your recent activity.
        </div>';
      $sHTML.= $oDisplay->getBlocEnd();

      return $sHTML;
    }

    $asSortedActivity = array();
    $nNow = time();
    $sNow = date('Y-m-d H:i:s');
    $sAbit = date('Y-m-d H:i:s', strtotime('-10 minutes'));
    $sToday = date('Y-m-d').' 00:00:00';
    //$sTonight = date('Y-m-d').' 23:59:59';
    $sYesterday = date('Y-m-d', strtotime('-1 day')).' 00:00:00';
    $sTomorrow = date('Y-m-d', strtotime('+1 day')).' 00:00:00';

    foreach($pasActivity as $asActivity)
    {

      if($asActivity['text'] = strip_tags($asActivity['text']))
        $asActivity['text'] = mb_strimwidth($asActivity['text'], 0, 60, '...');

      //dump($asActivity);
      if(!empty($asActivity['log_link']))
      {
        if(isset($asActivity['data']['qb']))
        {
          $asActivity['log_link'] = preg_replace('/\&/', '&replay_search='.$asActivity['login_activitypk'].'&', $asActivity['log_link'], 1);
        }

        if(isset($asActivity['class']))
          $Activity = '<a href="'.$asActivity['log_link'].'" class="'.$asActivity['class'].'">'.$asActivity['text'].'</a><br />';
        else
          $Activity = '<a href="'.$asActivity['log_link'].'">'.$asActivity['text'].'</a><br />';
      }
      else
      {
        $Activity = $asActivity['text'];
      }


      if($asActivity['log_date'] > $sNow)
      {
        //future notifications

        if($asActivity['log_date'] > $sTomorrow)
        {
          $asSortedActivity['Incoming'][] = '<span>'.date('Y-m-d', strtotime($asActivity['log_date'])).'</span> '.$Activity;
        }
        elseif($asActivity['log_date'] > $sToday)
        {
          $asSortedActivity['Tomorrow'][] = '<span>'.date('h:i a', strtotime($asActivity['log_date'])).'</span> '.$Activity;
        }
        else
          $asSortedActivity['Today'][] = '<span>'.date('h:i a', strtotime($asActivity['log_date'])).'</span> '.$Activity;
      }
      else
      {
        //Past messages
        if($asActivity['log_date'] > $sAbit)
        {
          $nTime = ($nNow - strtotime($asActivity['log_date'])) / 60;
          $asSortedActivity['A few minutes ago'][] = '<span>'.floor($nTime).'min</span> '.$Activity;
        }
        elseif($asActivity['log_date'] > $sToday)
        {
          $asSortedActivity['Today'][] = '<span>'.date('h:i a', strtotime($asActivity['log_date'])).'</span> '.$Activity;
        }
        elseif($asActivity['log_date'] > $sYesterday)
        {
          $asSortedActivity['Yesterday'][] = '<span>'.date('h:i a', strtotime($asActivity['log_date'])).'</span> '.$Activity;
        }
        else
          $asSortedActivity['A while ago'][] = '<span>'.date('Y-m-d', strtotime($asActivity['log_date'])).'</span> '.$Activity;
      }
    }

    $sHTML = $oDisplay->getBlocStart('', array('class' => 'history_bloc'));

    if(!empty($psTitle))
      $sHTML.= $oDisplay->getBloc('', $psTitle, array('class' => 'home_item_title'));

    foreach($asSortedActivity as $sTitle => $asActivity)
    {
      $sHTML.= '<span class="home_item_section">'.$sTitle.'</span>
        <div class="home_item_row">'.implode('</div><div class="home_item_row">', $asActivity).'</div>';
    }

    $sHTML.= $oDisplay->getBlocEnd();

    return $sHTML;
  }



  private function _getUSerStatistics($pnUserPk, $pbAllowCache = true)
  {
    if(!assert('is_key($pnUserPk)'))
      return '';

    $oChart = CDependency::getComponentByName('charts');
    $oChart->includeChartsJs(true);

    $oPage = CDependency::getCpPage();
    $oPage->addjsFile('/component/sl_stat/resources/js/highchart_extend.js');

    if(getValue('force_refresh'))
      $pbAllowCache = false;



    //charts refreshed every few minutes
    if($pbAllowCache && isset($_SESSION['HOME_PAGE_CHARTS']) && $_SESSION['HOME_PAGE_CHARTS_DATE'] > strtotime('-8 minutes'))
    {
      return $_SESSION['HOME_PAGE_CHARTS'];
    }


    $asSettings = CDependency::getComponentByName('settings')->getSettings(array('home_taregt_tbl'), false);
    $asSettings['home_taregt_tbl'] = (int)$asSettings['home_taregt_tbl'];
    $sHTML = $sTable = '';

    $objectives = @file_get_contents(CONST_PATH_ROOT.CONST_PATH_UPLOAD_DIR.'/sl_stat/charts/'.$pnUserPk.'_objectives.html');
    $sMet = @file_get_contents(CONST_PATH_ROOT.CONST_PATH_UPLOAD_DIR.'/sl_stat/charts/'.$pnUserPk.'_met.html');
    $sPlay = @file_get_contents(CONST_PATH_ROOT.CONST_PATH_UPLOAD_DIR.'/sl_stat/charts/'.$pnUserPk.'_play.html');
    $sCandidate = '';
    $sPosition = @file_get_contents(CONST_PATH_ROOT.CONST_PATH_UPLOAD_DIR.'/sl_stat/charts/'.$pnUserPk.'_position.html');
    $sPipeline = @file_get_contents(CONST_PATH_ROOT.CONST_PATH_UPLOAD_DIR.'/sl_stat/charts/'.$pnUserPk.'_pipeline.html');

    if(!empty($objectives) && $asSettings['home_taregt_tbl'] > 0)
      $sTable.= '
      <div class="graph_bloc">
        <div class="home_item_title">Objectives</div>
        '.$objectives.'
      </div>';

    if(!empty($sPipeline))
      $sHTML.= '
      <div class="graph_bloc">
        <div class="home_item_title">Pipeline repartition</div>
        '.$sPipeline.'
      </div>';

    if(!empty($sMet))
      $sHTML.= '
      <div class="graph_bloc">
        <div class="home_item_title">Meetings</div>
        '.$sMet.'
      </div>';


    if(!empty($sPlay))
      $sHTML.= '
      <div class="graph_bloc">
        <div class="home_item_title">New candidates in play</div>
        '.$sPlay.'
      </div>';

    if(!empty($sPosition))
      $sHTML.= '
      <div class="graph_bloc">
        <div class="home_item_title">New positions in play</div>
        '.$sPosition.'
      </div>
      ';

    //Display target table before or after the charts
    if($asSettings['home_taregt_tbl'] == 2)
      $sHTML.= $sTable;
    else
      $sHTML = $sTable.$sHTML;

    $_SESSION['HOME_PAGE_CHARTS'] = $sHTML;
    $_SESSION['HOME_PAGE_CHARTS_DATE'] = time();

    return $sHTML;
  }

  private function _getClassFromValue($pnValue, $pnTarget)
  {
    if($pnValue >= (0.85 * $pnTarget))
      return 'obj-good';

    if($pnValue < (0.70 * $pnTarget))
      return 'obj-bad';

    return 'obj-average';
  }
}