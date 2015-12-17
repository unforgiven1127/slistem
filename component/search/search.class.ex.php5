<?php

require_once 'component/search/search.class.php5';
require_once 'component/sl_candidate/resources/class/slate_vars.class.php5';
define('CONST_SEARCH_TYPE_ROW', 'searow');
define('CONST_ACTION_RELOAD', 'relo');

class CSearchEx extends CSearch
{
  private $casSearchableComponent = array();
  private $cnSearchableComponent = 0;
  private $cbDisplayInMenuNav = false;
  private $cbDisplayInMenuAction = true;
  private $cbAllowComplexSearch = false;
  private $casSearchField = array();
  protected $coModel = null;

  private $coPage = null;
  private $coHTML = null;
  private $casError = array();

  public function __construct()
  {
    $this->slate_vars = new CSlateVars();
    $this->casSearchableComponent = CDependency::getComponentUidByInterface('searchable');
    $this->cnSearchableComponent = count($this->casSearchableComponent);

    $oSettings = CDependency::getComponentByName('settings');
    $asSettings = $oSettings->getSettings(array('search_link_menu_nav', 'search_link_menu_action', 'search_allow_complex'), true);
    //dump($asSettings);

    if($asSettings['search_link_menu_nav'] !== '')
    {
      if((int)$asSettings['search_link_menu_nav'] > 0)
        $this->cbDisplayInMenuNav = true;
      else
        $this->cbDisplayInMenuNav = false;
    }

    if($asSettings['search_link_menu_action'] !== '')
    {
      if((int)$asSettings['search_link_menu_action'] > 0)
        $this->cbDisplayInMenuAction = true;
      else
        $this->cbDisplayInMenuAction = false;
    }

    if($asSettings['search_allow_complex'] !== '')
    {
      if((int)$asSettings['search_allow_complex'] > 0)
        $this->cbAllowComplexSearch = true;
      else
        $this->cbAllowComplexSearch = false;
    }

    //Try to reload fields from session.
    $this->_loadSearchField();
    /*dump('contruct: reload fields');
    dump($this->casSearchField);*/

    $this->coPage = CDependency::getCpPage();
    $this->coHTML = CDependency::getCpHtml();

    return true;
  }

  public function getDefaultType()
  {
    return CONST_SEARCH_TYPE_SEARCH;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_SEARCH;
  }

  //====================================================================
  //  accessors
  //====================================================================


  //====================================================================
  //  interface
  //====================================================================

  public function declareSettings()
  {
    $asSettings[] = array(
        'fieldname' => 'search_link_menu_nav',
        'fieldtype' => 'integer',
        'label' => 'Add global search in navigation menu ',
        'description' => 'Appears in menu',
        'value' => '0'
    );

    $asSettings[] = array(
        'fieldname' => 'search_link_menu_action',
        'fieldtype' => 'integer',
        'label' => 'Add global search in menu action',
        'description' => 'Appears in menu action',
        'value' => '1'
    );

    $asSettings[] = array(
        'fieldname' => 'search_allow_complex',
        'fieldtype' => 'integer',
        'label' => 'Allow advanced and complex search',
        'description' => 'Allow advanced and complex search',
        'value' => '0'
    );

    return $asSettings;
  }


  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    //if no component are available for search, dont return the search link
    if(!$this->cbDisplayInMenuAction || $this->cnSearchableComponent == 0)
      return array();

    $asActions = array();
    $asActions['all'][] = array('picture' => $this->getResourcePath().'/pictures/menu/search.png', 'title'=>'Search', 'url' => 'javascript:;');

    return $asActions;
  }

  public function displayCustomMenuItem()
  {
    //if no component are available for search, dont return the search link
    if(!$this->cbDisplayInMenuNav || $this->cnSearchableComponent == 0)
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sUrl = $oPage->getUrl('search', CONST_ACTION_SEARCH, '', 0, array('formType'=>'global'));
    $sPic = $oHTML->getPicture(CONST_PICTURE_MENU_SEARCH, 'Search');

    $sHTML = $oHTML->getListItemStart('folderslink');
    $sHTML.= $oHTML->getLink($sPic, $sUrl);
    $sHTML.= $oHTML->getListItemEnd();

    return $sHTML;
  }


  public function getAjax()
  {
    $this->_processUrl();
    $oPage = CDependency::getCpPage();

    switch($this->csAction)
    {
      case CONST_ACTION_SEARCH:
        $sCpUid = getValue('CpUid');
        $sCpType = getValue('CpType');
        $sFormType = getValue('formType');
        return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_displaySearchForm($sFormType, $sCpUid, $sCpType, true))));
        break;


      case CONST_ACTION_RELOAD:
        return json_encode($oPage->getAjaxExtraContent($this->_getSearchFormRow()));
        break;


      case CONST_ACTION_RESULTS:

        $sUid = getValue('cpuid');
        $sDatatype = getValue('datatype');
        $sDatatypeName = getValue('datatype-name');
        $sFieldType = getValue('fieldtype');
        $sKeywords = getValue('keywords');
        $nPage = (int)getValue('page');
        $sHTML = '';
        switch($this->csType)
        {
          case 'only-rows':
            $sHTML =
              $this->_displayRowsForDataType(
              array(
                  'cpuid' => $sUid,
                  'datatype' => $sDatatype,
                  'fieldtype' => $sFieldType
                  ),
              $sKeywords,
              $nPage
              );
            break;

          case 'all':
            $sHTML = $this->_displayAllResults();
            break;

          default:
            $sHTML = $this->_displayResultsForDataType(
              array(
                  'cpuid' => $sUid,
                  'datatype' => $sDatatype,
                  'fieldtype' => $sFieldType,
                  'datatype-name' => $sDatatypeName
                  ),
              $sKeywords
              );
            break;
        }

        return json_encode($oPage->getAjaxExtraContent(array('data' => $sHTML)));
        break;
    }
  }


  //====================================================================
  //  public methods
  //====================================================================

  public function getHtml()
  {
    $this->_processUrl();
    switch($this->csAction)
    {
      case CONST_ACTION_SEARCH:
        $sCpUid = getValue('CpUid');
        $sCpType = getValue('CpType');
        $sFormType = getValue('formType');
        return $this->_displaySearchForm($sFormType, $sCpUid, $sCpType);
        break;

      case CONST_ACTION_RESULTS:
        return $this->_displayAllResults();
        break;
    }
  }

  // Displays the global search form, or a specific search form if a component is specified
  private function _displaySearchForm($psType = 'global', $psComponentUid = '', $psComponentType = '', $pbInAjax = false)
  {
    $oHTML = CDependency::getCpHtml();

    if(!$this->cnSearchableComponent)
      return $oHTML->getBlocMessage('Unable to use the search right now');

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(self::getResourcePath().'css/search.css');


    $asComponents = array();
    if(!empty($psComponentUid))
      $asComponents[] = $psComponentUid;
    else
      $asComponents = $this->casSearchableComponent;

    if(empty($asComponents))
      return 'No searchable component available';


    //load fields when displaying the form
    $this->_loadSearchField(array($psComponentUid));
    /*dump('load fields when displaying the form');
    dump($this->casSearchField);
    echo '<hr/><hr/><hr/><hr/>';
    dump($_SESSION['search_adv_field_list']);*/


    $sHTML = '';
    $sExtraClass = '';
    $sForm = '';
    $bIsComplex = false;

    switch($psType)
    {
      case 'custom':
        // Retrieves custom form with Uid and Type
        exit("Not implemented yet");
        break;

      case 'history':
        // list of saved searches: user's and templates
        $sExtraClass = ' searchFormFullWidth ';
        $sForm = $this->_getUserSearchHistory($pbInAjax);
        break;


      case 'advanced':
      case 'complex':

        //doesn't make sense to make a complex search on multiple component/items...
        if(empty($psComponentUid))
          return 'error, need a component';

        if(empty($psComponentType))
          return 'error, need a data type';


        $asSearchParam = CDependency::getComponentByUid($psComponentUid)->getSearchResultMeta($psComponentType);

        $sExtraClass = ' searchFormFullWidth ';
        $oForm = $oHTML->initForm('searchForm');
        if($pbInAjax)
          $oForm->setFormParams('searchForm', true, array('submitLabel'=>'Search', 'noCancelButton' => 'noCancelButton', 'inajax' => 'inajax', 'ajaxTarget' => 'search-results-container'));


        if($psType == 'complex')
        {
          $bIsComplex = true;
          $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Complex search - advanced mode'));
          $sExtraClass.= ' searchFormComplex ';
        }
        else
        {
          $bIsComplex = false;
          $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Complex search - standard mode'));
          $sExtraClass.= ' searchFormNoGroup ';
        }


        $oPage->addJsFile(self::getResourcePath().'/js/search_form.js');
        $sSubmit = '';

        if(isset($asSearchParam['custom_result_page']))
        {
          $sURL = $asSearchParam['custom_result_page'];

          if(isset($asSearchParam['onBeforeSubmit']))
            $sSubmit.= $asSearchParam['onBeforeSubmit'];
        }
        else
          $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_RESULTS, '', 0);

        $oForm->setFormParams('searchForm', $pbInAjax, array('action' => $sURL, 'class' => 'fullPageForm advancedSearchForm', 'submitLabel'=>'Search', 'onBeforeSubmit' => $sSubmit));
        $oForm->addField('input', 'complex_search', array('type' => 'hidden', 'value' => 1));
        $oForm->addField('input', 'component_uid', array('type' => 'hidden', 'value' => $psComponentUid));
        $oForm->addField('input', 'data_type', array('type' => 'hidden', 'value' => $psComponentType));
        $oForm->addField('input', 'complex_mode', array('type' => 'hidden', 'value' => (int)$bIsComplex));

        $nGroup = 0;
        $nField = 0;
        $oForm->addSection('', array('class' => 'advancedSearchFieldContainer'));
        $oForm->addSection('', array('class' => 'advancedSearchFieldGroup selected', 'group_nb' => $nGroup, 'onclick' => 'selectGroup(this); '));

        $this->_addGroupOperator($oForm, $nGroup);


        //Reload a previous search, or slice the array of fields and display the default form
        $asAllFields = $this->casSearchField[$psComponentType];
        foreach($asAllFields as $sFieldName => $asFieldData)
        {
          $oForm->addSection('', array('class' => 'advancedSearchRow', 'group_nb' => $nGroup, 'row_nb' => $nField,  'id' => 'search_row_'.$nField));
          if($bIsComplex)
          {
            $this->_addAdvancedSearchRowOperator($oForm, $nGroup, $nField);
          }

          $this->_addAdvancedSearchFieldSelector($oForm, $psComponentType, $sFieldName, $asAllFields, $nGroup, $nField, $bIsComplex);
          $this->_addAdvancedSearchFieldOperator($oForm, $asFieldData, $nGroup, $nField);
          $this->_addAdvancedSearchField($oForm, $sFieldName, $asFieldData, $nGroup, $nField);

          $this->_addAdvancedSearchControls($oForm, $bIsComplex, $nGroup, $nField);
          $oForm->closeSection();

          $nField++;
          if($nField > 5)
            break;
        }

        if($bIsComplex)
        {
          $oForm->closeSection();
          $nGroup++;
        }

        $oForm->closeSection();
        $this->_addAdvancedSearchBottom($oForm, $bIsComplex, $nField);
        $sForm = $oForm->getDisplay();
        break;


      case 'global':

        $oForm = $oHTML->initForm('searchForm');
        $oPage->addJsFile(self::getResourcePath().'js/global.js');
        $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Global search'));

        $sHTML = '';
        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_RESULTS, 'all');
        $oForm->setFormParams('searchForm', true, array('action' => $sURL, 'submitLabel'=>'Search', 'noCancelButton' => 'noCancelButton', 'inajax' => 'inajax', 'ajaxTarget' => 'search-results-container'));

        $oForm->addField('input', 'keywords', array('type' => 'text' ,'id' => 'keywords', 'onfinishinput' => 'checkInput($(\'#keywords\').val());', 'inputtimeout' => 1000));
        $oForm->setFieldControl('keywords', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => '1','jsFieldMaxSize' => 255));

        $oForm->addSection('data-types');
        $oForm->addField('select', 'datatypes[]', array('multiple' => 'multiple', 'sortable' => 'sortable', 'label' => 'Search in', 'allNoneLink' => 'allNoneLink'));
        $oForm->setFieldControl('datatypes[]', array('jsFieldNotEmpty' => ''));

        foreach ($asComponents as $sComponentUid)
        {
          $oComponent = CDependency::getComponentByUid($sComponentUid);
          $aTypes = $oComponent->getSearchFields();

          foreach ($aTypes as $sType => $aType)
            $oForm->addOption('datatypes[]', array('value' => $sType, 'label' => $aType['label']));
        }
        $oForm->closeSection();

        $oForm->addField('misc', 'misc', array('type' => 'text', 'text' => 'It is...'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'text', 'label' => 'a title, a name, a description', 'class' => 'fieldtype'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'email', 'label' => 'an email', 'class' => 'fieldtype'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'date', 'label' => 'a date', 'class' => 'fieldtype'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'address', 'label' => 'an address', 'class' => 'fieldtype'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'phone', 'label' => 'a phone number', 'class' => 'fieldtype'));
        $oForm->addField('radio', 'fieldtype', array('value' => 'all', 'label' => 'I dont know', 'checked' => 'checked', 'class' => 'fieldtype'));
        $oForm->setFieldControl('fieldtype', array('jsFieldNotEmpty' => ''));

        $sForm = $oForm->getDisplay();
        break;
    }

    if($this->cbAllowComplexSearch)
      $sHTML .= $oHTML->getBloc('search-form-container', $this->_getSearchFormMenu($psType, $pbInAjax, $bIsComplex) . $sForm, array('class' => $sExtraClass));
    else
      $sHTML .= $oHTML->getBloc('search-form-container', $sForm, array('class' => $sExtraClass));

    $sHTML .= $oHTML->getBloc('search-results-container');
    return $sHTML;
  }



  private function _getSearchFormMenu($psSearchType, $pbInAjax, $pbComplex)
  {
    $oPage = CDependency::getCpPage();
    $sMenu = '<div class="searchActionBar">';

    if($pbComplex)
      $sSearchType = 'complex';
    else
      $sSearchType = 'advanced';

    //Custom menu items ... need an interface !!!
    $sURL = $oPage->getRequestedUrl();

    if($pbInAjax)
    {
      //$sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH);
      $sMenu.= '<div class="formMenuItem savedSearch" onclick="AjaxRequest(\''.$sURL.'&formType=history\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>
      <div class="formMenuItem candidate" onclick="AjaxRequest(\''.$sURL.'&CpType=candi&formType='.$sSearchType.'\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>
      <div class="formMenuItem company" onclick="AjaxRequest(\''.$sURL.'&CpType=comp&formType='.$sSearchType.'\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div> ';
    }
    else
    {
      //$sURL = $oPage->getUrl($this->csUid, CONST_ACTION_SEARCH);
      $sMenu.= '<div class="formMenuItem savedSearch" onclick="window.location.href= \''.$sURL.'&formType=history\'">&nbsp;</div>
      <div class="formMenuItem candidate" onclick="window.location.href= \''.$sURL.'&CpType=candi&formType='.$sSearchType.'\'">&nbsp;</div>
      <div class="formMenuItem company" onclick="window.location.href= \''.$sURL.'&CpType=comp&formType='.$sSearchType.'\'">&nbsp;</div>';
    }


    switch($psSearchType)
    {
      case 'custom':
        // Retrieves custom form with Uid and Type
        break;

      case 'advanced':

        if($pbInAjax)
        {
          $sMenu.= '<div class="searchFormSwitchMode global" onclick="AjaxRequest(\''.$sURL.'&formType=global\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode complex" onclick="AjaxRequest(\''.$sURL.'&formType=complex\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
        }
        else
        {
          $sMenu.= '<div class="searchFormSwitchMode global" onclick="window.location.href= \''.$sURL.'&formType=global\'">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode complex" onclick="window.location.href= \''.$sURL.'&formType=complex\'">&nbsp;</div>';
        }
        break;

      case 'complex':

        if($pbInAjax)
        {
          $sMenu.= '<div class="searchFormSwitchMode global" onclick="AjaxRequest(\''.$sURL.'&formType=global\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode advanced" onclick="AjaxRequest(\''.$sURL.'&formType=advanced\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
        }
        else
        {
          $sMenu.= '<div class="searchFormSwitchMode global" onclick="window.location.href= \''.$sURL.'&formType=global\'">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode advanced" onclick="window.location.href= \''.$sURL.'&formType=advanced\'">&nbsp;</div>';
        }
        break;

      case 'global':
      default:

        if($pbInAjax)
        {
          $sMenu.= '<div class="searchFormSwitchMode advanced" onclick="AjaxRequest(\''.$sURL.'&formType=advanced\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode complex" onclick="AjaxRequest(\''.$sURL.'&formType=complex\', \'\', \'\', $(this).closest(\'.ui-dialog-content\').attr(\'id\'));">&nbsp;</div>';
        }
        else
        {
          $sMenu.= '<div class="searchFormSwitchMode advanced" onclick="window.location.href= \''.$sURL.'&formType=advanced\'">&nbsp;</div>';
          $sMenu.= '<div class="searchFormSwitchMode complex" onclick="window.location.href= \''.$sURL.'&formType=complex\'">&nbsp;</div>';
        }
        break;
    }

    $sMenu.= '</div>';
    return $sMenu;
  }


  private function _getUserSearchHistory($pbInAjax)
  {
    $oHTML = CDependency::getCpHtml();
    $asTabs = array();
    $asOption = array();


    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');
    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(false);
    $oConf->addBlocMessage('The searches you have previously saved');

    $oConf->addColumn('Date', 'date_created', array('width' => '10%', 'sortable'=> array('javascript' => 1)));
    $oConf->addColumn('Title', 'title', array('width' => '75%','sortable'=> array('javascript' => 1)));
    $oConf->addColumn('-', 'action', array('width' => '10%'));

    $asSearches = array(array('date_created' => '2010-01-02', 'title' => 'saved search #1', 'action' => '<a href="">reload</a>'),
                        array('date_created' => '2011-01-02', 'title' => 'saved search #2', 'action' => '<a href="">reload</a>'),
                        array('date_created' => '2013-01-02', 'title' => 'saved search #3', 'action' => '<a href="">reload</a>'));

    $sHTML = $oHTML->getBloc('user_searches', $oTemplate->getDisplay($asSearches));
    $asTabs[] = array('label' => 'user_searches', 'title' =>'My saved searches', 'content' => $sHTML, 'options' => $asOption);


    $oConf->clearBlocMessage();
    $oConf->addBlocMessage('A few generic templates');
    $asSearches = array(array('date_created' => '2014-01-02', 'title' => 'saved search #4', 'action' => '<a href="">reload</a>'),
                        array('date_created' => '2015-01-02', 'title' => 'saved search #5', 'action' => '<a href="">reload</a>'));

    $sHTML = $oHTML->getBloc('templates', $oTemplate->getDisplay($asSearches));
    $asTabs[] = array('label' => 'templates', 'title' =>'Templates', 'content' => $sHTML, 'options' => $asOption);


    return $oHTML->getBloc('', $oHTML->getTabs('', $asTabs), array('class' => 'searchHistoryContainer'));
  }


  /**
   * Load all the fields available for the advanced search in session
   * Avoid instanciating all the component everytime we acccess the search
   *
   * @param array $pasComponents
   * @return boolean
   */
  private function _loadSearchField($pasComponents = array())
  {
    if(!assert('is_array($pasComponents)'))
      return false;

    //dump(' _loadSearchField()  called... ');
    //assert('false;');


    //No component ID ==> try to reload the fields from session
    if(empty($pasComponents))
    {
      if(!empty($_SESSION['search_adv_field_list']))
      {
        $this->casSearchField = $_SESSION['search_adv_field_list'];
      }
    }
    else
    {
      $asFieldList = array();
      foreach($pasComponents as $sComponentUid)
      {
        $oComponent = CDependency::getComponentByUid($sComponentUid);
        $asFields = $oComponent->getSearchFields('', true);

        $asFieldList = array_merge($asFieldList, $asFields);
      }

      //preserve field in session if no result loadded here
      if(!empty($asFieldList))
      {
        $this->casSearchField = $asFieldList;
        $_SESSION['search_adv_field_list'] = $this->casSearchField;
      }
    }

    //dump(' field loaded, here is the result... ');
    //dump($this->casSearchField);

    return true;
  }

  private function _displayAllResults()
  {
    $aDataTypes = getValue('datatypes');
    $sFieldType = getValue('fieldtype');
    $sKeyWords = getValue('keywords');
    $nGlobal = (int)getValue('global');

    if(empty($sKeyWords))
      return 'No keyword was received.';

    if(empty($aDataTypes) && ($nGlobal==0))
      return 'No type of data received.';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(self::getResourcePath().'css/search.css');
    $oPage->addJsFile(self::getResourcePath().'js/search-results.js');

    $sHTML = $oHTML->getTitle('Search result for <strong>'.$sKeyWords.'</strong>');

    if($sFieldType != 'all')
      $sHTML .= $oHTML->getText('Search only '.$sFieldType);

    $sHTML.= $oHTML->getBlocStart('results');

    $aoComponents = CDependency::getComponentsByInterface('searchable');

    $aQueue = array();
    foreach ($aoComponents as $oComponent)
    {
      $aCpDataTypes = $oComponent->getSearchFields();

      foreach ($aCpDataTypes as $sCpDataType => $aCpFieldTypes)
      {
        if(($nGlobal == 1) || (in_array($sCpDataType, $aDataTypes)))
          $aQueue[] = array('cpuid' => $oComponent->getComponentUid(), 'datatype' => $sCpDataType, 'datatype-name' => $aCpFieldTypes['label'], 'fieldtype' => $sFieldType);
      }
    }


    $nCount = 0;
    foreach ($aQueue as $aSearchIn)
    {
      $sDivName = 'results_for_'.$aSearchIn['datatype'];
      $sHTML.= $oHTML->getBlocStart($sDivName, array('class' => 'result-row'));

      if($nCount == 0)
        $sHTML.= $this->_displayResultsForDataType($aSearchIn, $sKeyWords, 0);
      else
      {
        $aUrlSettings=$aSearchIn;
        $aUrlSettings['keywords'] = $sKeyWords;
        $aUrlSettings['page'] = 0;
        $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_RESULTS, '', 0, $aUrlSettings);
        $sHTML.= $oHTML->getLoadingAnimation();
        $sHTML.= $oHTML->getText('', array('id' => $sUrl, 'rel' => $sDivName, 'class' => 'link-results'));
      }
      $sHTML.= $oHTML->getBlocEnd();
      $nCount++;
    }

    $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= '<script>startLoadResults()</script>';

    return $sHTML;
  }


  // Process the research of $psKeywords in components and datatypes given by
  // $paSearchIn
  private function _displayResultsForDataType($paSearchIn, $psKeywords)
  {
    if(!assert('is_array($paSearchIn) && !empty($paSearchIn)'))
      return '';

    if(!assert('!empty($psKeywords) && is_string($psKeywords)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';

    $oComponent = CDependency::getComponentByUid($paSearchIn['cpuid']);
    $aResults = $oComponent->getSearchResult($paSearchIn['datatype'], $psKeywords, $paSearchIn['fieldtype']);

    if(isset($aResults['custom_result']))
    {
      if(isset($aResults['custom_result']['script']))
        $oPage->addCustomJs($aResults['custom_result']['script']);

      if(isset($aResults['custom_result']['html']))
        $sHTML.= $aResults['custom_result']['html'];
    }
    else
    {
      if(!isset($aResults['total']) || $aResults['total'] == 0)
        $sHTML .= 'No match in '.$paSearchIn['datatype-name'];
      else
      {
        $sHTML .= $oHTML->getLink($aResults['total'].' matches in '.$paSearchIn['datatype-name'], 'javascript:;', array('class' => 'show-results', 'onclick' => '$(\'#results_'.$paSearchIn['datatype'].'\').toggle(); return false;'));
        $sHTML .= $oHTML->getBlocStart('results_'.$paSearchIn['datatype'], array('style' => 'display:none;', 'class' => 'divlist'));
        $sHTML .= $this->_displayRowsForDataType($paSearchIn, $psKeywords, 0, $aResults);
        $sHTML .= $oHTML->getBlocEnd();
      }
    }

    return $sHTML;
  }

  private function _displayRowsForDataType($paSearchIn, $psKeywords, $pnPage = 0, $paResults= array())
  {
    if(!assert('is_array($paResults)'))
      return '';

    if(!assert('is_array($paSearchIn) && !empty($paSearchIn)'))
      return '';

    if(!assert('!empty($psKeywords) && is_string($psKeywords)'))
      return '';

    if(!assert('is_integer($pnPage)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $oComponent = CDependency::getComponentByUid($paSearchIn['cpuid']);
    $aResultMeta = $oComponent->getSearchResultMeta($paSearchIn['datatype']);

    if(empty($paResults))
      $aResults = $oComponent->getSearchResult($paSearchIn['datatype'], $psKeywords);
    else
      $aResults = $paResults;

    $sHTML = '';

    foreach($aResults['results']  as $aResult)
    {
      if(!empty($aResultMeta))
      {
        $nPk = (int)$aResult[$aResultMeta['pk']];
        $sTitle = $this->_getSearchResult('title', $aResult, $aResultMeta, $psKeywords);
        $sDescription = $this->_getSearchResult('excerpt', $aResult, $aResultMeta, $psKeywords);
        $sMoreData = $this->_getSearchResult('more-data', $aResult, $aResultMeta, $psKeywords);

        $sUrl = $oPage->getUrl($paSearchIn['cpuid'], CONST_ACTION_VIEW, $paSearchIn['datatype'], $nPk);

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'divlist-item'));
          $sHTML.= $oHTML->getBloc('', $oHTML->getLink($sTitle, $sUrl), array('class' => 'item-title'));
          $sHTML.= $oHTML->getBloc('', $sDescription, array('class' => 'item-description'));
          $sHTML.= $oHTML->getBloc('', $sMoreData);
        $sHTML.= $oHTML->getBlocEnd();
      }
      else
      {
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'divlist-item'));
          $sHTML.= $aResult;
        $sHTML.= $oHTML->getBlocEnd();
      }
    }

    $nRank = ($pnPage*10)+10;
    if($nRank<$aResults['total'])
    {
      $sDivName = $paSearchIn['datatype'].'_'.($pnPage+1);
      $sHTML .= $oHTML->getBlocStart($sDivName);
        $sUrlMore = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_RESULTS, 'only-rows', 0,
                array('keywords' => $psKeywords, 'datatype' => $paSearchIn['datatype'], 'cpuid' => $paSearchIn['cpuid'], 'page' => $pnPage+1));
        $sJavascript = "AjaxRequest('".$sUrlMore."', false, '', '".$sDivName."');";

        $sHTML .= $oHTML->getLink('.. show more results ..', 'javascript:;', array('class' => 'show-more-results', 'onclick' => $sJavascript));
      $sHTML .= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }

  // Replaces placeholders by values and highlight text
  private function _getSearchResult($psName, $paResult, $paResultsMeta, $psKeywords='')
  {
    if(!assert('is_string($psName) && !empty($psName)'))
      return '';

    if(!assert('is_string($psKeywords)'))
      return '';

    if(!assert('is_array($paResult) && !empty($paResult)'))
      return '';

    if(!assert('is_array($paResultsMeta) && !empty($paResultsMeta)'))
      return '';

    $sOutput = $paResultsMeta[$psName];
    foreach ($paResult as $sLabel => $sValue)
      $sOutput = str_replace('%'.$sLabel.'%', $sValue, $sOutput);

    if ((empty($sOutput)) || (empty($psKeywords)))
      return $sOutput;

    return highlightKeywords($sOutput, $psKeywords);
  }



  /**
   * In complex mode, we display a row operator. FDefault is AND
   * @param oForm $poForm
   * @param integer $pnRowNumber
   */
  private function _addAdvancedSearchRowOperator(&$poForm, $pnGroupNumber, $pnRowNumber)
  {
    $poForm->addField('select', 'row_operator['.$pnGroupNumber.']['.$pnRowNumber.']');
    $poForm->setFieldDisplayParams('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('class' => 'searchForm_row_operator'));

    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => 'and', 'label' => 'AND'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => 'or', 'label' => 'OR'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => 'and(', 'label' => 'AND ('));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => ')and', 'label' => ') AND'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => 'or(', 'label' => 'OR ('));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => ')or', 'label' => ') OR'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => ')', 'label' => ')'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => '))', 'label' => '))'));
    $poForm->addOption('row_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('value' => '))', 'label' => ')))'));
  }

  private function _addGroupOperator(&$poForm, $pnGroup)
  {
    $poForm->addField('select', 'group_operator['.$pnGroup.']');

    if($pnGroup <= 0)
      $poForm->setFieldDisplayParams('group_operator['.$pnGroup.']', array('class' => ' row_group_operator hidden'));

    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => 'and', 'label' => 'AND'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => 'or', 'label' => 'OR'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => 'and(', 'label' => 'AND ('));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => ')and', 'label' => ') AND'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => 'or(', 'label' => 'OR ('));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => ')or', 'label' => ') OR'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => ')', 'label' => ')'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => '))', 'label' => '))'));
    $poForm->addOption('group_operator['.$pnGroup.']', array('value' => '))', 'label' => ')))'));
  }


  private function _addAdvancedSearchFieldSelector(&$poForm, $psDataType, $psFieldName, $pasAllFields, $pnGroupNumber, $pnRowNumber, $pbIsComplex = false)
  {
    $sURL = $this->coPage->getAjaxurl($this->csUid, CONST_ACTION_RELOAD, CONST_SEARCH_TYPE_ROW, 0,
            array('is_complex' => (int)$pbIsComplex, 'cp_type' => $psDataType));
    $sJs = '
    var nGroupNumber = $(this).closest(\'.advancedSearchRow\').attr(\'group_nb\');
    var nRowNumber = $(this).closest(\'.advancedSearchRow\').attr(\'row_nb\');
    AjaxRequest(\''.$sURL.'&group_number=\'+nGroupNumber+\'&ppk=\'+nRowNumber+\'&field_name=\'+ $(this).val(), \'\', \'\', \'search_row_\'+nRowNumber, \'\', \'\', \'refreshOperator();\'); ';
    $poForm->addField('select', 'field_selector['.$pnGroupNumber.']['.$pnRowNumber.']', array('class' => 'field_selector', 'onchange' => $sJs));

    foreach($pasAllFields as $sFieldName => $asField)
    {
      if($psFieldName == $sFieldName)
        $poForm->addOption( 'field_selector['.$pnGroupNumber.']['.$pnRowNumber.']', array('label' => $asField['display']['label'], 'value' => $sFieldName, 'group' => $asField['display']['group'], 'selected' => 'selected'));
      else
        $poForm->addOption( 'field_selector['.$pnGroupNumber.']['.$pnRowNumber.']', array('label' => $asField['display']['label'], 'group' => $asField['display']['group'], 'value' => $sFieldName));
    }
  }

  private function _addAdvancedSearchFieldOperator(&$poForm, $pasField, $pnGroupNumber, $pnRowNumber, $psSelectedOp = '')
  {
    //dump($pasField);
    if(isset($pasField['display']['operator']['params']))
    {
      $asParams = $pasField['display']['operator']['params'];
      $pasField['display']['operator'] = $pasField['display']['operator']['values'];
    }
    else
      $asParams = array();

    $asParams['class'] = 'field_operator';

    $poForm->addField('select', 'field_operator['.$pnGroupNumber.']['.$pnRowNumber.']', $asParams);
    $poForm->setFieldDisplayParams('field_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('keepinline' => 1));

    if(empty($psSelectedOp))
      $psSelectedOp = $pasField['display']['default_operator'];

    foreach($pasField['display']['operator'] as $sValue => $vLabel)
    {
      if (($pasField['display']['label'] == 'Resume' ||
        $pasField['display']['label'] == 'Resume (all text documents)') && $sValue == 'equal')
        continue;

      if(is_array($vLabel))
      {
        if($sValue == $psSelectedOp)
          $vLabel['selected'] = 'selected';

        $vLabel['value'] = $sValue;
        $poForm->addOption('field_operator['.$pnGroupNumber.']['.$pnRowNumber.']', $vLabel);
      }
      else
      {
        if($sValue == $psSelectedOp)
          $poForm->addOption('field_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('label' => $vLabel, 'value' => $sValue, 'selected' => 'selected'));
        else
          $poForm->addOption('field_operator['.$pnGroupNumber.']['.$pnRowNumber.']', array('label' => $vLabel, 'value' => $sValue));
      }
    }
  }


  private function _addAdvancedSearchField(&$poForm, $psFieldName, $pasField, $pnGroupNumber, $pnRowNumber)
  {

    //remove the label, used in the field list
    $asFieldParam = array('label' => '', 'class' => 'field_value', 'field_name' => $psFieldName);

    //add all opional parameters
    if(!empty($pasField['display']['param']))
      $asFieldParam = array_merge($asFieldParam, $pasField['display']['param']);

    //pre-load a value ? default field value ?
    $asFieldParam['value'] = null;
    if(!empty($pasField['display']['value']))
    {
      if(count($pasField['display']['value']) > 1)
        $asFieldParam['value'] = $pasField['display']['value'];
      else
        $asFieldParam['value'] = $pasField['display']['value'][0];
    }
    else
    {
      if(!empty($pasField['display']['default_value']))
      {
        if(count($pasField['display']['default_value']) > 1)
          $asFieldParam['value'] = $pasField['display']['default_value'];
        else
          $asFieldParam['value'] = $pasField['display']['default_value'][0];
      }
    }

    if(isset($pasField['display']['multiple']))
    {
      $asFieldParam['multiple'] = $pasField['display']['multiple'];
      $asFieldParam['nbresult'] = $pasField['display']['multiple'];   //compatibility autocomplete
      $psFieldName.= '['.$pnGroupNumber.']['.$pnRowNumber.'][]';
    }
    else
      $psFieldName.= '['.$pnGroupNumber.']['.$pnRowNumber.']';

    if(isset($pasField['display']['nbresult']))
      $asFieldParam['nbresult'] = $pasField['display']['nbresult'];



    //specific params
    $bAddOption = false;
    switch($pasField['display']['type'][0])
    {
      case 'selector':
        $asFieldParam['url'] = $pasField['display']['type'][1];
        break;

      case 'select':
      case 'paged_tree':
        $bAddOption = true;
        break;

      default:
        $asFieldParam['type'] = $pasField['display']['type'][1];
    }



    $poForm->addField($pasField['display']['type'][0], $psFieldName, $asFieldParam);
    $poForm->setFieldControl($psFieldName, array($pasField['display']['js_control']));
    $poForm->setFieldDisplayParams($psFieldName, array('keepinline' => 1, 'class' => 'advfieldValue'));

    if($bAddOption && !empty($pasField['display']['option']))
    {
      foreach($pasField['display']['option'] as $vValue)
      {
        if(!empty($asFieldParam['value']) && @$vValue['value'] == $asFieldParam['value'])
          $vValue['selected'] = 'selected';

        $poForm->addOption($psFieldName, $vValue);
      }
    }

    return true;
  }


  /**
   * Add row actions and javascript
   * @param type $poForm
   * @param bool $pbComplexMode
   * @param type $pnRowNumber
   * @return boolean
   */
  private function _addAdvancedSearchControls(&$poForm, $pbComplexMode, $pnRowNumber)
  {
    $sHTML = '<img src="'.self::getResourcePath().'/pictures/delete_row_16.png" onclick="removeSearchFormRow(this);" />';

    $sFieldName = uniqid();
    $poForm->addField('misc', $sFieldName, array('type' => 'text', 'text' => $sHTML));
    $poForm->setFieldDisplayParams($sFieldName, array('keepinline' => 1, 'class' => 'advfieldControl'));

    return true;
  }

  /**
   * Add row actions and javascript
   * @param type $poForm
   * @param bool $pbComplexMode
   * @return boolean
   */
  private function _addAdvancedSearchBottom(&$poForm, $pbComplexMode, $pnField)
  {

    $poForm->addSection('', array('class' => 'advancedSearchButtons'));

    if($pbComplexMode)
    {
      $sHTML = '<div class="advfieldButtonContainer complex">';
      $sHTML.= '<div class="advfieldButton shadow" onclick="addSearchFormGroup(this);">Add a field group</div>';
      $sHTML.= '<div class="advfieldButton shadow" onclick="addSearchFormRow(this); ">Add a new criteria</div>';
    }
    else
    {
      $sHTML = '<div class="advfieldButtonContainer">';
      $sHTML.= '<div class="advfieldButton shadow" onclick="addSearchFormRow(this);">Add a new criteria</div>';
    }

    $sHTML.= '</div>';

    $poForm->addField('misc', '', array('type' => 'text', 'text' => $sHTML));
    $poForm->addField('input', 'fiel_number', array('type' => 'hidden', 'value' => $pnField));
    $poForm->closeSection();

    return true;
  }








  // =====================================================================================
  // Public methods
  // =====================================================================================

  public function getFieldOperators($psFieldType)
  {
    switch($psFieldType)
    {
      case 'numeric':
        return array('equal' => ' = ', 'different' => ' != ', 'superior' => ' >= ', 'inferior' => ' <= ');
        break;

      case 'select':
        return array('notin' => ' != (not any)', 'in' => ' In (one of) ');
        break;

      case 'string':
        return array('equal' => ' Equals ', 'different' => ' Different ', 'contain' => ' Contains ', 'start' => ' Starts with ',
           'end' => ' Ends With ', 'in' => ' One of (, separated) ', 'all' => ' All (, separated) ');
        break;

      case 'date':
        return array('values' => array('equal' => ' = ', 'different' => ' != ', 'superior' => ' >= ', 'inferior' => ' <= ', 'between' => ' Between '),
                     'params' => array('onchange' => 'if(this.value == \'between\'){ jQuery(this).closest(\'li\').find(\'div.fieldValueContainer input.datepicker\').addClass(\'datepicker_range\'); } else{ jQuery(this).closest(\'li\').find(\'div.fieldValueContainer input.datepicker\').removeClass(\'datepicker_range\').val(\'\'); } '));
        break;

      case 'range':
        return array('between' => ' Between ', 'notbetween' => ' Not between ', 'min' => ' inferior to min ', 'max' => ' superior to max ');
        break;


      case 'autocomplete':
        return array('in' => ' In ', 'notin' => ' Not in ');
        break;

      case 'text':
        return array('contain' => ' Contains ', 'different' => ' Do not contains ', 'start' => ' Starts with ');
        break;

      case 'is':
        return array('equal' => ' = ');
        break;

      case 'in':
        return array('in' => 'In');
        break;

      case 'egality':
        return array('equal' => ' = ', 'different' => ' != ');
        break;

      case 'age':
        return array('between' => ' Between ', 'between_null' => ' Age unknown or between ', 'notbetween' => ' Not between ');
        break;


      //candidate attribute --> need a all selected case
      case 'select+':
        return array('in' => ' In (one of) ', 'different' => ' != (not any)', '=' => ' = all selected ');
        break;

      case 'select_all':
        return array('in' => ' = (all selected) ');
        break;

      case 'autocomplete+':
        return array('in' => ' In (one of) ', 'notin' => ' Not in (not any)', '=' => ' = all selected ');
        break;


      case 'skill':
        return array('superior' => ' >= ', 'inferior' => ' <= ', 'equal' => ' = ', 'different' => ' != ');
        break;

      case 'fts':
        return array('fts_equal' => ' Strictly equals ', 'fts_in' => ' Strictly In (, separated) ', 'contain' => ' Contains ', 'in' => ' In (, separated) ');
        break;

      case 'status':
        return array('values' => array('equal' => ' = ', 'different' => ' != ', 'superior' => ' >= ', 'inferior' => ' <= '),
                     'params' => array('onchange' => 'if(this.value == \'equal\' || this.value == \'different\')
                       { jQuery(this).closest(\'.formSection\').find(\'div.advfieldValue select option:not(visible)\').show(); }
                       else
                       { jQuery(this).closest(\'.formSection\').find(\'div.advfieldValue select option\').each(function(){ if(jQuery(this).val() > 6){ jQuery(this).hide(); }  });  } ')
                    );
        break;

      default:
        return array();
        break;
    }
  }


  private function _getSearchFormRow()
  {
    $nField = $this->cnPk;
    $sType = getValue('cp_type');
    $sFieldName = getValue('field_name');
    $nGroup = (int)getValue('group_number', 0);

    //load field from the requested component OR reload ones stored in session
    $sUid = getValue('cp_uid');
    if(!empty($sUid))
      $this->_loadSearchField(array($sUid));
    else
      $this->_loadSearchField();


    if(empty($sFieldName) || empty($sType) || !isset($this->casSearchField[$sType][$sFieldName]))
    {
      //dump($_SESSION['search_adv_field_list']);
      //dump($this->casSearchField);
      return array('error' => __LINE__.' - Can not load the requested field ['.$sFieldName.' | '.$sType.' | '.(int)isset($this->casSearchField[$sType][$sFieldName]).']');
    }
    $bIsComplex = (bool)getValue('is_complex', false);
    //$nGroup = getValue('group');

    $oForm = $this->coHTML->initForm('dummy_form');
    //$oForm->addSection('', array('class' => 'advancedSearchRow', 'id' => 'search_row_'.$nField));

    if($bIsComplex)
      $this->_addAdvancedSearchRowOperator($oForm, $nGroup, $nField);

    $this->_addAdvancedSearchFieldSelector($oForm, $sType, $sFieldName, $this->casSearchField[$sType], $nGroup, $nField, $bIsComplex);
    $this->_addAdvancedSearchFieldOperator($oForm, $this->casSearchField[$sType][$sFieldName], $nGroup, $nField);
    $this->_addAdvancedSearchField($oForm, $sFieldName, $this->casSearchField[$sType][$sFieldName], $nGroup, $nField);

    $this->_addAdvancedSearchControls($oForm, $bIsComplex, $nGroup, $nField);
    //$oForm->closeSection();

    return array('data' => $oForm->getFormContent());
  }






  /* *********************************************************************************************** */
  /* *********************************************************************************************** */
  /* *********************************************************************************************** */
  //functions below build a search query based on the complex search form data


  public function buildComplexSearchQuery()
  {
    $bComplex = (bool)getValue('complex_mode', 0);
    $sCpUid = getValue('component_uid');
    $sDataType = getValue('data_type');

    $asMessage = array('short' => array());
    $oQB = $this->_getModel()->getQueryBuilder();


    //$this->_loadSearchField(array($sCpUid));
    $this->_loadSearchField();
    $asAllFields = $this->casSearchField[$sDataType];

    if(!isset($_POST['field_selector']) || empty($_POST['field_selector']) || !is_array($_POST['field_selector']))
      return array('error' => 'no search field found.');

    if(!isset($_POST['group_operator']))
      $_POST['group_operator'] = array(0 => 'AND ');

    if($bComplex)
      $nGroup = count($_POST['group_operator']);
    else
      $nGroup = 1;

    $nParanthese = 0;
    $ignore_explode = array('lastname', 'firstname', 'resume', 'company_name', 'company_prev', 'department', 'title', 'char_note',
      'note', 'all_note', 'resume_all');

    foreach($_POST['group_operator'] as $nGroup => $sGroupOperator)
    {

      //$nRow = count($_POST['field_selector']);
      $bFirstRow = true;
      $asCondition = array();
      foreach($_POST['field_selector'][$nGroup] as $nRowNumber => $sFieldName)
      {
        $vFieldValue = @$_POST[$sFieldName][$nGroup][$nRowNumber];

        if (!is_array($vFieldValue) && !in_array($sFieldName, $ignore_explode))
        {
          $temp = explode(',', $vFieldValue);

          if (count($temp) > 1)
            $vFieldValue = $temp;
        }

        //if(empty($vFieldValue)) // ===>>>   0 is a valid value
        if($vFieldValue == null || $vFieldValue == '' || (is_array($vFieldValue) && empty($vFieldValue)))
        {
          //dump('debug: field ['.$sFieldName.'['.$nRowNumber.']'.'] empty');
        }
        else
        {
          //fetch row data
          $sFieldOperator = @$_POST['field_operator'][$nGroup][$nRowNumber];

          if($bComplex)
            $sRowOperator = @$_POST['row_operator'][$nGroup][$nRowNumber];
          else
            $sRowOperator = 'and';


          //fetch field description to know how to treat it
          $asFieldData = @$asAllFields[$sFieldName];
          if(empty($asFieldData))
            return array('error' => 'Could not find the field ['.$sFieldName.'] description');



          if(!empty($asFieldData['sql']['join']))
          {
            foreach($asFieldData['sql']['join'] as $asJoin)
            {
              $oQB->addJoin($asJoin['type'], $asJoin['table'], $asJoin['alias'], $asJoin['clause']);

              if(isset($asJoin['select']) && !empty($asJoin['select']))
                $oQB->addSelect($asJoin['select']);

              if(isset($asJoin['where']) && !empty($asJoin['where']))
                $oQB->addWhere($asJoin['where']);
            }
          }

          if(!empty($asFieldData['sql']['select']))
          {
            $oQB->addSelect($asFieldData['sql']['select']);
          }

          if($bFirstRow)
          {
            $bFirstRow = false;
            $sRowOperator = '';
          }

          $sCondition = '';
          if(!empty($asFieldData['sql']['unmanageable']))
          {
            //replace template operator   !!! some type don't have any !!!
            $sOperator = $this->_getSqlOperator($asFieldData['data'], $sFieldOperator, $vFieldValue);
            $sCondition = str_replace('<YYY>', $sOperator, $asFieldData['sql']['unmanageable']);

            if ($sFieldOperator == 'notin')
              $sCondition = str_replace('<logic>', 'AND', $sCondition);
            else
              $sCondition = str_replace('<logic>', 'OR', $sCondition);

            $asMatch = array();
            preg_match_all('/<<([^>]{2,})>>/i', $sCondition, $asMatch);
            //dump($asMatch);

            //dump($asFieldData);
            //dump($vFieldValue);

            if( $asFieldData['data']['type'] == 'rangeInteger')
            {
              if($sFieldOperator == 'between')
              {
                $sCondition = str_replace('XXX', ((int)$vFieldValue[0] * $asFieldData['sql']['multiplier']), $sCondition);
                $sCondition =  $sRowOperator.' ( '. str_replace('ZZZ', ((int)$vFieldValue[1] * $asFieldData['sql']['multiplier']), $sCondition).' ) ';
              }
              elseif($sFieldOperator == 'notbetween')
              {
                //reverse min and max values
                $sCondition = str_replace('XXX', ((int)$vFieldValue[1] * $asFieldData['sql']['multiplier']), $sCondition);
                $sCondition = str_replace(' AND ', ' OR ', $sCondition);
                $sCondition =  $sRowOperator.' ( '. str_replace('ZZZ', ((int)$vFieldValue[0] * $asFieldData['sql']['multiplier']), $sCondition).' ) ';
              }
              elseif($sFieldOperator == 'min')
              {
                //ignore max
                $sCondition = str_replace('XXX', ($vFieldValue * $asFieldData['sql']['multiplier']), $sCondition);
                $sCondition =  $sRowOperator.' ( '. str_replace('ZZZ', ($vFieldValue * $asFieldData['sql']['multiplier']), $sCondition).' ) ';

                $sCondition = str_replace('>=', '<=', $sCondition);
              }
              elseif($sFieldOperator == 'max')
              {
                //ignore min
                $sCondition = str_replace('XXX', ($vFieldValue * $asFieldData['sql']['multiplier']), $sCondition);
                $sCondition =  $sRowOperator.' ( '. str_replace('ZZZ', ($vFieldValue * $asFieldData['sql']['multiplier']), $sCondition).' ) ';

                $sCondition = str_replace('<=', '>=', $sCondition);
              }
            }
            elseif(empty($asMatch[1]))
            {
              // Case #1 ==>   totally unmanageable --> just replace (XXX) (operator probably hardecoded  with is or =)
              if(is_array($vFieldValue))
              {
                $sCondition =  $sRowOperator.' ( '. str_replace('XXX', implode(',', $vFieldValue), $sCondition).' ) ';
              }
              else
                $sCondition =  $sRowOperator.' ( '. str_replace('XXX', $vFieldValue, $sCondition).' ) ';
            }
            else
            {
              // Case #2 ==>   we've got a template to play with
              $sCondition =  $sRowOperator.' '.$sCondition.' ';

              foreach($asMatch[1] as $sMatch)
              {
                $asFieldData['data']['field'] = $sMatch;
                $sSql = $this->_getSqlFromOperator($asFieldData['data'], $sFieldOperator, $vFieldValue);
                $sCondition =  str_replace('<<'.$sMatch.'>>', $sSql, $sCondition);
              }

              $sCondition =  $sRowOperator.' '. $sCondition .' ';
              //dump($sCondition);
            }
          }
          else
          {
            // - - - - - - - - - - - - - - - - - - - - - - - -
            //Standard case: use default feature to build sql

            //Multiple select are treated as inList (imploded)
            if(is_array($vFieldValue) && $asFieldData['data']['type'] != 'intList')
            {
              //dump(' is an array');

              $asFieldData['data']['field'] = $asFieldData['sql']['field'];

              $asArrayCondition = array();
              foreach($vFieldValue as $vValue)
              {
                if(!empty($vValue))
                  $asArrayCondition[] = ' ('.$asFieldData['sql']['field'].' '.$this->_getSqlFromOperator($asFieldData['data'], $sFieldOperator, $vValue).') ';
              }

              if(!empty($asArrayCondition))
                $sCondition = $sRowOperator.' '.implode(' OR ', $asArrayCondition).' ';
            }
            else
            {
              //dump(' is NOT an array');

              if(isset($asFieldData['sql']['field']) && !empty($asFieldData['sql']['field']))
              {
                $asFieldData['data']['field'] = $asFieldData['sql']['field'];
                $sCondition = $sRowOperator.' '.$asFieldData['sql']['field'].' '.$this->_getSqlFromOperator($asFieldData['data'], $sFieldOperator, $vFieldValue).' ';

                //dump(' field => '.$sCondition);
              }
              elseif(isset($asFieldData['sql']['where']) && !empty($asFieldData['sql']['where']))
              {
                $sCondition = $sRowOperator.' '.$asFieldData['sql']['where'].' '.$this->_getSqlFromOperator($asFieldData['data'], $sFieldOperator, $vFieldValue).' ';

                //dump(' where => '.$sCondition);
              }
            }
          }

          if(!empty($sCondition))
          {
            $asCondition[] = $sCondition;

            $explained_operator = $this->explain_field_operator($sFieldOperator);
            if(is_numeric($vFieldValue) || is_string($vFieldValue))
            {
              $explained_value = $this->explain_field_value($sFieldName, $vFieldValue);

              if (is_array($explained_value))
                $explained_value = $explained_value['label'];

              $asMessage['long'][] = $asFieldData['display']['label'].' '.$explained_operator.' '.$explained_value;
            }
            else
            {
              if (is_array($vFieldValue))
              {
                $field_value = ' ';
                foreach ($vFieldValue as $value)
                {
                  $explained_value = $this->explain_field_value($sFieldName, $value);

                  if (is_array($explained_value))
                    $explained_value = $explained_value['label'];

                  $field_value .= $explained_value.', ';
                }
              }
              else
                $field_value = ' ...';

              $asMessage['long'][]  = $asFieldData['display']['label'].' '.$explained_operator.$field_value;
            }
          }
        }
      }

      $nOpening = strlen($sGroupOperator) - strlen(str_replace('(', '', $sGroupOperator));
      $nEnding = strlen($sGroupOperator) - strlen(str_replace(')', '', $sGroupOperator));
      $nParanthese+= ($nOpening - $nEnding);

      if(!empty($asCondition))
      {
        //dump($asCondition);
        $oQB->addWhere($sGroupOperator.' ('.implode(' ', $asCondition).')', '');
      }
    }

    //-------------------------------------------------
    //finished treating all the groups, check paranthese
    if($nParanthese != 0)
    {
      if($nParanthese < 0)
      {
        $this->_addError('line '.__LINE__.' - Missing some parentheses. ');
      }
      else
      {
        $sParanthese = '';
        for($nCount = 0; $nCount < $nParanthese; $nCount++)
          $sParanthese.= ')';

        $oQB->addWhere($sParanthese, '');
      }
    }

    if (!empty($asMessage['long']))
      $oQB->setTitle('CpxSearch: '.implode(' , ', $asMessage['long']));
    else
      $oQB->setTitle('CpxSearch: Some data is missing');

    return $oQB;
  }

  private function explain_field_operator($operator)
  {
    $explanation = '';

    switch ($operator)
    {
      case 'superior':
        $explanation = 'higher or equal to';
        break;

      case 'inferior':
        $explanation = 'lesser or equal to';
        break;

      case 'different':
        $explanation = 'does not equal';
        break;

      case 'notin':
        $explanation = 'not in/such as';
        break;

      case 'in':
        $explanation = 'in/such as';
        break;

      case 'start':
        $explanation = 'start with';
        break;

      case 'end':
        $explanation = 'end with';
        break;

      default:
        $explanation = $operator;
        break;
    }

    return $explanation;
  }

  private function explain_field_value($label, $value)
  {
    $explanation = $this->slate_vars->get_var_info_by_label(strtolower(trim($label)), $value);

    if (empty($explanation))
      $explanation = $value;

    return $explanation;
  }

  private function _getSqlFromOperator($pasFieldType, $psOperator, $pvValue)
  {

    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    //Treat field by field in fucntion of the type of data
    if($pasFieldType['type'] == 'int' || $pasFieldType['type'] == 'key')
    {
      //check value
      if(!is_numeric($pvValue) || (int)$pvValue != $pvValue)
      {
        $this->_addError('line '.__LINE__.' - integer field, value is not an int ['.$pvValue.']');
        return ' IS NULL ';
      }

      if($pasFieldType['type'] == 'key' && $pvValue <= 0)
      {
        $this->_addError('line '.__LINE__.' - key field, value is < 0 ['.$pvValue.']');
        return ' IS NULL ';
      }

      //create query
      if($psOperator == '=' || $psOperator == 'equal' )
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).$this->_getModel()->dbEscapeString($pvValue);
      }
      if($psOperator == '!=' || $psOperator == 'different')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).$this->_getModel()->dbEscapeString($pvValue);
      }
      if($psOperator == '>=' || $psOperator == 'superior')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).$this->_getModel()->dbEscapeString($pvValue);
      }
      if($psOperator == '<=' || $psOperator == 'inferior')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).$this->_getModel()->dbEscapeString($pvValue);
      }

      $this->_addError('line '.__LINE__.' - operator inconnu for integer fields ['.$psOperator.']');
      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }


    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    if($pasFieldType['type'] == 'intList')
    {
      if(is_array($pvValue))
      {
        $pvValue = implode(',', $pvValue);
      }
      elseif(!is_listOfInt($pvValue))
      {
        $this->_addError('line '.__LINE__.' - listOfInt field, value is not a a coma sep list ['.$pvValue.']');
        return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
      }

      if($psOperator == 'in' )
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' ('.$pvValue.')';
      }

      if($psOperator == '!=' || $psOperator == 'different' || $psOperator == 'notin')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' ('.$pvValue.')';
      }

      if($psOperator == '=' || $psOperator == 'equal')
      {
        $asList = explode(',', $pvValue);

        //first value, just stick operator + value (field already there)
        $sSQL = ' = '.$asList[0];
        unset($asList[0]);

        foreach($asList as $nKey => $sValue)
        {
          $asList[$nKey] = ' '.$pasFieldType['field'].' = '.$sValue.' ';
        }

        return $sSQL. ' AND '.implode(' AND ', $asList);
      }


      $this->_addError('line '.__LINE__.' - operator inconnu for list of integers field ['.$psOperator.']');
      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';

    }


    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    if($pasFieldType['type'] == 'text')
    {
      if(strlen(trim($pvValue)) < 2)
      {
        $this->_addError('line '.__LINE__.' - text field, value is less than 2 characters ['.$pvValue.']');
        return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
      }

      if($psOperator == 'equal')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }
      if($psOperator == 'different')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }
      if($psOperator == 'contain')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString('%'.$pvValue.'%');
      }
      if($psOperator == 'start')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue.'%');
      }
      if($psOperator == 'end')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString('%'.$pvValue);
      }
      if($psOperator == 'fts_equal')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString('%'.$pvValue.'%');
      }
      if($psOperator == 'in')
      {
        $asWords = explode(',', $pvValue);

        if(count($asWords) == 1)
          return ' LIKE '.$this->_getModel()->dbEscapeString('%'.$pvValue.'%');

        /*$asWords = explode(',', $pvValue);
        foreach($asWords as $nKey => $sWord)
        {
          if($nKey == 0)
            $asWords[$nKey] = ' LIKE "%'.$this->_getModel()->dbEscapeString(trim($sWord), '', true).'%"';
          else
            $asWords[$nKey] = $pasFieldType['field'].' LIKE "%'.$this->_getModel()->dbEscapeString(trim($sWord), '', true).'%"';
        }
        return ' '.implode(' AND ', $asWords).' ';*/

        foreach($asWords as $nKey => $sWord)
        {
          $asWords[$nKey] = $this->_getModel()->dbEscapeString(trim($sWord), '', true);
        }

        return ' REGEXP "('.implode('|', $asWords).')" ';
      }
      if($psOperator == 'all')
      {
        $asWords = explode(',', $pvValue);
        foreach($asWords as $nKey => $sWord)
        {
          if($nKey == 0)
            $asWords[$nKey] = ' LIKE '.$this->_getModel()->dbEscapeString('%'.trim($sWord).'%');
          else
            $asWords[$nKey] = $pasFieldType['field'].' LIKE '.$this->_getModel()->dbEscapeString('%'.trim($sWord).'%');
        }

        return implode(' AND ', $asWords);
      }
    }


    if($pasFieldType['type'] == 'date')
    {
      if($psOperator != 'between' && !is_date($pvValue) && !is_datetime($pvValue))
      {
        $this->_addError('line '.__LINE__.' - date field, is not a proper date format ['.$pvValue.']');
        return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
      }

      if($psOperator == '=' || $psOperator == 'equal')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }

      if($psOperator == '!=' || $psOperator == 'different')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }

      if($psOperator == '>=' || $psOperator == 'superior')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }

      if($psOperator == '<=' || $psOperator == 'inferior')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString($pvValue);
      }

      if($psOperator == 'between')
      {
        $asDate = explode(' to ', $pvValue);
        if(count($asDate) < 1 || !is_date($asDate[0]))
        {
          $this->_addError('line '.__LINE__.' - date field, is not a proper date format ['.$pvValue.']');
          return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
        }

        if(!isset($asDate[1]) || !is_date($asDate[1]))
          $asDate[1] = $asDate[0];

        $asDate[0].= ' 00:00:00';
        $asDate[1].= ' 23:59:59';

        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' >= '.$this->_getModel()->dbEscapeString($asDate[0]).' AND '.$pasFieldType['field'].' <= '.$this->_getModel()->dbEscapeString($asDate[1]);
      }
    }



    if($pasFieldType['type'] == 'fts')
    {
      if(strlen(trim($pvValue)) < 1)
      {
        $this->_addError('line '.__LINE__.' - text field, value is less than 1 characters ['.$pvValue.']');
        return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
      }

      //strictly equal
      if($psOperator == 'fts_equal' || $psOperator == 'equal')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString(' '.$pvValue.' ').' ';
      }

      if($psOperator == 'contain')
      {
        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' '.$this->_getModel()->dbEscapeString('%'.$pvValue.'%');
      }

      if($psOperator == 'fts_in' || $psOperator == 'in')
      {
        $asWords = explode(',', $pvValue);
        foreach($asWords as $nKey => $sWord)
        {
          $asWords[$nKey] = $this->_getModel()->dbEscapeString(trim($sWord), '', true);
        }

        return $this->_getSqlOperator($pasFieldType, $psOperator, $pvValue).' "('.implode('|', $asWords).')" ';
      }

      if($psOperator == 'all')
      {
        $asWords = explode(',', $pvValue);
        foreach($asWords as $nKey => $sWord)
        {
          if($nKey == 0)
            $asWords[$nKey] = ' LIKE '.$this->_getModel()->dbEscapeString('%'.trim($sWord).'%');
          else
            $asWords[$nKey] = $pasFieldType['field'].' LIKE '.$this->_getModel()->dbEscapeString('%'.trim($sWord).'%');
        }

        return implode(' AND ', $asWords);
      }


    }

    $this->_addError('line '.__LINE__.' - operator not coded ['.$psOperator.'] for type ['.$pasFieldType['type'].']');
    return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
  }


  private function _getSqlOperator($pasFieldType, $psOperator, &$pvValue = null)
  {
    if(!assert('is_array($pasFieldType) && !empty($pasFieldType)'))
    {
      dump($pasFieldType);
      return __LINE__.' - missing field data ';
    }


    //Treat field by field in fucntion of the type of data
    if($pasFieldType['type'] == 'int' || $pasFieldType['type'] == 'key')
    {
      if($psOperator == '=' || $psOperator == 'equal' )
        return ' = ';

      if($psOperator == '!=' || $psOperator == 'different')
        return ' <> ';

      if($psOperator == '>=' || $psOperator == 'superior')
        return ' >= ';

      if($psOperator == '<=' || $psOperator == 'inferior')
        return ' <= ';

      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }



    if($pasFieldType['type'] == 'intList')
    {
      if($psOperator == 'in' )
        return ' IN ';

      if($psOperator == '!=' || $psOperator == 'different' || $psOperator == 'notin')
        return ' NOT IN';

      if($psOperator == '=' || $psOperator == 'equal')
        return '';

      if($psOperator == 'all')
        return '';

      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }



    if($pasFieldType['type'] == 'text')
    {
      if($psOperator == 'equal')
        return ' LIKE ';

      if($psOperator == 'different')
        return ' NOT LIKE ';

      if($psOperator == 'contain')
        return ' LIKE ';

      if($psOperator == 'start')
        return ' LIKE ';

      if($psOperator == 'end')
        return ' LIKE ';

      if($psOperator == 'fts_equal')
        return ' LIKE ';

      if($psOperator == 'in')
        return ' ';

      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }


    if($pasFieldType['type'] == 'date')
    {
      if($psOperator == '=' || $psOperator == 'equal')
        return ' = ';

      if($psOperator == '!=' || $psOperator == 'different')
        return ' <> ';

      if($psOperator == '>=' || $psOperator == 'superior')
        return ' >= ';

      if($psOperator == '<=' || $psOperator == 'inferior')
        return ' <= ';

      if($psOperator == 'between')
        return '';

      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }


    if($pasFieldType['type'] == 'fts')
    {
      if($psOperator == 'fts_equal' || $psOperator == 'equal')
      {
        $pvValue = addcslashes($pvValue, '*+-./\\"\';?');
        return ' REGEXP ';
      }

      if($psOperator == 'fts_in')
      {
        $pvValue = addcslashes($pvValue, '*+-./\\"\';?');
        return ' REGEXP ';
      }

      if($psOperator == 'contain' || $psOperator == 'equal')
        return ' LIKE ';

      if($psOperator == 'in')
      {
        $pvValue = addcslashes($pvValue, '*+-./\\"\';?');
        return ' REGEXP ';
      }

      return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
    }

    if($pasFieldType['type'] == 'rangeInteger')
    {
      $pvValue = explode('|', $pvValue);

      if($psOperator == 'between' || $psOperator == 'notbetween')
      {
        return ' ';
      }

      if($psOperator == 'min')
      {
        $pvValue = $pvValue[0];
        return ' ';
      }

      if($psOperator == 'max')
      {
        $pvValue = $pvValue[1];
        return ' ';
      }
    }

    assert('false; /* sql operator all wrong ==> '.$pasFieldType['type'].' / '.$psOperator.' */');
    return ' <[ IS NULL '.__LINE__.' / '.$pasFieldType['type'].' / '.$psOperator.' ]> ';
  }


  private function _addError($psString)
  {
    $this->casError[] = $psString;
    return true;
  }

  public function getError()
  {
    return $this->casError;
  }

}