<?php

  //tell dependency portal has a navigation path t
  CDependency::addComponentInterface('111-111', 'portal', 'has_navigationpath');
  

  define('CONST_WEBSITE_LOGO_URL', 'https://bcm.bulbouscell.com');
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
  define('CONST_HEADER_LOGO','/media/picture/bcm_logo.png');
  define('CONST_HEADER_FAVICON', '/media/picture/bcm/favicon.ico');
  define('CONST_LOGIN_MESSAGE','Welcome to BCMedia CRM, please login to access the application. ');
  define('CONST_TYPE_HOMEPAGE','home');

  define('CONST_WEBSITE_LOADING_PICTURE','/common/pictures/loading.gif');
  define('CONST_SYTEM_LOG_ACTIVE', true);


  //mail
  define('CONST_MAIL_HEADER_PICTURE', '');
  define('CONST_MAIL_FOOTER_PICTURE', '');

  define('CONST_LOGIN_USERLINK_CALLBACK', '');





  // ===============================================================================
  // ===============================================================================

  function getCustomWebsiteFooter($pasFooter)
  {
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();

    $sHTML = $oHTML->getBlocStart('footerContainerId');
    $sHTML.= $oHTML->getBlocStart();

    $sHTML.= '<ul>';

    if(isset($pasFooter) && !empty($pasFooter))
    {
      foreach($pasFooter as $asFooterLinks)
      {
        if(isset($asFooterLinks['name']))
        {
          $sHTML.= '<li>';
          $sTitle = $asFooterLinks['name'];
          if(!empty($asFooterLinks['link']))
            $sLink = $asFooterLinks['link'];
          else
          {
            if(!empty($asFooterLinks['uid']))
            {
              $nPnPk = (int)$asFooterLinks['pk'];
              $sLink = $oPage->getUrl(''.$asFooterLinks['uid'].'',''.$asFooterLinks['type'].'',''.$asFooterLinks['action'].'',$nPnPk);
            }
              else
              $sLink = '#';
          }
          $sHTML.= $oHTML->getLink($sTitle, $sLink,array('target'=>$asFooterLinks['target']));
          $sHTML.= '</li>';
        }
    }
    }
    $sHTML.= '</ul>';

    $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();
    return $sHTML;
  }


  function getMailHeader()
  {
    $oHTML = CDependency::getCpHtml();

    $sContent = "<html><body style='font-family: Verdana, Arial; font-size: 11px;'>";
    $sContent.= $oHTML->getBlocStart('', array('style' => 'border-left: 2px solid #252525;'));

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px; border-bottom: 1px solid #000000; background-color: #252525; margin: 5px 0; color: #ffffff; height: 45px; line-height: 26px; font-size: 24px;'));
    $sContent.= $oHTML->getPicture('http://'.CONST_CRM_HOST.'/media/picture/bcm/logo.png', '', CONST_CRM_DOMAIN, array('style' => 'background-color: #252525; margin: 5px;'));
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px; min-height:300px; margin: 8px 0; padding: 5px 0;'));
    $sContent.= $oHTML->getBlocStart('', array('style' => 'margin:5px 15px;'));

    return $sContent;
  }

  function getMailFooter()
  {
    $oHTML = CDependency::getCpHtml();

    $sContent = $oHTML->getBlocEnd();
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px; padding: 2px 15px; font-size: 10px; font-style: italic; height: 14px; background-color: #252525; color: #ffffff;'));
    $sContent.= 'Sent by BCM spamming services © '.date('Y').' - Powered by NPS, BCMedia and the PHP gods.';
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocEnd();
    $sContent.= '</body></html>';

    return $sContent;
  }




?>