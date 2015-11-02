<?php

require_once('component/page/page.class.php5');

class CPageEx extends CPage
{
  private $coRight = null;

  private $casCustomJs = array();
  private $casJsFile = array();
  private $casCssFile = array();
  private $casCustomCss = array();

  private $csRequestedUrl = '';
  private $csEmbedUrl = '';
  private $casUrlDetail = array();
  private $cbIsLogged = false;

  private $csPageKeywords = '';
  private $csPageTitle = '';
  private $csPageDesc = '';

  private $cbTestMode = false;
  private $casUrl = array();

  public function __construct()
  {
    if(isset($_GET['debug']))
    {
      if($_GET['debug'] == 'none')
        unset($_SESSION['debug']);
      else
        $_SESSION['debug'] = $_GET['debug'];
    }

    if($_SERVER['SERVER_PORT'] === '80')
      $this->csRequestedUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    else
      $this->csRequestedUrl = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $this->casUrlDetail = parse_url($this->csRequestedUrl);

    if(!isset($this->casUrlDetail['query']))
      $this->casUrlDetail['query'] = '';

    if(empty($_SESSION['browser']))
    {
      //load the detection class and check if we're mobile
      require_once('component/page/resources/class/mobile_detect.class.php');
      $oDetector = new Mobile_Detect();

      $_SESSION['browser']['is_mobile'] = (bool)$oDetector->isMobile();
      $_SESSION['browser']['device_type'] = ($_SESSION['browser']['is_mobile'] ? ($oDetector->isTablet() ? CONST_PAGE_DEVICE_TYPE_TABLET : CONST_PAGE_DEVICE_TYPE_PHONE) : CONST_PAGE_DEVICE_TYPE_PC);
    }

    if(getValue('setPage') == 'mobile')
    {
      $_SESSION['browser']['is_mobile'] = 1;
      $_SESSION['browser']['device_type'] = CONST_PAGE_DEVICE_TYPE_PHONE;
    }


    if(!isset($_SESSION['page']['save_page_size']))
      $_SESSION['page']['save_page_size'] = array();

    if(!isset($_SESSION['page']['save_page_size_req']))
      $_SESSION['page']['save_page_size_req'] = 0;

    if(getValue('testMode'))
      $this->cbTestMode = true;
  }

  // Function for the cookie and session management
  public function init()
  {

    if(isset($_SESSION['userData']['pk']) && !empty($_SESSION['userData']['pk']))
    {
      //user is or has been logged recently
      $oLogin = CDependency::getCpLogin();

      //Check if we need to re-create the cookie (renew for 4 extra hours)
      $nLoginPk = $oLogin->getUserPk();
      if(isset($_COOKIE['login_userdata']) && !empty($_COOKIE['login_userdata']) && !empty($nLoginPk))
      {
        setcookie('login_userdata', $_COOKIE['login_userdata'], time()+3600*3, '/');
      }
      else
      {
        //cookie expired... recreate the cookie from SESSION data
        $oLogin->rebuildCookie();
      }
    }

    return true;
  }

  //=============================
  //accessors

  public function getUid()
  {
    return $this->csUid;
  }
  public function setUid($psUid)
  {
    $this->csUid = $psUid;
    return true;
  }

  public function getAction()
  {
    return $this->csAction;
  }
  public function getActionReturn()
  {
    return $this->csActionReturn;
  }

  public function getType()
  {
    return $this->csType;
  }

  public function getPk()
  {
    return $this->cnPk;
  }

  public function getMode()
  {
    return $this->csMode;
  }

  public function getRequestedUrl()
  {
    return $this->csRequestedUrl;
  }
  public function getRequestedComponent()
  {
    return array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => $this->csAction, CONST_CP_TYPE => $this->csType, CONST_CP_PK => $this->cnPk);
  }

  public function getEmbedUrl()
  {
    return $this->csEmbedUrl;
  }

  public function isMobileBrowser()
  {
    return (bool)$_SESSION['browser']['is_mobile'];
  }

  public function getDeviceType()
  {
    return $_SESSION['browser']['device_type'];
  }

  public function getLanguage()
  {
    //language saved in attribute to make the detection once
    if(!empty($this->csLanguage))
      return $this->csLanguage;

    if(empty($this->coSettings))
      $this->coSettings = CDependency::getComponentByName('settings');

    //$asLanguage =  explode(',', CONST_AVAILABLE_LANGUAGE);
    $asSettings = $this->coSettings->getSettings('languages');
    if(empty($asSettings['languages']))
      $asSettings['languages'] = array(CONST_DEFAULT_LANGUAGE);

    //a request to set the language and save it in session
    $sRequestedLang = getValue('setLang', '');
    if(!empty($sRequestedLang) && in_array($sRequestedLang, $asSettings['languages']))
    {
      $_SESSION['lang'] = $sRequestedLang;
      $this->csLanguage = $sRequestedLang;
      return $sRequestedLang;
    }

    //a request to set the language for the current page
    $sRequestedLang = getValue('lg', '');
    if(!empty($sRequestedLang) && in_array($sRequestedLang, $asSettings['languages']))
    {
      $this->csLanguage = $sRequestedLang;
      return $sRequestedLang;
    }

    if(isset($_SESSION['lang']) && !empty($_SESSION['lang']))
    {
      $this->csLanguage = $_SESSION['lang'];
      return $_SESSION['lang'];
    }

    return CONST_DEFAULT_LANGUAGE;
  }

  public function getPageSize()
  {
    if(!isset($_SESSION['page']['save_page_size']) || empty($_SESSION['page']['save_page_size']))
      return array();

    return array('height' => $_SESSION['page']['save_page_size'][0], 'width' => $_SESSION['page']['save_page_size'][1]);
  }



  //====================================================================
  //  public methods
  //====================================================================

  public function getPage($psUid = '', $psAction = '', $psType = '', $pnPK = 0, $psMode = 'pg')
  {
    if(!assert('is_string($psUid)'))
      return '';
    if(!assert('is_string($psAction)'))
      return '';
    if(!assert('is_string($psType)'))
      return '';
    if(!assert('is_integer($pnPK)'))
      return '';
    if(!assert('is_string($psMode)'))
      return '';

    if(empty($this->csUid))
      $this->csUid = $psUid;
    if(empty($this->csAction))
      $this->csAction = $psAction;
    if(empty($this->csType))
      $this->csType = $psType;
    if(empty($this->cnPk))
      $this->cnPk = $pnPK;
    if(empty($this->csMode))
      $this->csMode = $psMode;

    $this->coSettings = CDependency::getComponentByName('settings');
    if(empty($this->coSettings))
      exit('Could not load settings component. Sorry, you can not go further.');

    $this->coRight = CDependency::getComponentByName('right');
    if(empty($this->coRight))
      exit('Could not load rights component. Sorry, you can not go further.');

    //*****************************************************************
    //*****************************************************************
    //gather and initialize some parameters
    $asMeta = $this->coSettings->getSettings(array('meta_tags', 'meta_desc', 'title', 'menunav1pos', 'menunav2pos', 'menunav3pos', 'menuactionpos'));

    $this->setPageDescription($asMeta['meta_desc']);
    $this->setPageKeywords($asMeta['meta_tags']);
    $this->setPageTitle($asMeta['title']);

    $this->_managePageSize($psUid, $psAction, $psType);


    $this->csActionReturn = getValue(CONST_URL_ACTION_RETURN);
    $asPageParam = array('class' => $this->getDeviceType(), 'uid' => $this->csUid);

    $sHTML = '';

    //Check login status, accessrights...
    $oLogin = CDependency::getCpLogin();
    $bIsLogged = $oLogin->isLogged();
    $this->cbIsLogged = $bIsLogged;

    $logout = check_session_expiry();

    if ($logout)
    {
      if ($psMode == 'ajx')
        $oLogin->_getLogout(true, true);
      else
        $oLogin->_getLogout(false, true);
    }
    //*****************************************************************
    //*****************************************************************

    //if i'm logged, I must be using SSL
    if($bIsLogged && $this->casUrlDetail['scheme'] !== 'https')
    {
      @header('location:https://'.$this->casUrlDetail['host'].$this->casUrlDetail['path'].'?'.$this->casUrlDetail['query']);
      echo '<script>document.location.href = "https://'.$this->casUrlDetail['host'].$this->casUrlDetail['path'].'?'.$this->casUrlDetail['query'].'"; </script>';
      echo 'Being redirected to safer place. Click <a href=""/>here</a> if nothing happens in the next 5 seconds.';
      exit();
    }

    if(CONST_MAINTENANCE && !$oLogin->isAdmin() && !getValue('pass_maintenance'))
    {
      switch($this->csMode)
      {
        case CONST_URL_PARAM_PAGE_AJAX:
            return json_encode(array('error' => 'Sorry the platform is in maintenance. Please try again later.'));
          break;

        default:

        return $this->_getPageHTML($this->csUid, $this->_getMaintenancePage(), $bIsLogged, $asMeta, $asPageParam);
      }
    }


    //*****************************************************************
    //*****************************************************************
    //check if a component as a default homepageInterface to replace login
    if(empty($psUid) || empty($this->csUid))
    {
      $asHomeUid = CDependency::getComponentUidByInterface('has_homepage');
      if(!empty($asHomeUid))
      {
        $this->csUid = $psUid = current($asHomeUid);
        //echo('got a home ==> '. $this->csUid);
      }
      else
      {
        //going to redirect to login, let's send the correct url for form
        $this->csUid = $psUid = $oLogin->getComponentUid();
        $this->csType = $psType = 'restricted';
        $this->csAction = $psAction = CONST_ACTION_VIEW;
        //echo('no home ==> '. $this->csUid.' '.$this->csAction);
      }
    }

    if(CDependency::hasInterfaceByUid($psUid, 'has_publicContent'))
      $bPublicContent = true;
    else
    {
      $bPublicContent = false;
      $this->_AccessLog($oLogin);
    }


    //--------------------------------------------------------------------
    // CASE 1 : User don't have the right to access the page
    //--------------------------------------------------------------------

    if(!$this->coRight->canAccess($this->csUid, $this->csAction, $this->csType, $this->cnPk))
    {
      $oHTML = CDependency::getCpHtml();
      if(empty($oHTML))
        exit(__LINE__.' No available library to display the page. Please contact your administrator.');

      //dump('can T access '.$this->csMode);

      switch($this->csMode)
      {
        case CONST_URL_PARAM_PAGE_AJAX:
            return json_encode(array('error' => 'You can not access this page:</br></br> - your session may have expired [ <a href="/index.php5?uid=579-704&ppa=&ppt=restricted&ppk=0" style="color: green; font-size: inherit;">login form here</a> ]<br /> - the requested page or content is restricted'));
          break;

        default:

        $sRestrictedPage = $oLogin->getRestrictedPage($bIsLogged);

        $sComponentFullHtml = $oHTML->getComponentStart($bIsLogged, array('uid' => $this->csUid));
        $sComponentFullHtml.= $sRestrictedPage;
        $sComponentFullHtml.= $oHTML->getComponentEnd();
        return  $this->_getPageHTML($this->csUid, $sComponentFullHtml, $bIsLogged, $asMeta, $asPageParam);
      }
    }

    switch($this->csMode)
    {

      //--------------------------------------------------------------------
      // CASE 2 : Page requested through an Ajax Request
      //--------------------------------------------------------------------

      case CONST_URL_PARAM_PAGE_AJAX:

        if(empty($this->csUid))
          return json_encode(array('error' => __LINE__.' - page: error bad uid'));

        //The only ajax request allowed when not logged in, is to actually log in :)
        /*if(!$bPublicContent && !$bIsLogged && $this->csUid != $sLoginUid)
          return json_encode(__LINE__.' - page: not allowed');*/

        // When using ajax transport, we need to specify the contentType for the iframe content to be properly parsed
        // Ajaxrequest adds rqjson=1 when that's the case (we keep it text/html otherwise: easier to debug)
        if(getValue('rqjson'))
          header('Content-type: application/json');


        $oRequestedComponent = CDependency::getComponentByUid($this->csUid, 'has_ajax');
        if(empty($oRequestedComponent))
          return json_encode(array('error' => 'error no interface for the uid requested('.$this->csUid.')'));

         return $oRequestedComponent->getAjax();
         break;

      //--------------------------------------------------------------------
      // CASE 3 : Cron job requested
      //--------------------------------------------------------------------

      case CONST_URL_PARAM_PAGE_CRON:

        if(getValue('hashCron') != '1')
          exit();

        $oLogin->setCronUser();
        $this->coRight->initializeRights(true);


        $sCpUid = getValue('custom_uid');
        $bSilent = (bool)getValue('cronSilent', 0);

        if(!$bSilent)
          echo 'Cron started at '.date('Y-m-d H:i:s').' '. microtime(true).'<br /><br />';

        $asComponentUid = CDependency::getComponentUidByInterface('has_cron');

        foreach($asComponentUid as $sUid)
        {
          if(empty($sCpUid) || $sCpUid == $sUid)
          {
            if(!$bSilent)
              echo '<br /><hr /><h1>'.$sUid.'</h1><br />';

            $oComponenent = CDependency::getComponentByUid($sUid);
            $oComponenent->getCronJob($bSilent);
          }
        }

        if(!$bSilent)
          echo '<br/><br/><hr/>Cron finished at '.date('Y-m-d H:i:s').' '.  microtime(true).'';
        exit();

        break;

      //--------------------------------------------------------------------
      // CASE 4 : Page is embed
      //--------------------------------------------------------------------

      case CONST_URL_PARAM_PAGE_EMBED:

        if($bIsLogged && isset($_GET[CONST_URL_EMBED]) && !empty($_GET[CONST_URL_EMBED]))
        {
          $this->csEmbedUrl = $_GET[CONST_URL_EMBED];
          $oHTML = CDependency::getCpHtml();

          $sComponentFullHtml = $oHTML->getComponentStart($bIsLogged, array('uid' => $this->csUid));
          $sComponentFullHtml.= $oHTML->getEmbedPage(urldecode($_GET[CONST_URL_EMBED]));
          $sComponentFullHtml.= $oHTML->getComponentEnd();

          $sHTML = $this->_getPageHTML($this->csUid, $sComponentFullHtml, $bIsLogged, $asMeta, $asPageParam);
        }
        break;

      default:

        $oHTML = CDependency::getCpHtml();
        //$oMenu = CDependency::getComponentByInterface('display_menu');

        if(empty($oHTML))
        {
          assert('false; // Page could not be loaded. Please contact your administrator.');
          exit();
        }

        //--------------------------------------------------------------------
        // CASE 5 : User is not logged in and request private content
        //--------------------------------------------------------------------

        if(!$bIsLogged && !$bPublicContent)
        {
          //call component before creating header to allow file inclusions
          $oLogin->setType('restricted');
          $sComponentHtml = $oLogin->getHtml();

          $sComponentFullHtml = $oHTML->getComponentStart($bIsLogged, array('uid' => $this->csUid)) . $sComponentHtml . $oHTML->getComponentEnd();
        }
        else
        {
          //--------------------------------------------------------------------
          // CASE 6 : Normal display - public or private but allowed access
          //--------------------------------------------------------------------
          $asPageParam['class'] = 'menunav1pos_'.$asMeta['menunav1pos'].' '.'menunav2pos_'.$asMeta['menunav2pos'].' '.'menunav3pos_'.$asMeta['menunav3pos'];

          if(empty($this->csUid))
            $oRequestedComponent = CDependency::getCpLogin();
          else
            $oRequestedComponent = CDependency::getComponentByUid($this->csUid);


          if(empty($oRequestedComponent))
            $sComponentFullHtml = $oHTML->getBlocMessage('Wrong parameters / component !! <br /> This url leads nowhere: '.$_SERVER['REQUEST_URI']);
          else
          {
            $sComponentHtml = $oRequestedComponent->getHtml();

            if(empty($sComponentHtml))
              $sComponentHtml = $oHTML->getNoContentMessage();

            //rebuild meta with what the component may have included/removed from it
            $asMeta['meta_tags'] = $this->getPageKeywords();
            $asMeta['meta_desc'] = $this->getPageDescription();
            $asMeta['title'] = $this->getPageTitle();


            //!!!! $this->csUid not psUid... could have been change if reirected to portal in login !!!!
            $sNavigation = '';
            if(CDependency::hasInterfaceByUid($this->csUid, 'has_navigationpath'))
            {
              $asNavigation = CDependency::getComponentByUid($this->csUid)->getPageNavigation();
              if(!empty($asNavigation))
                $sNavigation = $oHTML->getNavigationPath($asNavigation, $this->csUid, $this->csAction, $this->csType, $this->cnPk);
            }

            $sComponentFullHtml = $oHTML->getComponentStart($bIsLogged, array('uid' => $this->csUid)).$sNavigation . $sComponentHtml . $oHTML->getComponentEnd();
          }
        }

        $sHTML = $this->_getPageHTML($this->csUid, $sComponentFullHtml, $bIsLogged, $asMeta, $asPageParam);

        break;
      }

    return $sHTML;
  }

  private function _getPageHTML($psUid, $psComponentHtml, $pbIsLogged, $pasMeta, $pasPageParam)
  {
    if(!assert('is_string($psComponentHtml) && !empty($psComponentHtml)'))
      return '';

    if(!assert('is_bool($pbIsLogged)'))
      return '';

    if(!assert('is_array($pasMeta)'))
      return '';

    if(!assert('is_array($pasPageParam)'))
      return '';

    //-----------------------------------------------------------------------
    //Loading / Managing specific HTML page parameters
    $this->_getCustomUserFeature();

    /*if(CDependency::hasInterfaceByUid($psUid, 'has_navigationpath'))
    {
      $asNavigation = CDependency::getComponentByUid($psUid)->getPageNavigation();
    }
    else
      $asNavigation = array();*/


    //-----------------------------------------------------------------------
    $oHTML = CDependency::getComponentByName('display');
    $oMenu = CDependency::getComponentByInterface('display_menu');
    $sHTML = '';

    //-----------------------------------------------------------------------
    // If a custom layout is defined through the portal component, we use it instead of the default one
    $oCustomLayout = CDependency::getComponentByInterface('set_custom_layout');
    if(!empty($oCustomLayout))
    {
      $sHTML = $oCustomLayout->getPageHTML($psComponentHtml, $pbIsLogged);
    }
    else
    {
      // Default layout
      $sHTML.= $oHTML->getHeader($pbIsLogged, $pasPageParam);
      $sHTML.= $oMenu->getMenuNav('left');
      $sHTML.= $oMenu->getMenuAction('left');

      //if(!empty($asNavigation))
        //$sHTML.= $oHTML->getNavigationPath($asNavigation, $psUid, $this->csAction, $this->csType, $this->cnPk);

      $sHTML.= $psComponentHtml;
      $sHTML.= $oMenu->getMenuNav('right');
      $sHTML.= $oMenu->getMenuAction('right');
      $sHTML.= $oHTML->getFooter();
    }

    if($this->cbTestMode)
      $this->_addTestJavascript();

    $sMeta = $oHTML->getMeta($pbIsLogged, $this->casJsFile, $this->casCustomJs, $this->casCssFile, $this->casCustomCss, $pasMeta, $pasPageParam);

    return $sMeta.$sHTML;
  }

  public function getHtml()
  {
    $oHTML = CDependency::getCpHtml();
    return $oHTML->getBlocMessage('Wrong parameters / component !! <br /> This url leads nowhere: '.$_SERVER['REQUEST_URI']);
  }

  public function getUrl($psComponent, $psAction = '', $psType = '', $pnPk = 0, $pasOptions = array(), $psHash = '')
  {
    if(!assert('is_string($psComponent)'))
      return '';
    if(!assert('is_string($psAction)'))
      return '';
    /*
    if(!assert('is_string($psType)'))
      return '';
    if(!assert('is_integer($pnPk)'))
      return '';
     *
     */
    if (preg_match('/^([0-9]{3})-([0-9]{3})$/i', $psComponent))
      $sUid = $psComponent;
    else
    {
      $sUid = CDependency::getComponentUidByName($psComponent);
    }

    if($this->cbIsLogged)
    {
      $sURL = 'https://'.CONST_CRM_HOST.'/index.php5?'.CONST_URL_UID.'='.$sUid.'&'.CONST_URL_ACTION.'='.$psAction;
      $sURL.= '&'.CONST_URL_TYPE.'='.$psType.'&'.CONST_URL_PK.'='.$pnPk;
    }
    else
    {
      $sURL = $this->casUrlDetail['scheme'].'://'.CONST_CRM_HOST.'/index.php5?'.CONST_URL_UID.'='.$sUid.'&'.CONST_URL_ACTION.'='.$psAction;
      $sURL.= '&'.CONST_URL_TYPE.'='.$psType.'&'.CONST_URL_PK.'='.$pnPk;
    }

    if(!empty($pasOptions))
    {
      foreach($pasOptions as $sOption => $sValue)
        $sURL.= '&'.urlencode($sOption).'='.urlencode($sValue);
    }

    if(!empty($psHash))
    {
      $sURL.='#'.$psHash;
    }

    if($this->cbTestMode)
      $this->casUrl['std'][$sURL] = $sURL;

    return $sURL;
  }


  public function getUrlHome($bLogout = false)
  {
    if(!assert('is_bool($bLogout)'))
      return '';

    $sUrl = $this->coSettings->getSettingValue('urlparam');
    $oLogin = CDependency::getCpLogin();

    if(empty($sUrl) || $bLogout)
      $sUrl = $this->getUrl($oLogin->getComponentUid(), '', 'restricted', 0);

    return $sUrl;
  }


  public function getUrlEmbed($psUrl)
  {
    $sUrl = $this->getUrl($this->_getUid(), CONST_ACTION_VIEW, '', 0, array(CONST_URL_MODE => CONST_URL_PARAM_PAGE_EMBED, CONST_URL_EMBED =>$psUrl));
    return $sUrl;
  }

  public function getAjaxUrl($psComponent, $psAction = '', $psType = '', $pnPk = 0, $pasOptions = array())
  {
    $sURL = $this->getUrl($psComponent, $psAction, $psType, $pnPk, $pasOptions);
    $sURL.= '&'.CONST_URL_MODE.'='.CONST_URL_PARAM_PAGE_AJAX;

    if($this->cbTestMode)
      $this->casUrl['ajax'][$sURL] = $sURL;

    return $sURL;
  }

  public function isAjaxUrl($psUrl)
  {
    if(!assert('is_string($psUrl)') || empty($psUrl))
      return false;

    if(preg_match('/'.'&'.CONST_URL_MODE.'='.CONST_URL_PARAM_PAGE_AJAX.'/', $psUrl) == false)
      return false;

    return true;
  }

  public function redirect($psURL)
  {
    @header('location: '.$psURL.'pg=normal');
    $sHTML = '<script type="text/javascript">document.location.href = "'.$psURL.'";</script>';
    $sHTML.= 'You\'re gonna be redirected in a few seconds. You can click on <a href="'.$psURL.'">this link </a> to access the page right now.';

    return $sHTML;
  }


  /**
   * Load / save / and add js vars to manage page size
   * @param type $psUid
   * @param type $psAction
   * @param type $psType
   * @return boolean
   */
  private function _managePageSize($psUid, $psAction, $psType)
  {
    if(!CONST_PAGE_USE_WINDOW_SIZE)
      return false;

    $sURL = $this->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_PAGE_TYPE_SETTING);
    $this->addCustomJs(
    '
        function updatePhpWindowSize()
        {
          //console.log("update page size => "+$(window).height()+" / "+$(window).width());
          $.get("'.$sURL.'&height="+$(window).height()+"&width="+$(window).width());
        }
    ');

    //1. If we receive the size in ajax, we save it in session
    if($psUid == $this->csUid && $psAction == CONST_ACTION_ADD && $psType == CONST_PAGE_TYPE_SETTING)
    {
      $sHeight = getValue('height');
      $sWidth = getValue('width');
      if(!is_numeric($sHeight) || $sHeight < 0 || !is_numeric($sWidth) || $sWidth < 0)
      {
        assert('false; // error saving page size');
        exit('#');
      }

      $_SESSION['page']['save_page_size'] = array($sHeight, $sWidth);
      exit('!');
    }

    //2. check if we've got the size in session
    if(empty($_SESSION['page']['save_page_size']))
    {
      if($_SESSION['page']['save_page_size_req'] < 20)
      {

        $this->addCustomJs(
        '
          $(document).ready(function()
          {
            updatePhpWindowSize();
            var goPage = {"height": $(window).height(), "width": $(window).width()};
          });');

        //security purpose: don't want to update settings indefinitly
        $_SESSION['page']['save_page_size_req']++;
      }
    }
    else
    {
      $this->addCustomJs('var goPage = {"height": '.$_SESSION['page']['save_page_size'][0].', "width": '.$_SESSION['page']['save_page_size'][1].'}; ');
    }

    return true;
  }

  public function addUrlParams($psUrl, $pasParams)
  {
    if(!assert('!empty($psUrl)'))
      return '';

    if(empty($pasParams))
      return $psUrl;

    $asUrl = parse_url($psUrl);
    if(!isset($asUrl['host']))
      $asUrl['host'] = CONST_CRM_DOMAIN;

    if(!isset($asUrl['path']))
      $asUrl['path'] = '/';

    $asQuery = array();
    foreach($pasParams as $sParam => $sValue)
      $asQuery[] = $sParam.'='.$sValue;

    if(!isset($asUrl['query']))
    {
      $asUrl['query'] = '?'.implode('&', $asQuery);
    }
    else
    {
       $asUrl['query'].= '&'.implode('&', $asQuery);
    }

    $sScheme   = isset($asUrl['scheme']) ? $asUrl['scheme'] . '://' : '';
    $sHost     = isset($asUrl['host']) ? $asUrl['host'] : '';
    $sPort     = isset($asUrl['port']) ? ':' . $asUrl['port'] : '';
    $sUser     = isset($asUrl['user']) ? $asUrl['user'] : '';
    $sPass     = isset($asUrl['pass']) ? ':' . $asUrl['pass']  : '';
    $sPass     = ($sUser || $sPass) ? "$sPass@" : '';
    $sPath     = isset($asUrl['path']) ? $asUrl['path'] : '';
    $sQuery    = isset($asUrl['query']) ? '?'.$asUrl['query'] : '';
    $sFragment = isset($asUrl['fragment']) ? '#' . $asUrl['fragment'] : '';

    return "$sScheme$sUser$sPass$sHost$sPort$sPath$sQuery$sFragment";
  }

  /**
   *
   * Allow a component to request specific JS files to be included in the page header
   * @param array $pasJsFile
   */
  public function addJsFile($pvJsFile)
  {
    if(empty($pvJsFile))
      return false;

    if(is_array($pvJsFile))
    {
      foreach($pvJsFile as $sFileName)
        $this->casJsFile[$sFileName] = $sFileName;
    }
    else
    {
      $this->casJsFile[$pvJsFile] = $pvJsFile;
    }

    return true;
  }

  /**
  * Allow a component to request specific JS files to be included in the page header
  * @param array $pasJsFile
  */
  public function addCustomJs($pvJavascript)
  {
    if(empty($pvJavascript))
      return false;

    if(is_array($pvJavascript))
    {
      foreach($pvJavascript as $sJavascript)
        $this->casCustomJs[$sJavascript] = $sJavascript;
    }
    else
    {
       $this->casCustomJs[$pvJavascript] = $pvJavascript;
    }

    return true;
  }

  /**
  *
  * Allow a component to request specific CSS files to be included in the page header
  * @param array $pasJsFile
  */
  public function addCssFile($pvCssFile)
  {
    if(empty($pvCssFile))
      return false;

    if(!is_array($pvCssFile))
      $pvCssFile = array($pvCssFile);

    if(CONST_SAVE_BANDWIDTH)
    {
      //minify css to reduce bandwidth usage ...
      // + introduce the possibility for CSS preprocessor implementation (http://leafo.net/scssphp/ for example)
      // will be extended to js in the future

      foreach($pvCssFile as $sFileName)
      {
        $this->_hasMinified($sFileName);
        $this->casCssFile[$sFileName] = $sFileName;
      }
      return true;
    }

    foreach($pvCssFile as $sFileName)
      $this->casCssFile[$sFileName] = $sFileName;

    return true;
  }

  /**
   * Check if we have a minified version of the css, and save it in session fpor further use
   *
   * To remove them !!
   * find /opt/eclipse-workspace/bcm_svn/trunk/component/ -type f -name '*.min.css'  -exec rm {} \;
   * find /opt/eclipse-workspace/bcm_svn/trunk/component/ -not \( -path "/opt/eclipse-workspace/bcm_svn/trunk/component/form/*" \) -type f -name '*.min.css'
   * find /home/slate/public_html/slistem/slistem_live/component/ -not \( -path "/home/slate/public_html/slistem/slistem_live/component/form/*" \) -type f -name '*.min.css'  -exec rm {} \;
   *
   * @param type $psCssFile
   * @return type
   */
  private function _hasMinified(&$psCssFile)
  {
    /*dump(' - - - - - - - - -');
    dump($psCssFile);
    dump(substr($psCssFile, -8, 8));*/

    $bForceRefresh = (bool)getValue('refresh_css', 0);
    if(!$bForceRefresh)
    {
      //if asking for a minified file
      if(substr($psCssFile, -8) == '.min.css')
      {
        //dump('already minified');
        return $psCssFile;
      }

      //if we've got in session the minifed version of this file, we replace it
      if(isset($_SESSION['min_css'][$psCssFile]))
      {
        //dump('found in session --> '.$_SESSION['min_css'][$psCssFile]);
        $psCssFile = $_SESSION['min_css'][$psCssFile];
        return $psCssFile;
      }

      $sFile = preg_replace('|\.css$|', '.min.css', $psCssFile);
      if(!file_exists($_SERVER['DOCUMENT_ROOT'].$sFile))
      {
        //dump('minified doesn not exist  '.$sFile);
        //dump('/usr/bin/java -jar '.$_SERVER['DOCUMENT_ROOT'].'/apps/yuicompressor/yuicompressor-2.4.8.jar --type "css" -o "'.$_SERVER['DOCUMENT_ROOT'].$sFile.'" "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; chown apache: "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; ');
        exec('/usr/bin/java -jar '.$_SERVER['DOCUMENT_ROOT'].'/apps/yuicompressor/yuicompressor-2.4.8.jar --type "css" -o "'.$_SERVER['DOCUMENT_ROOT'].$sFile.'" "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; chown apache: "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; ');
      }
    }
    else
    {
      if(substr($psCssFile, -8) == '.min.css')
        $psCssFile = substr($psCssFile, -8).'.css';

      $sFile = preg_replace('|\.css$|', '.min.css', $psCssFile);
      exec('/usr/bin/java -jar '.$_SERVER['DOCUMENT_ROOT'].'/apps/yuicompressor/yuicompressor-2.4.8.jar --type "css" -o "'.$_SERVER['DOCUMENT_ROOT'].$sFile.'" "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; chown apache: "'.$_SERVER['DOCUMENT_ROOT'].$psCssFile.'"; ');
    }


    $_SESSION['min_css'][$psCssFile] = $sFile;
    $psCssFile = $sFile;
    return $sFile;
  }

  /**
  * Allow a component to request specific JS files to be included in the page header
  * @param array $pasJsFile
  */
  public function addCustomCss($pvCSS)
  {
    if(empty($pvCSS))
      return false;

    if(is_array($pvCSS))
    {
      foreach($pvCSS as $sCss)
        $this->casCustomCss[] = $sCss;
    }
    else
    {
       $this->casCustomCss[] = $pvCSS;
    }

    return true;
  }

  /**
  * Log when an user logs in the crm
  * @param object $poLogin dbresult of login information
  */
  private function _AccessLog($poLogin)
  {
    /*@var $poLogin CLoginEx */
    $sIP = $_SERVER['REMOTE_ADDR'];
    if(empty($sIP))
      $sIP = 'unKnown_ip';

    $bFirstConnection = false;

    if(empty($poLogin) || !$poLogin->isLogged())
      $nUserPk = 0;
    else
      $nUserPk = $poLogin->getUserPk();

    if(!isset($_SESSION['accessLogStart']))
    {
      $bFirstConnection = true;
      $_SESSION['accessLogStart'] = date('Y-m-d H:i:s');
      $_SESSION['accessLogTime'] = time();
      $_SESSION['accessLogUid'] = uniqid('sess_', true);
      $_SESSION['accessLogCount'] = 1;
      $_SESSION['accessLogLogged'] = (int)$poLogin->isLogged();
    }
    else
    {
      $_SESSION['accessLogCount']++;
    }

    $sMessage = '';

    //basic security check / protection
    if($_SESSION['accessLogCount'] > 20)
    {
        $fNbPagePerSec = ($_SESSION['accessLogCount'] / (time() - $_SESSION['accessLogTime']));
        if($fNbPagePerSec > 1)
        {
          sleep(3);
          exit('too many requests');
        }

        if($fNbPagePerSec > 0.5)
        {
          //echo "usleep(5000000*$fNbPagePerSec)";
          usleep(5000000*$fNbPagePerSec);
          $sMessage = 'Lot of requests from that user ['.$fNbPagePerSec.' pages per second] ';
        }
    }

    if($bFirstConnection)
    {
      $sQuery = 'INSERT INTO login_access_history (ip_address, loginfk, date_start, nb_page, session_uid) ';
      $sQuery.=  ' VALUES ("'.$sIP.'", "'.$nUserPk.'", "'.$_SESSION['accessLogStart'].'", "'.$_SESSION['accessLogCount'].'", "'.$_SESSION['accessLogUid'].'") ';
    }
    else
    {
      $sQuery = 'UPDATE login_access_history SET loginfk = "'.$nUserPk.'", nb_page = "'.$_SESSION['accessLogCount'].'" ';

      if(!empty($sMessage))
        $sQuery.= ' , history = CONCAT(history, " || ", "'.$sMessage.'") ';

      $sQuery.= ' WHERE session_uid = "'.$_SESSION['accessLogUid'].'" ';
      //echo $sQuery;
    }

    /*@var $oDb CDatabaseEx */
    $oDb = CDependency::getComponentByName('database');
    $oDb->dbConnect();
    $bResult =  $oDb->ExecuteQuery($sQuery);

    if(!$bResult)
      assert('false; // couldn\'t log activity ');

    return $bResult;
  }

  public function getPageRequiredJsFile()
  {
    return $this->casJsFile;
  }
  public function getPageRequiredCssFile()
  {
    return $this->casCssFile;
  }
  public function getPageCustomJs()
  {
    return $this->casCustomJs;
  }

  public function getAjaxExtraContent($pasAjaxData)
  {
    if(!empty($this->casCustomJs))
    {
      if(isset($pasAjaxData['js']))
        $pasAjaxData['js'] = "\n ".implode("\n ", $this->casCustomJs);
      else
        $pasAjaxData['js'] = implode("\n ", $this->casCustomJs);
    }

    if(!empty($this->casJsFile))
    {
      if(isset($pasAjaxData['jsfile']))
        $pasAjaxData['jsfile'] = array_merge((array)$pasAjaxData['jsfile'], $this->casJsFile);
      else
        $pasAjaxData['jsfile'] = $this->casJsFile;
    }

    if(!empty($this->casCssFile))
    {
      if(isset($pasAjaxData['cssfile']))
        $pasAjaxData['cssfile'] = array_merge((array)$pasAjaxData['cssfile'], $this->casCssFile);
      else
        $pasAjaxData['cssfile'] = $this->casCssFile;
    }

    if(!empty($this->casCustomCss))
    {
      if(isset($pasAjaxData['data']))
        $pasAjaxData['data'] .= '<style>'.implode(' ', $this->casCustomCss).'</style>';
    }

    return $pasAjaxData;
  }

  public function setPageKeywords($psKeywords, $pbEraseDefault = false)
  {
    if(!assert('is_string($psKeywords)'))
      return false;

    if($pbEraseDefault)
      $this->csPageKeywords = $psKeywords;
    else
      $this->csPageKeywords.= ' '.$psKeywords;

    return true;
  }
  public function getPageKeywords()
  {
    return $this->csPageKeywords;
  }

  public function setPageDescription($psDescription, $pbEraseDefault = false)
  {
    if(!assert('is_string($psDescription)'))
      return false;

    if($pbEraseDefault)
      $this->csPageDesc = $psDescription;
    else
      $this->csPageDesc.= ' '.$psDescription;

    return true;
  }
  public function getPageDescription()
  {
    return $this->csPageDesc;
  }

  public function setPageTitle($psTitle, $pbEraseDefault = false)
  {
    if(!assert('is_string($psTitle)'))
      return false;

    if($pbEraseDefault)
      $this->csPageTitle = $psTitle;
    else
      $this->csPageTitle.= ' '.$psTitle;

    return true;
  }

  public function getPageTitle()
  {
    return $this->csPageTitle;
  }

  private function _getCustomUserFeature($bIsLogged = false)
  {
    $oLogin = CDependency::getCpLogin();

    if($bIsLogged && $oLogin->getUserEmail() == 'nnakazawa@bulbouscell.com')
      $this->addCssFile('/common/style/custom_style.css');

    if(isDevelopment())
        $this->addCssFile ('/common/style/dev.css');

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

  public function getUrlDetail()
  {
    return $this->casUrlDetail;
  }

  private function _addTestJavascript()
  {
    $nMode = (int)getValue('testMode', 0);
    if($nMode === 0)
      return false;

    $asJavascript = array();
    $nCount = 1;

    if(isset($this->casUrl['std']))
    {
      foreach($this->casUrl['std'] as $sUrl)
      {
        if($nMode === 1)
          $asJavascript[] = ' setTimeout("AjaxRequest(\''.$sUrl.'&testMode=1'.'\'); ", '.($nCount*500).'); ';
        else
          $asJavascript[] = ' setTimeout("window.open(\''.$sUrl.'\', \'test_url_'.$nCount.'\'); ", '.($nCount*500).'); ';

        $nCount++;
      }
    }

    if(isset($this->casUrl['ajax']))
    {
      foreach((array)@$this->casUrl['ajax'] as $sUrl)
      {
        if($nMode === 1)
          $asJavascript[] = ' setTimeout("AjaxRequest(\''.$sUrl.'\'); ", '.($nCount*500).'); ';
        else
          $asJavascript[] = ' setTimeout("window.open(\''.$sUrl.'\', \'test_url_'.$nCount.'\'); ", '.($nCount*500).'); ';

        $nCount++;
      }
    }

    $this->addCustomJs($asJavascript);
    return true;
  }

  public function isPage($psUid = '', $psAction = '', $psType = '', $pnPK = 0, $paParams = array())
  {
    if($this->csUid!=$psUid)
      return false;

    if($this->csAction!=$psAction)
      return false;

    if($this->csType!=$psType)
      return false;

    if(is_key($pnPK) && ($this->cnPk!=$pnPK))
      return false;

    if(!empty($paParams))
    {
      foreach ($paParams as $sKey => $sValue)
      {
        $sParam = getValue($sKey, '');
        if($sParam!=$sValue)
          return false;
      }
    }

    return true;
  }

  private function _getMaintenancePage()
  {

    return '<div class="maintenance">
      <div>
      Sorry, the software is undergoing a maintenance.<br />Please try again later.
      </div>
      </div>';
  }
}
