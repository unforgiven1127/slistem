<?php

  define('CONST_WEBSITE_LOGO_URL', 'https://'.$_SERVER['SERVER_NAME']);
  define('CONST_WEBSITE_GOOGLE_ANALYTICS', '');

  define('CONST_DISPLAY_SEARCH_MENU', true);
  define('CONST_DISPLAY_HAS_LOGGEDIN_CSS', true);
  define('CONST_DISPLAY_HOMEPAGE_ICON', 'home_48.png');
  define('CONST_PAGER_NUM', '50');
  $gasMainMenu = array();

  //specific component constants
  define('CONST_SUB_HEADER_BAR', 'true');
  define('CONST_PROFILE_HEADER', 'true');
  define('CONST_LEFT_MENU', 'true');
  define('CONST_HOME_ICON_HEADER', 'true');
  define('CONST_CONTACT_ICON_HEADER', 'true');
  define('CONST_EMAIL_ICON_HEADER', 'true');
  define('CONST_HEADER_LOGO','/common/pictures/slistem_logo.gif');
  define('CONST_HEADER_FAVICON', '/common/pictures/slistem_favicon.ico');
  define('CONST_LOGIN_MESSAGE','Welcome to Slistem 3, please login to access the application. ');
  define('CONST_TYPE_HOMEPAGE','home');

  define('CONST_WEBSITE_LOADING_PICTURE','/common/pictures/loading_slistem.gif');
  define('CONST_SYTEM_LOG_ACTIVE', true);

  //mail
  define('CONST_MAIL_HEADER_PICTURE', 'https://'.$_SERVER['SERVER_NAME'].'/common/pictures/slistem_logo.gif');


  define('CONST_LOGIN_USERLINK_CALLBACK', 'addTipCallback');
  function addTipCallback($pasParam)
  {
    //$pasParam['onmouseover'] = 'stp(this);';
    $pasParam['onclick'] = ' stp(this); ';

    /* if needed, but make html heavier
    if($pasParam['active'])
    {
      //$pasParam['onclick'] = 'sMsg(this);';

      $sURL = CDependency::getCpPage()->getAjaxUrl('notification', CONST_ACTION_ADD, CONST_NOTIFY_TYPE_MESSAGE, 0, array('loginfk' => $pasParam['loginfk']));
      $pasParam['onclick'] = 'var oConf = goPopup.getConfig();
        oConf.height = 500;
        oConf.width = 850;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
    }*/

    return $pasParam;
  }




  //=======================================================
  //plateform specific components constants

  define('CONST_CANDIDATE_TYPE_CANDI', 'candi');
  define('CONST_CANDIDATE_TYPE_COMP', 'comp');
  define('CONST_CANDIDATE_TYPE_POS', 'pos');

  define('CONST_CANDIDATE_TYPE_CONTACT', 'cont');
  define('CONST_CANDIDATE_TYPE_DOC', 'doc');
  define('CONST_CANDIDATE_TYPE_FEED', 'cprss');
  define('CONST_CANDIDATE_TYPE_LOGS', 'logs');
  define('CONST_CANDIDATE_TYPE_MEETING', 'meet');
  define('CONST_CANDIDATE_TYPE_USER', 'usr');
  define('CONST_CANDIDATE_TYPE_RM', 'rm');

  define('CONST_CANDIDATE_TYPE_INDUSTRY', 'indus');
  define('CONST_CANDIDATE_TYPE_OCCUPATION', 'occu');

  define('CONST_POSITION_TYPE_JD', 'jd');
  define('CONST_POSITION_TYPE_LINK', 'link');
  define('CONST_POSITION_TYPE_PLACEMENT', 'pla');

  define('CONST_STAT_TYPE_CHART', 'chart');
  define('CONST_STAT_TYPE_PIPELINE', 'pipe');
  define('CONST_STAT_TYPE_PIPEEXT', 'pipex');
  define('CONST_STAT_TYPE_KPI', 'kpi');
  define('CONST_STAT_TYPE_POSITION_PIPE', 'popipe');


  define('CONST_EVENT_SYNC_FLAG', '/debug/novalidate-cert/tls/readonly');
  define('CONST_EVENT_SYNC_ALIASES', 'ct=555-001__ppav__candi,ct#=555-001__ppav__candi,candi=555-001__ppav__candi,candi#=555-001__ppav__candi,candidate=555-001__ppav__candi,cp=555-001__ppav__comp,cp#=555-001__ppav__comp,comp=555-001__ppav__comp,comp#=555-001__ppav__comp,company=555-001__ppav__comp');



  function getCustomWebsiteFooter($pasFooter)
  {
    $oHTML = CDependency::getCpHtml();
    $sHTML = $oHTML->getBlocStart('footerContainerId');
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }

  function assoc_array_shuffle($array)
  {
    $orig = array_flip($array);
    shuffle($array);
    foreach($array as $n)
    {
      $data[$n] = $orig[$n];
    }
    return array_flip($data);
  }



  function getTips($pnNumber = 1, $pbShuffle = true)
  {
    $asTips = array(
      '1' => 'Rule #1: if you don\'t understand, ask. Ask your client or ask another consultant.',
      '2' => 'Establish excellent rapport with the line manager. If the client doesn\'t have time to talk to you or help you to help him, don\'t waste your time.',
      '3' => 'Ensure that the client has the headcount to hire a guy and the budget to engage a headhunter. You are wasting your time if you find a guy that they can\'t afford to hire.',
      '4' => 'Define the parameters for suitable candidates. If you don\'t know what you are looking for you can\'t expect to find it.',
      '5' => 'Clearly define the parameters of the search -what are your target industries or companies? How about target departments and are there any special considerations? If you don\'t have clear communication with the client, this is impossible. You are not wasting your time or their time by discussing the position requirements at length.',
      '6' => 'Before doing anything else, ask around the company to see if someone knows about a suitable candidate.',
      '7' => 'Map out target companies. What is already on the DB (name collects or otherwise)? Are there people on the db who used to work at target companies? Speak with your existing candidates to see if they can recommend a good people -great guys know great guys.',
      '8' => 'Develop long list of suitable candidates and systematically call or meet everyone. Get a short list from this. Don\'t be afraid to name collect if you don\'t have enough potential candidates. Name collect asd early as possible so that you can use the names sooner',
      '9' => 'Meet everyone you send, or at least ensure that they have been met and assessed by someone whose judgment you absolutely trust. All you have to do is send a chump once to lose your good reputation for judgment. Meet at least 8 candidates always for the position even if they are slightly off spec. for the position. Keep the long term in mind.',
      '10' => 'Send one candidate at a time. Always call before you send the resume to pimp your guy. Confirm that client\'s needs and then work your good candidates into their mental picture. Don\'t send more than one resume at a time otherwise you may end up with a batch of good guys getting shot down without careful consideration by the client.',
      '11' => 'Only send over candidates who have a vested personal interest in going there. It has to be a great move for their career development or why would they do it? Find out what candidates really want and what their objectives are over the long term. If they don\'t already know, walk them through it. -what do they love? What are they best at? Where do their talents and the needs of the world intersect? Take a very long term to candidates. Never burn them. Always be fair and never try to force them into something just so that you can make a commission.',
      '12' => 'Use negative feedback to build a stronger case for the next guys you send -you didn\'t like x because of blah, so maybe you should think about y.',
      '13' => 'Discuss with the client how your guy will fit into the Organizational Chart and the transition plan. His probable long term potential within their organization. Get the client speaking in terms of having your excellent candidate work there.',
      '14' => 'Take a very long term approach to clients. Try to get inside of their heads and understand what they are trying to accomplish as a company. Why are they willing to spend a considerable amount of money to find a good guy? Who answers for spending 3MYen in budget to hire a guy and are you in contact with him? How does he hope to improve his organization by hiring a guy? What is their corporate culture like and what is the appropriate guy to send over there?',
      '15' => 'Don\'t always focus on the sexy and famous companies. Sometimes the best clients are small and relatively unknown companies who really appreciate our help and are quick to hire.',
      '16' => 'Continuously move closer to the line managers and the decision makers. Use any opportunity to speak to the next higher guy -just to get info first hand or what ever.',
      '17' => 'Last but not least, there is no substitute for hard work. ');

    if($pbShuffle)
      $asTips = assoc_array_shuffle($asTips);

    return array_slice($asTips, 0, $pnNumber, true);
  }


  function logUserHistory($psItemUid, $psItemAction='', $psItemType='', $pnItemPk=0, $pasLogData = array(), $pbAddToSysLog = true)
  {
    $oLogin = CDependency::getCpLogin();
    $pasLogData['force_log'] = 1;

    if(!isset($pasLogData['data']))
      $pasLogData['data'] = array();

    if(!isset($pasLogData['data']['qb']))
    {
      $pasLogData['data']['post'] = $_POST;
      $pasLogData['data']['get'] = $_GET;
    }
    else
    {
     // '&replay_search='.$asActivity['login_activitypk'];
    }

    //save it permanently in the system logs
    if($pbAddToSysLog)
    {
      $sText = '';
      if(isset($pasLogData['text']))
        $sText = $pasLogData['text'];

      if(empty($sText) && isset($pasLogData['label']))
        $sText = $pasLogData['label'];

      if(empty($sText) && isset($pasLogData['msg']))
        $sText = $pasLogData['msg'];

      if(empty($sText))
        $sText = '';

      $oModel = new CModel();
      $oModel->_logChanges(array('action' => 'log user history'), 'user_history', $sText);
    }

    //save the user activity (2-3 months history)
    return $oLogin->logUserAction($oLogin->getUserPk(), $psItemUid, $psItemAction, $psItemType, $pnItemPk, $pasLogData);
  }

  function getContactTypes()
  {
   return array(
       1 => array('label' => 'Home', 'control' => ''),
       2 => array('label' => 'Work', 'control' => ''),
       3 => array('label' => 'Web', 'control' => ''),
       4 => array('label' => 'Fax', 'control' => ''),
       5 => array('label' => 'Email', 'control' => ''),
       6 => array('label' => 'Mobile', 'control' => ''),
       7 => array('label' => 'Facebook', 'control' => ''),
       8 => array('label' => 'LinkedIn', 'control' => ''),
       9 => array('label' => 'Info', 'control' => '')
       );
  }


  function getAjaxCall($psUrl, $psCallback = '', $psTabType = '',  $psLabel = '')
  {
    $psLabel = mb_strimwidth($psLabel, 0, 14, '...');
    return '
      var asContainer = goTabs.create(\''.$psTabType.'\', \'\', \'\', \''.$psLabel.'\');
      AjaxRequest(\''.$psUrl.'\', \'body\', \'\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); '.$psCallback.'\' );
      goTabs.select(asContainer[\'number\']); ';
  }


  //function addPageStructure($psContent, $psTabType = '', $psExtraClass = '')
  function customComponentBlockStart($psTabType = '', $psExtraClass = '')
  {
    $oPage = CDependency::getCpPage();
    $oPage->addJSfile('/component/sl_candidate/resources/js/sl_candidate.js');
    $oPage->addCSSfile('/component/sl_candidate/resources/css/sl_candidate.css');

    $sLiId = uniqid();
    $oPage->addCustomJs('$(document).ready(function()
    {
      $(window).resize();
      goTabs.preload(\''.$psTabType.'\', \''.$sLiId.'\', true);
    });
    ');

    return '<div class="topCandidateSection hidden " id="topCandidateSection"></div>
    <div class="bottomCandidateSection" id="bottomCandidateSection">
    <ul id="tab_content_container"><li id="'.$sLiId.'" ><div class="scrollingContainer '.$psExtraClass.'" >';

  }
  function customComponentBlockEnd()
  {
    return '
    </div></li></ul>
    </div>';
  }

  function getMailHeader()
  {
    $oHTML = CDependency::getCpHtml();

    $sContent = "<html><body style='font-family: Verdana, Arial; font-size: 11px;'>";
    $sContent.= $oHTML->getBlocStart('', array('style' => 'border-left: 2px solid #98321F;'));

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px; border-top: 2px solid #98321F; border-bottom: 1px solid #aaaaaa; background-color: #f0f0f0; margin: 5px 0; color: #ffffff; height: 38px; line-height: 26px; font-size: 24px;'));
    $sContent.= $oHTML->getPicture(CONST_MAIL_HEADER_PICTURE, '', CONST_CRM_DOMAIN, array('style' => 'background-color: #ffffff; margin: 5px;'));
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:500px; min-height:200px; margin: 8px 0; padding: 5px 0;'));
    $sContent.= $oHTML->getBlocStart('', array('style' => 'margin:5px 15px;'));

    return $sContent;
  }

  function getMailFooter()
  {
    $oHTML = CDependency::getCpHtml();

    $sContent = $oHTML->getBlocEnd();
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:500px; margin: 0; padding: 2px 15px; font-size: 10px; font-style: italic; color: #ffffff; height: 14px; background-color: #98321F;'));
    $sContent.= 'Sent by <a href="'.CONST_CRM_DOMAIN.'" style="color: #fff; text-decoration: none;" >'.CONST_APP_NAME.'</a> © '.date('Y').' - Powered by people of Slate.';
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocEnd();
    $sContent.= '</body></html>';

    return $sContent;
  }
