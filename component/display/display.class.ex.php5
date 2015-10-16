<?php

require_once('component/display/display.class.php5');

class CDisplayEx extends CDisplay
{
  private $coCustomContainer = null;

  public function __construct()
  {
    $this->coCustomContainer = CDependency::getComponentByInterface('set_custom_container');
    return true;
  }

  //****************************************************************************
  //****************************************************************************
  // Interfaces and component settings
  //****************************************************************************
  //****************************************************************************


  public function declareUserPreferences()
  {

    $aPrefs[] = array(
        'fieldname' => 'record_number',
        'fieldtype' => 'select',
        'options' => array(
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '100' => '100',
            '200' => '200'
        ),
        'label' => 'Number of records per page',
        'description' => 'Number of records you wish to display on list pages.',
        'value' => '25'
    );

    return $aPrefs;
  }

  public function declareSettings()
  {
    $asSettings[] = array(
        'fieldname' => 'css',
        'fieldtype' => 'text',
        'label' => 'Css Path',
        'description' => 'Filepath of the application CSS',
        'value' => ''
    );
    $asSettings[] = array(
        'fieldname' => 'title',
        'fieldtype' => 'text',
        'label' => 'Title',
        'description' => 'Application title',
        'value' => 'Powered by BC Media',
        'controls' => array('jsFieldNotEmpty' => '')
    );
    $asSettings[] = array(
        'fieldname' => 'logo',
        'fieldtype' => 'image',
        'label' => 'Logo',
        'description' => 'Application logo',
        'value' => 'BulbousCell Master'
    );
    $asSettings[] = array(
        'fieldname' => 'sitename',
        'fieldtype' => 'text',
        'label' => 'App name',
        'description' => 'Application name',
        'value' => ''
    );
    $asSettings[] = array(
        'fieldname' => 'site_email',
        'fieldtype' => 'text',
        'label' => 'Email',
        'description' => 'Contact email',
        'value' => ''
    );
    $asSettings[] = array(
        'fieldname' => 'meta_tags',
        'fieldtype' => 'text',
        'label' => 'Meta tags',
        'description' => 'App meta tags',
        'value' => ''
    );
    $asSettings[] = array(
        'fieldname' => 'meta_desc',
        'fieldtype' => 'text',
        'label' => 'Meta description',
        'description' => 'App meta description',
        'value' => ''
    );

    $asSettings[] = array(
        'fieldname' => 'wide_css',
        'fieldtype' => 'select',
        'options' => array('1' => 'yes', '0' => 'no'),
        'label' => 'Allow wide screen css for this platform.',
        'description' => 'Include a javascript to let user switch between a standard and wide display.',
        'value' => '0'
    );
    $asSettings[] = array(
        'fieldname' => 'wide_size',
        'fieldtype' => 'text',
        'label' => 'Specify size for resize (,)',
        'description' => 'min page width, min page height, width that triggers the change between css',
        'value' => '1260,700,1420'
    );

    $asSettings[] = array(
        'fieldname' => 'wide_css_file',
        'fieldtype' => 'text',
        'label' => 'Specify a css file',
        'description' => 'Choose a specific css file for the wide css',
        'value' => '/common/style/widescreen.css'
    );

    return $asSettings;
  }

  /*
   * Librairie of function to "draw" the page element
   * All components must use this librairy to display elements of the page.
   * Use ogf HTML is forbiden except here.
   */

  //****************************************************************************
  //****************************************************************************
  // Low level display functions
  //****************************************************************************
  //****************************************************************************

  /**
   *
   * @param string $psLabel
   * @param string $psUrl
   * @param array $pasOptions
   * @return string
   */
  public function getLink($psLabel, $psUrl='', $pasOptions = array())
  {
    if(!assert('!empty($psLabel) && is_array($pasOptions)'))
      return '';

   /*
    * TODO: finish the popup and manage the mouseover
    */

    $oPage = CDependency::getCpPage();
    if(empty($psUrl))
      $psUrl = 'javascript:;';

    if($oPage->isAjaxUrl($psUrl))
    {
      if(isset($pasOptions['ajaxLoadingScreen']))
        $sLoadingScreen = $pasOptions['ajaxLoadingScreen'];
      else
        $sLoadingScreen = 'body';

      if(isset($pasOptions['ajaxFormToSerialize']))
        $sForm = $pasOptions['ajaxFormToSerialize'];
      else
        $sForm = '';

      if(isset($pasOptions['ajaxTarget']))
        $sRefresh = $pasOptions['ajaxTarget'];
      else
        $sRefresh = '';

      if(isset($pasOptions['ajaxReload']))
        $bRelaod = true;
      else
        $bRelaod = false;

      if(isset($pasOptions['ajaxCallback']))
        $sCallback = $pasOptions['ajaxCallback'];
      else
        $sCallback = '';


      $sAjaxJs = $this->getAjaxJs($psUrl, $sLoadingScreen, $sForm, $sRefresh, $bRelaod, false, $sCallback);
      $psUrl = 'javascript:;';

      if(isset($pasOptions['onclick']))
        $pasOptions['onclick'] .= ' '.$sAjaxJs;
      else
        $pasOptions['onclick'] = $sAjaxJs;
    }

    $sHTML = '<a href="'.$psUrl.'"';

    if(!empty($pasOptions))
    {
      foreach($pasOptions as $sOption => $sValue)
      {
        $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
      }
    }

    $sHTML.= '>'.$psLabel.'</a>';

    return $sHTML;
  }

  public function getAjaxJs($psUrl, $psLoadingScreen = '', $psFormToSerialize = '', $psZoneToRefresh = '', $psReloadPage = '',  $pbSynch = false, $psCallback = '')
  {
    $sHTML = 'AjaxRequest(\''.$psUrl.'\', \''.$psLoadingScreen.'\', \''.$psFormToSerialize.'\'';
    $sHTML.= ', \''.$psZoneToRefresh.'\', \''.$psReloadPage.'\', \''.$pbSynch.'\', \''.addslashes($psCallback).'\');';
    return $sHTML;
  }

  public function getAjaxPopupJS($psUrl, $psLoadingScreen = '', $pbSynch = false, $psHeight='',$psWidth='', $pasPopupParams = array())
  {
    if($pbSynch)
      $pbSynch = 'true';
    else
      $pbSynch = 'false';

    $sJavascript = 'var oConf = goPopup.getConfig(); ';

    if(!empty($psHeight))
      $sJavascript.= ' oConf.height = '.$psHeight.'; ';

    if(!empty($psWidth))
      $sJavascript.= ' oConf.width = '.$psWidth.'; ';

    if(is_array($pasPopupParams))
    {
      foreach($pasPopupParams as $sParam => $vValue)
      {
        $sJavascript.= ' oConf.'.$sParam.' = \''.$vValue.'\'; ';
      }
    }

    if($psLoadingScreen)
    {
      $sJavascript.= ' oConf.modal = true; ';
      $psLoadingScreen = ''; // don't use the loading screen from ajaxRequest
    }

    return $sJavascript.' goPopup.setLayerFromAjax(oConf,  \''.$psUrl.'\', \''.$psLoadingScreen.'\', '.$pbSynch.'); ';
  }

  public function getAjaxPopupLink($psLabel, $psUrl, $psLoadingScreen = '', $pbSynch = false, $psHeight='',$psWidth='', $pasPopupParams = array())
  {
    return $this->getLink($psLabel, 'javascript:;', array('onclick' => $this->getAjaxPopupJs($psUrl, $psLoadingScreen, $pbSynch, $psHeight,$psWidth, $pasPopupParams)));
  }

  //--------------------------------------
  //--------------------------------------
  // make div functions

  public function getBlocStart($psID = '', $pasOptions = array())
  {

    $sHTML = '<div';

    if($psID!='')
      $sHTML .= ' id=\''.$psID.'\'';

    if(!empty($pasOptions))
    {
      foreach($pasOptions as $sOption=> $sValue)
      {
        if(!is_array($sValue))
          $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
      }
    }
    $sHTML.= '>';

    return $sHTML;
  }

  public function getBlocEnd()
  {
    return '</div>';
  }

  public function getBloc($psID = '', $psContent ='', $asOptions = array())
  {
    return $this->getBlocStart($psID, $asOptions).$psContent.$this->getBlocEnd();
  }

  public function getFloatHack()
  {
    $sHTML= $this->getBlocStart('', array('class' => 'floatHack'));
    $sHTML.= $this->getBlocEnd();
    return $sHTML;
  }

  public function getHtmlContainer($psContent, $psID = '', $pasOptions = array())
  {
    if(isset ($pasOptions['class']))
      $pasOptions['class'].= ' htmlContainer ';
    else
      $pasOptions['class'] = ' htmlContainer ';

    return $this->getBlocStart($psID, $pasOptions) .$psContent. $this->getBlocEnd($psID = '', $pasOptions);
  }

  //--------------------------------------
  //--------------------------------------
  // make div functions


  public function getField($psID = '', $psLabel = '', $psValue = '')
  {
    $sHTML= $this->getBlocStart('',array('class'=>'holderSection'));
      $sHTML.= $this->getBlocStart('',array('class'=>'leftSection'));
      $sHTML.= $this->getText($psLabel);
      $sHTML.= $this->getBlocEnd();

      $sHTML.= $this->getBlocStart('',array('class'=>'rightSection'));
      $sHTML.= $this->getText($psValue);
      $sHTML.= $this->getBlocEnd();
    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }

  public function getSpanStart($psID = '', $asOptions = array())
  {
    $sHTML = '<span';

    if($psID!='')
      $sHTML .= ' id="'.$psID.'"';

    if(!empty($asOptions))
    {
      foreach($asOptions as $sOption=> $sValue)
      {
        $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
      }
    }

    $sHTML.= '>';

    return $sHTML;
  }

  public function getSpanEnd()
  {
    return '</span>';
  }

  public function getSpan($psID = '', $psContent ='', $pasOptions = array())
  {
    return $this->getSpanStart($psID, $pasOptions).$psContent.$this->getSpanEnd();
  }


  public function getFittingBlocStart($psID = '', $pasOptions = array())
  {
    if(isset($pasOptions['class']))
      $pasOptions['class'].= ' fittingBloc';

    return $this->getSpanStart($psID = '', $pasOptions);
  }
  public function getFittingBlocEnd()
  {
    return '</span>';
  }

  //--------------------------------------
  //--------------------------------------
  // Images related functions

  public function getPicture($psPath, $psTitle = '', $psUrl = '',  $asOptions = array())
  {
   if(preg_match('|^(http)|', $psPath))
      $psPath = $psPath;
   elseif(!preg_match('|^('.CONST_CRM_DOMAIN.')|', $psPath))
      $psPath = CONST_CRM_DOMAIN.''.$psPath;

    $sHTML = '<img src="'.$psPath.'" title="'.$psTitle.'" ';

    if(isset($asOptions['onclick']))
    {
      $asLinkOption = array('onclick' => $asOptions['onclick']);
      unset($asOptions['onclick']);
    }
    else
      $asLinkOption = array();

    if(!empty($asOptions))
    {
      foreach($asOptions as $sOption => $sValue)
      {
        $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
      }
    }

    $sHTML.= ' />';

    if(empty($psUrl))
      return $sHTML;
    else
      return $this->getLink($sHTML, $psUrl, $asLinkOption);
  }

  //--------------------------------------
  //--------------------------------------

  public function getListStart($psID = '', $pasOptions = array())
  {
    $sHTML = '<ul';

    if($psID!='')
      $sHTML .= ' id=\''.$psID.'\' ';

    foreach($pasOptions as $sOption=> $sValue)
    {
      $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
    }

    return $sHTML.'>';
  }

  public function getListEnd()
  {
    return '</ul>';
  }

  public function getListItemStart($psID = '', $pasOptions = array())
  {
    $sHTML = '<li';

    if($psID!='')
      $sHTML .= ' id=\''.$psID.'\'';

    foreach($pasOptions as $sOption=> $sValue)
    {
      $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
    }

    return $sHTML.'>';
  }

  public function getListItemEnd()
  {
    return '</li>';
  }

  public function getListItem($pvValue, $psID = '', $pasOptions = array())
  {
    $sHTML = '<li';

    if($psID!='')
      $sHTML .= " id='.$psID.'";

    foreach($pasOptions as $sOption=> $sValue)
    {
      $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
    }

    return $sHTML.'>'.$pvValue.'</li>';
  }

  //--------------------------------------
  //--------------------------------------
  // Text functions

  public function getText($psText, $pasOptions = array(), $pnShortenTo = 0)
  {
    if(!assert('(is_string($psText) || is_float($psText) || is_integer($psText)) && is_array($pasOptions) && is_integer($pnShortenTo)'))
      return '';

    // maybe replace nl by <br /> ...
    $sHTML = '';
    $nLength = strlen($psText);

    if(isset($pasOptions['extra_open_content']))
    {
      $sExtraContent = $pasOptions['extra_open_content'];
      unset($pasOptions['extra_open_content']);
    }

    if(isset($pasOptions['open_content_nl2br']))
    {
      $bNl2br = (bool)$pasOptions['open_content_nl2br'];
    }
    else
      $bNl2br = false;

    if(empty($pnShortenTo) || $nLength < $pnShortenTo)
    {

      if(!empty($pasOptions))
      {
        $sHTML.= '<span ';
        foreach($pasOptions as $sOption=> $sValue)
        {
          $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
        }

        return $sHTML.='>'.$psText.'</span>';
      }
      else
          return $psText;
    }
    else
    {
      $sId = uniqid('CDisplayEx_');
      $sFullContentId = $sId.'_full';
      $sExtraContent = '';

      $sFoldedPic = $this->getResourcePath().'/pictures/details_folded.png';
      $sPicture = $this->getPicture($sFoldedPic);

      $sDisplayedText = $this->getSpanStart($sId, $pasOptions);
      $sDisplayedText.= $this->getLink($sPicture.' '.substr(strip_tags($psText), 0, $pnShortenTo).'...', 'javascript:;', array('class' => 'display_shortened_text',
          'onclick' => '$(\'#'.$sId.'\').fadeToggle(\'fast\', function(){ $(\'#'.$sFullContentId.'\').fadeToggle(\'fast\'); }); '));
      $sDisplayedText.= $this->getSpanEnd();


      $asOptions = $pasOptions;
      if(isset($asOptions['style']))
        $asOptions['style'].= ' display:none; ';
      else
        $asOptions['style'] = ' display:none; ';

      $sOpenedPic = $this->getResourcePath().'/pictures/details_opened.png';
      $sPicture = $this->getPicture($sOpenedPic);

      if($bNl2br)
        $psText = nl2br($psText);

      $sDisplayedText.= $this->getSpanStart($sFullContentId, $asOptions);
      $sDisplayedText.= $this->getLink($sPicture.' '.$psText, 'javascript:;', array('class' => 'display_shortened_text',
          'onclick' => '$(\'#'.$sId.'\').fadeToggle(\'fast\', function(){ $(\'#'.$sFullContentId.'\').fadeToggle(\'fast\'); }); '));

      $sDisplayedText.= $sExtraContent;
      $sDisplayedText.= $this->getSpanEnd();

      return $sDisplayedText;
    }
  }

  public function getSpacedText($pnSliceSize, $psText, $pasOptions = array(), $pnShortenTo = 0)
  {
    if(!assert('is_integer($pnSliceSize) && !empty($psText)'))
      return '';

    $nSize = strlen($psText);
    if($nSize < $pnSliceSize)
      return $this->getText($psText, $pasOptions, $pnShortenTo);

    $sExtractedText = substr($psText, 0, $pnSliceSize);

    if(strpos($sExtractedText, ' ') === false)
      $sSlicedText = $sExtractedText.' '.substr($psText, $pnSliceSize+1, -1);
    else
      $sSlicedText = $psText;

    return $this->getText($sSlicedText, $pasOptions, $pnShortenTo);
  }

  public function getTitle($psText, $psTitleType = 'h3', $pbFullLine = false, $pasOptions = array())
  {
    $sClass = 'title ';

    if(isset($pasOptions['class']))
      $sClass.= $pasOptions['class'];

    if(isset($pasOptions['onclick']))
      $sClass.= ' titleToggle ';

    $sHTML = '<div class="'.$sClass.'" ';

    if(!isset($pasOptions['float']))
      $sFloat = 'left';
    else
      $sFloat = $pasOptions['float'];

    foreach($pasOptions as $sOption=> $sValue)
    {
      $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
    }

    if(isset($pasOptions['isHtml']) || !empty($pasOptions['isHtml']))
     $psText = html_entity_decode($psText);

    if($pbFullLine)
      return $sHTML.'><div class = "'.$psTitleType.'" style="float:'.$sFloat.'; width:98%;">'.$psText.'</div><div class="floatHack"></div></div>';

    return $sHTML.'><div class = "'.$psTitleType.'" style="float:'.$sFloat.';" >'.$psText.'</div><div class="floatHack"></div></div>';
  }

/* --------------------------------------------------------------------
 *  GETTABS() - 2013-03-15
 * --------------------------------------------------------------------
 * - Auto selects the first tab if it's not specified through parameter
 * - Two templates available 'inline' and 'vertical'
 * - $psDisplayall add a 'display all' link
 * - Set $psSelected to 'all' if you wish to display all tabs
 * - Generic javascripts and css
 */

 public function getTabs($psId, $pasTabs, $psSelected = '', $psTemplate = 'inline', $psDisplayall = false)
 {
    if(!assert('is_array($pasTabs) && !empty($pasTabs)'))
      return '';

    if(empty($psId))
      $psId = uniqid();

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile('/common/style/tabs.css');

    $sJavascript = '
    function tabClick(poLi)
    {
      var target = $(poLi).attr(\'rel\');
      $(poLi).closest(\'.tabs\').find(\'> .contents > .selected\').removeClass(\'selected\');
      $(poLi).closest(\'.tabs\').find(\'> .contents > [rel=\'+target+\']\').addClass(\'selected\');

      $(poLi).siblings(\'.selected\').removeClass(\'selected\');
      $(poLi).addClass(\'selected\');

      var ajax = $(poLi).attr(\'link\');
      if (ajax!=undefined)
      {
        AjaxRequest(ajax,\'body\',false,"area_"+target);
      }

      return false;
    } ';

    $oPage->addCustomJs($sJavascript);

    // TODO : Use sessions to remember last selected tab


    if(empty($psSelected))
      $bDisplayFirst = true;
    else
      $bDisplayFirst = false;


    // Initializing two strings that will be filled when browsing the tab array
    $sTabLinks = $this->getListStart($psId.'_links', array('class' => 'links'));
    $sTabContents = $this->getBlocStart($psId.'_contents',array('class' => 'contents'));

    //we add a "all" tab that comtains the content of all other tabs
    //and check if it s the tab to display by default
    if($psDisplayall)
    {
      if($bDisplayFirst || $psSelected == 'all')
      {
        $sTabLinks.= $this->getListItemStart('', array('rel' => 'all', 'class' => 'displayall selected', 'onclick' => 'tabClick(this);'));
        $sTabLinks.= $this->getText('All');
        $sTabLinks.= $this->getListItemEnd();
        $sTabContentAll = $this->getBlocStart('', array('rel' => 'all', 'class' => 'selected'));

        //we' re've displayed the "all" tab first, no need to check further
        $bDisplayFirst = false;
      }
      else
      {
        $sTabLinks.= $this->getListItemStart('', array('rel' => 'all', 'class' => 'displayall', 'onclick' => 'tabClick(this);'));
        $sTabLinks.= $this->getText('All');
        $sTabLinks.= $this->getListItemEnd();
        $sTabContentAll = $this->getBlocStart('', array('rel' => 'all'));
      }
    }

    $nCount = 0;
    foreach($pasTabs as $pasTab)
    {
      $pasTab['options']['rel'] = $pasTab['label'];
      set_array($pasTab['options']['onclick'], ' tabClick(this); ', ' tabClick(this); ');

      if($bDisplayFirst && $nCount == 0)
      {
        set_array($pasTab['options']['class'], '');
        $pasTab['options']['class'].= ' selected';

        $sTabLinks.= $this->getListItemStart('', $pasTab['options']);
        $sTabLinks.= $this->getText($pasTab['title']);
        $sTabLinks.= $this->getListItemEnd();

        $sTabContents.= $this->getBlocStart('', array('rel' => $pasTab['label'], 'class' => 'selected'));
      }
      else
      {
        if($pasTab['label'] == $psSelected)
        {
          set_array($pasTab['options']['class'], '');
          $pasTab['options']['class'].= ' selected';

          $sTabLinks.= $this->getListItemStart('', $pasTab['options']);
          $sTabLinks.= $this->getText($pasTab['title']);
          $sTabLinks.= $this->getListItemEnd();

          $sTabContents.= $this->getBlocStart('', array('rel' => $pasTab['label'], 'class' => 'selected'));
        }
        else
        {
          $sTabLinks.= $this->getListItemStart('', $pasTab['options']);
          $sTabLinks.= $this->getText($pasTab['title']);
          $sTabLinks.= $this->getListItemEnd();

          $sTabContents.= $this->getBlocStart('', array('rel' => $pasTab['label']));
        }
      }

      $sTabContents.= $this->getText($pasTab['content']);
      $sTabContents.= $this->getFloatHack().$this->getBlocEnd();

      if($psDisplayall)
        $sTabContentAll.= $this->getText($pasTab['content']);

      $nCount++;
    }

    if($psDisplayall)
    {
      $sTabContentAll.= $this->getFloatHack().$this->getBlocEnd();
      $sTabContents.= $sTabContentAll;
    }

    $sTabLinks.= $this->getListEnd();
    $sTabContents.= $this->getFloatHack().$this->getBlocEnd();

    // Writing HTML code to be returned
    $sHTML = $this->getBlocStart($psId, array('class' => 'tabs '.$psTemplate));
    $sHTML.= $sTabLinks;
    $sHTML.= $sTabContents;
    $sHTML.= $this->getFloatHack();
    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }

  public function getTitleLine($psText, $psPicture = '', $pasOptions = array(), $psMenu = '', $psButton = '')
  {
    if(!assert('is_string($psMenu)'))
      return '';

    if(!assert('is_string($psButton)'))
      return '';

    if(!assert('is_string($psPicture)'))
      return '';

    if(!assert('is_string($psText)'))
      return '';

    if(!assert('is_array($pasOptions)'))
      return '';

    if(empty($psPicture))
     return $this->getTitle ($psText, 'h1', true, $pasOptions);

    if(!isset($pasOptions['isHtml']) || !$pasOptions['isHtml'])
      $psText = htmlentities($psText);

    if(!isset($pasOptions['class']) || empty($pasOptions['class']))
      $pasOptions['class'] = 'h1';

      $sHTML = '<div class="titleLine shadow">';
      $sHTML.= '<div class="titleLinePicture">'.$this->getPicture($psPicture).'</div>';
      $sHTML.= '<div class="titleLineText">';

        $sHTML.= '<div ';
        foreach($pasOptions as $sOption=> $sValue)
        {
          $sHTML.= ' '.$sOption.'="'.$sValue.'" ';
        }
        $sHTML.= '>'.$psText.'</div>';

      if(isset($psMenu))
        $sHTML .= $psMenu;

      $sHTML.= '</div>';

      if(isset($psButton))
        $sHTML .= $psButton;

      $sHTML.= '<div class="floatHack"></div>';
    $sHTML.= '</div>';

    return $sHTML;
  }

  //previously getCarriageReturn()
  public function getCR($pnNumber = 1)
  {
    $sHTML = '';

    for($nCount = 0; $nCount < $pnNumber; $nCount++)
      $sHTML.= '<br />';

    return $sHTML;
  }

  public function getSpace($pnNumber = 1)
  {
    $sHTML = '';

    for($nCount = 0; $nCount < $pnNumber; $nCount++)
      $sHTML.= '&nbsp;';

    return $sHTML;
  }

  public function getRedirection($psUrl, $psTimer = 0, $psMessage = '')
  {
    //make sure we never redirect to a ajax page
    $psUrl.= '&pg=normal';

    if(empty($psTimer))
    {
      @header('location:'.$psUrl);
      return '<script> document.location.href= \''.$psUrl.'\'; </script><a href="'.$psUrl.'">Click here to be redirected</a>';
    }

    $oHTML = CDependency::getCpHtml();
    $sHTML = '';

    if(!empty($psMessage))
      $sHTML.= $oHTML->getBlocMessage($psMessage);

    $sHTML.= $oHTML->getCR(5);
    $sHTML.= '<script> setTimeout("document.location.href = \''.$psUrl.'\';", '.$psTimer.'); </script>';
    $sHTML.= '<span class="system_redirect_message"><a href="'.$psUrl.'">Click here</a> if nothing happens in the next 15 seconds.</span>';

    $sHTML.= $oHTML->getCR(2);
    $sHTML.= $oHTML->getBlocStart('', array('style' => 'text-align: center;'));
    $sHTML.= $oHTML->getPicture(CONST_PICTURE_LOADING);
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  //****************************************************************************
  //****************************************************************************
  // High level display functions
  //****************************************************************************
  //****************************************************************************

  public function getMeta($pbIsLogged = false, $pasJsFile = array(), $pasCustomJs = array(), $pasCssFile = array(), $pasCustomCss = array(), $pasMeta = array(), $pasPageParam = array())
  {
    if(isset($pasMeta['title']) && !empty($pasMeta['title']))
      $sCustomPageTitle = $pasMeta['title'];
    else
      $sCustomPageTitle = '';

    if($_SERVER['SERVER_ADDR'] == '172.31.29.60')
      $sCustomPageTitle = '- '.$sCustomPageTitle;
    elseif($_SERVER['SERVER_ADDR'] == '172.31.29.61')
      $sCustomPageTitle = '+ '.$sCustomPageTitle;

    if(isset($pasMeta['meta_desc']) && !empty($pasMeta['meta_desc']))
      $sCustomDescription = $pasMeta['meta_desc'];
    else
      $sCustomDescription = '';

    if(isset($pasMeta['meta_tags']) && !empty($pasMeta['meta_tags']))
      $sCustomKeywords = $pasMeta['meta_tags'];
    else
      $sCustomKeywords = '';

    //$sTime = '?n='.time();    //after an update, to force refresh css and js files
    $sTime = '';

    $date_obj = new DateTime("+12 hours", new DateTimeZone('Greenwich'));
    // html 5 doctype --> need one for jQuery , so ...
    $sHTML = '<!DOCTYPE html>
    <html>
    <head>
    <title>'.$sCustomPageTitle.'</title>
    <meta name="description" content="'.$sCustomDescription.'"/>
    <meta name="keywords" content="'.$sCustomKeywords.'"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta content="utf-8" http-equiv="encoding">
    <meta http-equiv="Cache-control" content="private">
    <meta http-equiv="Cache-control" content="max-age=43200">
    <meta http-equiv="expires" content="'.$date_obj->format('D, d M Y H:i:s T').'">

    <link rel="shortcut icon" href="'.CONST_HEADER_FAVICON.'" type="image/vnd.microsoft.icon" />
    <link rel="shortcut icon" href="'.CONST_HEADER_FAVICON.'" type="image/x-icon" />
    <link rel="icon" href="'.CONST_HEADER_FAVICON.'" type="image/vnd.microsoft.icon" />
    <link rel="icon" href="'.CONST_HEADER_FAVICON.'" type="image/x-icon" />

    <link rel="stylesheet" href="/common/style/template.css'.$sTime.'" type="text/css" media="screen" />
    <link rel="stylesheet" href="'.CONST_PATH_CSS_JQUERYUI.$sTime.'" type="text/css" media="screen" />
    <link rel="stylesheet" href="/conf/custom_config/'.CONST_WEBSITE.'/'.CONST_WEBSITE.'.css'.$sTime.'" type="text/css" media="screen" />
    <link rel="stylesheet" href="/common/style/style.css'.$sTime.'" type="text/css" media="screen" />';

    //include logged in css
    if($pbIsLogged && CONST_DISPLAY_HAS_LOGGEDIN_CSS && !getValue(CONST_PAGE_NO_LOGGEDIN_CSS))
    {
      $sHTML.= '<link rel="stylesheet" href="/common/style/private.css'.$sTime.'" type="text/css" media="screen" />';
      $sHTML.= '<link rel="stylesheet" href="/conf/custom_config/'.CONST_WEBSITE.'/'.CONST_WEBSITE.'_private.css'.$sTime.'" type="text/css" media="screen" />';
    }

    $asCssFile = array();
    foreach($pasCssFile as $sFileName)
    {
      $sHTML.= '<link rel="stylesheet" href="'.$sFileName.'" />';

      $asFileDate = parse_url($sFileName);
      $asCssFile[] = $asFileDate['path'];
    }

    if(!empty($pasCustomCss))
    {
      $sHTML.= '<style rel="stylesheet">'.implode("\n", $pasCustomCss).'</style>';
    }

    //css gradient hack for ie9
    $sHTML.= '<!--[if gte IE 9]><style type="text/css">.gradient { filter: none; }</style><![endif]-->

    <script type="text/javascript" src="'.CONST_PATH_JS_JQUERY.$sTime.'"></script>
    <script type="text/javascript" src="'.CONST_PATH_JS_JQUERYUI.$sTime.'"></script>
    <script type="text/javascript" src="/common/js/yepnope.1.5.4-min.js'.$sTime.'"></script>
    <script type="text/javascript" src="/common/js/jquery.iframe-transport.min.js'.$sTime.'"></script>
    <script type="text/javascript" src="/component/form/resources/js/tinymce/jquery.tinymce.min.js'.$sTime.'"></script>
    <script type="text/javascript" src="/component/form/resources/js/tinymce/tinymce.min.js'.$sTime.'"></script>
    <script type="text/javascript" src="/component/form/resources/js/jquery.tokeninput.js'.$sTime.'"></script>
    <script type="text/javascript" src="'.CONST_PATH_JS_POPUP.$sTime.'"></script>
    <script type="text/javascript" src="/common/js/velocity.min.js'.$sTime.'"></script>
    <script type="text/javascript" src="'.CONST_PATH_JS_COMMON.$sTime.'"></script>';

    $asJsFile[] = CONST_PATH_JS_JQUERY;
    $asJsFile[] = CONST_PATH_JS_JQUERYUI;
    $asJsFile[] = '/component/form/resources/js/tiny_mce/jquery.tinymce.js';
    $asJsFile[] = '/component/form/resources/js/tiny_mce/tiny_mce.js';
    $asJsFile[] = '/component/form/resources/js/jquery.tokeninput.js';
    $asJsFile[] = CONST_PATH_JS_POPUP;
    $asJsFile[] = CONST_PATH_JS_COMMON;

    foreach($pasJsFile as $sFileName)
    {
      $sHTML.= '<script type="text/javascript" src="'.$sFileName.'"></script>';
      $asFileDate = parse_url($sFileName);
      $asJsFile[] = str_replace('//', '/', $asFileDate['path']);
    }

    if(!empty($pasCustomJs))
    {
      $sHTML.= '<script type="text/javascript">';
      foreach($pasCustomJs as $sJsCode)
      {
        $sHTML.= "\n".$sJsCode."\n";
      }
      $sHTML.= '</script>';
    }

    // ==============================================================================================
    //allow page size management (js and/or php)
    if($pbIsLogged)
    {
      $oSetting = CDependency::getComponentByName('settings');
      $asSetting = $oSetting->getSettings(array('wide_size', 'wide_css', 'wide_css_file', 'wide_css_on'), false);

      if(!isset($asSetting['wide_css_file']) || empty($asSetting['wide_css_file']))
        $asSetting['wide_css_file'] = '/common/style/widescreen.css';

      //dump($asSetting);

      if(isset($asSetting['wide_css']) && (bool)$asSetting['wide_css'])
      {
        if(isset($asSetting['wide_css_on']) && (bool)$asSetting['wide_css_on'])
        {
          $sHTML.= '<link id="widecss" rel="stylesheet" href="'.$asSetting['wide_css_file'].'" type="text/css" media="screen" />';
          $pasPageParam['wide-css'] = 1;
        }
        else
          $pasPageParam['wide-css'] = 0;

        $sHTML.= '<script type="text/javascript" src="/common/js/page_resize.js"></script>';
        $sHTML.= '<script type="text/javascript"> var sWideCssFile = "'.$asSetting['wide_css_file'].'";
          var asPageParam = new Array('.$asSetting['wide_size'].');
          sizeManagement(asPageParam, '.(int)CONST_PAGE_USE_WINDOW_SIZE.'); </script>';
      }
    }

    //if there s no wide_css management, we bind the update on resize()
    if(!isset($asSetting['wide_css']) && empty($asSetting['wide_css']) && CONST_PAGE_USE_WINDOW_SIZE)
    {
      $sHTML.= '<script type="text/javascript">
        $(window).resize(function(event)
        {
           //resize event is triggered by jquery-ui dialog, we dont treat those
           if($(event.target).hasClass("ui-resizable"))
             return true;

           updatePhpWindowSize();
         });
      </script>';
    }
    // ==============================================================================================

    $sHTML.= '<script type="text/javascript">';
    $sHTML.= 'var gasJsFile = ["'.implode('", "', $asJsFile).'"]; ';
    $sHTML.= 'var gasCssFile = ["'.implode('", "', $asCssFile).'"]; ';
    $sHTML.= 'var goPopup = new CPopup(); ';
    //$sHTML.= ' $(document).tooltip({items: \'.tooltip\', position: {my: "center top",at: "center bottom+5",}, show: {duration: "fast"},hide: {effect: "hide"}}); ';
    $sHTML.= '</script>';

    //For controlling the anchor tag
    $sHTML.= '<script type="text/javascript">';
    $sHTML.= 'var anchorId = window.location.hash;';
    $sHTML.= 'if(anchorId){';
    $sHTML.= '  $(document).ready( function(){';
        $sHTML.= '$(anchorId).click();';
      $sHTML.= '});';
    $sHTML.= '}';
    $sHTML.= '</script>';
    $sHTML.= CONST_WEBSITE_GOOGLE_ANALYTICS;

    $sHTML.= '</head>';

    $sHTML.= '<body id="body" ';

    $pasPageParam['logged']=(int)$pbIsLogged;

    foreach($pasPageParam as $sParam => $vValue)
      $sHTML.= ' '.$sParam.'="'.$vValue.'" ';

    $sHTML.= '>';

    $sHTML.= $this->getBlocStart('pageContainerId');
    $sHTML.= $this->getBlocStart('pageMainId');

    return $sHTML;
  }

  public function getHeader($pbIsLogged = false, $pasPageParam = array())
  {
    $oMenu = CDependency::getComponentByInterface('display_menu');

    $sHTML = '';

      if(!$pbIsLogged)
      {
        $sDiv = $this->getBloc('websiteLogo', '&nbsp;');
        $sHTML.= $this->getLink($sDiv, CONST_WEBSITE_LOGO_URL);
      }
      else
      {
        $sHTML.= $this->getBlocStart('headerId');
        $sHTML .= $this->getLogo();
        if(!empty($oMenu))
        {
          $sHTML .= $oMenu->getMenuNav('top');
          $sHTML .= $oMenu->getMenuAction('top');
        }

        $sHTML .= $this->_getUserMenuBloc($pbIsLogged);
        $sHTML.= $this->getFloatHack();
        $sHTML.= $this->getBlocEnd();
      }

      $sHTML.= $this->getFloatHack();

    return $sHTML;
  }

  public function getLogo()
  {
    $oPage = CDependency::getCpPage();

    $sPicture = $this->getPicture(CONST_HEADER_LOGO, $oPage->getPageTitle());
    $sHTML = $this->getBlocStart('logo');
    $sHTML.= $this->getLink($sPicture, CONST_WEBSITE_LOGO_URL);
    $sHTML.= $this->getBlocEnd();
    return $sHTML;
  }

  public function getUserMenuBloc($pbIsLogged)
  {
    return $this->_getUserMenuBloc($pbIsLogged);
  }

  private function _getUserMenuBloc($pbIsLogged)
  {
    if(!$pbIsLogged)
       return '';

    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();

    $sHTML = $this->getBlocStart('userPannel');
    $sUser = $oLogin->getCurrentUserName();
    $asUserLogins = $oLogin->getUserLogins();

      $sHTML.= $this->getBlocStart('', array('class' => 'userName'));
      if(count($asUserLogins) > 0)
      {
        $asUser = array_keys($oLogin->getUserList(0, true, true));

        $sHTML.='Logged as <select onchange="AjaxRequest($(this).val());">';
        $sHTML.= '<option>'.$sUser.'</option>';
        foreach($asUserLogins as $nUserLoginFk => $sUserLoginName)
        {
          if(in_array($nUserLoginFk, $asUser))
          {
            $sUrl = $oPage->getAjaxUrl('579-704', CONST_ACTION_RELOG, '', $nUserLoginFk);
            $sHTML.='<option value="'.$sUrl.'");">'.$sUserLoginName.'</option>';
          }
        }
        $sHTML.='</select>';
      }
      else
      {
        $sHTML.='Welcome <span>'.$sUser.'</span>';
      }
      $sHTML.= $this->getBlocEnd();

      //add a link to logout. Made like this so it's easy to customize with css hidding text, changing picture, moving text left/right/center...
      $sUrl = $oPage->getAjaxUrl('579-704', CONST_ACTION_LOGOUT, '', 0, array('logout' => 1));
      $sLink = $this->getLink('Logout', $sUrl);
      $sHTML.= $this->getBloc('', $sLink, array('class' => 'logoutLink'));

    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }

  public function getComponentStart($pbIsLogged, $pasParam = array())
  {
    if($pbIsLogged)
    {
      $sHTML = $this->getBlocStart('componentContainerId', $pasParam);
    }
    else
    {
      if(isset($pasParam['class']))
        $pasParam['class'].= ' containerUnlogged';
      else
        $pasParam['class'] = 'containerUnlogged';

      $sHTML = $this->getBlocStart('componentContainerId', $pasParam);
    }

    $sHTML.= $this->getBlocStart('', array('class' => 'componentMainContainer'));


    if($this->coCustomContainer)
      $sHTML.= $this->coCustomContainer->getCustomContainerStart();

    return $sHTML;
  }

  public function getComponentEnd()
  {
    $sHTML = '';

    if($this->coCustomContainer)
      $sHTML.= $this->coCustomContainer->getCustomContainerEnd();

    $sHTML.= $this->getBlocEnd();
    $sHTML.= $this->getBlocEnd();
    return $sHTML;
  }

  public function getFooter()
  {
    $oSettings = CDependency::getComponentByName('settings');
    $oMenu = CDependency::getComponentByInterface('display_menu');

    $asFooter = $oSettings->getSettings('footer');

    $sHTML = $this->getFloatHack();

    //closing blocs opened in header
    $sHTML.= $this->getBlocEnd();
    $sHTML.= $this->getBlocEnd();

    $sBottomMenu = $oMenu->getMenuNav('bottom');

    $sHTML .= $this->getBlocStart('footerId');
      if(!empty($sBottomMenu))
      {
        $sHTML.= $sBottomMenu;
      }
      if(CONST_DISPLAY_VERSION)
      {
        $sHTML.= $this->getBlocStart('', array('class'=>'versionBloc'));
        $sHTML.= $this->getText(CONST_WEBSITE.' v. '.CONST_VERSION);
        $sHTML.= $this->getBlocEnd();
      }
      if(function_exists('getCustomWebsiteFooter'))
        $sHTML .= getCustomWebsiteFooter($asFooter);
    $sHTML .= $this->getBlocEnd();


    $sHTML.= $this->getBlocStart('ajaxErrorContainerId', array('class' => 'ajaxErrorBlock'));
      $sHTML.= $this->getBlocStart('ajaxErrorInnerId', array('class' => 'notice2'));
      $sHTML.= $this->getBlocStart();

      $sHTML.= $this->getBlocStart('', array('style' => 'float:right; '));
      $sHTML.= $this->getLink('Close', 'javascript:;', array('onclick' => "setCoverScreen(false); $('#ajaxErrorContainerId').hide();"));
      $sHTML.= $this->getBlocEnd();

      $sHTML.= $this->getTitle('Oops, an error occured', 'h2', true);
      $sHTML.= $this->getCR();
      $sHTML.= $this->getText("An unknown error occured while executing your last action.");
      $sHTML.= $this->getCR();
      $sHTML.= $this->getText("If you're seeing this message for the first time, please try to reload the page or close your web browser before starting again.");
      $sHTML.= $this->getCR();
      $sHTML.= 'In the other case, please contact the administrator or report the problem using <a href="javascript:;" onclick=\' $("#dumpFormId").submit();\'>this form</a>.';
      $sHTML.= '<form name="dumpForm" id="dumpFormId" target="_blank" method="post" action="/error_report.php5" class="hidden"
        onsubmit=" if(!$(this).attr(\'loaded\'))
        {
          event.preventDefault();
          var oHead = $(\'head\').clone();
          $(oHead).find(\'script\').remove();

          $(\'#dump_html_id\').val(\'<html><head>\' + $(oHead).html() + \'</head><body>\' + $(body).html() + \'</body></html>\');
          $(this).attr(\'loaded\', 1).submit();
        }" >
        <input type="hidden" name="dump" id="dumpId" />
        <textarea class="_hidden" name="dump_html" id="dump_html_id"></textarea></form>';
      $sHTML.= $this->getBlocEnd();
      $sHTML.= $this->getBlocEnd();
      $sHTML.= $this->getBlocEnd();

      $sHTML.= $this->getBlocStart('embedPopupId');
      $sHTML.= $this->getBlocEnd();

      $sHTML.= $this->getBlocStart('popupBlockId', array('style' => 'display:none; position:absolute;'));
      $sHTML.= $this->getBlocEnd();

      $sHTML.= '
        <div id="loadingScreenAnimation">
          <img src="'.CONST_WEBSITE_LOADING_PICTURE.'"/>
        </div>
      </div>

      <div id="loadingScreen">

        <div id="loadingScreenAnimation">
          <img src="'.CONST_WEBSITE_LOADING_PICTURE.'"/>
        </div>
      </div>';

    if(isDevelopment())
    {
      /*include __DIR__.'/resources/debugbar.inc.php5';
      $sHTML.= getDebugBar();*/
    }

    $sHTML.= '</body></html>';
    return $sHTML;
  }

  /**
   * $pasNavigation is the current component navigation path array
   * @param array $pasNavigation
   * @param string $psAction
   * @param string $psType
   * @param integer $pnPk
   * @return string HTML
  */
  public function getNavigationPath($pasNavigation, $psUid, $psAction = '', $psType = '', $pnPk = 0)
  {
    if(!assert('is_array($pasNavigation)') || empty($pasNavigation))
      return '';

    //dump($pasNavigation);
    $asItems = array();

    if(isset($pasNavigation['*']['*']['*']['*']))
      $asItems = $pasNavigation['*']['*']['*']['*'];

    if(isset($pasNavigation[$psUid]['*']['*']['*']))
      $asItems = array_merge($asItems, $pasNavigation[$psUid]['*']['*']['*']);

    if(isset($pasNavigation[$psUid][$psAction]['*']['*']))
      $asItems = array_merge($asItems, $pasNavigation[$psUid][$psAction]['*']['*']);

    if(isset($pasNavigation[$psUid][$psAction][$psType]['*']))
      $asItems = array_merge($asItems, $pasNavigation[$psAction][$psType]['*']);

    if(isset($pasNavigation[$psUid][$psAction][$psType][$pnPk]))
      $asItems = array_merge($asItems, $pasNavigation[$psUid][$psAction][$psType][$pnPk]);

    if(empty($asItems))
      return '';

    $oPage = CDependency::getCpPage();

    //dump($asItems);
    foreach($asItems as $nKey => $asItem)
    {
      if(!isset($asItem['option']) || empty($asItem['option']))
        $asItem['option'] = array();


      if(!isset($asItem['url']) || empty($asItem['url']))
      {
        if(!empty($asItem['option']['ajax']))
          $asItem['url'] = $oPage->getAjaxUrl($psUid, $psAction, $psType, $pnPk);
        else
          $asItem['url'] = $oPage->getUrl($psUid, $psAction, $psType, $pnPk);
      }

      $asItems[$nKey] = $this->getBloc('', $this->getLink($asItem['label'], $asItem['url'], $asItem['option']), array('class' => 'item'));
    }

    $sSeparator = ' <div class="separator">&nbsp;</div> ';
    return $this->getBloc('', implode($sSeparator, $asItems), array('class' => 'navigation_path_container'));
  }

  public function getNoContentMessage()
  {
    //closing blocs opened in header
    $sHTML = $this->getBlocMessage("Sorry, the page you're requesting doesn't exist.");

    return $sHTML;
  }

  public function getEmbedPage($psUrl)
  {
    $sHTML = '<iframe src="'.$psUrl.'" id="embedFrameId" class="embedFrame" scrolling="auto" frameborder="0" width="800" height="500" ';

    $sHTML.= 'onload="
    var nwidth = $(this).parent().width() - 30;
    var nHeight = $(document).height() -125;
    $(this).animate({\'height\':nHeight, \'width\':nwidth}, 1, function(){ $(this).fadeIn(); });" ';
    $sHTML.= ' id="embedIframeId" class="embedIframe"></iframe>';

    return $sHTML;
  }

  public function getBlocMessage($psMessage, $pbIsHtml = false)
  {
    $sHTML = $this->getBlocStart('', array('class'=>'blocMessage'));
    if($pbIsHtml)
      $sHTML.= $psMessage;
    else
      $sHTML.= $this->getText($psMessage);
    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }


  public function getErrorMessage($psMessage, $pbHtml = false)
  {
    $sHTML = $this->getBlocStart('', array('class'=>'notice2'));

    if($pbHtml)
      $sHTML.= $psMessage;
    else
      $sHTML.= $this->getText($psMessage);

    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }

  public function getMessage($psMessage, $psURL = '', $psType = '')
  {
    switch($psType)
    {
      case '':
      case 'info':
        $sClass = 'notice';
        $sPopup = 'setNotice';
      break;

      case 'error':
        $sClass = 'notice2';
        $sPopup = 'setErrorMessage';
      break;
    }

    $sHTML = $this->getBlocStart('', array('class' => $sClass));
    $sHTML.= $this->getText($psMessage);
    $sHTML.= $this->getBlocEnd();


    $sHTML.= '<script>$(document).ready(function(){ goPopup.'.$sPopup.'("'.$psMessage.'", "'.$psURL.'", "'.$sClass.'"); });</script>';

    return $sHTML;
  }

  public function utf8_strcut($psString, $pnStart = 0, $nLength = 25, $psTrimmarker = '...')
  {
    if(!assert('is_integer($pnStart) && is_integer($nLength)')&& !assert('!empty($nCount)') )
      return '';

    if(empty($psString))
      return '';

    if(mb_strlen($psString) <= $nLength)
      $psTrimmarker = '';

    $sString = mb_substr($psString, $pnStart, $nLength);
    $sString = @iconv('UTF-8', 'UTF-8//IGNORE', $sString);

    return $sString.$psTrimmarker;
  }

  public function getNiceTime($psDate = '', $pnTime = 0, $pbAdvDisplay = false, $pbAdvDisplayPic = false, $pnConvertUntil = 999)
  {
    if((empty($psDate) || $psDate == '0000-00-00' || $psDate == '0000-00-00 00:00:00') && empty($pnTime))
    {
      return ' - ';
    }

    if(!empty($psDate))
      $nTime = (int)strtotime($psDate);
    else
      $nTime = (int)$pnTime;

    $nNow = (int)time();
    $nTimeDif = ($nNow - $nTime);

    $sDateNow = date('Y-m-d', $nNow);
    $sDate = date('Y-m-d', $nTime);

    if($sDate > $sDateNow)
    {
      $sPrefix = 'in ';
      $sSuffix = '';
    }
    else
    {
      $sPrefix = '';
      $sSuffix = ' ago';
    }

    if($pbAdvDisplay)
    {
      $sHtmlStart = '<a href="javascript:;" class="niceTimeLink ';
      if($pbAdvDisplayPic)
      {
        $sHtmlStart.= ' niceTimePic ';
      }
      $sHtmlStart.= '">';

      $sTimeDisplay = date('H:i:s', $nTime);
      if($sTimeDisplay == '00:00:00')
        $sHtmlEnd = '<div class="niceTimeDetail">'.date('Y-m-d', $nTime).'</div></a>';
      else
        $sHtmlEnd = '<div class="niceTimeDetail">'.date('Y-m-d H:i:s', $nTime).'</div></a>';
    }
    else
    {
      $sHtmlStart = '';
      $sHtmlEnd = '';
    }

    //text for todays dates
    if($sDateNow == $sDate)
    {
       if($nTimeDif <= 60)
         return $sHtmlStart.$sPrefix.'a few sec.'.$sSuffix.$sHtmlEnd;

       if($nTimeDif > 60 && $nTimeDif <= 3600)
         return $sHtmlStart.$sPrefix.floor($nTimeDif/60).' minutes'.$sSuffix.$sHtmlEnd;

       if($nTimeDif > 3600 && $nTimeDif <= 86400)
         return $sHtmlStart.$sPrefix.floor($nTimeDif/3600).' hours'.$sSuffix.$sHtmlEnd;
    }

    $oDatetNow = new DateTime();
    $oDate = new DateTime($sDate);
    $oDateDiff = $oDatetNow->diff($oDate);

    $nDiffDays = (int)$oDateDiff->format('%a');
    if($pnConvertUntil >= 15 && $nDiffDays <= 15)
      return $sHtmlStart.$sPrefix.$nDiffDays.' days'.$sSuffix.$sHtmlEnd;

    if($pnConvertUntil >= 100 && $nDiffDays <= 100)
      return $sHtmlStart.$sPrefix.floor($nDiffDays/7).' weeks'.$sSuffix.$sHtmlEnd;

    if($pnConvertUntil >= 365 && $nDiffDays <= 365)
       return $sHtmlStart.$sPrefix.$oDateDiff->format('%m').' months'.$sSuffix.$sHtmlEnd;

    if($pnConvertUntil >= 999)
      return $sHtmlStart.$sPrefix.$oDateDiff->format('%y').' years'.$sSuffix.$sHtmlEnd;

    return $sDate;
  }

  // Returns a nice display for a number of seconds
  // Ex: 3mn 30sec
  public function getNiceDuration($pnDuration)
  {
    if(!assert('is_numeric($pnDuration)'))
      return '';

    $sDuration = '';
    $nDays = floor($pnDuration / 86400);
    $pnDuration -= $nDays * 86400;
    $nHours = floor($pnDuration / 3600);
    $pnDuration -= $nHours * 3600;
    $nMinutes = floor($pnDuration / 60);
    $nSeconds = $pnDuration - $nMinutes * 60;

    if($nDays > 0) {
      $sDuration .= $nDays . ' days';
    }
    if($nHours > 0) {
      $sDuration .= ' ' . $nHours . ' hours';
    }
    if($nMinutes > 0) {
      $sDuration .= ' ' . $nMinutes . ' mn';
    }
    if($nSeconds > 0) {
      $sDuration .= ' ' . $nSeconds . ' s';
    }
    return $sDuration;
  }

  //****************************************************************************
  //****************************************************************************
  //Form managment
  //****************************************************************************
  //****************************************************************************

  /**
   *
   * Enter description here ...
   * @param string $psFormName
   * @return CForm
   */
  public function initForm($psFormName = '')
  {
    require_once('component/form/form.class.ex.php5');
    $oForm = new CFormEx($psFormName);

    return $oForm;
  }

  // Extendable content with 'See More' buton

  public function getExtendableBloc($psId, $psContent, $pnMaxLenght = 130, $psLinkLabel = 'See more')
  {
    if(!assert('is_string($psId) || !empty($psId)'))
      return '';

    if(!assert('is_string($psContent) || !empty($psContent)'))
      return '';

    $psContentb = strip_tags($psContent);

    if(strlen($psContentb) < $pnMaxLenght)
      return $psContentb;

    $sHTML = substr($psContentb, 0, $pnMaxLenght).' ... ';
    $sHTML .= $this->getSeeMoreLink($psLinkLabel, $psId);
    $sHTML .= $this->getSeeMoreContent($psContent, $psId, array('class' => 'blue'));

    return $sHTML;
  }

  public function getSeeMoreLink($psLabel, $psRel, $psToToggle = '')
  {
    if(!assert('!empty($psLabel) && !empty($psRel)'))
      return '';

    $oPage= CDependency::getCpPage();
    $oPage->addJsFile($this->getResourcePath().'/js/display.js');

    return $this->getLink($psLabel, '', array('rel' => $psRel, 'class' => 'seeMoreLink', 'data-to-toggle' => $psToToggle));
  }

  public function getSeeMoreContent($psContent, $psRel, $pavParams = array())
  {
    if(!assert('is_string($psRel) || !empty($psRel) || is_array($pavParams)'))
      return '';

    $pavParams['rel'] = $psRel;
    if(isset($pavParams['class']))
      $pavParams['class'] .= ' seeMoreContent';
    else
      $pavParams['class'] = 'seeMoreContent';

    $oPage= CDependency::getCpPage();
    $oPage->addJsFile($this->getResourcePath().'/js/display.js');

    return $this->getBlocStart('', $pavParams).$psContent.$this->getBlocEnd();
  }


  public function getTogglingText($psShortContent, $psLongContent, $psLinkOpenLabel = '', $psLinkCloseLabel = '')
  {
    if(!assert('!empty($psShortContent) && !empty($psLongContent)'))
      return '';

    $oPage= CDependency::getCpPage();
    $oPage->addJsFile($this->getResourcePath().'/js/display.js');
    $oPage->addCssFile($this->getResourcePath().'/css/display.css');

    if(empty($psLinkOpenLabel))
    {
      $psLinkOpenLabel = '<div class="togg_expand">see more</div>';
    }

    if(empty($psLinkCloseLabel))
    {
      $psLinkCloseLabel = '<div class="togg_close">reduce</div>';
    }

    $sId = uniqid('sfb_');
    $sHTML = $this->getBlocStart('', array('class' => 'toggleTextContainer'));

      $sHTML.= $this->getBloc('togg_short_'.$sId, $psShortContent, array('class' => 'togg_short_text'));
      $sHTML.= $this->getBloc('togg_long_'.$sId, $psLongContent, array('class' => 'togg_long_text hidden'));

      $sHTML.= $this->getBlocStart();
      $sHTML.= $this->getLink($psLinkOpenLabel, 'javascript:;', array('class' => 'togg_link_open', 'onclick' => 'toggleText(this, \'open\');'));
      $sHTML.= $this->getLink($psLinkCloseLabel, 'javascript:;', array('class' => 'hidden togg_link_close', 'onclick' => 'toggleText(this, \'close\');'));
      $sHTML.= $this->getBlocEnd();



    $sHTML.= $this->getBlocEnd();

    return $sHTML;
  }




  /**
   *
   * @param array $pasButtonData
   * @param integer $pnWrapAfter
   * @param string $psDefaultText
   * @param array $pasParam
   * @return html string of the action buttons/list
   *
   * @samples:
   *
   *  $sPic = $oEvent->getResourcePath().'pictures/add_event_16.png';
   *   $sActivityBtn = 'New action';
   *   //independent buttons
   *  $sHTML.= $oHTML->getActionButton($sActivityBtn, $sUrl, $sPic);
   *   $sHTML.= $oHTML->getActionButton($sActivityBtn, $sUrl);
   *   $sHTML.= $oHTML->getCR();
   *
   *   //independent buttons displayed base on an array of actions
   *   $asButtons[0] = array('url' => '', 'label' => 'Add a new activitYY', 'onclick' => 'alert(\'gaaaa\'); goPopup.setLayer(\'\', \'https://bcmedia.devserv.com/index.php5?uid=555-123&ppa=ppaa&ppt=opp&ppk=0&cp_uid=777-249&cp_action=ppav&cp_type=cp&cp_pk=4866&pg=ajx\', \'body\', false, 20, 860, 1); ');
   *   $asButtons[1] = array('url' => $sUrl, 'label' => $sActivityBtn, 'pic' => $sPic);
   *   $asButtons[2] = array('url' => '', 'label' => 'Add a new guuuu', 'params' => array('class' => 'notice', 'onclick' => 'alert(\'guuuuuu\');'));
   *   $sHTML.= $oHTML->getActionButtons($asButtons);
   *
   *   //number of actions exceeds $pnWrapAfter, actions displayed in a list
   *   $asButtons[3] = array('url' => '', 'label' => 'Add a new teeee', 'pic' => $sPic, 'onclick' => 'alert(\'gaaaa\'); goPopup.setLayer(\'\',  \'https://bcmedia.devserv.com/index.php5?uid=555-123&ppa=ppaa&ppt=opp&ppk=0&cp_uid=777-249&cp_action=ppav&cp_type=cp&cp_pk=4866&pg=ajx\', \'body\', false, 20, 860, 1); ');
   *   $asButtons[4] = array('url' => $sUrl, 'label' => $sActivityBtn);
   *   $sHTML.= $oHTML->getActionButtons($asButtons);
   */
  public function getActionButtons($pasButtonData, $pnWrapAfter = 3, $psDefaultText = '', $pasParam = array())
  {
    if(!assert('is_array($pasButtonData) && !empty($pasButtonData)'))
      return '';

    if(!assert('is_integer($pnWrapAfter) && !empty($pnWrapAfter) && is_array($pasParam)'))
      return '';

    if(!isset($pasParam['class']) || empty($pasParam['class']))
      $pasParam['class'] = 'actionButtonMultiple';
    else
      $pasParam['class'].= ' actionButtonMultiple';

    //applicable only when there are less actions than $pnWrapAfter
    if(isset($pasParam['vertical']) && !empty($pasParam['vertical']))
    {
      $pasParam['class'].= ' actionButtonVertical';
      unset($pasParam['vertical']);
    }

    if(!isset($pasParam['width']) || empty($pasParam['width']))
      $sSelectStyle = 'min-width: 175px; ';
    else
    {
      $sSelectStyle = 'width: '.$pasParam['width'].'px;';
      $pasParam['style'] = $sSelectStyle;
      unset($pasParam['width']);
    }

    if(!isset($pasParam['custom_render']) || empty($pasParam['custom_render']))
      $pasParam['custom_render'] = '';

    $sHTML = '';

    if(count($pasButtonData) > $pnWrapAfter)
    {
      $oPage = CDependency::getCpPage();
      $oPage->addJsFile(CONST_PATH_JS_SELECT2);
      $oPage->addCssFile(CONST_PATH_CSS_SELECT2);

      $sSelectId = uniqId();
      $bPicRender = false;
      $sOption = '';

      foreach($pasButtonData as $asData)
      {
        $sRowId = uniqid();

        if(!isset($asData['params']) || empty($asData['params']))
          $asData['params'] = array();

        if(!isset($asData['url']) || empty($asData['url']))
          $asData['url'] = 'javascript:;';

        //allow to simply create popups. For advanced otpion, use onclick and customize the action
        if(isset($asData['ajaxLayer']) && !empty($asData['ajaxLayer']))
        {
          if(!isset($asData['params']['onclick']))
            $asData['params']['onclick'] = 'goPopup.setLayerFromAjax(null, \''.$asData['url'].'\'); ';
          else
            $asData['params']['onclick'] .= 'goPopup.setLayerFromAjax(null, \''.$asData['url'].'\'); ';

          set_array($asData['params']['onclick']);
          $asData['url'] = '';
        }

        if(!isset($asData['value']))
          $sValue = $asData['url'];
        else
          $sValue = $asData['value'];

        if(!isset($asData['label']) || empty($asData['label']))
          $asData['label'] = '<em>no label</em>';

        if(isset($asData['pic']) && !empty($asData['pic']))
        {
          $bPicRender = true;
          $asData['params']['pic'] = $asData['pic'];
        }

        if(isset($asData['onclick']) && !empty($asData['onclick']))
        {
          $asData['params']['onclick'] = $asData['onclick'];
        }

        $sOption.= '<option id="'.$sRowId.'" value="'.$sRowId.'" data-value="'.$sValue.'" data-url="'.$asData['url'].'" ';

        foreach($asData['params'] as $sKey => $sValue)
          $sOption.= ' data-'.$sKey.'="'.$sValue.'" ';

        $sOption.= '>'.$asData['label'].'</option>';
      }

      if($bPicRender)
      {
        $sDropDownClass = 'actionButtonMultiple actionButtonPic';
      }
      else
        $sDropDownClass = 'actionButtonMultiple';

      $sHTML.= $this->getBlocStart('', $pasParam);
      $sHTML.= '<select id="'.$sSelectId.'" style="'.$sSelectStyle.'" ><option></option>';
      $sHTML.= $sOption;
      $sHTML.= '</select>';
      $sHTML.= $this->getBlocEnd();

      if(!empty($psDefaultText))
        $sPlaceHolder = $psDefaultText;
      else
        $sPlaceHolder = count($pasButtonData).' actions available...';

      $sJavascript = '<script>
      $("#'.$sSelectId.'").select2(
        {placeholder: "'.$sPlaceHolder.'", dropdownCssClass: "'.$sDropDownClass.'", '; //allowClear: true, maximumSelectionSize: 1,

      if($pasParam['custom_render'])
      {
        $sJavascript.= $pasParam['custom_render'];
      }
      elseif($bPicRender)
      {
        $sJavascript.= '
        formatResult: select2PictureRender,
        formatSelection: select2PictureRender,
        escapeMarkup: function(m) { return m; } ';
      }

      $sJavascript.= '}); ';


      if(isset($pasParam['custom_action']) && !empty($pasParam['custom_action']))
      {
        $sJavascript.= ' $("#'.$sSelectId.'").change('.$pasParam['custom_action'].'); ';
      }
      else
        $sJavascript.= ' $("#'.$sSelectId.'").change(select2OnChangeRedirect); ';

      $sJavascript.= '</script>';
      $sHTML.= $sJavascript;
    }
    else
    {
      $sHTML.= $this->getBlocStart('', $pasParam);
      $sHTML.= $this->getListStart();

      foreach($pasButtonData as $asData)
      {
        if(!isset($asData['id']) || empty($asData['id']))
          $asData['id'] = uniqid();

        if(!isset($asData['label']) || empty($asData['label']))
          $asData['label'] = '<em>no label</em>';


        //pic is a bad name, keep it for retro compatibility
        if(!isset($asData['pic']) && isset($asData['img']))
          $asData['pic'] = $asData['img'];

        if(!isset($asData['pic']))
          $asData['pic'] = '';


        if(isset($asData['ajaxLayer']) && !empty($asData['ajaxLayer']))
        {
          set_array($asData['params']['onclick']);
          $asData['params']['onclick'] .= 'goPopup.setLayerFromAjax(null, \''.$asData['url'].'\'); ';
          $asData['url'] = '';
        }

        if(isset($asData['onclick']))
        {
          if(!isset($asData['params']['onclick']))
            $asData['params']['onclick'] = $asData['onclick'];
          else
            $asData['params']['onclick'].= ' '.$asData['onclick'];
        }

        if(!isset($asData['params']) || empty($asData['params']))
          $asData['params'] = array();


        $sHTML.= $this->getListItemStart();
        $sHTML.= $this->getActionButton($asData['label'], $asData['url'], $asData['pic'], $asData['params']);
        $sHTML.= $this->getListItemEnd();
      }

      $sHTML.= $this->getListEnd();
      $sHTML.= $this->getFloatHack();
      $sHTML.= $this->getBlocEnd();
    }

    return $sHTML;
  }

  public function getActionButton($psLabel, $psUrl = '', $psPicture = '', $pasParams = array(), $psLink = '')
  {
    if(!isset($pasParams['class']) || empty($pasParams['class']))
      $pasParams['class'] = 'actionButton';
    else
      $pasParams['class'].= ' actionButton';

    if(!empty($psPicture))
    {
      $pasParams['class'].= ' actionButtonImg ';
      $sPic = $this->getPicture($psPicture);
    }
    else
      $sPic = '';

    //replace sandard link by onclick to open an ajaxLayer
    if(isset($pasParams['ajaxLayer']) && !empty($pasParams['ajaxLayer']))
    {
      set_array($pasParams['onclick']);
      $pasParams['onclick'] .= ' goPopup.setLayerFromAjax(null, \''.$psUrl.'\'); ';
      $psUrl = 'javascript:;';
    }

    if(empty($psUrl))
      $psUrl = 'javascript:;';

    $sHTML = $this->getSpanStart('', $pasParams);
    $sHTML.= $sPic;
    if(empty($psLink))
      $sHTML.= $this->getLink($psLabel, $psUrl);
    else
      $sHTML.= $psLink;
    $sHTML.= $this->getSpanEnd();

    return $sHTML;
  }

  public function getLoadingAnimation()
  {
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(CONST_PATH_CSS_COMMON.'loader.css');

    $sHTML = '';
    $sHTML .= $this->getBlocStart('floatingBarsG');
    for ($i=1; $i<=8; $i++)
      $sHTML.= $this->getBloc('rotateG_0'.$i, '', array('class' => 'blockG'));
    $sHTML .= $this->getBlocEnd();

    return $sHTML;
  }

  // Returns a <select> based navigation menu

  public function getSelectMenu($psId = '', $paValues = array(), $pvSelectedValue = '')
  {
    if(!assert('is_string($psId)'))
      return '';

    if(!assert('is_string($pvSelectedValue) || is_numeric($pvSelectedValue)'))
      return '';

    if(!assert('is_array($paValues)'))
      return '';

    if(empty($psId))
      $psId = uniqid();

    $sHTML = '<select id=\''.$psId.'\' onchange="window.location.href = $(this).children(\'option:selected\').val();">';
      foreach ($paValues as $vKey => $aOptionValues)
      {
        $sHTML .= '<option value=\''.$aOptionValues['value'].'\'';
        if($pvSelectedValue==$vKey)
          $sHTML .= ' selected=\'selected\'';
        $sHTML .= '>'.$aOptionValues['label'].'</option>';
      }
    $sHTML .= '</select>';

    return $sHTML;
  }


  /* ******************************************************************************************* */
  /* ******************************************************************************************* */
  /* ******************************************************************************************* */
  /* ******************************************************************************************* */
  // Give a shot at a custom templating/view system

  public function getTemplate($psTemplate, $pasParams = array(), $pvData = array())
  {
    require_once(__DIR__.'/resources/class/template_manager.class.php5');
    $oTemplate = new CTemplateManager();

    if(!$oTemplate)
      assert('false; // no template found for ['.$psTemplate.']');


    if($oTemplate->initTemplate($psTemplate, $pasParams, $pvData))
      return $oTemplate;


    assert('false; // could not initialize the template ['.$psTemplate.']');
    return null;
  }

  public function render($filename, $data = array())
  {
    $file = __DIR__.'/resources/html/'.$filename.'.php';

    try
    {
      if( !is_readable($file) )
      {
          throw new Exception("View $file not found!", 1);
      }

      ob_start() && extract($data, EXTR_SKIP);
      include $file;
      $content = ob_get_clean();
      ob_flush();

      return $content;
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
  }

}
