<?php

  //tell dependency portal has a custom layout taht should be used to build the page
  CDependency::addComponentInterface('111-111', 'portal', 'set_custom_layout');


  define('CONST_WEBSITE_LOGO_URL','http://www.globusjapan.com/');
  define('CONST_WEBSITE_GOOGLE_ANALYTICS', "");

  define('CONST_DISPLAY_SEARCH_MENU', true);
  define('CONST_DISPLAY_HOMEPAGE_ICON', 'home_48.png');
  define('CONST_DISPLAY_HAS_LOGGEDIN_CSS', true);
  define('CONST_PAGER_NUM', '15');
  define('CONST_SYTEM_LOG_ACTIVE', 1);

  define('CONST_WEBSITE_LOADING_PICTURE','/component/portal/resources/pictures/globus/loading.gif');
  define('CONST_HEADER_FAVICON','/component/portal/resources/pictures/globus/favicon.ico');
  define('CONST_HEADER_LOGO','/component/portal/resources/pictures/globus/logo.png');
  define('CONST_PICTURE_TESTOK', '/component/portal/resources/pictures/globus/tick.png');
  define('CONST_PICTURE_TIMEOVER', '/component/portal/resources/pictures/globus/timeover.png');
  define('CONST_PICTURE_TESTNOTOK', '/component/portal/resources/pictures/globus/cross.png');
  define('CONST_PICTURE_TEST', '/component/portal/resources/pictures/globus/icons/assignments_grey.png');
  define('CONST_PICTURE_SETTINGS', '/component/portal/resources/pictures/globus/icons/settings.png');
  define('CONST_PICTURE_GBDELETE', '/component/portal/resources/pictures/globus/icons/delete.png');
  define('CONST_PICTURE_GBSEARCH', '/component/portal/resources/pictures/globus/search.png');

  define('CONST_TYPE_STUDENT', 'stud');
  define('CONST_TYPE_TEACHER', 'teac');
  define('CONST_TYPE_HRMANAGER', 'hrmn');
  define('CONST_TYPE_GBADMIN', 'gbad');

  define('CONST_ACTION_CONFIRM', 'aconf');
  define('CONST_LOGIN_USERLINK_CALLBACK', '');


  function getMailHeader()
  {
    $oHTML = CDependency::getComponentByInterface('do_html');
    $sHTML = '';

    $sHTML .= $oHTML->getBlocStart('container', array('style' => 'width:800px; font-family:Arial; '));
     // $sHTML .= $oHTML->getBloc('header', $oHTML->getPicture('http://'.CONST_CRM_HOST.CONST_HEADER_LOGO));
      $sHTML .= $oHTML->getBlocStart('main', array('style' => ''));

    return $sHTML;
  }

  function getMailFooter()
  {
    $oHTML = CDependency::getComponentByInterface('do_html');
    $sHTML = '';

    $sFooter = 'Regards,<br/><strong>Globus Consulting</strong><br /><hr style="color: blue; margin: 5px 0;"/>'.$oHTML->getPicture('http://'.CONST_CRM_HOST.CONST_HEADER_LOGO);
      $sHTML .= $oHTML->getBloc('footer', $sFooter);
      $sHTML .= $oHTML->getBloc('', '<br />This is an automatic email. Please do not reply.', array('style' => 'font-size:10px; font-style:italic;'));
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }
