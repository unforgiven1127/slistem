
<?php

require_once('component/sl_candidate/sl_candidate.class.php5');
require_once('component/sl_candidate/sl_candidate.model.php5');
require_once('component/sl_candidate/sl_candidate.model.ex.php5');
require_once('component/sl_candidate/resources/class/slate_vars.class.php5');

class CSl_candidateEx extends CSl_candidate
{
  private $_oPage = null;
  private $_oDisplay = null;
  private $casUserData = array();
  private $casActiveUser = array();
  private $coSlateVars = null;
  private $casCandidateData = array();

  private $casSettings = array();
  private $csTabSettings = '';
  private $csTplSettings = '';

  private $csSearchId = '';


  public function __construct()
  {
    $this->_oLogin = CDependency::getCpLogin();

    if($this->_oLogin->isLogged())
    {
      $this->_oPage = CDependency::getCpPage();
      $this->_oDisplay = CDependency::getCpHtml();

      $this->casUserData = $this->_oLogin->getUserData();

      //fetch all candidate display settings
      $oSettings = CDependency::getComponentByName('settings');

      $this->casSettings = $oSettings->getSettings(array('candidate_tabs', 'candidate_template', 'candi_list_field', 'candi_salary_format'), false);
      $this->csTabSettings = $this->casSettings['candidate_tabs'];
      $this->csTplSettings = $this->casSettings['candidate_template'];

      if(empty($this->casSettings['candi_list_field']))
        $this->casSettings['candi_list_field'] = array();
    }

    return true;
  }

  public function getDefaultType()
  {
    return CONST_CANDIDATE_TYPE_CANDI;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_LIST;
  }
  //====================================================================
  //  accessors
  //====================================================================

  /**
   * specific for SlateVars
   * @return object CSl_candidateModelEx
  */
  public function getModel()
  {
    return $this->_getModel();
  }

  public function getVars()
  {
    if($this->coSlateVars !== null)
      return $this->coSlateVars;

    if(empty($_SESSION['slate_vars']))
    {
      $this->coSlateVars = new CSlateVars();
      $_SESSION['slate_vars'] = serialize($this->coSlateVars);
    }
    else
    {
      $this->coSlateVars = unserialize($_SESSION['slate_vars']);
      if($this->coSlateVars == false)
        assert('false; // could not restore the var object');
    }

    return $this->coSlateVars;
  }

  //====================================================================
  //  interface
  //====================================================================

  /**   !!! Generic component method but linked to interfaces !!!
   *
   * Return an array listing the public "items" the component filtered by the interface
   * @param string $psInterface
   * @return array
   */
  public function getComponentPublicItems($psInterface = '')
  {
    $asItem = array();

    switch($psInterface)
    {
      case 'notification_item':
      case 'searchable':
        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('autocomplete' => 1));
        $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
            CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, 'label' => 'Candidate', 'search_url' => $sURL);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP);
        $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
            CONST_CP_TYPE => CONST_CANDIDATE_TYPE_COMP, 'label' => 'Company', 'search_url' => $sURL);
        break;

      default:
        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('autocomplete' => 1));
        $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
            CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, 'label' => 'Candidate', 'search_url' => $sURL);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP);
        $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
            CONST_CP_TYPE => CONST_CANDIDATE_TYPE_COMP, 'label' => 'Company', 'search_url' => $sURL);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING);
        $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
            CONST_CP_TYPE => CONST_CANDIDATE_TYPE_MEETING, 'label' => 'Candidate meeting ', 'search_url' => $sURL);
    }

    return $asItem;
  }


  //remove if the interface is not used
  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  /**
   *remove if the interface is not used
   * @return string json encoded
   */
  public function getAjax()
  {
    $this->_processUrl();
    $oPage = CDependency::getCpPage();

    // --------------------------------------------------------------
    //Complex search need 1 entry point on search for both data types

    if($this->csAction == CONST_ACTION_LIST || $this->csAction == CONST_ACTION_SEARCH)
    {
      if(!getValue('searchId') || getValue('clear_search'))
        $this->csSearchId = manageSearchHistory($this->csUid, $this->csType, true);
      else
      {
        /*dump('load search from id: '.getValue('searchId'));
        dump($_POST);*/
        $this->csSearchId = manageSearchHistory($this->csUid, $this->csType);
        //dump($_POST);

        //If no specific sorting value, reload previous sorting values
        if(!getValue('sortfield'))
        {
          $asOrder = getSearchHistory($this->csSearchId, $this->csUid, $this->csType);
          $_POST['sortfield'] = $asOrder['sortfield'];
          $_POST['sortorder'] = $asOrder['sortorder'];
        }
      }
    }



    switch($this->csType)
    {
      case CONST_CANDIDATE_TYPE_CANDI:

        switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return json_encode($oPage->getAjaxExtraContent(array('data' => convertToUtf8($this->_getCandidateView($this->cnPk)))));
            break;

          case CONST_ACTION_LIST:
            return json_encode($oPage->getAjaxExtraContent(array('data' => convertToUtf8($this->_getCandidateList(true)))));
            break;

          case CONST_ACTION_ADD:
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getCandidateAddForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($oPage->getAjaxExtraContent($this->_saveCandidate($this->cnPk)));
            break;

          case CONST_ACTION_EDIT:
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getCandidateAddForm($this->cnPk), 'UTF-8')));
            break;

          case CONST_ACTION_LOG:
            $this->_accessRmContactDetails($this->cnPk);
            return json_encode(array('data' => 'ok'));
            break;

          case CONST_ACTION_SEARCH:

            if(getValue('autocomplete'))
            {
              return $this->_autocompleteCandidate();
            }

            if(getValue('complex_search'))
            {
              $this->csType = getValue('data_type');
              $oSearch = CDependency::getComponentByName('search');
              $oQB = $oSearch->buildComplexSearchQuery();

              $asError = $oSearch->getError();
              if(!empty($asError))
                return json_encode(array('alert' => implode('<br />', $asError)));
            }
            else
            {
              $oQB = $this->_getModel()->getQueryBuilder();

              require_once('component/sl_candidate/resources/search/quick_search.class.php5');
              $oQS = new CQuickSearch($oQB);
              $sError = $oQS->buildQuickSearch();

              if(!empty($sError))
                return json_encode(array('alert' => $sError));
            }

            return json_encode($oPage->getAjaxExtraContent(array('data' => convertToUtf8($this->_getCandidateList(true, $oQB)), 'action' => 'goPopup.removeActive(\'layer\'); initHeaderManager(); ')));
            break;

            case CONST_ACTION_MANAGE:
              $asDuplicate = $this->_getMergeForm($this->cnPk);

              if(isset($asDuplicate['error']))
                return json_encode($oPage->getAjaxExtraContent(array('data' => $asDuplicate['error'])));

              return json_encode($oPage->getAjaxExtraContent(array('data' => convertToUtf8($asDuplicate['data']))));
              break;

              case CONST_ACTION_TRANSFER:

                return json_encode($oPage->getAjaxExtraContent($this->_mergeDeleteCandidate($this->cnPk)));
                break;

        }
        break;

      case CONST_CANDIDATE_TYPE_COMP:

        switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return json_encode($oPage->getAjaxExtraContent(array('data' => convertToUtf8($this->_getCompanyView($this->cnPk)))));
            break;

          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getCompanyForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_saveCompany($this->cnPk));
            break;

          case CONST_ACTION_LIST:
            //list and search
            $asHTML = $this->_getCompanyList();
            $asHTML['data'] = convertToUtf8($asHTML['data']);
            return json_encode($oPage->getAjaxExtraContent($asHTML));
            break;

          case CONST_ACTION_SEARCH:

            if(getValue('complex_search'))
            {
              $this->csType = getValue('data_type');
              $oSearch = CDependency::getComponentByName('search');
              $oQB = $oSearch->buildComplexSearchQuery();

              $asError = $oSearch->getError();
              if(!empty($asError))
                return json_encode(array('alert' => implode('<br />', $asError)));

              $asHTML = $this->_getCompanyList($oQB);
              $asHTML['data'] = convertToUtf8($asHTML['data']);
              return json_encode($oPage->getAjaxExtraContent($asHTML));
            }

            return $this->_autocompleteCompany();
            break;
        }
        break;


      case CONST_CANDIDATE_TYPE_FEED:

        if(empty($this->cnPk))
          return json_encode(array('alert' => 'No company to search news about'));

        $asData = array('sl_candidatepk' => (int)getValue('sl_candidatepk', 0), 'companyfk' => $this->cnPk);
        $this->_updateCompanyRss($this->cnPk);
        $asFeed = $this->_getCompanyFeedTab($asData);
        return json_encode(array('data' => $asFeed['content'], 'action' => '$(\'.aTabContent:visible\').mCustomScrollbar(\'update\');'));
        break;

      case CONST_CANDIDATE_TYPE_MEETING:

         switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getCandidateMeetingHistory($this->cnPk))));
            break;

          case CONST_ACTION_LIST:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getConsultantMeeting())));
            break;

          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            $nMeetingPk = (int)getValue('meetingpk');
            return json_encode($this->_oPage->getAjaxExtraContent($this->_getCandidateMeetingForm($this->cnPk, $nMeetingPk)));
            break;

          case CONST_ACTION_DONE:
            $nMeetingPk = (int)getValue('meetingpk');
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getMeetingDoneForm($this->cnPk, $nMeetingPk))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_saveMeeting($this->cnPk));
            break;

          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_updateMeeting($this->cnPk, true));
            break;

          case CONST_ACTION_VALIDATE:
            return json_encode($this->_updateMeetingDone($this->cnPk));
            break;
        }
        break;

        case CONST_CANDIDATE_TYPE_CONTACT:

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getCandidateContactForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_getCandidateContactSave($this->cnPk));
            break;
        }
        break;

      case CONST_CANDIDATE_TYPE_DOC:

        switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_getViewLastDocument($this->cnPk)));
            break;

          case CONST_ACTION_ADD:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getResumeAddForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_getResumeSaveAdd($this->cnPk));
            break;
        }
        break;

      case CONST_CANDIDATE_TYPE_RM:

        switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_getRmList($this->cnPk)));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($this->_cancelCandidateRm($this->cnPk));
            break;

          case CONST_ACTION_ADD:
            return json_encode($this->_addCandidateRm($this->cnPk));
            break;

          case CONST_ACTION_EDIT:
            return json_encode($this->_extendCandidateRm($this->cnPk));
            break;

        }
        break;

      case CONST_CANDIDATE_TYPE_LOGS:

        return json_encode($this->_oPage->getAjaxExtraContent($this->_getMoreLogs($this->cnPk)));
        break;


      /* ******************************************** */
      /* ******************************************** */
      // Automcplete fields
      case CONST_CANDIDATE_TYPE_INDUSTRY:
      case CONST_CANDIDATE_TYPE_OCCUPATION:
        switch($this->csAction)
        {
          case CONST_ACTION_SEARCH:
            return $this->_autocompleteSearch($this->csType);
            break;
        }
        break;
    }
  }

  //remove if the interface is not used
  public function getHtml()
  {
    $this->_processUrl();

    //================================================================
    //================================================================
    //Going to have a few generic pages requiring "addPageStructure", so it's here

    if(getValue('contact_sheet'))
    {
      $oLogin = CDependency::getComponentByName('login');
      $this->_oPage->addJsFile(self::getResourcePath().'js/sl_candidate.js');
      return $oLogin->displayList(false);
    }


    switch($this->csType)
    {
      case CONST_CANDIDATE_TYPE_CANDI:

        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return $this->_displayCandidateList();
            break;

          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return $this->_getCandidateAddForm($this->cnPk);
            break;

          case CONST_ACTION_FASTEDIT:
            return $this->_getCandidateFastEdit($this->cnPk);
            break;

          case CONST_ACTION_VIEW:
            /*//load an empty tab with a js to load the candidate
            $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $this->cnPk);
            $sHTML = 'Candidate #'.$this->cnPk.'

              <script>view_candi("'.$sURL.'");</script>';
            return addPageStructure($sHTML, 'candi');*/
            $_POST['candidate'] = $this->cnPk;
            return mb_convert_encoding($this->_getCandidateList(), 'utf8');
            break;
        }
        break;


      case CONST_CANDIDATE_TYPE_MEETING:

        switch($this->csAction)
        {
          case CONST_ACTION_SAVEEDIT:
            $asResult = $this->_updateMeeting($this->cnPk);

            if(isset($asResult['error']))
              return $this->_oDisplay->getErrorMessage($asResult['error'], true);

            return $this->_oDisplay->getBlocMessage($asResult['data'], true);

            break;

          case CONST_ACTION_EDIT:

            $asResult = $this->_getCandidateMeetingForm($this->cnPk);
            if(isset($asResult['error']) && !empty($asResult['error']))
              return $this->_oDisplay->getErrorMessage($asResult['error'], true);

            return $this->_oDisplay->getBlocMessage($asResult['data'], true);
            break;
        }
        break;


        case CONST_CANDIDATE_TYPE_COMP:
          switch($this->csAction)
          {
            case CONST_ACTION_LIST:
              return $this->_getNoScoutList();
              break;
          }
          break;


        case CONST_CANDIDATE_TYPE_USER:
          $oLogin = CDependency::getCpLogin();
          return $oLogin->displayUserList(true, '');
          break;
    }
  }


  //==> cron interface 1 fct
  //remove if the interface is not used
  public function getCronJob()
  {
    $nHour = (int)date('H');
    echo 'SL_Candidate cron job | Hr: '.$nHour.'<br />
      &update_rss_feed=1 for Company rss feed<br />
      &update_currency=1 for Currency listing<br /><br />
      &update_profile_rating=1 for quality profile<br />
      &rm_notification=1 for send email for expiring rm (usually at 7am)<br /><br />';

    if(getValue('update_rss_feed'))
    {
      $this->_updateCompanyRss();
    }

    if(getValue('update_profile_rating'))
    {
       $this->updateCandidateProfiles();
    }

    //$this->_updateRm();
    if(getValue('update_currency'))
    {
      require_once('component/sl_candidate/resources/currency/update_currency.php5');
    }

    $nHour = (int)date('H');
    $bForceNotify = (bool)getValue('rm_notification');
    if($nHour == 7 || $bForceNotify)
    {
      $oSetting = CDependency::getComponentByName('settings');
      $sLastUpdate = $oSetting->getSettingValue('cron_rm_notification');
      if(!$bForceNotify && $sLastUpdate > date('Y-m-d'))
      {
        echo 'already launched on RM '.$sLastUpdate;
      }
      else
      {
        $oSetting->setSystemSettings('cron_rm_notification', date('Y-m-d H:i:s'));
        $this->_manageRmExpiration($bForceNotify);
      }
    }

    return '';
  }


  //==> has settings interface 2 fcts
  public function declareSettings()
  {
    return array();
  }


  public function declareUserPreferences()
  {
    $asPrefs[] = array(
        'fieldname' => 'candidate_tabs',
        'fieldtype' => 'select',
        'options' => array('full' => '1 block - Vertical tabs', 'fullH' => '1 block - Horizontal tabs', 'half' => '2 blocks - Vertical tabs', 'halfH' => '2 blocks - Horizontal tabs'),
        'label' => 'Tabs display option',
        'description' => 'Define if the candidates and companies tabs are displayed in 1 or 2 sections',
        'value' => 'half'
    );

    $asPrefs[] = array(
        'fieldname' => 'candidate_template',
        'fieldtype' => 'select',
        'options' => array('default_candidate' => 'Slistem 2.6', 'candidate_sl3' => 'Slistem 3'),
        'label' => 'Template for candidate profile',
        'description' => 'Choose a template to display the candidates details',
        'value' => 'default_candidate'
    );

    $asPrefs[] = array(
        'fieldname' => 'qs_wide_search',
        'fieldtype' => 'select',
        'options' => array('0' => 'No', '1' => 'Yes'),
        'label' => 'Use wide search by default',
        'description' => 'Use wide search by default (firstname%, lastname%)',
        'value' => '0'
    );

    $asPrefs[] = array(
        'fieldname' => 'qs_name_order',
        'fieldtype' => 'select',
        'options' => array('lastname' => 'Lastname, Firstname', 'firstname' => 'Firstname, Lastname', 'none' => 'Indifferent'),
        'label' => 'Order in the name field',
        'description' => 'Order of the name in QS field',
        'value' => 'lastname'
    );

    $asPrefs[] = array(
        'fieldname' => 'candi_list_field',
        'fieldtype' => 'select_multi',
        'options' => array('Age' => 'date_birth', 'Salary' => 'salary', 'Managed by' => 'manager', 'Last note' => 'note', 'Title' => 'title', 'Department' => 'department'),
        'label' => 'Field to display in the list',
        'description' => 'Select the fields you want tosee in the candidate list',
        'value' => '',
        'multiple' => 1
    );

    $asPrefs[] = array(
        'fieldname' => 'candi_salary_format',
        'fieldtype' => 'select',
        'options' => array('' => 'Raw format 1,000,000¥', 'K' => 'Kilo 1,000 K¥', 'M' => 'Million 1 M¥'),
        'label' => 'Salary format',
        'description' => 'Choose what format to use to input salary and bonus values',
        'value' => '',
        'multiple' => 1
    );


    return $asPrefs;
  }


  //==> search interface 3 fcts
  public function getSearchFields($psType = '', $pbAdvanced = false)
  {
    $asFields = array();

    if($pbAdvanced)
    {
      //keep the file somwhere else, gonna be big
      require_once($_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'conf/field_description.inc.php5');
    }
    else
    {
      $asFields = array(
          CONST_CANDIDATE_TYPE_CANDI => array(
              'table' => 'sl_candidate',
              'custom_url' => 'google.com',
              'label' => 'Candidates',
              'fields' => array(
                  'text' => array('firstname', 'lastname', 'occupation', 'industry', 'note')
                  )
              )
          );
    }

    return $asFields;
  }

  public function getSearchResultMeta($psType = '')
  {
    $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, $psType);
    $asResultMeta = array('custom_result_page' => $sURL,
    'onBeforeSubmit' =>
      ' var asContainer = goTabs.create(\''.$psType.'\', \'\', true, \''.ucfirst($psType).' search\');
        AjaxRequest(\''.$sURL.'\', \'body\', \'searchFormId\', asContainer[\'id\']);
        return true; ');

    //AjaxRequest(\''.$sURL.'\', \'\', \'searchFormId\', asContainer[\'id\'], \'\', \'\', \'goPopup.removeActive(\\\'layer\\\'); \');

    return $asResultMeta;
  }

  public function getSearchResult($psDatatype, $psKeywords, $psFieldType = 'all', $pnDisplayPage=0)
  {
    $oPage = CDependency::getCpPage();
    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, $psDatatype);

    $asResult = array();
    $asResult['custom_result']['script'] = '
      var asContainer = goTabs.create(\''.$psDatatype.'\', \'\', \'\', \'search result\');
      AjaxRequest(\''.$sURL.'\', \'\', \'\', asContainer[\'id\']);
      goPopup.removeActive(\'layer\');
      ';

    $asResult['custom_result']['html'] = 'Loading ... ';
    return $asResult;
  }



  //notification_item => 1 function

  /**
   * Return an array that MUST contain 4 fields: label. description, url, link
   * @param variant $pvItemPk (integer or array of int)
   * @param string $psAction
   * @param string $psItemType
   * @return array of string
  */
  public function getItemDescription($pvItemPk, $psAction = '', $psItemType = 'candi')
  {
    if(!assert('is_arrayOfInt($pvItemPk) || is_key($pvItemPk)'))
      return array();

    if(!assert('!empty($psItemType)'))
      return array();

    $oLogin = CDependency::getCpLogin();

    switch($psItemType)
    {
      case 'candi':

        $asCandidate = $this->_getModel()->getCandidateData($pvItemPk, true, true);
        if(empty($asCandidate))
          return array();

        $asItem = array();
        foreach($asCandidate as $nPk => $asData)
        {
          $asItem[$nPk]['label'] = $this->_getCandidateNameFromData($asData);
          $asItem[$nPk]['url'] = $this->_oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nPk);
          $asItem[$nPk]['link'] = $this->_oDisplay->getLink($asItem[$nPk]['label'], $asItem[$nPk]['url'], array('target' => '_blank'));
          $asItem[$nPk]['link_popup'] = $this->_oDisplay->getLink($asItem[$nPk]['label'], 'javascript:;', array('onclick' => 'popup_candi(this, \''.$asItem[$nPk]['url'].'&pg=ajx\'); '));
          $asItem[$nPk]['status'] = $asData['statusfk'];

          $asDesc = array();
          $asDesc[0] = 'RefId : #<a href="'.$asItem[$nPk]['url'].'">'.$nPk.'</a>';
          $asDesc[1] = '';

          if(!empty($asData['company_name']))
            $asDesc[1].= 'Working at '.$asData['company_name'];

          if(!empty($asData['department']))
            $asDesc[1].= '  |  department: '.$asData['department'];

          if(!empty($asData['title']))
            $asDesc[1].= '  |  as : '.$asData['title'];

          $asDesc[2] = '';
          if(!empty($asData['industry']))
            $asDesc[2] = 'Industry: '.$asData['industry'];

          if(!empty($asData['occupation']))
          {
            if(!empty($asDesc[2]))
              $asDesc[2].= '  |  ';

            $asDesc[2].= 'Occupation: '.$asData['occupation'];
          }

          $asDesc[3] = 'Created on the '.$asData['date_added'].' by '.$oLogin->getUserLink((int)$asData['created_by']);
          $asItem[$nPk]['description'] = implode('<br />', $asDesc);
        }

        break;

      case 'comp':

        if(is_integer($pvItemPk))
          $sPk = ' = '.$pvItemPk;
        else
          $sPk = ' IN('.implode(',', $pvItemPk).') ';

        $oDbResult = $this->_getModel()->getByWhere('sl_company', 'sl_companypk '. $sPk);
        $bRead = $oDbResult->readFirst();
        if(!$bRead)
          return array();

        $asItem = array();
        while($bRead)
        {
          $nPk = (int)$oDbResult->getFieldValue('sl_companypk');

          $asItem[$nPk]['label'] = '#'.$nPk.' - '.$oDbResult->getFieldValue('name');

          $asItem[$nPk]['description'] = $oDbResult->getFieldValue('description');
          $asItem[$nPk]['description'].= '<br />Created on the '.$oDbResult->getFieldValue('date_created').' by '.$oLogin->getUserLink((int)$oDbResult->getFieldValue('created_by'));


          $asItem[$nPk]['url'] = $this->_oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, $nPk);
          $asItem[$nPk]['link'] = $this->_oDisplay->getLink($asItem[$nPk]['label'], $asItem[$nPk]['url']);
          $asItem[$nPk]['link_popup'] = $this->_oDisplay->getLink($asItem[$nPk]['label'], 'javascript:;', array('onclick' => 'popup_candi(this, \''.$asItem[$nPk]['url'].'&pg=ajx\'); '));

          $bRead = $oDbResult->readNext();
        }

        break;

      default:
        assert('false; // unknown type');
        return array();
        break;
    }

    return $asItem;
  }

  //------------------------------------------------------
  //  System and cached function (industry, location, occupations ...
  //------------------------------------------------------





  private function _getCandidateNameFromData($pasData)
  {
    if(empty($pasData))
      return '';

    return (($pasData['sex'] == 1)? 'Mr ': 'Ms ').$pasData['lastname'].' '.$pasData['firstname'];
  }

  //====================================================================
  //  Component core
  //====================================================================




    //------------------------------------------------------
    //  Private methods
    //------------------------------------------------------

    private function _displayCandidateList($pbInAjax = false)
    {
      $this->_oPage->addCssFile(self::getResourcePath().'css/sl_candidate.css');
      $this->_oPage->addJsFile(self::getResourcePath().'js/sl_candidate.js');
      $sHTML = $this->_getTopPageSection();

      $sLiId = uniqid();
      if(!$pbInAjax)
        $this->_oPage->addCustomJs('$(document).ready(function(){  initHeaderManager(); goTabs.preload(\'candi\', \''.$sLiId.'\', true); });');


      //container in which we'll put the list
      $sHTML.=  $this->_oDisplay->getBlocStart('', array('id' => 'bottomCandidateSection', 'class' => 'bottomCandidateSection'));
      $sHTML.=  $this->_oDisplay->getListStart('tab_content_container');

        $sHTML.=  $this->_oDisplay->getListItemStart($sLiId);

          //$sHTML.= $this->_oDisplay->getBlocStart(uniqid(), array('class' => 'scrollingContainer'));
          $sHTML.= $this->_getCandidateList($pbInAjax);
          //$sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.=  $this->_oDisplay->getListItemEnd();

      $sHTML.=  $this->_oDisplay->getListEnd();
      $sHTML.=  $this->_oDisplay->getBlocEnd();
      return $sHTML;
    }


    private function _getTopPageSection()
    {
      $nItemPk = (int)getValue('slpk', 0);
      $sItemType = getValue('sltype');

      if(!empty($nItemPk))
      {
        if(empty($sItemType))
          $sItemType = CONST_CANDIDATE_TYPE_CANDI;

        $sContent = $this->_getItemTopSection($sItemType, $nItemPk);
        $sClass = '';
      }
      else
      {
        $sContent = '';
        $sClass = ' hidden ';
      }

      return $this->_oDisplay->getBloc('', $sContent, array('id' => 'topCandidateSection', 'class' => 'topCandidateSection'.$sClass));
    }



    private function _getItemTopSection($psItemType, $pnItemPk)
    {
      if(!assert('!empty($psItemType) && is_key($pnItemPk)'))
        return '';

      switch($psItemType)
      {
        case CONST_CANDIDATE_TYPE_CANDI:
          return $this->_getCandidateView($pnItemPk);
          break;

        case CONST_CANDIDATE_TYPE_COMP:
          return $this->_getCompanyView($pnItemPk);
          break;

        case CONST_CANDIDATE_TYPE_POS:
          return $this->_getPositionView($pnItemPk); // or call position component
          break;

      }

      return __LINE__.' - Nothing to display.';
    }


    private function _getCandidateView($pnPk, $pasRedirected = array())
    {
      if(!assert('is_key($pnPk)'))
        return '';

      $sHTML = '';

      //-----------------------------------------------------------------------
      //check the candidate profile and update _has_doc, in_play, quality_ratio
      if(getValue('check_profile'))
      {
        $asCandidate = $this->updateCandidateProfile($pnPk);
      }

      $sViewURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pnPk);
      if(getValue('preview'))
      {
        $sHTML.= $this->_oDisplay->getBloc('', '
          <a href="javascript:;" class="candi-pop-link" onclick="goPopup.removeAll(true); view_candi(\''.$sViewURL.'\');">close <b>all</b> popops & view in page<img src="/component/sl_candidate/resources/pictures/goto_16.png" /></a>
          ', array('class' => 'close_preview'));
      }

      $asCandidate = $this->_getModel()->getCandidateData($pnPk, true);
      if(!empty($asCandidate['_sys_redirect']))
      {
        $oRight = CDependency::getComponentByName('right');
        if(!$oRight->canAccess($this->csUid, 'sys_dba', CONST_CANDIDATE_TYPE_CANDI))
          return $this->_getCandidateView((int)$asCandidate['_sys_redirect'], $asCandidate);
      }

      //converting language attributes
      if(isset($asCandidate['attribute']['candi_lang']))
      {
        $asLanguage = $this->getVars()->getLanguageList();
        foreach($asCandidate['attribute']['candi_lang'] as $nKey => $nLanguageFk)
              $asCandidate['attribute']['candi_lang'][$nKey] = $asLanguage[$nLanguageFk];
      }

      if(empty($asCandidate))
      {
        return $this->_oDisplay->getBlocMessage('<div class="no-candidate">
          Candidate #'.$pnPk.' not found.<br /><br />
            This candidate may have been deleted or access to its data may be restricted.<br />
            If you think it\'s an error report a bug using the link in the menu.</div>');
      }

      //----------------------------------------------------------------------
      //fetch other data that are not in the candidate table
      //TODO: same queries/functions used again when creatign tabs ...
      $asCandidate['rm'] = $this->_getModel()->getCandidateRm($pnPk);
      $asCandidate['redirected'] = $pasRedirected;



      $oPosition = CDependency::getComponentByName('sl_position');
      $asPlayFor = $oPosition->getApplication($pnPk, false, true);

      $asCandidate['in_play'] = count($asPlayFor['active']);
      if(empty($asCandidate['in_play']))
      {
        $asCandidate['in_play'] = 0 - count($asPlayFor['history']);
      }
      if(empty($asCandidate['in_play']))
      {
        $asCandidate['in_play'] = 0;
      }


      $asCandidate['nb_meeting'] = 0;
      $asCandidate['date_meeting'] = '';
      $asCandidate['last_meeting'] = '';

      $oDbResult = $this->_getModel()->getByFk($pnPk, 'sl_meeting', 'candidate', '*', 'meeting_done, date_meeting');
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        //$sMeetingDate = $oDbResult->getFieldValue('date_meeting');
        $sMeetingDate = $oDbResult->getFieldValue('date_met');
        $nStatus = (int)$oDbResult->getFieldValue('meeting_done');

        if($nStatus >= 0)
        {
          if($nStatus > 0)
          {
            if(empty($asCandidate['last_meeting']) || $asCandidate['last_meeting'] < $sMeetingDate)
              $asCandidate['last_meeting'] = $sMeetingDate;
          }
          else
          {
            if(empty($asCandidate['date_meeting']) || $asCandidate['date_meeting'] > $sMeetingDate)
              $asCandidate['date_meeting'] = $sMeetingDate;
          }

          $asCandidate['nb_meeting']++;
        }

        $bRead = $oDbResult->readNext();
      }

      //----------------------------------------------------------------------

      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTopSectLeft'));
      $sHTML.= $this->_getCandidateProfile($asCandidate);

      //store a description of the current item for later use in javascript
      $sHTML.= $this->_oDisplay->getBloc('', '', array('class' => 'itemDataDescription hidden',
          'data-type' => 'candi',
          'data-pk' => $pnPk,
          'data-label' => $asCandidate['lastname'].' '.$asCandidate['firstname'],
          'data-cp_item_selector' => '555-001|@|ppav|@|candi|@|'.$pnPk));

      $sHTML.= $this->_oDisplay->getBlocEnd();

      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTopSectRight candiTabContainer'));
      $sHTML.= $this->_getCandidateRightTabs($asCandidate);
      $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getFloatHack();

      //fired before all the code is loaded ->
      //$sHTML.='<script> $(".candiTabsContent").mCustomScrollbar(); </script>';
      //a bit slow ?
      //$sHTML.='<script>$(".aTabContent").mCustomScrollbar({advanced:{updateOnContentResize: true}}); </script>';



      $sLink = 'javascript: view_candi(\''.$sViewURL.'\'); ';
      $sName = $asCandidate['lastname'].' '.$asCandidate['firstname'];
      logUserHistory($this->csUid, $this->csAction, $this->csType, $this->cnPk, array('text' => 'view - '.$sName.' (#'.$pnPk.')', 'link' => $sLink));

      return $sHTML;
    }

    private function _getCandidateProfile($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return '';

      $sTemplate =  $_SERVER['DOCUMENT_ROOT'].'/'.self::getResourcePath().'/template/';

      if(!empty($this->casSettings['candidate_template']))
        $sTemplate.= $this->casSettings['candidate_template'].'.tpl.class.php5';
      else
        $sTemplate.= 'default_candidate.tpl.class.php5';

      //params for the sub-templates when required
      $oTemplate = $this->_oDisplay->getTemplate($sTemplate);
      return $oTemplate->getDisplay($pasCandidateData, $this->csTplSettings);
    }

    private function _getCandidateRightTabs($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return '';

      //gonna be needed for multiple tabs
      $this->casUsers = $this->_oLogin->getUserList(0, false, true);

      if($this->csTabSettings == 'full')
        return $this->_getRightTabsFull($pasCandidateData);

      if($this->csTabSettings == 'fullH')
        return $this->_getRightTabsFull($pasCandidateData, 'candiHoriSizeTabs');

      if($this->csTabSettings == 'halfH')
        return $this->_getRightTabsHalfed($pasCandidateData, 'candiHoriHalfSizeTabs', true);

      return $this->_getRightTabsHalfed($pasCandidateData);
    }

    private function _getRightTabsHalfed($pasCandidateData, $psClass = '', $pbLinkTabs = false)
    {
      $sCharSelected = $sNoteSelected = 'selected';
      $sDocSelected = $sContactSelected = $sPositionSelected = $sJdSelected = '';
      $pasCandidateData['sl_candidatepk'] = (int)$pasCandidateData['sl_candidatepk'];


      // fetch the content of each tab first. Tab selection, or specific actions may come from that
      $oNotes = CDependency::getComponentByName('sl_event');
      $asCharNotes = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, 'character', array(), true, 'character');
      if(empty($asCharNotes['nb_result']))
      {
        $sCharSelected = '';
        $sContactSelected = 'selected';
        $asCharNotes['nb_result'] = '';
      }
      else
        $asCharNotes['nb_result'] = '<span class="tab_number tab_level_1">'.$asCharNotes['nb_result'].'</span>';

      $asContact = $this->_getContactTab($pasCandidateData);
      if(empty($asContact['nb_result']))
      {
         $sContactSelected = '';
        (empty($sCharSelected))? $sDocSelected = 'selected' : '';
        $asContact['nb_result'] = '';
      }
      else
        $asContact['nb_result'] = '<span class="tab_number tab_level_1">'.$asContact['nb_result'].'</span>';


      $asNotes = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, '', array('character', 'cp_history', 'cp_hidden'), true, 'note');
      if(empty($asNotes['nb_result']))
      {
        $sNoteSelected = '';
        $sJdSelected = 'selected';
        $asNotes['nb_result'] = '';
      }
      else
        $asNotes['nb_result'] = '<span class="tab_number tab_level_1">'.$asNotes['nb_result'].'</span>';

      $asDocument = $this->_getDocumentTab($pasCandidateData);
      if(empty($asDocument['nb_result']))
      {
        $asDocument['nb_result'] = '';
      }
      else
        $asDocument['nb_result'] = '<span class="tab_number tab_level_1">'.$asDocument['nb_result'].'</span>';

      $asPosition = $this->_getPositionTab($pasCandidateData);
      if(empty($asPosition['nb_result']))
      {
        $asPosition['nb_result'] = '';
      }
      else
        $asPosition['nb_result'] = '<span class="tab_number tab_level_1">'.$asPosition['nb_result'].'</span>';

      $asCpHistory = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, 'cp_history', array(), false);
      if(empty($asCpHistory['nb_result']))
      {
        $asCpHistory['nb_result'] = '';
      }
      else
        $asCpHistory['nb_result'] = '<span class="tab_number tab_level_1">'.$asPosition['nb_result'].'</span>';


      $asCompanyFeed = $this->_getCompanyFeedTab($pasCandidateData);
      $asActivity = $this->_getRecentActivity($pasCandidateData['sl_candidatepk']);
      $sActionTab = $this->_getActionTab($pasCandidateData);


      //manage tab height by adding halfSize class. Full size by defaut
      if(empty($psClass))
        $psClass = 'candiHalfSizeTabs';

      $sHTML = $this->_oDisplay->getBlocStart('', array('class' => $psClass.' candiRightTabsContainer'));

        $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candiTabsVertical'));
          $sHTML.= '<li id="tabLink0" onclick="toggleCandiTab(this, \'candiTab0\', \'#ctc_1\');" class="tabActionLink tab_action" title="All the actions to be done on a candidate"></li>';
          $sHTML.= '<li id="tabLink1" onclick="toggleCandiTab(this, \'candiTab1\', \'#ctc_1\');" class="tab_character '.$sCharSelected.'" title="Displays the character notes" >'.$asCharNotes['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink2" onclick="toggleCandiTab(this, \'candiTab2\', \'#ctc_1\');" class="tab_contact '.$sContactSelected.' title="Displays the contact details">'.$asContact['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink3" onclick="toggleCandiTab(this, \'candiTab3\', \'#ctc_1\');" class="tab_document '.$sDocSelected.'" title="Displays the uploaded documents">'.$asDocument['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink4" onclick="toggleCandiTab(this, \'candiTab4\', \'#ctc_1\');" class="tab_company" title="Displays the company news feed"></li>';
        $sHTML.= $this->_oDisplay->getListEnd();

        if($pbLinkTabs)
        {
          $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candiTabsVertical'));
          $sHTML.= '<li id="tabLink5" onclick="toggleCandiTab(this, \'candiTab5\', \'#ctc_2\');" class="tab_note '.$sNoteSelected.'" title="Displays notes">'.$asNotes['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink6" onclick="toggleCandiTab(this, \'candiTab6\', \'#ctc_2\');" class="tab_activity" title="Displays the recent activity of this candidate"></li>';
          $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\', \'#ctc_2\');" class="tab_history" title="Displays the company history">'.$asCpHistory['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink8" onclick="toggleCandiTab(this, \'candiTab8\', \'#ctc_2\');" class="tab_position '.$sJdSelected.'" title="Displays the positions & applications">'.$asPosition['nb_result'].'</li>';
          $sHTML.= $this->_oDisplay->getListEnd();
        }

        $sHTML.= $this->_oDisplay->getBlocStart('ctc_1', array('class' => 'candiTabsContent'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab0', $sActionTab, array('class' => 'aTabContent hidden '));
          $sHTML.= $this->_oDisplay->getBloc('candiTab1', $asCharNotes['content'], array('class' => 'aTabContent hidden '.$sCharSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab2', $asContact['content'], array('class' => 'aTabContent hidden '.$sContactSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab3', $asDocument['content'], array('class' => 'aTabContent hidden '.$sDocSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab4', $asCompanyFeed['content'], array('class' => 'aTabContent hidden'));
        $sHTML.= $this->_oDisplay->getBlocEnd();

      $sHTML.= $this->_oDisplay->getBlocEnd();

      //separator
      if($psClass == 'candiHoriHalfSizeTabs')
        $sHTML.= $this->_oDisplay->getBloc('', '&nbsp;', array('class' => 'candiTabsSeparator Htabs'));
      else
        $sHTML.= $this->_oDisplay->getBloc('', '&nbsp;', array('class' => 'candiTabsSeparator '));

      $sHTML.= $this->_oDisplay->getBlocStart('ctc_2', array('class' => $psClass.' candiRightTabsContainer'));

        if(!$pbLinkTabs)
        {
          $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candiTabsVertical'));
          $sHTML.= '<li id="tabLink5" onclick="toggleCandiTab(this, \'candiTab5\', \'#ctc_2\');" class="tab_note '.$sNoteSelected.'" title="Displays notes">'.$asNotes['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink6" onclick="toggleCandiTab(this, \'candiTab6\', \'#ctc_2\');" class="tab_activity" title="Displays the recent activity of this candidate"></li>';
          $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\', \'#ctc_2\');" class="tab_history" title="Displays the company history">'.$asCpHistory['nb_result'].'</li>';
          $sHTML.= '<li id="tabLink8" onclick="toggleCandiTab(this, \'candiTab8\', \'#ctc_2\');" class="tab_position '.$sJdSelected.'" title="Displays the positions & applications">'.$asPosition['nb_result'].'</li>';
          $sHTML.= $this->_oDisplay->getListEnd();
        }

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTabsContent'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab5', $asNotes['content'], array('class' => 'aTabContent hidden '.$sNoteSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab6', $asActivity['content'], array('class' => 'aTabContent hidden'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab7', $asCpHistory['content'], array('class' => 'aTabContent hidden'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab8', $asPosition['content'], array('class' => 'aTabContent hidden '.$sJdSelected));
        $sHTML.= $this->_oDisplay->getBlocEnd();

      $sHTML.= $this->_oDisplay->getBlocEnd();


      return $sHTML;
    }

    private function _getRightTabsFull($pasCandidateData, $psClass = '')
    {
      $pasCandidateData['sl_candidatepk'] = (int)$pasCandidateData['sl_candidatepk'];


      $sCharSelected =  'selected';
      $sDocSelected = $sContactSelected = $sJdSelected = $sNoteSelected = '';
      $pasCandidateData['sl_candidatepk'] = (int)$pasCandidateData['sl_candidatepk'];

      // fetch the content of each tab first. Tab selection, or specific actions may come from that
      $oNotes = CDependency::getComponentByName('sl_event');
      $asCharacter = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, 'character', array(), true, 'character');
      if(empty($asCharacter['nb_result']))
      {
        $sCharSelected = '';
        $sNoteSelected = 'selected';
      }

      $asNotes = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, '', array('character', 'cp_history', 'cp_hidden'), true, 'note');
      if(empty($asNotes['nb_result']))
      {
        $sNoteSelected = '';
        (empty($sCharSelected))? $sContactSelected = 'selected' : '';
      }

      $asContact = $this->_getContactTab($pasCandidateData);
      if(empty($asContact['nb_result']))
      {
        $sContactSelected = '';
        (empty($sCharSelected) && empty($sNoteSelected))? $sDocSelected = 'selected' : '';
      }

      $asDocument = $this->_getDocumentTab($pasCandidateData);
      if(empty($asDocument['nb_result']))
      {
        $sDocSelected = '';
        (empty($sCharSelected) && empty($sNoteSelected) && empty($sContactSelected))? $sJdSelected = 'selected' : '';
      }

      $asCompanyFeed = $this->_getCompanyFeedTab($pasCandidateData);
      $asActivity = $this->_getRecentActivity($pasCandidateData['sl_candidatepk']);
      $asPosition = $this->_getPositionTab($pasCandidateData);
      $sActionTab = $this->_getActionTab($pasCandidateData);

      $asCpHistory = $oNotes->displayNotes($pasCandidateData['sl_candidatepk'], CONST_CANDIDATE_TYPE_CANDI, 'cp_history', array(), false);

      $nTotalData = $asCharacter['nb_result'] + $asNotes['nb_result'] + $asContact['nb_result'] +
              $asDocument['nb_result'] +$asPosition['nb_result'] + $asCpHistory['nb_result'];

      if(empty($nTotalData))
      {
        $sJdSelected = '';
        $sActionTabSelected = ' selected';
      }
      else
        $sActionTabSelected = '';

      if(empty($psClass))
        $psClass = 'candiFullSizeTabs';

      $sHTML = $this->_oDisplay->getBlocStart('', array('class' => $psClass.' candiRightTabsContainer'));
        $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candiTabsVertical'));

          $sHTML.= '<li id="tabLink0" onclick="toggleCandiTab(this, \'candiTab0\');" class="tabActionLink tab_action'.$sActionTabSelected.'" title="All the actions to be done on a candidate" />&nbsp;</li>';

          if($asCharacter['nb_result'] > 0)
            $sHTML.= '<li id="tabLink1" onclick="toggleCandiTab(this, \'candiTab1\');" class="'.$sCharSelected.' tab_character" title="Displays the character notes" ><span class="tab_number tab_level_'.$asCharacter['priority'].'">'.$asCharacter['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink1" onclick="toggleCandiTab(this, \'candiTab1\');" class="tab_empty '.$sCharSelected.' tab_character" title="Displays the character notes" /></li>';

          if($asNotes['nb_result'] > 0)
            $sHTML.= '<li id="tabLink11" onclick="toggleCandiTab(this, \'candiTab5\');" class="'.$sNoteSelected.' tab_note" title="Displays the candidate notes" ><span class="tab_number tab_level_'.$asNotes['priority'].'">'.$asNotes['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink11" onclick="toggleCandiTab(this, \'candiTab5\');" class="tab_empty '.$sNoteSelected.' tab_note" title="Displays the candidate notes" ></li>';

          if($asContact['nb_result'] > 0)
            $sHTML.= '<li id="tabLink2" onclick="toggleCandiTab(this, \'candiTab2\');" class="'.$sContactSelected.' tab_contact" title="Displays the contact details"><span class="tab_number tab_level_'.$asContact['priority'].'">'.$asContact['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink2" onclick="toggleCandiTab(this, \'candiTab2\');" class="tab_empty '.$sContactSelected.' tab_contact" title="Displays the contact details"></li>';

          if($asDocument['nb_result'] > 0)
            $sHTML.= '<li id="tabLink3" onclick="toggleCandiTab(this, \'candiTab3\');" class="'.$sDocSelected.' tab_document" title="Displays the uploaded documents"><span class="tab_number tab_level_'.$asDocument['priority'].'">'.$asDocument['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink3" onclick="toggleCandiTab(this, \'candiTab3\');" class="tab_empty '.$sDocSelected.' tab_document" title="Displays the uploaded documents"></li>';

          if($asPosition['nb_result'] > 0)
            $sHTML.= '<li id="tabLink8" onclick="toggleCandiTab(this, \'candiTab8\');" class="'.$sJdSelected.' tab_position title="Displays the positions & applications"><span class="tab_number tab_level_'.$asPosition['priority'].'">'.$asPosition['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink8" onclick="toggleCandiTab(this, \'candiTab8\');" class="tab_empty '.$sJdSelected.' tab_position" title="Displays the positions & applications"></li>';


          $sHTML.= '<li id="tabLink4" onclick="toggleCandiTab(this, \'candiTab4\');" class="tab_empty tab_company" title="Displays the company news"></li>';
          $sHTML.= '<li id="tabLink6" onclick="toggleCandiTab(this, \'candiTab6\');" class="tab_empty tab_activity" title="Displays the recent activity of this candidate"></li>';

          if($asCpHistory['nb_result'] > 0)
            $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\');" class="tab_history" title="Displays the company history"><span class="tab_number">'.$asCpHistory['nb_result'].'</span></li>';
          else
            $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\');" class="tab_empty tab_history" title="Displays the company history"></li>';

        $sHTML.= $this->_oDisplay->getListEnd();


        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTabsContent'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab0', $sActionTab, array('class' => 'aTabContent hidden '.$sActionTabSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab1', $asCharacter['content'], array('class' => 'aTabContent hidden '.$sCharSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab5', $asNotes['content'], array('class' => 'aTabContent hidden '.$sNoteSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab2', $asContact['content'], array('class' => 'aTabContent hidden '.$sContactSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab3', $asDocument['content'], array('class' => 'aTabContent hidden '.$sDocSelected));
          $sHTML.= $this->_oDisplay->getBloc('candiTab4', $asCompanyFeed['content'], array('class' => 'aTabContent hidden'));

          $sHTML.= $this->_oDisplay->getBloc('candiTab6', $asActivity['content'], array('class' => 'aTabContent hidden'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab7', $asCpHistory['content'], array('class' => 'aTabContent hidden'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab8', $asPosition['content'], array('class' => 'aTabContent hidden '.$sJdSelected));

        $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getBlocEnd();


      return $sHTML;
    }


    private function _getActionTab($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return '';

      $oPage = CDependency::getCpPage();
      $asItem = array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $pasCandidateData['sl_candidatepk']);

      $sHTML = $this->_oDisplay->getBlocStart('', array('class' => 'candi_action_tab'));
      $sHTML.= $this->_oDisplay->getListStart();

        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasCandidateData['sl_candidatepk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 1080; oConf.height = 725;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick=" '.$sJavascript.'"><img title="Edit candidate" src="/component/sl_candidate/resources/pictures/tabs/character_24.png"> Edit candidate</a></li>';


        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_CANDI, $pasCandidateData['sl_candidatepk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 1080; oConf.height = 725;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick=" '.$sJavascript.'"><img title="Edit candidate" src="/component/sl_candidate/resources/pictures/duplicate_24.png"> Duplicate candidate</a></li>';

        $sURL = $oPage->getAjaxUrl('sl_event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick="$(\'#tabLink5\').click(); '.$sJavascript.'"><img src="/component/sl_candidate/resources/pictures/tabs/note_24.png" title="Add notes"/> Add notes or character notes</a></li>';

        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_CONTACT, $pasCandidateData['sl_candidatepk'], $asItem);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick="$(\'#tabLink2\').click(); '.$sJavascript.'"><img src="/component/sl_candidate/resources/pictures/tabs/contact_24.png" title="Add/edit contact details"/> Add contact details</a></li>';

        $sURL = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_ADD, CONST_SS_TYPE_DOCUMENT, 0, $asItem);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick="$(\'#tabLink3\').click(); '.$sJavascript.'"><img src="/component/sl_candidate/resources/pictures/tabs/document_24.png" title="Upload documents"/> Upload a document</a></li>';

        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_DOC, 0, $asItem);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 1000; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick="'.$sJavascript.'"><img src="/component/sl_candidate/resources/pictures/create_doc_24.png" title="Create a new resume"/> Create a resume</a></li>';

        $sURL = $oPage->getAjaxUrl('555-005', CONST_ACTION_ADD, CONST_POSITION_TYPE_LINK, 0, array('candidatepk' => $pasCandidateData['sl_candidatepk']));
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick="$(\'#tabLink8\').click(); '.$sJavascript.'"><img src="/component/sl_candidate/resources/pictures/tabs/position_24.png" title="Set in play"/> Set in play for a new position</a></li>';

        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_MEETING, $pasCandidateData['sl_candidatepk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick=" '.$sJavascript.'"><img title="New meeting" src="/component/sl_candidate/resources/pictures/calendar_24.png"> Set up a meeting</a></li>';

        $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_RM, $pasCandidateData['sl_candidatepk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 600; oConf.height = 400;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= '<li><a href="javascript:;" onclick=" '.$sJavascript.'"><img title="Set up a meeting with this candidate" src="/component/sl_candidate/resources/pictures/calendar_24.png"> RM list</a></li>';



        if($this->_oLogin->isAdmin())
        {
          $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pasCandidateData['sl_candidatepk'], array('check_profile' => 1));
          $sHTML.= '<li><a href="javascript:;" onclick="view_candi(\''.$sURL.'\'); ">
            <img title="Set up a meeting with this candidate" src="/component/sl_candidate/resources/pictures/admin_24.png">
            Check profile</a></li>';

        }

       $sHTML.= $this->_oDisplay->getListEnd();
       $sHTML.= $this->_oDisplay->getBlocEnd();
       return $sHTML;
    }

    private function _getContactTab($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return array();

      $oPage = CDependency::getCpPage();
      $asTypeTitle = array( 1 => 'Home phone number', 2 => 'Office phone number', 3 => 'Website url', 4 => 'Fax number',
                            5 => 'Email address', 6 => 'Mobile phone number', 7 => 'LinkedIn url', 8 => 'Facebook url', 9 => 'info');

      ///in case there's no group
      if(!isset($this->casUserData['group']))
        $this->casUserData['group'] = array();

      $oDbResult = $this->_getModel()->getContact($pasCandidateData['sl_candidatepk'], 'candi', $this->casUserData['pk'], array_keys($this->casUserData['group']), true);
      $bRead = $oDbResult->readFirst();
      $nCount = 0;
      $nPriority = 0;
      $sHTML = '';
      $bRmMasked = false;

      //if there's a RM, we hide contact details
      if(!empty($pasCandidateData['rm']) && !isset($pasCandidateData['rm'][$this->casUserData['loginpk']])
              /*&& $this->_isActiveConsultant($pasCandidateData['managerfk'])*/)
      {
        if(isset($_SESSION['sl_candidate']['contact_acccess'][$pasCandidateData['sl_candidatepk']]))
          $nAccess = $_SESSION['sl_candidate']['contact_acccess'][$pasCandidateData['sl_candidatepk']];
        else
          $nAccess = 0;

        //Once clicked and RM notified, we grant access to contact details for 1 hour
        if($nAccess < (time() - 3600))
        {
          $bRmMasked = true;
          $nManagers = count($pasCandidateData['rm']);
          if($nManagers == 1)
          {
            foreach($pasCandidateData['rm'] as $asRm)
              $sManager = $asRm['link'].' is this candidate RM';
          }
          else
            $sManager = 'there are '.$nManagers.' RMs';

          $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LOG, CONST_CANDIDATE_TYPE_CANDI, $pasCandidateData['sl_candidatepk']);
          $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'notice1 clickable', 'style' => '',  'onclick' => 'AjaxRequest(\''.$sURL.'\'); $(this).parent().find(\'.toggle_contact\').toggle(); $(this).fadeOut(function(){ remove(); }); '));
          $sHTML.= 'Notice: '.$sManager.'. Click here to display the contact information.';
          $sHTML.= $this->_oDisplay->getBlocEnd();
          $sHTML.= $this->_oDisplay->getFloatHack();
        }
      }

      if($bRead)
      {
        $sAMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
        $sTwoMonthAgo = date('Y-m-d H:i:s', strtotime('-2 month'));
        $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'contactList'));

        //Warning about recently contacted candidates
        $asDate = array(
            (empty($pasCandidateData['date_added']))? 0: strtotime($pasCandidateData['date_created']),
            (empty($pasCandidateData['date_updated']))? 0: strtotime($pasCandidateData['date_updated']),
            (empty($pasCandidateData['date_met']))? 0: strtotime($pasCandidateData['date_met']),
            (empty($pasCandidateData['last_meeting']))? 0: strtotime($pasCandidateData['last_meeting'])
        );

        $sLastUpdate = date('Y-m-d', max($asDate));
        if($sLastUpdate > $sAMonthAgo)
        {
          if($pasCandidateData['date_added'] > $sAMonthAgo)
          {
            $sHTML.= '<div class="contact_warning">Achtung !! Candidate created on the '.$sLastUpdate.' </div>';
          }
          elseif($pasCandidateData['date_updated'] > $sAMonthAgo)
          {
            $sHTML.= '<div class="contact_warning">Achtung !! Candidate updated on the '.$sLastUpdate.' </div>';
          }
          else
            $sHTML.= '<div class="contact_warning">Achtung !! Candidate met on the '.$sLastUpdate.' </div>';


        }


        while($bRead)
        {
          $asData = $oDbResult->getData();
          if(empty($asData['value']))
            $asData['value'] = '-';

          if(empty($asData['date_update']))
          {
            $sDate = $asData['date_create'];
            $nLoginFk = (int)$asData['creatorfk'];
          }
          else
          {
            $sDate = $asData['date_update'];
            $nLoginFk = (int)$asData['updated_by'];
          }

          if($sDate > $sTwoMonthAgo)
            $nPriority = 2;
          elseif($sDate > $sAMonthAgo)
            $nPriority = 1;

          if($nLoginFk > 0)
          {
            if($nLoginFk == $this->casUserData['pk'])
            {
              $sUser = 'by me';
              $sUserName = 'me';
            }
            else
            {
              $sUser = 'by '.$this->_oLogin->getUserLink((int)$nLoginFk, true);
              $sUserName = $this->_oLogin->getUserName((int)$nLoginFk, true);
            }
          }
          else
            $sUser = '';

          $sItem = $this->_oDisplay->getBloc('', '&nbsp;', array('class' => 'contactIcon contact_type'.$asData['type'], 'title' => $asTypeTitle[$asData['type']]));
          $bVisible = (bool)$asData['visible'];

          if(!$bRmMasked && $bVisible)
          {
            switch($asData['type'])
            {
              case 3:
              case 7:
              case 8:

                if(preg_match('/$http/i', $asData['value']) !== 0)
                  $asData['value'] = 'http://'.$asData['value'];

                $asData['value'] = $this->_oDisplay->getLink(mb_strimwidth($asData['value'], 0, 45, '...'), $asData['value'], array('target' => '_blank'));
                break;

              case 5:
                $sCopyEmail = '<keep_to_copy_email_in_slistem_note '.$this->csUid.'__'.CONST_ACTION_VIEW.'__'.CONST_CANDIDATE_TYPE_CANDI.'__'.$pasCandidateData['sl_candidatepk'].'@slistem.slate.co.jp>';
                $sCopyEmail = urlencode($sCopyEmail);
                $asData['value'] = $this->_oDisplay->getLink($asData['value'], 'javascript:;', array('onclick' => 'window.open(\'mailto:'.$asData['value'].'?bcc='.$sCopyEmail.'\', \'zm_mail\');'));
                break;
            }

            $sItem.= $this->_oDisplay->getBloc('', '&nbsp;'.$asData['value'], array('class' => 'contactData'));
          }
          else
          {

            if($bVisible)
            {
              $sMaskedValue = substr($asData['value'], 0, 5).'<span class="contact_masked_symbol"> ';
              for($nLetter = 0; $nLetter < (strlen($asData['value']) -5); $nLetter++)
                $sMaskedValue.= '&#9679;';

              $sMaskedValue.= '</span>';
              $sValueString = $this->_oDisplay->getSpan('', $sMaskedValue, array('class' => 'toggle_contact contact_masked'));
              $sValueString.= $this->_oDisplay->getSpan('', $asData['value'], array('class' => 'toggle_contact hidden'));
            }
            else
            {
              $sValueString = $this->_oDisplay->getSpan('', ' -=[ private ]=- ', array('class' => 'toggle_contact contact_masked'));
            }


            $sItem.= $this->_oDisplay->getBloc('', '&nbsp;'.$sValueString, array('class' => 'contactData'));
          }


          if(empty($asData['date_update']))
            $sItem.= $this->_oDisplay->getBloc('','<em>added '.date('Y-M-d', strtotime($sDate)).'</em><br />'.$sUser, array('class' => 'contactDate'));
          else
            $sItem.= $this->_oDisplay->getBloc('','<em>updated '.date('Y-M-d', strtotime($sDate)).'</em><br />'.$sUser, array('class' => 'contactDate'));

          if($bVisible)
          {
            if(!empty($asData['description']))
              $sItem.= $this->_oDisplay->getBloc('', $asData['description'], array('class' => 'contactDescription'));
            else
              $sItem.= $this->_oDisplay->getBloc('', '<em class="text_small">no description</em>', array('class' => 'contactDescription'));
          }
          else
            $sItem.= $this->_oDisplay->getBloc('', '<em class="text_small">Ask
              <a href="javascript:;" onclick="
              var oConf = goPopup.getConfig();
              oConf.height = 500;
              oConf.width = 850;
              goPopup.setLayerFromAjax(oConf, \'https://slistem.devserv.com/index.php5?uid=333-333&ppa=ppaa&ppt=msg&ppk=0&loginfk='.$nLoginFk.'&pg=ajx\'); " >'.$sUserName.'</a> if you need to access this.</em>', array('class' => 'contactDescription'));


          $sHTML.= $this->_oDisplay->getListItem($sItem);
          $bRead = $oDbResult->readNext();
          $nCount++;
        }
      }
      else
      {
        $sHTML = '<div class="entry"><div class="note_content"><em>No contact details.</em></div></div>';
      }

      $sHTML.= $this->_oDisplay->getFloatHack();
      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'tab_bottom_link'));
      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_CONTACT, (int)$pasCandidateData['sl_candidatepk']);
      $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
      $sHTML.= '<a href="javascript:;" onclick="$(\'#tabLink2\').click(); '.$sJavascript.'">Add/edit contact details</a>';
      $sHTML.= $this->_oDisplay->getBlocEnd();

      return array('content' => $sHTML, 'nb_result' => $nCount, 'priority' => $nPriority);
    }

    /** return the lastest update feed obout the candidate company
     *  $pasCandidateData must contain at least the candidate pk [sl_candidatepk] and [companyfk]
     *  if there's no rss feed data included in the array, the function will fetch it
     *
     * @param array $pasCandidateData
     * @return array
     */
    private function _getCompanyFeedTab($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return array();

      if(empty($pasCandidateData['companyfk']))
      {
        $sHTML = '<div class="floathack" />';
        $sHTML.= '<div class="tab_bottom_link">No company to search news about</div>';
        return array('content' => $sHTML, 'nb_result' => 0);
      }

      $oPage = CDependency::getCpPage();

      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_FEED, (int)$pasCandidateData['companyfk']);
      $sURL.= '&sl_candidatepk='.(int)$pasCandidateData['sl_candidatepk'];
      $sURL.= '&companyfk='.(int)$pasCandidateData['companyfk'];

      $sId = 'company_feed_'.$pasCandidateData['companyfk'];
      //$sJavascript = $this->_oDisplay->getAjaxJs($sURL, '', '', $sId, '', '', '$(this).closest(\'.aTabContent\').mCustomScrollbar(\'update\');');
      $sJavascript = $this->_oDisplay->getAjaxJs($sURL, '', '', $sId);

      //check if the data is in the candidate data or if I have to get it (ajax)
      if(isset($pasCandidateData['sl_company_rsspk']))
      {
        $asFeed = $pasCandidateData;
        $asFeed['date_created'] = $asFeed['date_rss'];
      }
      else
      {
        $oDbResult = $this->_getModel()->getFeedByCompanyFk((int)$pasCandidateData['companyfk']);
        $bRead = $oDbResult->readFirst();

        if($bRead)
          $asFeed = $oDbResult->getData();
        else
          $asFeed = array('nb_news' => 0);
      }

      $sHTML = $this->_oDisplay->getBlocStart($sId);
      if(!empty($asFeed['date_created']))
      {
        $sHTML.= 'Last updated the '.$asFeed['date_created'].'<br/><br/>';
        $sHTML.= $asFeed['content'];


        $sHTML.= '<div class="floathack" />';
        $sHTML.= '<div class="tab_bottom_link">';
        $sHTML.= '<a href="javascript:;" onclick="'.$sJavascript.'">Update feed now</a> <br />';
        $sHTML.= 'More news available <a href="https://www.google.com/search?tbm=nws&q='.urlencode($asFeed['company_name']).'" target="_blank"> here </a>';
        $sHTML.= '</div>';
      }
      else
      {
        $sHTML.= '<div class="floathack" />';
        $sHTML.= '<div class="tab_bottom_link">';
        $sHTML.= 'No result yet, <a href="javascript:;" onclick="'.$sJavascript.'">search now</a> ?  <br />';
        $sHTML.= 'Or search straight on <a href="https://www.google.com/search?tbm=nws&q='.urlencode($asFeed['company_name']).'" target="_blank">google news</a>';
        $sHTML.= '</div>';
      }

      $sHTML.= $this->_oDisplay->getBlocEnd();
      return array('content' => $sHTML, 'nb_result' => (int)$asFeed['nb_news']);
    }


    /** Return the user activity based on the system logs
     *
     * @param array $pasCandidateData
     * @return array html + nb results
     */
    private function _getRecentActivity($pnPk, $psType = '', $pnPage = 1)
    {
      if(!assert('is_key($pnPk)'))
        return array();

      $nActivityToDisplay = 25;

      //request 1 more activity than what is displayed to know if everything is displayed
      if($pnPage < 2)
      {
        $pnPage = 1;
        $sLimit = ($nActivityToDisplay+1);
      }
      else
        $sLimit = (($pnPage-1)*$nActivityToDisplay).','.(($pnPage*$nActivityToDisplay)+1);

      //$asComponent = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => $this->csAction, CONST_CP_TYPE => $this->csType, CONST_CP_PK => $this->cnPk);
      $asComponent = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => '', CONST_CP_TYPE => $psType, CONST_CP_PK => $pnPk,
          'table' => array('sl_candidate', 'document', 'sl_document', 'sl_meeting', 'position', 'user_history'),
          'uids' => array('555-001', '999-111'),
          );
      $asHistory = $this->_oLogin->getSystemHistoryItem($asComponent, $sLimit);


      $sId = 'activity_feed_'.$pnPk.'_'.$pnPage;
      $sHTML = $this->_oDisplay->getSpanStart($sId);
      $nCount = 0;

      if(empty($asHistory))
      {
        $sHTML.= 'No activity found.<br /><br />';
        $sHTML.= $this->_oDisplay->getSpanEnd();
      }
      else
      {
        foreach($asHistory as $asHistoryData)
        {
          $sHTML.= '<div class="entry">';
            $sHTML.= '<div class="note_header">';
            $sHTML.= '&rarr;&nbsp;&nbsp;<span class="note_date">'.$asHistoryData['date'].'</span>';
            $sHTML.= ' - <span> by '.$this->_oLogin->getUserLink((int)$asHistoryData['userfk'], true).'</span>';
            $sHTML.= '</div>';

            $sHTML.= ' <div class="note_content">'.$asHistoryData['action'];

            if(!empty($asHistoryData['description']))
               $sHTML.= '<br />'.$asHistoryData['description'];

            $sHTML.= '</div>';
          $sHTML.= '</div>';

          $nCount++;
          if($nCount > $nActivityToDisplay)
            break;
        }

        $sHTML.= $this->_oDisplay->getSpanEnd();

        if(count($asHistory) > $nActivityToDisplay)
        {
          $pnPage++;

          //add an extra block to load next logs entries
          $sId = 'activity_feed_'.$pnPk.'_'.$pnPage;
          $sHTML.= $this->_oDisplay->getSpan($sId);


          $oPage = CDependency::getCpPage();
          $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_LOGS, $pnPk, array('page' => $pnPage));
          $sJavascript = $this->_oDisplay->getAjaxJs($sURL, '', '', $sId, '', '', '$(\'#tabLink6\').click(); ');

          $sHTML.= '<div class="floathack" />';
          $sHTML.= '<div class="tab_bottom_link">';
          $sHTML.= '<a href="javascript:;" onclick="'.$sJavascript.'; $(this).parent().remove();">See previous activities... </a>';
          $sHTML.= '</div>';
        }
      }

      return array('content' => $sHTML, 'nb_result' => $nCount);
    }

    private function _getMoreLogs($pnCandidatePk)
    {
      $asLogs = $this->_getRecentActivity($pnCandidatePk, CONST_CANDIDATE_TYPE_CANDI, (int)getValue('page'));
      return array('data' => $asLogs['content']);
    }

    /** Return list of document linked to the candidates
     *
     * @param array $pasItemData
     * @return array html + nb results
     */
    private function _getDocumentTab($pasItemData, $psDataType = CONST_CANDIDATE_TYPE_CANDI)
    {
      if(!assert('is_array($pasItemData) && !empty($pasItemData)'))
        return array('content' => '', 'nb_result' => 0);


      if($psDataType == CONST_CANDIDATE_TYPE_CANDI)
      {
        $nPk = (int)$pasItemData['sl_candidatepk'];
        $sTitle = $pasItemData['firstname'].' '.$pasItemData['lastname'].'\'s resume';
        $sCallback = 'refresh_candi('.$nPk.', true); ';
      }
      else
      {
        $nPk = (int)$pasItemData['sl_companypk'];
        $sTitle = 'company document ';
        $sCallback = 'refresh_comp('.$nPk.'); ';
      }

      $oPage = CDependency::getCpPage();
      $sHTML = '';

      $asItem = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => $psDataType, CONST_CP_PK => $nPk);
      $oShareSpace = CDependency::getComponentByName('sharedspace');
      $asDocument = $oShareSpace->getDocuments(0, $asItem);
      $nDocument = count($asDocument);
      $nPriority = 0;

      if($nDocument == 0)
        $sHTML.= '<div class="entry"><div class="note_content"><em>No document found.</em></div></div>';
      else
      {
        $sAMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
        $sTwoMonthAgo = date('Y-m-d H:i:s', strtotime('-2 month'));

        foreach($asDocument as $asDocData)
        {
          if($asDocData['date_creation'] > $sTwoMonthAgo)
            $nPriority = 2;
          elseif($asDocData['date_creation'] > $sAMonthAgo)
            $nPriority = 1;

          $sHTML.= '<div class="entry">
            <div class="note_header">
            &rarr;&nbsp;&nbsp;<span class="note_date">'.$asDocData['date_creation'].'</span>
             - <span> by '.$this->_oLogin->getUserLink($this->casUsers[$asDocData['creatorfk']], true).'</span>
            </div>
            <div class="note_content documentRow">
              <span class="doc_detail">
                <a href="javascript:;" onclick="'.$asDocData['view_popup_js'].'">details & edit</a>
              </span>

              <div class="doc_picture">
                <a href="'.$asDocData['dl_url'].'" class="" >
                <img src="'.$asDocData['icon'].'" title="'.$asDocData['mime_type'].'"/>
                  </a>
              </div>

              <div class="doc_name">
              <a href="'.$asDocData['dl_url'].'" class="" target="_blank">
                 '.$asDocData['title'].'<br />'.$asDocData['initial_name'].'
              </a>
              </div>

            <div class="floatHack" />
            </div>
          </div>';
        }
      }


      $sHTML.= '<div class="floathack" />';
      $sHTML.= '<div class="tab_bottom_link">';

      $asItem['document_title'] = $sTitle;
      $asItem['callback'] = $sCallback;

      $sURL = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_ADD, CONST_SS_TYPE_DOCUMENT, 0, $asItem);
      $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
      $sHTML.= '<a href="javascript:;" onclick="'.$sJavascript.'"> Upload a document</a>';

      $sHTML.= '&nbsp;&nbsp;-&nbsp;&nbsp;';

      $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_DOC, 0, $asItem);
      $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 1000; oConf.height = 750;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
      $sHTML.= '<a href="javascript:;" onclick="'.$sJavascript.'">Create a resume</a>';
      $sHTML.= '</div>';

      return array('content' => $sHTML, 'nb_result' => $nDocument, 'priority' => $nPriority);
    }


    private function _getPositionTab($pasCandidateData)
    {
      if(!assert('is_array($pasCandidateData) && !empty($pasCandidateData)'))
        return array('content' => '', 'nb_result' => 0);

      $oPosition = CDependency::getComponentByName('sl_position');
      $asPosition = $oPosition->getApplication($pasCandidateData['sl_candidatepk'], false, true);
      //dump($asPosition);

      $sURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_ADD, CONST_POSITION_TYPE_LINK, 0, array('candidatepk' => $pasCandidateData['sl_candidatepk']));
      $nPosition = $nPriority = 0;
      $sHTML = '';

      if(empty($asPosition['active']) && empty($asPosition['history']))
      {
        $sHTML = '<div class="tab_bottom_link">
            <em>No application found.</em>
            <br /><a href="javascript:;" onclick="
            oConf = goPopup.getConfig();
            oConf.height = 600;
            oConf.width = 900;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">pitch to new position</a>
         </div>';
      }
      else
      {
        $oLogin = CDependency::getCpLogin();
        $asDisplayLink = array();
        foreach($asPosition['active'] as $asJdData)
        {
          if($asJdData['date_created'] > date('Y-m-d', strtotime('-1 month')));
            $nPriority = 1;

          $asJdData['link_date'] = substr($asJdData['link_date'], 0, 10);
          $asJdData['link_creator'] = $oLogin->getUserLink((int)$asJdData['link_creator'], true);

          $sHTML.= $this->_getPositionTabRow($asJdData, $pasCandidateData['sl_candidatepk']);
          $asDisplayLink[] = $asJdData['sl_positionpk'];
          $nPosition++;
        }


        //separator
        if(!empty($asPosition['history']))
        {

          $sHistory = '';
          foreach($asPosition['history'] as $asJdData)
          {
            //not display twice a position that has been re-opened
            if(!in_array($asJdData[0]['sl_positionpk'], $asDisplayLink))
            {
              $asJdData[0]['link_date'] = substr($asJdData[0]['link_date'], 0, 10);
              $asJdData[0]['link_creator'] = $oLogin->getUserLink((int)$asJdData[0]['link_creator'], true);

              $sHistory.= $this->_getPositionTabRow($asJdData[0], $pasCandidateData['sl_candidatepk']);
              $nPosition++;
            }
          }

          if(!empty($sHistory))
          {
            if(!empty($asPosition['active']))
              $sHTML.= $this->_oDisplay->getBloc('', 'Inactive & expired positions', array('class' => 'position_separator'));


            $sHTML.= $this->_oDisplay->getFloatHack();  //to keep the odd/par colors in order
            $sHTML.= $sHistory;
          }
        }


        $sHTML.= '<div class="tab_bottom_link">
            <a href="javascript:;" onclick="
            oConf = goPopup.getConfig();
            oConf.height = 500;
            oConf.width = 900;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">pitch to new position</a>
         </div>';

      }

      return array('content' => $sHTML, 'nb_result' => $nPosition, 'priority' => $nPriority);
    }


    private function _getPositionTabRow($pasPosition, $pnCandidatePk)
    {

      $sEncoding = mb_detect_encoding($pasPosition['title']);
      //dump($pasPosition);

      //$pasPosition['title'] = mb_convert_encoding($pasPosition['title'], 'UTF-8', $sEncoding);
      if(!in_array($sEncoding, array('UTF-8', 'ASCII')))
        $pasPosition['title'].= ' [in '.$sEncoding.']';


      $sViewURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$pasPosition['sl_companypk']);
      $sCompany = $this->_oDisplay->getLink(mb_strimwidth($pasPosition['company_name'], 0, 55, '...'), 'javascript:;', array('class' => 'link_view', 'onclick' => 'popup_candi(this, \''.$sViewURL.'\');'));
      $sCompany.= $this->_oDisplay->getLink('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="/component/sl_candidate/resources/pictures/goto_16.png" />&nbsp;', 'javascript:;', array('onclick' => 'view_comp(\''.$sViewURL.'\');'));

      $sViewURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_VIEW, CONST_POSITION_TYPE_JD, (int)$pasPosition['positionfk']);
      $sPosition = $this->_oDisplay->getLink('#'.$pasPosition['sl_positionpk'].' - '.mb_strimwidth($pasPosition['title'], 0, 55, '...'), 'javascript:;', array('class' => 'link_view', 'onclick' => 'view_position(\''.$sViewURL.'\');'));

      $sViewURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_EDIT, CONST_POSITION_TYPE_LINK, (int)$pasPosition['sl_position_linkpk'], array('positionfk' => (int)$pasPosition['positionfk'], 'candidatefk' => $pnCandidatePk));
      $sOnclick = 'view_position(\''.$sViewURL.'\'); ';

      if($pasPosition['current_status'] <= 100)
        $sDate = 'in play since '.substr($pasPosition['date_created'], 0, 10);
      else
        $sDate = 'played until '.substr($pasPosition['date_created'], 0, 10);


      if($pasPosition['current_status'] <= 53)
      {
        $sClass = 'ontrack';  //green
      }
      elseif($pasPosition['current_status'] < 100)
      {
        $sClass = 'ontrack2 '; //blue - CCM > 3
      }
      elseif($pasPosition['current_status'] < 101)
      {
        $sClass = 'warning '; //yellow - offer
      }
      elseif($pasPosition['current_status'] == 101)
      {
        $sClass = 'placed '; //yellow - offer
      }
      elseif($pasPosition['current_status'] < 200)
      {
        $sClass = 'critical '; //red stalled - expired
      }
      else
        $sClass = 'stopped';

      $sHTML = '<div class="entry">
        <div class="note_header">
        &rarr;&nbsp;&nbsp;
         position <span class="note_type">#'.$pasPosition['sl_positionpk'].'</span>
         - <span> created by '.$this->_oLogin->getUserLink($this->casUsers[$pasPosition['created_by']], true).'</span>
         <span class="note_date"><em class="light">'.$sDate.'</em></span>
        </div>
        <div class="note_content" style="margin-left: 0px;">

          <div class="position_row">

            <div class="position_status '.$sClass.'" onclick="'.$sOnclick.'">
              <div>'.$pasPosition['status_label'].'</div>

         </div>
            <div>
              <div class="row"><div class="title">Position: </div><div class="data">'.$sPosition.'</div></div>
              <div class="row"><div class="title">Company: </div><div class="data">'.$sCompany.'</div></div>
              <div class="row"><div class="title">Update: </div><div class="data">by&nbsp;&nbsp;&nbsp;'.$pasPosition['link_creator'].'
                &nbsp;&nbsp;&nbsp;&nbsp;on the&nbsp;&nbsp;&nbsp;'.$pasPosition['link_date'].'</div></div>
            </div>

            <div class="position_view" onclick="'.$sOnclick.'"><span>View & edit</span></div>

          </div>

        <div class="floatHack" />
        </div>
      </div>';

      return $sHTML;
    }


    private function _getCompanyView($pnPk)
    {
      if(!assert('is_key($pnPk)'))
        return '';

      $asCompany = $this->_getModel()->getCompanyData($pnPk, true);
      if(empty($asCompany))
        return '';

      $sHTML = '';

      $sViewURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, $pnPk);
      if(getValue('preview'))
      {
        $sHTML.= $this->_oDisplay->getBloc('', '
          <a href="javascript:;" class="candi-pop-link" onclick="goPopup.removeAll(true); view_comp(\''.$sViewURL.'\');">close <b>all</b> popops & view in page<img src="/component/sl_candidate/resources/pictures/goto_16.png" /></a>
          ', array('class' => 'close_preview'));
      }

      $oDbResult = $this->_getModel()->getCompanyDepartment($pnPk);
      $bRead = $oDbResult->readFirst();

      $asCompany['department'] = array();
      $asCompany['nb_employee'] = 0;
      while($bRead)
      {
        $sDepartment = $oDbResult->getFieldValue('department');
        $asCompany['nb_employee'] += (int)$oDbResult->getFieldValue('nCount');

        if(empty($sDepartment))
        {
          $sDepartment = '- Not defined - ';
          $asCompany['department'][] = '__no_department__';
          $asCompany['department_label'][] = '-- unknown -- ('.$oDbResult->getFieldValue('nCount').')';
        }
        else
        {
          $asCompany['department'][] = trim($sDepartment);
          $asCompany['department_label'][] = $sDepartment.' ('.$oDbResult->getFieldValue('nCount').')';
        }

        $bRead = $oDbResult->readNext();
      }

      //fetch data about positions and employees in play. Will be used in the view and tabs
      $oPosition = CDependency::getComponentByName('sl_position');
      $asPosition = array();
      $asPosition['jd'] = $oPosition->getCompanyPositionTabContent($pnPk);
      $asPosition['inplay'] = $oPosition->getEmployeeApplicantTabContent($pnPk, true);

      $anPositionStatus = array('critical' => $asPosition['jd']['nb_critical'], 'open' => $asPosition['jd']['nb_open'],
          'close' => $asPosition['jd']['nb_close']);

      $nApplicant = $asPosition['inplay']['nb_result'];


      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTopSectLeft'));

        $sTemplate =  $_SERVER['DOCUMENT_ROOT'].'/'.self::getResourcePath().'/template/';

        /*if(isset($pasSettings['company_template']) && !empty($pasSettings['company_template']))
          $sTemplate.= $pasSettings['company_template'].'.tpl.class.php5';
        else
          $sTemplate.= 'default_company.tpl.class.php5';*/

        $sTemplate.= 'company_sl3.tpl.class.php5';

        //params for the sub-templates when required
        $oTemplate = $this->_oDisplay->getTemplate($sTemplate);
        $sHTML.= $oTemplate->getDisplay($asCompany, $anPositionStatus, $nApplicant);


        //store a description of the current item for later use in javascript
      $sHTML.= $this->_oDisplay->getBloc('', '', array('class' => 'itemDataDescription hidden',
          'data-type' => 'comp',
          'data-pk' => $pnPk,
          'data-label' => $asCompany['name'],
          'data-cp_item_selector' => '555-001|@|ppav|@|comp|@|'.$pnPk));

      $sHTML.= $this->_oDisplay->getBlocEnd();


      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTopSectRight candiTabContainer'));
      $sHTML.= $this->_getCompanyRightTabs($asCompany, $asPosition);
      $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getFloatHack();


      $sLink = 'javascript: view_candi(\''.$sViewURL.'\'); ';
      logUserHistory($this->csUid, $this->csAction, $this->csType, $this->cnPk, array('text' => 'view - '.$asCompany['name'].' (#'.$pnPk.')', 'link' => $sLink));

      return $sHTML;
    }


    /**
     * Display all the company tabs
     *
     * @param array $pasCompany
     * @param array $pasPosition
     * @return string html content
     */
    private function _getCompanyRightTabs($pasCompany, $pasPosition)
    {
      if(!assert('is_array($pasCompany) && !empty($pasCompany)'))
        return '';

      //gonna be needed for multiple tabs
      if(empty($this->casUsers))
        $this->casUsers = $this->_oLogin->getUserList(0, false, true);


      if($this->csTabSettings == 'full')
        $sClass = 'candiFullSizeTabs';
      else
        $sClass = 'candiHoriSizeTabs';

      $sHTML = $this->_oDisplay->getBlocStart('', array('class' => $sClass.' candiRightTabsContainer'));


      $sNoteSelected = $sDocSelected = $sContactSelected = $sInPlaySelected = $sPositionSelected = $sActionSelected = '';
      $bOneSelected = false;
      $pasCompany['sl_companypk'] = (int)$pasCompany['sl_companypk'];

      // fetch the content of each tab first. Tab selection, or specific actions may come from that
      $oNotes = CDependency::getComponentByName('sl_event');
      $asNotes = $oNotes->displayNotes($pasCompany['sl_companypk'], CONST_CANDIDATE_TYPE_COMP);

      $asActivity = $this->_getRecentActivity($pasCompany['sl_companypk'], CONST_CANDIDATE_TYPE_COMP);
      $asDocument = $this->_getDocumentTab($pasCompany, CONST_CANDIDATE_TYPE_COMP);

      $pasCompany['companyfk'] = $pasCompany['sl_companypk'];
      $pasCompany['sl_candidatepk'] = 0;
      $asCompanyFeed = $this->_getCompanyFeedTab($pasCompany);

      $sAction = $this->_getCpActions($pasCompany);

      //$asIndustry = array('content' => 'Nothing there.', 'nb_result' => 0);
      //$asPosition = array('content' => 'Nothing position from this company.', 'nb_result' => 0);
      $asPosition = $pasPosition['jd'];
      $asInPlay = $pasPosition['inplay'];


      if($asNotes['nb_result'] > 0)
      {
        $bOneSelected = true;
        $sNoteSelected =  'selected';
      }

      if(!$bOneSelected && $asDocument['nb_result'] > 0)
      {
        $bOneSelected = true;
        $sNoteSelected = '';
        $sDocSelected = 'selected';
      }

      if(!$bOneSelected && $asInPlay['nb_result'] > 0)
      {
        $bOneSelected = true;
        $sDocSelected = '';
        $sInPlaySelected = 'selected';
      }

      if(!$bOneSelected && $asPosition['nb_result'] > 0)
      {
        $bOneSelected = true;
        $sInPlaySelected = '';
        $sPositionSelected = 'selected';
      }


      $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('company' => $pasCompany['sl_companypk'], 'data_type' => CONST_CANDIDATE_TYPE_CANDI, 'qs_exact_match' => 1));
      $nDepartment = count($pasCompany['department']);
      $asDepartment = array('content' => 'No department found', 'nb_result' => $nDepartment);
      $asFirstLetter = array();
      if($nDepartment > 0)
      {
        $asDepartment['content'] = $this->_oDisplay->getBlocStart('', array('class' => 'cp_department_list'));
        foreach($pasCompany['department'] as $nKey => $sDepartment)
        {
          $sAnchor = '';

          if($nDepartment > 25 )
          {
            $sFirst = substr($sDepartment, 0, 1);
            if(!isset($asFirstLetter[$sFirst]))
            {
              $asFirstLetter[$sFirst] = '<a href="javascript:;" onclick="
                $(this).closest(\'.aTabContent\').mCustomScrollbar(\'scrollTo\', \'#dep_'.$sFirst.'\'); " >'.strtoupper($sFirst).'</a>';
              $sAnchor = 'dep_'.$sFirst;
            }
          }

          $sJavascript = '
            var asContainer = goTabs.create(\'comp\', \'\', \'\', \'Company list\');
            AjaxRequest(\''.$sURL.'&department='.urlencode($sDepartment).'\', \'body\', \'\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); \');
            goTabs.select(asContainer[\'number\']); ';
          $asDepartment['content'].= $this->_oDisplay->getBloc($sAnchor,  $this->_oDisplay->getLink($pasCompany['department_label'][$nKey], 'javascript:;', array('onclick' => $sJavascript)));
        }

        $asDepartment['content'].= $this->_oDisplay->getBlocEnd();

        if(count($asFirstLetter) > 0)
        {
          $asDepartment['content'] = '<div class="department_abc">'.implode('&nbsp;&nbsp;', $asFirstLetter).'</div>'. $asDepartment['content'];
        }

        $asDepartment['content'] = 'We have listed '.$nDepartment.' department(s) in this company:'.$asDepartment['content'];
        $asDepartment['nb_result'] = $nDepartment;
      }


      if(!$bOneSelected)
      {
        $sActionSelected = 'selected';
      }


      $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candiTabsVertical'));
        $sHTML.= '<li id="tabLink0" onclick="toggleCandiTab(this, \'candiTab0\');" class="'.$sActionSelected.' tabActionLink tab_action" title="All the actions to be done on a candidate"></li>';

        if($asNotes['nb_result'] > 0)
          $sHTML.= '<li id="tabLink1" onclick="toggleCandiTab(this, \'candiTab1\');" class="'.$sNoteSelected.' tab_note" title="Displays the character notes" ><span class="tab_number">'.$asNotes['nb_result'].'</span></li>';
        else
          $sHTML.= '<li id="tabLink1" onclick="toggleCandiTab(this, \'candiTab1\');" class="tab_empty '.$sNoteSelected.' tab_note" title="Displays the character notes" ></li>';

        if($asDocument['nb_result'] > 0)
          $sHTML.= '<li id="tabLink3" onclick="toggleCandiTab(this, \'candiTab3\');" class="'.$sDocSelected.' tab_document" title="Displays the uploaded documents"><span class="tab_number">'.$asDocument['nb_result'].'</span></li>';
        else
          $sHTML.= '<li id="tabLink3" onclick="toggleCandiTab(this, \'candiTab3\');" class="tab_empty '.$sDocSelected.' tab_document" title="Displays the uploaded documents"></li>';

        $sHTML.= '<li id="tabLink4" onclick="toggleCandiTab(this, \'candiTab4\');" class="tab_company tab_empty" title="Displays the company news"></li>';
        //$sHTML.= '<li id="tabLink6" onclick="toggleCandiTab(this, \'candiTab6\');" class="tab_industry tab_empty" title="Displays employee department"></li>';

        if($asDepartment['nb_result'] > 0)
          $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\');" class="tab_department tab_empty" title="Display company departments"><span class="tab_number">'.$asDepartment['nb_result'].'</span></li>';
        else
         $sHTML.= '<li id="tabLink7" onclick="toggleCandiTab(this, \'candiTab7\');" class="tab_department tab_empty" title="Display company departments"></li>';


        $sHTML.= '<li id="tabLink8" onclick="toggleCandiTab(this, \'candiTab8\');" class="tab_activity tab_empty" title="Displays the recent activity of this company"></li>';

        if($asPosition['nb_result'] > 0)
          $sHTML.= '<li id="tabLink9" onclick="toggleCandiTab(this, \'candiTab9\');" class="'.$sPositionSelected.' tab_job" title="Company positions"><span class="tab_number">'.$asPosition['nb_result'].'</span></li>';
        else
          $sHTML.= '<li id="tabLink9" onclick="toggleCandiTab(this, \'candiTab9\');" class="tab_job tab_empty" title="Company positions"></li>';

        if($asInPlay['nb_result'] > 0)
          $sHTML.= '<li id="tabLink10" onclick="toggleCandiTab(this, \'candiTab10\');" class="'.$sInPlaySelected.' tab_position" title="Employees in play"><span class="tab_number tab_level_1">'.$asInPlay['nb_result'].'</span></li>';
        else
          $sHTML.= '<li id="tabLink10" onclick="toggleCandiTab(this, \'candiTab10\');" class="tab_position tab_empty" title="Employees in play"></li>';

      $sHTML.= $this->_oDisplay->getListEnd();

      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'candiTabsContent'));
        $sHTML.= $this->_oDisplay->getBloc('candiTab0', $sAction, array('class' => 'aTabContent hidden '.$sActionSelected));
        $sHTML.= $this->_oDisplay->getBloc('candiTab1', $asNotes['content'], array('class' => 'aTabContent hidden '.$sNoteSelected));
        $sHTML.= $this->_oDisplay->getBloc('candiTab3', $asDocument['content'], array('class' => 'aTabContent hidden '.$sDocSelected));
        $sHTML.= $this->_oDisplay->getBloc('candiTab4', $asCompanyFeed['content'], array('class' => 'aTabContent hidden'));

        //$sHTML.= $this->_oDisplay->getBloc('candiTab6', $asIndustry['content'], array('class' => 'aTabContent hidden'));
          $sHTML.= $this->_oDisplay->getBloc('candiTab7', $asDepartment['content'], array('class' => 'aTabContent hidden'));

        $sHTML.= $this->_oDisplay->getBloc('candiTab8', $asActivity['content'], array('class' => 'aTabContent hidden'));

        $sHTML.= $this->_oDisplay->getBloc('candiTab9', $asPosition['content'], array('class' => 'aTabContent hidden '.$sPositionSelected));
        $sHTML.= $this->_oDisplay->getBloc('candiTab10', $asInPlay['content'], array('class' => 'aTabContent hidden '.$sInPlaySelected));

        $sHTML.= $this->_oDisplay->getFloathack();

      $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getFloathack();


      $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getFloathack();

      return $sHTML;
    }


    private function _getCpActions($pasCompany)
    {
      $sHTML = $this->_oDisplay->getBlocStart('', array('class' => 'candi_action_tab'));
      $sHTML.= $this->_oDisplay->getListStart('', array('class' => 'candi_action_tab'));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_COMP, $pasCompany['sl_companypk']);
        $sJavascript = 'var oConf = goPopup.getConfig(); oConf.width = 1080; oConf.height = 725;  goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';
        $sHTML.= $this->_oDisplay->getListItem($this->_oDisplay->getLink('Edit company', 'javascript:;', array('onclick' => $sJavascript)));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_COMP, $pasCompany['sl_companypk']);
        $sHTML.= $this->_oDisplay->getListItem($this->_oDisplay->getLink('Add a note', $sURL));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_COMP, $pasCompany['sl_companypk']);
        $sHTML.= $this->_oDisplay->getListItem($this->_oDisplay->getLink('Add a document', $sURL));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_COMP, $pasCompany['sl_companypk']);
        $sHTML.= $this->_oDisplay->getListItem($this->_oDisplay->getLink('Add a position', $sURL));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_COMP, $pasCompany['sl_companypk']);
        $sHTML.= $this->_oDisplay->getListItem($this->_oDisplay->getLink('Add a candidate', $sURL));

      $sHTML.= $this->_oDisplay->getListEnd();
      $sHTML.= $this->_oDisplay->getBlocEnd();

      return $sHTML;
    }

    /******************************************************************************************/
    /******************************************************************************************/



    /******************************************************************************************/
    /******************************************************************************************/



    /******************************************************************************************/
    /******************************************************************************************/



    private function _getCandidateList($pbInAjax = false, &$poQB = null)
    {
      global $gbNewSearch;
      $oDb = CDependency::getComponentByName('database');
      $this->_getModel()->loadQueryBuilderClass();
      $oLogin = CDependency::getCpLogin();

      $asListMsg = array();
      $sTemplate = getValue('tpl');
      $bHeavyJoin = false;
      //$bLogged = false;
      $bFilteredList = (bool)getValue('__filtered');


      //replay candoidate searches  (filters, sorting...)
      $nHistoryPk = (int)getValue('replay_search');
      if($nHistoryPk > 0)
      {
        $this->csSearchId = getValue('searchId');
        //$asListMsg[] = 'replay search '.$nHistoryPk.': reload qb saved in db...';

        $asHistoryData = $oLogin->getUserActivityByPk($nHistoryPk);
        $poQB = $asHistoryData['data']['qb'];
        if(!$poQB || !is_object($poQB))
        {
          //dump($poQB);
          $poQB = $this->_getModel()->getQueryBuilder();
          $poQB->addWhere(' (false) ');
          $asListMsg[] = ' Error, could not reload the search. ';
        }
      }

      //Basic integration of the quick search tyhrough query builder
      if(!$poQB)
        $poQB = $this->_getModel()->getQueryBuilder();


      // ============================================
      // search and pagination management
      if(empty($this->csSearchId) && empty($nHistoryPk))
      {
        //$asListMsg[] = ' new search id [empty sId or history]. ';
        $this->csSearchId = manageSearchHistory($this->csUid, CONST_CANDIDATE_TYPE_CANDI);
        $poQB->addLimit('0, 50');
        $nLimit = 50;
      }
      else
      {
        //$asListMsg[] = ' just apply pager to reloaded search. ';
        $oPager = CDependency::getComponentByName('pager');
        $oPager->initPager();
        $nLimit = $oPager->getLimit();
        $nPagerOffset = $oPager->getOffset();

        $poQB->addLimit(($nPagerOffset*$nLimit).' ,'. $nLimit);
      }




      // =============================================================
      //TODO: to be moved when the search arrives

      $poQB->setTable('sl_candidate', 'scan');

      //join profile industry and occupation no matter what by default
      $poQB->addJoin('left', 'sl_candidate_profile', 'scpr', 'scpr.candidatefk = scan.sl_candidatepk');
      $poQB->addJoin('left', 'sl_company', 'scom', 'scom.sl_companypk = scpr.companyfk');
      $poQB->addJoin('left', 'sl_industry', 'sind', 'sind.sl_industrypk = scpr.industryfk');
      $poQB->addJoin('left', 'sl_occupation', 'socc', 'socc.sl_occupationpk = scpr.occupationfk');

      $sNow = date('Y-m-d H:i:s');
      $poQB->addSelect('scan.*,
          scom.name as company_name, scom.sl_companypk, scom.is_client as cp_client,
          (scpr.salary + scpr.bonus) as full_salary, scpr.grade, scpr.title, scpr._has_doc, scpr._in_play, scpr._pos_status, scpr.department,
          sind.label as industry, socc.label as occupation, TIMESTAMPDIFF(YEAR, scan.date_birth, "'.$sNow.'") AS age,
          scan.sl_candidatepk as PK');

      $poQB->addCountSelect('count(DISTINCT scan.sl_candidatepk) as nCount');


      $poQB->addJoin('left', 'event_link', 'elin', '(elin.cp_uid = "555-001" AND elin.cp_action = "ppav" AND elin.cp_type="candi" AND elin.cp_pk = scan.sl_candidatepk)');
      $poQB->addSelect('count(elin.eventfk) as nb_note');
      //$poQB->addSelect('MAX(elin.eventfk) as lastNote');
      $poQB->addSelect('MAX(elin.event_linkpk) as lastNote');

      if(!$oLogin->isAdmin())
      {
        $poQB->addWhere('(_sys_status = 0 OR _sys_redirect > 0)');
        $poQB->addSelect('IF(_sys_redirect > 0, _sys_redirect, scan.sl_candidatepk) as PK, 0 as _is_admin ');
      }
      else
        $poQB->addSelect(' 1 as _is_admin ');

      $sGroupBy = '';
      if(!empty($this->cnPk))
      {
        $asListMsg[] = ' + Mode name collect  ==> (status <= 3) ';
        $poQB->addWhere('scan.sl_candidatepk = '.$this->cnPk);
      }


      //-----------------------------------------------------------------------------
      //-----------------------------------------------------------------------------
      //add to the queryBuilder specific conditions for pipe or other custom filters
      $nFolderPk = getValue('folderpk');
      if(!empty($nFolderPk))
      {
        //$bLogged = $this->_addFolderFilter($asListMsg, $poQB);
        $nHistoryPk = $this->_addFolderFilter($asListMsg, $poQB);
      }


      if(getValue('pipe_filter'))
      {
        $this->_addPipeFilter($asListMsg, $poQB);
      }

      if($sTemplate == 'name_collect' || 'display' == 'last notes' || 'dba' == 'tools')
      {
        $bHeavyJoin = true;

        if($sTemplate == 'name_collect')
        {
          $asListMsg[] = ' + Mode name collect  ==> (status <= 3) ';
          $poQB->addWhere('scan.statusfk <= 3');
        }
      }

      //-----------------------------------------------------------------------------
      //-----------------------------------------------------------------------------


      //manage default options
      if(!$poQB->hasLimit())
        $poQB->addLimit('0, 50');

      //no scan.sl_candidatepk  --> make the HeavyJoin mode crash (subQuery)
      $sSortField = getValue('sortfield');
      if(!empty($sSortField))
      {
        if($sSortField == 'salary')
          $sSortField = 'full_salary';

        $poQB->setOrder($sSortField.' '.getValue('sortorder', 'DESC'));
      }

      if(!$poQB->hasOrder())
        $poQB->addOrder('sl_candidatepk DESC');

      if(empty($sGroupBy))
        $poQB->addGroup('sl_candidatepk', false);
      else
        $poQB->addGroup($sGroupBy, false);


      /*if(!$poQB->hasWhere())
      {
        $asListMsg[] = 'My most recent candidates';
        $sLastMonth = date('Y-m-d', strtotime('-6 month'));
        $poQB->addWhere('scan.date_created >= "'.$sLastMonth.'" AND scan.created_by = '.$this->casUserData['loginpk']);
      }*/


      $sMessage = $poQB->getTitle();
      if(!empty($sMessage))
        $asListMsg[] = $sMessage;

      // =====================================================================================

      //dump($poQB);
      $sQuery = $poQB->getCountSql();

      //echo $sQuery;
      $oDbResult = $oDb->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead || (int)$oDbResult->getFieldValue('nCount') == 0)
      {
        $sDebug = '<a href="javascript:;" onclick="$(this).parent().find(\'.query\').toggle(); ">query... </a>
          <span class="hidden query"><br />'.$sQuery.'</span><br /><br /><br />';
        return $this->_oDisplay->getBlocMessage('No candidate found for: '.implode(', ', $asListMsg)).$sDebug;
      }

      $nResult = (int)$oDbResult->getFieldValue('nCount');
      $sQuery = $poQB->getSql();
      //dump($sQuery);



      //Some joins are too heavy to make (notes, contacts...)
      //So we put the main query in a subquery, and join with the filtered / size-limited result
      if($bHeavyJoin)
      {
        if($sTemplate == 'name_collect')
        {
          $sQuery = 'SELECT *, GROUP_CONCAT(DISTINCT(scon.value) SEPARATOR ", ") as contact_detail FROM ('.$sQuery.') as candidate ';

          $sQuery.= ' LEFT JOIN sl_contact as scon ON (scon.item_type = \'candi\' AND scon.itemfk = candidate.sl_candidatepk) ';
          $sQuery.= ' WHERE candidate.statusfk <= 3 ';
          $sQuery.= ' GROUP BY candidate.sl_candidatepk ';

          $asSql = $poQB->getSqlArray();
          if(!empty($asSql['order']))
            $sQuery.= ' ORDER BY '.implode(', ', $asSql['order']);
        }
      }

      //echo $sQuery;
      $oDbResult = $oDb->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      if(!$bRead || !$oDbResult->numRows())
      {
        assert('false; // count query returned results but not the select');
        return $this->_oDisplay->getBlocMessage('No candidate found.');
      }


      //------------------------------------------------------------------
      //------------------------------------------------------------------
      //Query done, we've got results,  we're about to generate the HTML results
      // we save the query just before.
      $_SESSION['555-001']['query'][$this->csSearchId] = $sQuery;

      //save search in history if it's a new search
      if(empty($nHistoryPk) /*&& !$bLogged*/)
      {
        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_CANDI, 0, array('searchId' => $this->csSearchId));
        $sLink = 'javascript: loadAjaxInNewTab(\''.$sURL.'\', \'candi\', \'candidate\');';
        $nHistoryPk = logUserHistory($this->csUid, $this->csAction, $this->csType, $this->cnPk, array('text' => implode(', ', $asListMsg).' (#'.$nResult.' results)', 'link' => $sLink, 'data' => array('qb' => $poQB)), false);
      }

      $asData = array();
      $asPk = array();

      while($bRead)
      {
        $asCandidate = $oDbResult->getData();
        $asCandidate['g'] = $asCandidate['lastname'].' '.$asCandidate['firstname'];
        $asCandidate['h'] = $asCandidate['company_name'];

        if(empty($asCandidate['created_by']))
          $asCandidate['k'] = '-';
        else
          $asCandidate['k'] = $oLogin->getUserLink((int)$asCandidate['created_by'], true);

        $asCandidate['n'] = $asCandidate['title'];

        $asPk[] = (int)$asCandidate['sl_candidatepk'];
        $asData[(int)$asCandidate['sl_candidatepk']] = $asCandidate;
        $bRead = $oDbResult->readNext();
      }


      //Template related -- #1
      //params for the sub-templates when required
      switch($sTemplate)
      {
        case 'name_collect':
          $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CCandi_nc', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/candi_nc.tpl.class.php5')))));
          break;

        case 'pipeline':
          $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CCandi_nc', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/candi_pipeline.tpl.class.php5')))));
          break;

        default:

          $this->_addNoteData($asData, $asPk);
          $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CCandi_row', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/candi_row.tpl.class.php5')))));
          break;
      }

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //if required, set specific params for the template
      $sListId = uniqid();
      $oTemplate->setTemplateParams('CTemplateList', array('id' => $sListId, 'data-type' => 'candi'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->setRenderingOption('full', 'full', 'full');


      $sActionContainerId = uniqid();
      $sPic = $this->_oDisplay->getPicture(self::getResourcePath().'/pictures/list_action.png');
      $sJavascript = "var oCurrentLi = $(this).closest('li');

        if($('> div.list_action_container', oCurrentLi).length)
        {
          $('> div.list_action_container', oCurrentLi).fadeToggle();
        }
        else
        {
          var oAction = $('#".$sActionContainerId."').clone().show(0);

          $(oCurrentLi).append('<div class=\'list_action_container hidden\'></div><div class=\'floatHack\' />');
          $('div.list_action_container', oCurrentLi).append(oAction).fadeIn();
        }";

      //Template related -- #2
      if($nResult <= $nLimit)
      {
        $sSortJs = 'javascript';
        $sURL = '';
        $nAjax = 0;
      }
      else
      {
        $sSortJs = '-';
        $sURL = $this->_oPage->getAjaxUrl('sl_candidate', $this->csAction, CONST_CANDIDATE_TYPE_CANDI, 0, array('searchId' => $this->csSearchId, '__filtered' => 1, 'data_type' => CONST_CANDIDATE_TYPE_CANDI, 'replay_search' => $nHistoryPk));
        $nAjax = 1;
      }

      $sActionLink = $this->_oDisplay->getLink($sPic, 'javascript:;', array('onclick' => $sJavascript));
      $oConf->addColumn($sActionLink, 'a', array('id' => 'aaaaaa', 'width' => '20'));
      $oConf->addColumn('ID', 'sl_candidatepk', array('id' => 'bbbbbb', 'width' => '43', 'style' => 'margin: 0;', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));


      switch($sTemplate)
      {
        case 'name_collect':
          $oConf->addColumn('Lastname', 'lastname', array('id' => '', 'width' => '12%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Firstname', 'firstname', array('id' => '', 'width' => '12%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Company', 'h', array('id' => '', 'width' => '21%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Contact details', 'contact', array('id' => '', 'width' => '46%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          break;

        default:
          $oConf->addColumn('C', 'cp_client', array('id' => '', 'width' => '16', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Status', '_in_play', array('id' => '', 'width' => '40', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('G', 'grade', array('id' => '', 'width' => '16', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('R', '_has_doc', array('id' => '', 'width' => '16', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Lastname', 'lastname', array('id' => '', 'width' => '13.5%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Firstname', 'firstname', array('id' => '', 'width' => '13%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));
          $oConf->addColumn('Company', 'company_name', array('id' => '', 'width' => '20%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));


          //~150px
          if(in_array('title', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Title', 'title', array('id' => '', 'width' => '11.5%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));

          if(in_array('department', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Department', 'department', array('id' => '', 'width' => '11%', 'sortable'=> array($sSortJs => 'text', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));

          if(in_array('note', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Note', 'lastNote', array('id' => '', 'width' => '35', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));

          if(in_array('date_birth', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Age', 'date_birth', array('id' => '', 'width' => '30', 'sortable' => array($sSortJs => 'integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));

          if(in_array('salary', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Salary', 'salary', array('id' => '', 'width' => '42', 'sortable'=> array($sSortJs => 'value_integer', 'ajax' => $nAjax, 'url' => $sURL, 'ajax_target' => $this->csSearchId)));

          if(in_array('manager', $this->casSettings['candi_list_field']))
            $oConf->addColumn('Managed by', 'manager', array('id' => '', 'width' => '105')); //108px

          break;
      }

      $oConf->addBlocMessage('<span class="search_result_title_nb">'.$nResult.' result(s)</span> '.implode(', ', $asListMsg), array('style' => 'cursor: crossair'), 'title');


      //$sURL = $this->_oPage->getAjaxUrl('sl_candidate', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('searchId' => $this->csSearchId, '__filtered' => 1));
      $sURL = $this->_oPage->getAjaxUrl('sl_candidate', $this->csAction, CONST_CANDIDATE_TYPE_CANDI, 0, array('searchId' => $this->csSearchId, '__filtered' => 1, 'data_type' => CONST_CANDIDATE_TYPE_CANDI, 'replay_search' => $nHistoryPk));
      $oConf->setPagerTop(true, 'right', $nResult, $sURL.'&list=1', array('ajaxTarget' => '#'.$this->csSearchId));
      $oConf->setPagerBottom(true, 'right', $nResult, $sURL.'&list=1', array('ajaxTarget' => '#'.$this->csSearchId));


      //===========================================
      //===========================================
      //start building the HTML
      $sHTML = '';

      /* debug
       *
      if(!$bFilteredList)
        $sHTML.= $this->_oDisplay->getBlocStart($this->csSearchId, array('class' => 'scrollingContainer')).' new list';
      else
        $sHTML.= 'replay a search, pager offset '.$nPagerOffset.', container/search ID '.$this->csSearchId;*/

      if(!$bFilteredList)
        $sHTML.= $this->_oDisplay->getBlocStart($this->csSearchId, array('class' => 'scrollingContainer'));


        $sHTML.= $this->_oDisplay->getBlocStart($sActionContainerId, array('class' => 'hidden'));
        $sHTML.= '
          <div><input type="checkbox"
          onchange="if($(this).is(\':checked\')){ listSelectBox(\''.$sListId.'\', true); }else{ listSelectBox(\''.$sListId.'\', false); }"/> all</div>';

        $sURL = $this->_oPage->getAjaxUrl('sl_folder', CONST_ACTION_ADD, CONST_FOLDER_TYPE_FOLDER, 0, array('item_type' => CONST_CANDIDATE_TYPE_CANDI));
        $sHTML.= '<div>Create a folder from [<a href="javascript:;" onclick="
          listBoxClicked($(\'#'.$sListId.' ul li:first\'));
          sIds = $(\'.multi_drag\').attr(\'data-ids\');
          if(!sIds)
            return alert(\'Nothing selected\');

          goPopup.setLayerFromAjax(\'\', \''.$sURL.'&ids=\'+sIds);">selected items</a>]';

        if($nResult <= 1000)
          $sHTML.= ' [<a href="javascript:;" onclick="goPopup.setLayerFromAjax(\'\', \''.$sURL.'&searchId='.$this->csSearchId.'\');">'.$nResult.' results</a>]';
        else
          $sHTML.= ' [<span title="Too many results. Can\'t save more than 1000 results." style="font-style: italic">all</span> ]';

        $sURL = $this->_oPage->getAjaxUrl('sl_folder', CONST_ACTION_ADD, CONST_FOLDER_TYPE_ITEM, 0, array('item_type' => CONST_CANDIDATE_TYPE_CANDI));
        $sHTML.= '</div><div>Move into a folder [<a href="javascript:;" onclick="
          listBoxClicked($(\'#'.$sListId.' ul li:first\'));
          sIds = $(\'.multi_drag\').attr(\'data-ids\');
          if(!sIds)
            return alert(\'Nothing selected\');

          goPopup.setLayerFromAjax(\'\', \''.$sURL.'&ids=\'+sIds);">selected ones</a>]';

        if($nResult <= 1000)
          $sHTML.= ' [<a href="javascript:;" onclick="goPopup.setLayerFromAjax(\'\', \''.$sURL.'&searchId='.$this->csSearchId.'\');">'.$nResult.' results</a>]';
        else
          $sHTML.= ' [<span title="Too many results. Can\'t save more than 1000 results." style="font-style: italic">all</span> ]';

        $sHTML.= '</div>';

        if(!empty($nFolderPk))
        {
          $sURL = $this->_oPage->getAjaxUrl('sl_folder', CONST_ACTION_DELETE, CONST_FOLDER_TYPE_ITEM, 0, array('folderpk' => $nFolderPk, 'item_type' => CONST_CANDIDATE_TYPE_CANDI));
          $sHTML.= '<div>Remove from folder [<a href="javascript:;" onclick="listBoxClicked($(\'#'.$sListId.' ul li:first\'));
          sIds = $(\'.multi_drag\').attr(\'data-ids\');
          if(!sIds)
            return alert(\'Nothing selected\');

           AjaxRequest(\''.$sURL.'&ids=\'+sIds);
          ">selected</a>]
          [<a href="javascript:;" onclick="AjaxRequest(\''.$sURL.'&searchId='.$this->csSearchId.'\');">'.$nResult.' results</a>]</div>';
        }

        $sHTML.= $this->_oDisplay->getBlocEnd();

        //Add the list template to the html
        $sHTML.= $oTemplate->getDisplay($asData, 1, 5, 'safdassda');


        //---------------------------------------------
        //manage javascript action
        $sURL = $this->_oPage->getAjaxUrl('sl_folder', CONST_ACTION_SAVEADD, CONST_FOLDER_TYPE_ITEM, 0);
        $sHTML.='<script> initDragAndDrop(\''.$sURL.'\'); </script>';

        if(count($asData) == 1)
        {
          $asData = current($asData);
          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$asData['sl_candidatepk']);
          $sHTML.='<script> view_candi(\''.$sURL.'\'); </script>';
        }

        //DEBUG: Dropp the query at the end
        if($oLogin->getUserPk() == 367 || isDevelopment() )
        {
          $sHTML.= '<a href="javascript:;" onclick="$(this).parent().find(\'.query\').toggle(); ">query... </a>
            <span class="hidden query"><br />'.$sQuery.'</span><br /><br /><br />';
        }

        if($gbNewSearch)
          $sHTML.= $this->_oDisplay->getBlocEnd();

      return $sHTML;
    }


    private function _addFolderFilter(&$asListMsg, &$poQB)
    {
      $nFolderPk = (int)getValue('folderpk');

      $oFolder = CDependency::getComponentByName('sl_folder');
      $oDbFolder = $oFolder->getFolder($nFolderPk);
      $bRead = $oDbFolder->readFirst();
      if(!$bRead)
      {
        $asListMsg[] = $this->_oDisplay->getBlocMessage('Folder not found. It may have been deleted');
        return 0;
      }

      $sFolderName = $oDbFolder->getFieldValue('label');
      $asFolderItem = $oFolder->getFolderItem($nFolderPk, true);
      if(empty($asFolderItem))
        $asFolderItem = array(0);

      $poQB->addSelect($nFolderPk.' as folderfk');
      $poQB->addWhere('scan.sl_candidatepk IN ('.implode(',', $asFolderItem).') ');
      $asListMsg[] = 'folder #'.$nFolderPk.' - '.$sFolderName;

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_CANDI, 0, array('folderpk' => $nFolderPk));
      $sLink = 'javascript: loadAjaxInNewTab(\''.$sURL.'\', \'candi\', \'Folder \');';

      return logUserHistory('555-002', CONST_ACTION_VIEW, CONST_FOLDER_TYPE_FOLDER, $nFolderPk, array('text' => 'folder #'.$nFolderPk.':  '.$sFolderName, 'link' => $sLink, 'data' => array('qb' => $poQB)), false);
    }

    private function _addPipeFilter(&$asListMsg, &$poQB)
    {
      $sFilter = getValue('pipe_filter');

      $oLogin = CDependency::getCpLogin();
      $nCurrentUser = $oLogin->getUserPk();
      $nLoginfk = (int)getValue('pipe_user', 0);
      if(empty($nLoginfk))
        $nLoginfk = $nCurrentUser;

      if($nCurrentUser == $nLoginfk)
        $sBy = 'My ';
      else
        $sBy = $oLogin->getUserName($nLoginfk, true).'\'s ';

      $asStatus = array('in_play' => '< 150', 'pitched' => '= 1', 'resume_sent' => '= 2', 'stalled' => '= 150', 'fallen_off' => '= 200', 'placed' => '= 101');

      switch($sFilter)
      {
        case 'in_play':

          $asListMsg[] = $sBy.' [ in_play ] candidates';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status > 0 AND spli.status < 101  AND spli.created_by = '.$nLoginfk.'');
          $poQB->addWhere('(scpr._in_play = 1 AND spli.created_by = '.$nLoginfk.')');
          break;

        case 'pitched':
        case 'resume_sent':
        case 'placed':

          $asListMsg[] = $sBy.' [ '.str_replace('_', ' ', $sFilter).' ] candidates';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status '.$asStatus[$sFilter].' AND spli.created_by = '.$nLoginfk.'');
          break;

        case 'ccm':

          $asListMsg[] = $sBy.' [ CCM ] candidates ';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status > 50 AND spli.status < 100 AND spli.created_by = '.$nLoginfk.'');
          break;

        case 'fallen_off':

          $asListMsg[] = $sBy.' [ fallen - not interested ] candidates ';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status IN (200,201) AND spli.created_by = '.$nLoginfk.'');
          break;

        case 'expired':

          $asListMsg[] = $sBy.' [ stalled - expired ] candidates ';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status IN (150,151) AND spli.created_by = '.$nLoginfk.'');
          break;

        case 'met':
        case 'met6':
        case 'met12':

          $nMonth = (int)str_replace('met', '', $sFilter);
          if(empty($nMonth))
            $nMonth = 3;

          $sDate = date('Y-m-d', strtotime('-'.$nMonth.' month'));
          $asListMsg[] = $sBy.' Recently met candidates ('.$nMonth.' months | since'.$sDate.')';

          $poQB->addJoin('inner', 'sl_meeting', 'smee', 'smee.candidatefk = scan.sl_candidatepk AND smee.meeting_done = 1 AND smee.attendeefk = '.$nLoginfk.' AND smee.date_met >= "'.$sDate.'"');
          break;

        case 'offer':
          $asListMsg[] = $sBy.' [ Offer ] candidates ';
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status = 100 AND spli.created_by = '.$nLoginfk.'');
          break;

        case 'meeting':
          $sDate = date('Y-m-d', strtotime('+3 month'));
          $asListMsg[] = $sBy.' Scheduled meetings  (next 3 month )';

          $poQB->addJoin('inner', 'sl_meeting', 'smee', 'smee.candidatefk = scan.sl_candidatepk AND smee.meeting_done = 0 AND smee.attendeefk = '.$nLoginfk.' AND smee.date_meeting < "'.$sDate.'"');
          break;

        case 'rm':
          $asListMsg[] = $sBy.' Followed candidates [RM]';
          $poQB->addJoin('inner', 'sl_candidate_rm', 'scrm', 'scrm.candidatefk = scan.sl_candidatepk AND scrm.date_expired IS NULL AND scrm.loginfk = '.$nLoginfk);
          break;

        case 'all_active':
          $oLogin = CDependency::getCpLogin();
          if($nLoginfk == $oLogin->getUserPk())
            $asListMsg[] = 'Active candidates created by me';
          else
            $asListMsg[] = 'Active candidates created by '.$oLogin->getUserName($nLoginfk);

          $poQB->addWhere('(scan.created_by = '.$nLoginfk.' OR scpr.managerfk = '.$nLoginfk.')');
          $poQB->addJoin('inner', 'sl_position_link', 'spli', 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status <= 100 ');
          break;

        case 'all':
        default:
          $oLogin = CDependency::getCpLogin();
          if($nLoginfk == $oLogin->getUserPk())
            $asListMsg[] = 'All Candidates created by me';
          else
            $asListMsg[] = 'All Candidates created by '.$oLogin->getUserName($nLoginfk);

          $poQB->addWhere('(scan.created_by = '.$nLoginfk.' OR scpr.managerfk = '.$nLoginfk.')');
          break;
      }

      return true;
    }


    private function _getRandomText($pnMinSize = 1, $pnMaxSize = 12)
    {
      $nSize = rand($pnMinSize, $pnMaxSize);
      $sString = '';

      for($nCount = 0; $nCount < $nSize; $nCount++)
      {
        $sString .= chr(rand(97, 122));
      }

      return $sString;
    }

    private function _addNoteData(&$asData, $panPk)
    {
      $oNote = CDependency::getComponentByName('sl_event');
      $oDbResult = $oNote->getLastEvent($panPk, '555-001', 'ppav', 'candi');
      //dump($oDbResult);

      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return true;

      while($bRead)
      {
        //dump($oDbResult);
        if($oDbResult->getFieldValue('title'))
          $sContent = $oDbResult->getFieldValue('title').'<br />'.$oDbResult->getFieldValue('content');
        else
          $sContent = $oDbResult->getFieldValue('content');

        $nCandidatePk = (int)$oDbResult->getFieldValue('cp_pk');

        $asData[$nCandidatePk]['note_type'] = $oDbResult->getFieldValue('type');
        $asData[$nCandidatePk]['note_title'] = $oDbResult->getFieldValue('title');
        $asData[$nCandidatePk]['note_content'] = $oDbResult->getFieldValue('content');
        $asData[$nCandidatePk]['note_date'] = $oDbResult->getFieldValue('date_display');
        $bRead = $oDbResult->readNext();
      }

      return true;
    }



    private function _updateCompanyRss($pnCompanyPk = 0)
    {
      if(!assert('is_integer($pnCompanyPk)'))
        return false;

      //echo 'SL_Candidate cron >> _updateCompanyRss() <br />';

      //different behaviour if cron job or manually launched by user
      if(empty($pnCompanyPk))
      {
        //define how many to treat in this batch.
        // refresh all cp in 3 months with 68 batch a day (90 * 68) = 6120
        // every 15 minutes at night, every 30min during daytime
        $nCompany = $this->_getModel()->countCompanies();
        $nLimit =  ceil($nCompany / 6120) ;

        $sLimit = '1, '.$nLimit;
        $nSleep = 500000;
        $bManual = false;
        $sQuery = 'SELECT scom.*, scrs.date_created as dateRss
          FROM sl_company as scom
          LEFT JOIN sl_company_rss as scrs ON (scrs.companyfk = scom.sl_companypk)
          WHERE  LENGTH(scom.name) > 1
          ORDER BY scrs.date_created, sl_companypk DESC
          LIMIT '. $sLimit;

        //echo $sQuery.'<br />';
        $oDbResult = $this->_getModel()->executeQuery($sQuery);
      }
      else
      {
        $sWhere = 'sl_companypk = '.$pnCompanyPk;
        $sLimit = '';
        $nSleep = 10;
        $bManual = true;

        $oDbResult = $this->_getModel()->getByWhere('sl_company', $sWhere, '*', ' LENGTH(name) > 1 AND sl_companypk DESC', $sLimit);
      }


      $bRead = $oDbResult->readFirst();
      $nCount = 0;
      while($bRead)
      {
        $this->_updateCompanyFeed($oDbResult->getData(), $bManual);
        usleep($nSleep);

        $nCount++;
        $bRead = $oDbResult->readNext();
      }

      //echo '<br />'.$nCount.' company RSS updated';
      return true;
    }

    private function _updateCompanyFeed($pasCompanyData, $pbManual = false, $pnAttempt = 0)
    {
      if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
        return false;

      if(!isset($pasCompanyData['sl_companypk']) || empty($pasCompanyData['sl_companypk']))
      {
        assert('false; // Missing company data in the rss feed');
        dump($pasCompanyData);
        return false;
      }
      if(empty($pasCompanyData['name']))
      {
        //there are some sadly, we do nothing and log it as done
         assert('false; // Company without name #'.$pasCompanyData['sl_companypk'].' !!!! ');
         dump($pasCompanyData);
         return false;
      }


      //If launched manually, we try an accurate search.
      //If no result, the function will be launched a second time
      //can't use as_epq= anymore, google is getting rid of RSS feeds
      if($pbManual && $pnAttempt == 0)
        $sNewsUrl = 'http://news.google.com/news/search?output=rss&gl=jp&geo=jp&q='.$pasCompanyData['name'];
      else
        $sNewsUrl = 'http://news.google.com/news/search?output=rss&gl=jp&q='.urlencode($pasCompanyData['name']);

      try
      {
        libxml_use_internal_errors(true);
        $oXml = @new SimpleXMLElement($sNewsUrl, null, true);
        if(!$oXml)
        {
          throw new Exception('bad xml');
        }
      }
      catch(Exception $oEx)
      {
        assert('false; // could not load news feed from '.$sNewsUrl.'. Error: '.$oEx->getMessage());
        return false;
      }


      $asInsert = array();
      $asInsert['sl_company_rssfk'] = null;
      $asInsert['companyfk'] = (int)$pasCompanyData['sl_companypk'];
      $asInsert['date_created'] = date('Y-m-d H:i:s');
      $asInsert['url'] = $sNewsUrl;
      $asInsert['nb_news'] = 0;
      $asInsert['content'] = '';
      $sNews = '';

      $oChannel = $oXml->channel;
      if($oChannel)
      {
        //dump($oChannel);
        //count items (-10 for title, global desc, date, generator, image...)
        $asInsert['nb_news'] = count($oChannel->children()) - 10;

        $nCount = 0;
        foreach($oChannel->item as $oItem)
        {
          $sContent = (string)$oItem->description;
          $sEncoding = mb_detect_encoding(strip_tags($sContent));
          if($sEncoding == 'ASCII')
            $sEncoding = 'utf-8';

          $sContent = html_entity_decode($sContent, ENT_QUOTES, $sEncoding);
          $sContent = str_ireplace('<a ', '<a target="_blank" ', $sContent);
          $sContent = str_ireplace('<b>', '<b class="rss"> ', $sContent);
          //dump($sContent);

          $asMatch = array();
          preg_match_all('/<td.*>(.*)<\/td>/Ui', $sContent, $asMatch);

          $sContent = '<div>';
          $bFirst = true;
          foreach($asMatch[0] as $nKey => $sTd)
          {
            if($bFirst)
            {
              //dump('checking first TD: '.$sTd);
              $bFirst = false;
              $bHasPicture = false;
              $nPosition = stripos($sTd, 'img');
              //dump($nPosition);
              if($nPosition !== false)
              {
                //dump('found an <img in @ position '.$nPosition.' : '.$sTd);
                $sContent.= '<div class="feed_image">'.$asMatch[1][$nKey].'</div>';
                $bHasPicture = true;
              }
            }
            else
            {
              if($bHasPicture)
                $sContent.= '<div class="feed_content hasPicture">'.$asMatch[1][$nKey].'</div>';
              else
                $sContent.= '<div class="feed_content">'.$asMatch[1][$nKey].'</div>';
              //dump('Second TD: <div class="feed_content">'.$asMatch[1][$nKey].'</div>');
            }
          }
          $sContent.= '</div>';
          //dump('result for this item'.$sContent);

          $sNews.= '<div class="rss_news_container">';
          $sNews.= '<div class="rss_news_title">'.(string)$oItem->title.'</div>';
          $sNews.= '<div class="rss_news_source">Google news</div>';
          $sNews.= '<div class="rss_news_date">'.date('Y-m-d H:i:s', strtotime((string)$oItem->pubDate)).'</div>';
          $sNews.= '<div class="rss_news_content">'.$sContent.'</div>';
          $sNews.= '<div class="floatHack"></div>';
          $sNews.= '</div>';

          $nCount++;
          if($nCount >=3)
            break;
        }
      }

      //if the accurate search didn't work, I try to wider the scope
      if($pbManual && $pnAttempt == 0 && $nCount == 0)
      {
        return $this->_updateCompanyFeed($pasCompanyData, true, 1);
      }

      $asInsert['content'] = $sNews;

      $this->_getModel()->deleteByFk($asInsert['companyfk'], 'sl_company_rss', 'companyfk');
      $nPk = $this->_getModel()->add($asInsert, 'sl_company_rss');

      if(!$nPk)
      {
        assert('false; // could not save the company feed '.var_export($asInsert, true));
        return false;
      }

     //echo 'RSS updated for company: '.$asInsert['companyfk'].'<hr />';
      return true;
    }





    // ====================================================================================
    // ====================================================================================
    // Start MEETING section


    private function _getCandidateMeetingHistory($pnCandiPk)
    {
      if(!assert('is_integer($pnCandiPk)'))
        return 'No history available';

      $oDbResult = $this->_getModel()->getByFk($pnCandiPk, 'sl_meeting', 'candidate', '*, IF(meeting_done = 1, 1, 0) as m_done', 'm_done, date_meeting');
      $bRead = $oDbResult->readFirst();

      $sHTML = '';
      $sHTML.= $this->_oDisplay->getBlocStart();

      //add a link to create meeting on the top right
      $sUrl = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_MEETING, $pnCandiPk);
      $sLink = $this->_oDisplay->getLink('Set a new meeting', 'javascript:;', array('onclick' => 'goPopup.removeActive(); var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550; goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\');'));
      $sHTML.= $this->_oDisplay->getBloc('', $sLink, array('style' => 'float: right; padding: 3px 5px; margin-bottom: 5px; background-color: #f0f0f0; border: 1px solid #ddd;'));
      $sHTML.= $this->_oDisplay->getFloatHack();

      if(!$bRead)
      {
        $sHTML = $this->_oDisplay->getTitle('Meeting history', 'h3', true);

        $sHTML.= $this->_oDisplay->getBlocStart('', array('style' => 'text-align: center; width: 400px; margin: 15px auto; padding: 15px; border: 1px solid #dedede;'));
        $sHTML.= '<em>No meeting set with this candidate.</em><br /><br />';

          $sHTML.= $this->_oDisplay->getBloc('', $sLink, array('style' => 'margin: 0 auto; width: 150px; text-align: center; background-color: #f0f0f0; border: 1px solid #ddd;'));

        $sHTML.= $this->_oDisplay->getBlocEnd();
      }
      else
      {
        $oLogin = CDependency::getCpLogin();
        $oRight = CDependency::getComponentByName('right');

        $nCurrentUser = $oLogin->getUserPk();
        $sNow = date('Y-m-d H:i:s');
        $sToday = date('Y-m-d');
        $sAweekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        $bManager = $oRight->canAccess($this->csUid, CONST_ACTION_MANAGE, CONST_CANDIDATE_TYPE_MEETING);
        $asMeeting = array('active' => array(), 'inactive' => array());

        while($bRead)
        {
          $nMeetingPk = (int)$oDbResult->getFieldValue('sl_meetingpk');
          $sMeetingDate = $oDbResult->getFieldValue('date_meeting');
          $asDate = explode(' ', $sMeetingDate);
          $nAttendee = (int)$oDbResult->getFieldValue('attendeefk');
          $asButtons = array();

          if($nCurrentUser == $nAttendee)
            $sLink = '- me -';
          else
            $sLink = $oLogin->getUserLink($nAttendee, true);

          $nStatus = (int)$oDbResult->getFieldValue('meeting_done');
          if($nStatus != 0)
          {
            $sType = 'inactive';

            if($nStatus < 0)
            {
              $sClass = 'meeting_cancelled';
              $sStatus = $this->_oDisplay->getText('cancelled', array('class' => $sClass));
            }
            else
            {
              $sClass = 'meeting_done';
              $sStatus = $this->_oDisplay->getText('meeting done', array('class' => $sClass));
            }
          }
          else
          {
            $sType = 'active';
            $sStatus = $this->_oDisplay->getText(' - need update -', array('class' => 'meeting_passed'));

            if($sMeetingDate < $sAweekAgo)
              $sClass = 'meeting_passed_late';
            elseif($sMeetingDate < $sNow)
              $sClass = 'meeting_passed';
            elseif(substr($sMeetingDate, 0, 10) == $sToday)
            {
              $sClass = 'meeting_close';
              $sStatus = $this->_oDisplay->getText('soon', array('class' => $sClass));
            }
            else
            {
              $sClass = '';
              $sStatus = $this->_oDisplay->getText(' scheduled');
            }
          }


          $sMeeting = $this->_oDisplay->getBlocStart('', array('class' => 'meeting_row '.$sClass));

            $sMeeting.= $this->_oDisplay->getBloc('', 'Meeting set for ', array('class' => 'meeting_row_forth'));
            $sMeeting.= $this->_oDisplay->getBloc('', $sLink, array('class' => 'meeting_row_attendee'));


            $sMeeting.= $this->_oDisplay->getBloc('', 'on the <span>'.$asDate[0].'</span> at <span>'.substr($asDate[1], 0, 5).'</span> ', array('class' => 'meeting_row_date '.$sClass));
            $sMeeting.= $this->_oDisplay->getFloathack();

            //----------------------------------------------------
            //second row
            $asDate = explode(' ',$oDbResult->getFieldValue('date_created'));
            if($nCurrentUser == $oDbResult->getFieldValue('created_by'))
              $sLink = '- me -';
            else
              $sLink = $oLogin->getUserLink((int)$oDbResult->getFieldValue('created_by'), true);

            $sMeeting.= $this->_oDisplay->getBloc('', 'Meeting set by', array('class' => 'meeting_row_first'));
            $sMeeting.= $this->_oDisplay->getBloc('', $sLink, array('class' => 'meeting_row_creator'));
            $sMeeting.= $this->_oDisplay->getBloc('', 'on the <span>'.$asDate[0].'</span>', array('class' => 'meeting_row_date'));
            $sMeeting.= $this->_oDisplay->getFloathack();


            //----------------------------------------------------
            //Third row
            $sMeeting.= $this->_oDisplay->getBloc('', 'Status', array('class' => 'meeting_row_sixth'));
            $sMeeting.= $this->_oDisplay->getBloc('', $sStatus, array('class' => 'meeting_row_status'));

            $sMeeting.= $this->_oDisplay->getBlocStart('', array('class' => 'meeting_row_action'));
            if($bManager || ($nStatus < 1 && ($nCurrentUser == $oDbResult->getFieldValue('created_by') || $nCurrentUser == $nAttendee)))
            {


              if($nCurrentUser == $nAttendee)
              {
                $sUrl = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_DONE, CONST_CANDIDATE_TYPE_MEETING, $pnCandiPk, array('meetingpk' => $nMeetingPk));
                $asButtons[] = array('url' => '', 'label' => 'Meeting done', 'pic' => $this->getResourcePath().'pictures/done_16.png',
                    'onclick' => 'oConf = goPopup.getConfig(); oConf.width = 850; oConf.height = 450; goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\');');
              }

              $sUrl = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_MEETING, $pnCandiPk, array('meetingpk' => $nMeetingPk));
              $asButtons[] = array('url' => '', 'label' => 'Edit meeting', 'pic' => $this->getResourcePath().'pictures/edit_16.png',
                  'onclick' => 'oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550; goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ');

              $sUrl = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_CANDIDATE_TYPE_MEETING, $nMeetingPk, array('fast_edit' => 1, 'status' => -1));
              $asButtons[] = array('url' => '', 'label' => 'Cancel meeting', 'pic' => $this->getResourcePath().'pictures/delete_16.png',
                  'onclick' => 'if(window.confirm(\'Delete this meeting may affect user stats. Continue ?\')){ AjaxRequest(\''.$sUrl.'\'); } ');


              $sMeeting.= $this->_oDisplay->getActionButtons($asButtons, 1, 'Manage meeting...');

            }
            else
            {
              $sMeeting.= '<em class="light italic"> - no action available - </em>';
            }
            $sMeeting.= $this->_oDisplay->getBlocEnd();

          $sMeeting.= $this->_oDisplay->getFloatHack();
          $sMeeting.= $this->_oDisplay->getBlocEnd();

          $asMeeting[$sType][] = $sMeeting;
          $bRead = $oDbResult->readNext();
        }

        if(!empty($asMeeting['active']))
        {
          $sHTML.= $this->_oDisplay->getTitle('Scheduled meetings', 'h3', true);
          $sHTML.= implode('', $asMeeting['active']);
          $sHTML.= $this->_oDisplay->getBloc('', '&nbsp;', array('style' => 'border-top: 1px solid #bbb; '));
        }

        if( !empty($asMeeting['inactive']))
        {
          $sHTML.= $this->_oDisplay->getCR();
          $sHTML.= $this->_oDisplay->getTitle('Past meetings', 'h3', true);
          $sHTML.= implode('', $asMeeting['inactive']);
        }
      }


      $sHTML.= $this->_oDisplay->getBlocEnd();
      return $sHTML;
    }

    private function _getCandidateMeetingForm($pnCandiPk, $pnMeetingPk = 0)
    {
      if(!assert('is_key($pnCandiPk) && is_integer($pnMeetingPk)'))
        return array('error' => 'Sorry, an error occured.');

      $oCandidateData = $this->_getModel()->getByPk($pnCandiPk, 'sl_candidate');
      if(!$oCandidateData)
        return array('error' => 'Sorry, could not fetch the candidate\'s data.');

      $oCandidateData->readFirst();
      $sName = $oCandidateData->getFieldValue('lastname'). ' '.$oCandidateData->getFieldValue('firstname');

      $oPage = CDependency::getCpPage();

      if(!empty($pnMeetingPk))
      {
        $oDbMeeting = $this->_getModel()->getByPk($pnMeetingPk, 'sl_meeting');
        if(!$oDbMeeting || ! $oDbMeeting->readFirst())
          return array('error' => 'Counld not find the meeting.');

        $oForm = $this->_oDisplay->initForm('meetingAddForm');
        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_CANDIDATE_TYPE_MEETING, $pnMeetingPk);

        $oForm->setFormParams('meetingAddForm', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Update meeting', 'noCancelButton' => true));
        $oForm->setFormDisplayParams(array('noCancelButton' => true));
        $oForm->addField('input', 'meetingpk', array('type' => 'hidden','value'=> $pnMeetingPk));
        $oForm->addField('hidden', 'creatorfk', array('value' => $oDbMeeting->getFieldValue('creatorfk')));

        $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Update meeting with <b>'.$sName.'</b>'));
      }
      else
      {
        $oDbMeeting = new CDbResult();

        $oForm = $this->_oDisplay->initForm('meetingAddForm');
        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_CANDIDATE_TYPE_MEETING, $pnMeetingPk);

        $oForm->setFormParams('meetingAddForm', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save meeting'));
        $oForm->setFormDisplayParams(array('noCancelButton' => true));
        $oForm->addField('input', 'meetingpk', array('type' => 'hidden','value'=> 0));
        $oForm->addField('hidden', 'creatorfk', array('value' => $this->casUserData['pk']));

        $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Set a new meeting...'));
      }

      $oLogin = CDependency::getCpLogin();

      $oForm->addField('hidden', 'candidatefk', array('value' => $pnCandiPk));
      $oForm->addField('hidden', 'pclose', array('value' => getValue('pclose')));
      $oForm->addField('misc', '', array('label' => 'Candidate', 'type' => 'text', 'text' => '#'.$pnCandiPk.' - '.$sName, 'class'  => 'readOnlyField'));

      $nType = (int)$oDbMeeting->getFieldValue('type');
      $oForm->addField('select', 'meeting_type', array('label'=> 'Meeting type'));
      $oForm->addOption('meeting_type', array('label'=> 'In person', 'value' => 1, 'selected' => 'selected'));
      if($nType === 2)
        $oForm->addOption('meeting_type', array('label'=> 'By phone', 'value' => 2, 'selected' => 'selected'));
      else
        $oForm->addOption('meeting_type', array('label'=> 'By phone', 'value' => 2));

      if($nType === 3)
        $oForm->addOption('meeting_type', array('label'=> 'Video chat', 'value' => 3, 'selected' => 'selected'));
      else
        $oForm->addOption('meeting_type', array('label'=> 'Video chat', 'value' => 3));

      $oForm->addOption('meeting_type', array('label'=> 'Other', 'value' => 4));

      $sDate = $oDbMeeting->getFieldValue('date_meeting');
      $sPickerDate = substr($sDate, 0, strlen($sDate)-3);
      if(empty($pnMeetingPk))
        $sJavascript = '';
      else
        $sJavascript = 'if($(this).val() != \''.$sPickerDate.'\'){ $(this).closest(\'form\').find(\'#confirm_changes\').show(0); } ';

      $oForm->addField('input', 'date_meeting', array('type' => 'datetime', 'label'=> 'Meeting date', 'value' => $sPickerDate, 'onchange' => $sJavascript));

      $oForm->addField('input', 'where', array('type' => 'text', 'label'=> 'Location', 'value' => $oDbMeeting->getFieldValue('location')));


      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
      $nAttendee = (int)$oDbMeeting->getFieldValue('attendeefk');
      if(empty($nAttendee))
        $nAttendee = $oLogin->getUserPk();

      $sJavascript = 'if($(this).val() == '.$oLogin->getUserPk().'){ $(this).closest(\'form\').find(\'#notify_attendee_0_Id\').removeProp(\'checked\', \'\'); } ';

      if(!empty($pnMeetingPk))
        $sJavascript.= ' if($(this).val() != \''.$nAttendee.'\'){ $(this).closest(\'form\').find(\'#confirm_changes\').show(0); } ';

      $oForm->addField('selector', 'attendee', array('label'=>'Attendees', 'url' => $sURL, 'onchange' => $sJavascript));
      $oForm->setFieldControl('attendee', array('jsFieldTypeIntegerPositive' => ''));

      $oForm->addOption('attendee', array('label' => $oLogin->getUserNameFromPk($nAttendee), 'value' => $nAttendee));


      $oForm->addField('textarea', 'description', array('label'=> 'Description', 'value' => $oDbMeeting->getFieldValue('description')));

      $oForm->addField('checkbox', 'notify_attendee', array('label' => 'Send a notification to attendee when saving', 'checked' => 'checked'));
      $oForm->addField('checkbox', 'add_reminder1', array('label' => 'Set a reminder  - the day of the meeting', 'checked' => 'checked'));
      $oForm->addField('checkbox', 'add_reminder2', array('label' => 'Set a reminder  - 2 hours before the meeting'));
      $oForm->addField('checkbox', 'add_reminder3', array('label' => 'Set a reminder  - after the meeting (to update the candidate)', 'checked' => 'checked'));

      if(!empty($pnMeetingPk))
      {
        $oForm->addSection('confirm_changes', array('class' => 'hidden', 'id' => 'confirm_changes'));
        $oForm->addField('misc', '', array('type' => 'text', 'label' => '', 'text' => '<br /><div style="padding-left: 150px;" class="text_small italic">If the meeting date or attendee change, all the existing reminders will be deleted and new ones will be created.</div>'));
        $oForm->addField('checkbox', 'delete_reminder', array('label' => 'Delete previous reminders'));
        $oForm->closeSection();
      }

      return array('data' => $oForm->getDisplay(), 'error' => '');
    }

    private function _getMeetingDoneForm($pnCandiPk, $pnMeetingPk)
    {
      if(!assert('is_key($pnCandiPk) && is_integer($pnMeetingPk)'))
        return array('error' => 'Sorry, an error occured.');

      $oCandidateData = $this->_getModel()->getByPk($pnCandiPk, 'sl_candidate');
      if(!$oCandidateData)
        return array('error' => 'Sorry, could not fetch the candidate\'s data.');

      $oCandidateData->readFirst();
      $sName = $oCandidateData->getFieldValue('lastname'). ' '.$oCandidateData->getFieldValue('firstname');

      $oPage = CDependency::getCpPage();
      $oLogin = CDependency::getCpLogin();

      $oDbMeeting = $this->_getModel()->getByPk($pnMeetingPk, 'sl_meeting');
      if(!$oDbMeeting || ! $oDbMeeting->readFirst())
        return array('error' => 'Counld not find the meeting.');

      $oForm = $this->_oDisplay->initForm('meetingAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_VALIDATE ,CONST_CANDIDATE_TYPE_MEETING, $pnMeetingPk);

      $oForm->setFormParams('meetingAddForm', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save', 'noCancelButton' => true));
      $oForm->setFormDisplayParams(array('noCancelButton' => true));
      $oForm->addField('hidden', 'creatorfk', array('value' => $oDbMeeting->getFieldValue('created_by')));

      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Update meeting status&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<b>#'.$pnCandiPk.' - '.$sName.'</b>'));


      $oLogin = CDependency::getCpLogin();
      $nCreator = (int)$oDbMeeting->getFieldValue('created_by');

      $oForm->addField('input', 'loginfk', array('type' => 'hidden', 'value' => $nCreator));
      $oForm->addField('input', 'candidatefk', array('type' => 'hidden', 'value' => $pnCandiPk));

      $sMessage = '<br />By changing this meeting status to "done", you\'re atomatically changing the candidate status to "met"&sup1; .<br/>';
      if($nCreator != $oLogin->getUserPk())
      {
        $sMessage.= 'Plus, you\'ll credit&sup2; this meeting to '.$oLogin->getUserLink($nCreator).' who set the meeting up for you.<br/>';
        $oForm->addField('misc', '', array('type' => 'text', 'label' => '', 'text' => $sMessage.'<br /><br />'));

        $oForm->addField('checkbox', 'notify_meeting_done', array('legend' => 'Notification', 'label' => 'Email '.$oLogin->getUserLink($nCreator).' about this meeting'));
        $oForm->addField('misc', '', array('type' => 'text', 'text' => ''));
      }
      else
        $oForm->addField('misc', '', array('type' => 'text', 'label' => '', 'text' => $sMessage.'<br /><br />'));


      // A section to quickly create a note !!
      $oForm->addField('textarea', 'meeting_note', array('label' => 'add a note'));


      return $oForm->getDisplay().'<br /><span style="float: right; font-style:italic; color: #777; font-size: 85%;" >
        &sup1; Status: candidate ill keep his status unchanged is above met.</br >
        &sup2; KPI: data used to generate set_vs_met KPI</span>';
    }


    private function _getConsultantMeeting($pnLoginPk = 0)
    {
      if(!assert('is_integer($pnLoginPk)'))
        return 'No history available';

      $oLogin = CDependency::getCpLogin();

      $nLoginPk = (int)getValue('loginpk', 0);
      if(!empty($nLoginPk))
        $pnLoginPk = $nLoginPk;

      if(empty($pnLoginPk))
      {
        $pnLoginPk = (int)$this->casUserData['loginpk'];
        $sTitle = 'My meetings ';
      }
      else
        $sTitle = $oLogin->getUserLink($pnLoginPk).'\'s meetings ';

      $asWhere = array();
      $asWhere[] = '(smee.created_by = '.$pnLoginPk.' OR smee.attendeefk = '.$pnLoginPk.') ';

      $sMonth = getValue('month', '');
      if(empty($sMonth) || !is_date($sMonth))
        $sMonth = date('Y-m').'-01';

      $asWhere[] = 'smee.date_meeting >= "'.$sMonth.'" AND smee.date_meeting <= "'.date('Y-m', strtotime('+1 months', strtotime($sMonth))).'-01"';





      $sDateStart = date('Y-m', strtotime('-6 months', strtotime($sMonth))).'-01';
      $sDateEnd = date('Y-m', strtotime('+6 months', strtotime($sMonth))).'-01';
      $asMonthlyMeeting = $this->_getModel()->getMonthlyMeeting($pnLoginPk, $sDateStart, $sDateEnd);


      $sQuery = 'SELECT smee.*, scan.firstname, scan.lastname, IF(meeting_done = 1, 1, 0) as m_done FROM sl_meeting as smee
        INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = smee.candidatefk) WHERE '. implode(' AND ', $asWhere).'
        ORDER BY m_done, smee.date_meeting DESC ';
      $oDbResult = $this->_getModel()->executeQuery($sQuery);

      //$oDbResult = $this->_getModel()->getByWhere('sl_meeting', implode(' AND ', $asWhere), '', '');
      $bRead = $oDbResult->readFirst();


      $sHTML = $this->_oDisplay->getTitle($sTitle.' - for '.  substr($sMonth, 0, 7), 'h3', true);
      $sHTML.= $this->_oDisplay->getCR(2);
      $sHTML.= $this->_oDisplay->getBlocStart();

      // - - - - - - - - - - - - - - - - - - - - - - - - -
      // left section
      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meetingListLeft'));

      if(!$bRead)
      {
        $sHTML.= $this->_oDisplay->getBlocMessage('No meeting found in '.substr($sMonth, 0, 7).'.');
      }
      else
      {
        $asMeeting = array();
        $sToday = date('Y-m-d');

        while($bRead)
        {
          $asData = $oDbResult->getData();
          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$asData['candidatefk']);

          $asData['attendee'] = $oLogin->getUserLink((int)$asData['attendeefk']);
          $asData['creator'] = $oLogin->getUserLink((int)$asData['created_by']);
          $asData['candidate'] = '<a href="javascript:;" onclick="view_candi(\''.$sURL.'\'); goPopup.removeLastByType(\'layer\');">#'.$asData['candidatefk'].' - '.$asData['firstname'].' '.$asData['lastname'].'</a>';

          if($asData['meeting_done'] == -1)
          {
            $asData['date_meeting'] = '<span class="strike" title="Cancelled">'.$asData['date_meeting'].'</span>';
          }
          elseif($asData['meeting_done'] == 1)
          {
            $asData['date_meeting'] = '<span class="meeting_list_done" title="Done">'.$asData['date_meeting'].'</span>';
          }
          elseif($asData['date_meeting'] < $sToday)
          {
            $asData['date_meeting'] = '<span class="meeting_list_late" title="Done">'.$asData['date_meeting'].'</span>';
          }

          if($asData['attendeefk'] == $pnLoginPk)
          {
            if($asData['meeting_done'] == 0)
              $asMeeting['incoming'][] = $asData;
            else
              $asMeeting['done'][] = $asData;
          }
          else
            $asMeeting['other'][] = $asData;


          $bRead = $oDbResult->readNext();
        }

        $sTabSelected = 'incoming';
        //$sHeader = 'Meeting ID | Meeting date | Attendee name | Created by | Candidate<br />';

        if(empty($asMeeting['incoming']))
          $asTabs[] = array('label' => 'incoming', 'title' => 'Incoming meetings', 'content' => 'No meetings found');
        else
          $asTabs[] = array('label' => 'incoming', 'title' => 'Incoming meetings ('.count($asMeeting['incoming']).')', 'content' => $this->_getMeetingTabList($asMeeting['incoming']));

        if(empty($asMeeting['done']))
          $asTabs[] = array('label' => 'done', 'title' => 'Passed meetings', 'content' => 'No meetings found');
        else
          $asTabs[] = array('label' => 'done', 'title' => 'Passed meetings ('.count($asMeeting['done']).')', 'content' => $this->_getMeetingTabList($asMeeting['done']));

        if(empty($asMeeting['other']))
          $asTabs[] = array('label' => 'other', 'title' => 'Created for others', 'content' => 'No meetings found');
        else
          $asTabs[] = array('label' => 'other', 'title' => 'Created for others ('.count($asMeeting['other']).')', 'content' => $this->_getMeetingTabList($asMeeting['other']));


        $sHTML.= $this->_oDisplay->getBlocStart('', array('style' => 'float: left;   width: 100%; min-height:450px;'));
        $sHTML.= $this->_oDisplay->getTabs('meeting_tabs', $asTabs, $sTabSelected);
        $sHTML.= $this->_oDisplay->getBlocEnd();
      }

      $sHTML.= $this->_oDisplay->getBlocEnd();


      // - - - - - - - - - - - - - - - - - - - - - - - - -
      // Right section


      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meetingListRight'));
      /*$sHTML.='<select name="user_list" id="user_list">
        <option value="'.$oLogin->getUserPk().'">Me</option></select><br /><br />';*/


      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meeting_list_selector'));

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_MEETING);
      $oForm = $this->_oDisplay->initForm();
      $oForm->setFormParams('filterMeeting', true, array('action' => $sURL, 'class' => 'filterMeeting', 'onBeforeSubmit' => 'event.preventDefault();'));
      $oForm->setFormDisplayParams(array('noButton' => true, 'columns' => 1));

        $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
        $oForm->addField('selector', 'user_list', array('label' => 'Consultant', 'url' => $sURL));
        if($pnLoginPk)
        {
          $sConsultant = strip_tags($oLogin->getUserLink($pnLoginPk));
          $oForm->addOption('user_list', array('label' => $sConsultant, 'value' => $pnLoginPk));
        }

      $sHTML.= $oForm->getDisplay();
      $sHTML.= $this->_oDisplay->getBlocEnd();


      $sHTML.= $this->_oDisplay->getBloc('', 'Month&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>(Incoming/cancelled/done)</span> ', array('class' => 'meetingListRightHeader'));
      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meetingListRightDate'));

      foreach($asMonthlyMeeting as $sFullDate => $asNumber)
      {
        $sDate = date('M Y', strtotime($sFullDate));

        if($sMonth == $sFullDate)
          $sClass = 'selected';
        else
          $sClass = '';

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meeting_filter'));
        if(empty($asNumber['nb_meeting']))
        {

          $sHTML.= $this->_oDisplay->getText($sDate);
        }
        else
        {
          if($asNumber['nb_pending'] > 0)
            $asNumber['nb_pending'] = '<span style="color: orange;">'.$asNumber['nb_pending'].'</span>';

            $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'meeting_picker'));
            $sHTML.= $this->_oDisplay->getLink($sDate, 'javascript:;', array('class' => $sClass,
              'value' => $sFullDate,
              'onclick' => '
                $(this).closest(\'.meetingListRightDate\').find(\'a\').removeClass(\'selected\');
                $(this).addClass(\'selected\'); '));
            $sHTML.= $this->_oDisplay->getBlocEnd();

            $sHTML.= $this->_oDisplay->getBloc('', $asNumber['nb_meeting'],  array('class' => 'meeting_total'));
            $sHTML.= $this->_oDisplay->getBloc('','( '.$asNumber['nb_pending'].' / '.$asNumber['nb_cancel'].' / '.$asNumber['nb_done'].' )',  array('class' => 'meeting_split'));
        }

        $sHTML.= $this->_oDisplay->getBlocEnd();
      }

      $sHTML.= $this->_oDisplay->getFloatHack();
      $sHTML.= $this->_oDisplay->getBlocEnd();

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_MEETING);
      $sHTML.= $this->_oDisplay->getCR();
      $sHTML.= $this->_oDisplay->getLink('Filter list', 'javascript:;', array('class' => 'meeting_filter_btn', 'onclick' => '

        //to get the autocomplete to save its value. Submit is prevented
        $(this).parent().find(\'form\').submit();

        var sMonth = $(this).parent().find(\'a.selected\').attr(\'value\');
        var nLoginFk = $(this).parent().find(\'#user_listId\').val();
        var sURL = \''.$sURL.'\' + \'&month=\'+ sMonth + \'&loginpk=\'+ nLoginFk;

        goPopup.removeLastByType(\'layer\');
        ajaxLayer(sURL, 1080, 725);
        '));
      $sHTML.= $this->_oDisplay->getBlocEnd();


      $sHTML.= $this->_oDisplay->getBlocEnd();
      return $sHTML;
    }


    private function _getMeetingTabList($asMeeting)
    {
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $oConf->addColumn('ID', 'sl_meetingpk', array('width' => 40, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Meeting date', 'date_meeting', array('width' => 140, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Attendee', 'attendee', array('width' => 155));
      $oConf->addColumn('Created by', 'creator', array('width' => 155));
      $oConf->addColumn('Candidate', 'candidate', array('width' => 290));

      return $oTemplate->getDisplay($asMeeting);
    }


    /**
     * Save a meeting and create reminders
     *
     * @param integer $pnMeetingPk
     * @return array
     */
    private function _saveMeeting($pnMeetingPk = 0)
    {
      $asTmp = array();

      $asTmp['type'] = (int)getValue('meeting_type');
      $asTmp['created_by'] = (int)getValue('creatorfk');
      $asTmp['candidatefk'] = (int)getValue('candidatefk');

      if(!assert('is_key($asTmp[\'type\']) && is_key($asTmp[\'created_by\']) && is_key($asTmp[\'candidatefk\'])'))
        return array('error' => 'Missing parameters.');

      $asTmp['date_meeting'] = getValue('date_meeting').':00';
      if(empty($asTmp['date_meeting']) || !is_datetime($asTmp['date_meeting']))
        return array('error' => 'Meeting date is not valid. ['.$asTmp['date_meeting'].']');

      $asCandidate = $this->getModel()->getCandidateData($asTmp['candidatefk']);
      if(empty($asCandidate))
        return array('error' => 'Could not fin dthe candidate.');

      if(empty($pnMeetingPk))
      {
        //when creating a meeting, check that the date is > today
        if($asTmp['date_meeting'] < date('Y-m-d') && !(bool)getValue('confirm_date'))
        {
          return array('data' => '', 'action' => 'goPopup.setPopupConfirm(\'Meeting date is set in the past. Is it ok ?\', \' confirmMeetingForm();\', \'\', \'Keep going\', \'\', \'\', 350, 175); ');
        }
      }

      $asTmp['attendeefk'] = (int)getValue('attendee');
      if(empty($asTmp['attendeefk']))
        return array('error' => 'Attendee is not valid.');

      $asTmp['location'] = getValue('where');
      $asTmp['description'] = getValue('description');
      $asTmp['date_created'] = date('Y-m-d H:i:s');


      //---------------------------------------------------------------------
      //Manage notifications and reminders

      $nTimeMeeting = strtotime($asTmp['date_meeting']);

      //Notify attendee right  now ?
      $sNotify = getValue('notify_attendee');

      //Notification the day before the reminder
      $sReminder = getValue('add_reminder1');
      if(!empty($sReminder))
      {
        $asTmp['date_reminder1'] = date('Y-m-d', strtotime('-1 day', $nTimeMeeting)).' 08:00:00';
      }
      else
        $asTmp['date_reminder1'] = null;

      //Notification 2 hours before before the reminder
      $sReminder = getValue('add_reminder2');
      if(!empty($sReminder))
      {
        $asTmp['date_reminder2'] = date('Y-m-d H:i:s', strtotime('-3 hours', $nTimeMeeting));
      }
      else
        $asTmp['date_reminder2'] = null;


      //Naggy notification once the meeting date is passed
      $sReminder = getValue('add_reminder3');
      if(!empty($sReminder))
      {
        $asTmp['reminder_update'] = date('Y-m-d', strtotime('+1 day', $nTimeMeeting)).' 08:00:00';
      }
      else
        $asTmp['reminder_update'] = null;


      //---------------------------------------------------------------------
      //save the meeting && notify RM
      if(empty($pnMeetingPk))
      {
        $nMeetingPk = $this->_getModel()->add($asTmp, 'sl_meeting');


        //Finally: notify people the candidate status has changed (remove the current user obviosuly)
        $asFollower = $this->_getmodel()->getCandidateRm($asTmp['candidatefk'] , true, false);

        //Do not notify current user or attendee
        if(isset($asFollower[$this->casUserData['loginpk']]))
          unset($asFollower[$this->casUserData['loginpk']]);

        if(isset($asFollower[$asTmp['attendeefk']]))
          unset($asFollower[$asTmp['attendeefk']]);

        if(!empty($asFollower))
        {
          $oLogin = CDependency::getCpLogin();
          $oMail = CDependency::getComponentByName('mail');
          $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $asTmp['candidatefk']);

          $sSubject = 'RM alert - Meeting set with #'.$asTmp['candidatefk'];
          $sContent = 'A meeting has just been set up with the candidate <a href="'.$sURL.'">#'.$asTmp['candidatefk'].' - '.$asCandidate['firstname'].' '.$asCandidate['lastname'].
                  '</a> you are following.<br />Meeting planned for the '.$oLogin->getUserLink($asTmp['attendeefk']).' on the '.$asTmp['date_meeting'].' <br /><br />
                    Please access Slistem for more details.';

          foreach($asFollower as $asUserData)
          {
            $sEmail = 'Dear '.$asUserData['name'].', <br /><br />';
            $sEmail.= $sContent;

            $oMail->createNewEmail();
            $oMail->setFrom(CONST_PHPMAILER_DEFAULT_FROM, CONST_CRM_MAIL_SENDER);
            $oMail->addRecipient($asUserData['email'], $asUserData['name']);

            $oMail->send($sSubject, $sEmail);
          }
        }
      }
      else
      {
        return array('error' => 'Edit meeting no ready yet.');
      }

      $asTmp['sl_meetingpk'] = $nMeetingPk;
      $asTmp['candidatefk'] = (int)$asTmp['candidatefk'];
      //
      //Meeting saved ==>  send the notification and reminders
      $this->_addMeetingReminder($asTmp, $sNotify);


      //Meeting all saved... We update the candidate status if needed
      if($asCandidate['statusfk'] < 3)
      {
        $sQuery = 'UPDATE sl_candidate SET statusfk = 3 WHERE statusfk < 3 AND sl_candidatepk = '.$asTmp['candidatefk'];
        $this->_getModel()->executeQuery($sQuery);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $asTmp['candidatefk']);

        $this->_getModel()->_logChanges(array('statusfk' => '3'), 'user_history', 'New meeting set for the '.$asTmp['date_meeting'].'.<br /> &rarr; status changed to [Interview set]', '',
              array('cp_uid' =>$this->csUid, 'cp_action' => 'ppae', 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $asTmp['candidatefk']));
      }

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING, $asTmp['candidatefk']);

      // By default remove all layers...
      // But only 1 when coming from candidate form
      if((int)getValue('pclose') > 0)
      {
        return array('notice' => 'Meeting saved.', 'action' => '
        goPopup.removeLastByType(\'layer\');
        goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');
        refresh_candi('.$asTmp['candidatefk'].'); ');
      }

      return array('notice' => 'Meeting saved.', 'action' => '
        goPopup.removeByType(\'layer\');
        goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');
        refresh_candi('.$asTmp['candidatefk'].'); ');
    }






    private function _updateMeeting($pnMeetingPk, $pbAjax = false)
    {
      if(!assert('is_key($pnMeetingPk) && is_bool($pbAjax)'))
        return array('error' => __LINE__.' - Wrong parameters.');

      //1. check if the meeting is there
      $oDbResult = $this->_getModel()->getByPk($pnMeetingPk, 'sl_meeting');
      if(!$oDbResult || !$oDbResult->readFirst())
        return array('error' => __LINE__.' - The meeting couldn\'t be found.');

      $asMeeting = $oDbResult->getData();
      $asMeeting['sl_meetingpk'] = (int)$asMeeting['sl_meetingpk'];
      $asMeeting['candidatefk'] = (int)$asMeeting['candidatefk'];
      $asMeeting['created_by'] = (int)$asMeeting['created_by'];

      $oLogin = CDependency::getCpLogin();
      $nCurrentUser = $oLogin->getUserPk();


      if(!$oLogin->isAdmin() &&  !in_array($nCurrentUser, array($asMeeting['attendeefk'], $asMeeting['created_by'])))
        return array('error' => __LINE__.' - Sorry, you can\'t update this meeting: wrong account.');

      $asCandidate = $this->getCandidateData($asMeeting['candidatefk']);
      if(empty($asCandidate) || $asCandidate['_sys_status'] != 0)
        return array('error' => __LINE__.' - Sorry, candidate not available.');


      //========================================================================
      //2. Check for fast edit mode (edit status only)
      //From reminder emails, we'll endup here with status = 1 OR -1 (cancelled)
      if(!$pbAjax || getValue('fast_edit'))
      {
        $nStatus = (int)getValue('status', 0);
        if($nStatus === 1 || $nStatus === -1)
        {
          if((int)$asMeeting['meeting_done'] !== 0 && !getValue('force_update', 0))
          {
            $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_CANDIDATE_TYPE_MEETING, $pnMeetingPk, array('status' => $nStatus, 'fast_edit' => 1));
            return array('error' => '<h3>Warning</h3><br />This meeting status has already been updated.
             Are you sure you want to change this meeting status to "<strong>'.(($nStatus===1)? 'done': 'cancelled').'</strong>" ?<br /><br />
             <a href="javascript:;" onclick="AjaxRequest(\''.$sURL.'&force_update=1\'); goPopup.removeActive(true);">Yes</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="goPopup.removeActive(true);">No</a>');
          }

          $asUpdate = array('meeting_done' => $nStatus, 'date_updated' => date('Y-m-d H:i:s'));
          if($nStatus === 1)
            $asUpdate['date_met'] = $asUpdate['date_updated'];

          $bUpdated = $this->_getModel()->update($asUpdate, 'sl_meeting', 'sl_meetingpk = '.$pnMeetingPk);
          if(!$bUpdated)
            return array('error' => __LINE__.' - Sorry couldn\'t update the meeting.');


          // Update candidate status if needed    - - - - - - - - -
          $this->_meetingUpdateCandiStatus($nStatus, $asCandidate, $asMeeting);


          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING, $asMeeting['candidatefk']);
          return array('data' => $pnMeetingPk.' - Meeting status updated successfully to "<strong>'.(($nStatus===1)? 'done': 'cancelled').'</strong>".',
              'action' => '
                refresh_candi('.(int)$asMeeting['candidatefk'].');
                goPopup.removeByType(\'layer\');
                goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');');
        }
      }
      //========================================================================


      //========================================================================
      //3. standard meeting update
      //check other parameters
      $asNewMeeting = $asMeeting;
      $asNewMeeting['date_updated'] = date('Y-m-d H:i:s');
      $asNewMeeting['meeting_done'] = (int)$asNewMeeting['meeting_done'];

      $asNewMeeting['date_meeting'] = getValue('date_meeting');
      if(strlen($asNewMeeting['date_meeting']) < 19)
        $asNewMeeting['date_meeting'].= ':00';

      if(empty($asNewMeeting['date_meeting']) || !is_datetime($asNewMeeting['date_meeting']) || $asNewMeeting['date_meeting'] == '0000-00-00 00:00:00')
        return array('error' => __LINE__.' - Meeting date is not valid.');

      $sNow = date('Y-m-d H:i:s');
      if($asNewMeeting['date_meeting'] < $sNow && $asMeeting['date_meeting'] > $sNow && !getValue('confirm_date'))
      {
         return array('data' => '', 'action' => 'goPopup.setPopupConfirm(\'Meeting date is beeing changed to a date in the past. Is it ok ?\', \' confirmMeetingForm();\', \'\', \'Keep going\', \'\', \'\', 350, 175); ');
      }

      $asNewMeeting['attendeefk'] = (int)getValue('attendee');
      if(empty($asNewMeeting['attendeefk']))
        return array('error' => __LINE__.' - You need to select an attendee.');

      $asNewMeeting['type'] = (int)getValue('meeting_type', 1);
      $asNewMeeting['description'] = getValue('description');
      $asNewMeeting['location'] = getValue('where');


      //================================================================================================================
      //================================================================================================================
      //!! If the date or attendee change, we have to delete previous reminders and recreate new ones.
      $sCancelled = '#';
      if($asNewMeeting['date_meeting'] != $asMeeting['date_meeting'] || $asNewMeeting['attendeefk'] != $asMeeting['attendeefk'])
      {

        //check if the checkbox has been checked
        $sConfirm = getValue('delete_reminder');
        if(empty($sConfirm))
          return array('error' => __LINE__.' - Please confirm you agree to delete all the previous reminders.');

        $oNotify = CDependency::getComponentByName('notification');
        $asSource = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_MEETING, CONST_CP_PK => (int)$asMeeting['sl_meetingpk']);

        //---------------------------------------------
        //A. we delete the previous notifications
        $sCancelled = $oNotify->cancelNotification($asSource);


        //---------------------------------------------
        //B. if different recipient and not the current user,  send an email to notify the event is cancelled

        $asSource = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_MEETING, CONST_CP_PK => $asMeeting['sl_meetingpk']);
        $asItem = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $asMeeting['candidatefk']);
        if($asMeeting['attendeefk'] != $asNewMeeting['attendeefk'] && $asMeeting['attendeefk'] != $nCurrentUser)
        {
          $sId = $oNotify->initNotifier($asSource);
          $sContent = 'The meeting set for the '.$asMeeting['date_meeting'].' with #'.$asMeeting['candidatefk'].' has been cancelled. ';

          $nMeeting = $oNotify->addItemMessage($sId, (int)$asMeeting['attendeefk'], $asItem, $sContent, 'Meeting cancelled');
          if(empty($nMeeting))
          {
            assert('false; // can not add meeting reminder.');
            return array('error' => __LINE__.' - An error occured. Sorry, the meeting has not been saved.');
          }
        }


        //---------------------------------------------
        //C. Re-create new reminders

        //Notify attendee right  now ?
        $sNotify = getValue('notify_attendee');
        $nTimeMeeting = strtotime($asMeeting['date_meeting']);

        //Notification the day before the reminder
        $sReminder = getValue('add_reminder1');
        if(empty($sReminder))
          $asNewMeeting['date_reminder1'] = null;
        else
          $asNewMeeting['date_reminder1'] = date('Y-m-d', strtotime('-1 day', $nTimeMeeting)).' 08:00:00';

        //Notification 2 hours before before the reminder
        $sReminder = getValue('add_reminder2');
        if(!empty($sReminder))
          $asNewMeeting['date_reminder2'] = null;
        else
          $asNewMeeting['date_reminder2'] = date('Y-m-d H:i:s', strtotime('-3 hours', $nTimeMeeting));


        //Naggy notification once the meeting date is passed
        $sReminder = getValue('add_reminder2');
        if(empty($sReminder))
          $asNewMeeting['reminder_update'] = null;
        else
          $asNewMeeting['reminder_update'] = date('Y-m-d', strtotime('+1 day', $nTimeMeeting)).' 08:00:00';


        $this->_addMeetingReminder($asNewMeeting, $sNotify);
        //---------------------------------------------
      }


      //================================================================================================================
      //================================================================================================================

      $bUpdated = $this->_getModel()->update($asNewMeeting, 'sl_meeting', 'sl_meetingpk = '.$pnMeetingPk);
      if(!$bUpdated)
         return array('error' => __LINE__.' - An error occured. Sorry, the meeting has not been saved.');


      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_MEETING, $asMeeting['candidatefk']);
      return array('notice' => $sCancelled.' - Meeting updated successfully.', 'action' => '
        goPopup.removeByType(\'layer\');
        goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');
        refresh_candi('.(int)$asMeeting['candidatefk'].'); ');
    }



    private function _meetingUpdateCandiStatus($pnStatus, $pasCandidate, $pasMeetingData)
    {
      //Meeting all saved (done)  ==> update candidate status if needed
      if($pnStatus === 1 && $pasCandidate['statusfk'] < 4)
      {
        dump($pasMeetingData);

        if($pasMeetingData['type'] == 1)
          $nStatus = 6;
        elseif($pasMeetingData['type'] == 2 || $pasMeetingData['type'] == 3)
          $nStatus = 5;
        else
           $nStatus = 4;

        $sQuery = 'UPDATE sl_candidate SET statusfk = '.$nStatus.' WHERE statusfk < 4 AND sl_candidatepk = '.$pasCandidate['sl_candidatepk'];
        $this->_getModel()->executeQuery($sQuery);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pasCandidate['sl_candidatepk']);

        $this->_getModel()->_logChanges(array('statusfk' => '4'), 'user_history', 'Interview done.<br /> &rarr; Candidate status changed to [ Met ]', '',
              array('cp_uid' =>$this->csUid, 'cp_action' => 'ppae', 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $pasCandidate['sl_candidatepk']));

        return true;
      }

      //Meeting all saved (cancelled)  ==> update candidate status if needed
      if($pnStatus === -1 && $pasCandidate['statusfk'] == 3)
      {
        //check if there are other scheduled meetings
        $oDbResult = $this->_getModel()->getByWhere('sl_meeting', 'candidatefk = '.$pasCandidate['sl_candidatepk'].' AND meeting_done = 0');
        if($oDbResult->numRows() > 0)
          return true;

        //meeting cancelled + no other meeting
        $sQuery = 'UPDATE sl_candidate SET statusfk = 2 WHERE sl_candidatepk = '.$pasCandidate['sl_candidatepk'];
        $this->_getModel()->executeQuery($sQuery);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pasCandidate['sl_candidatepk']);

        $this->_getModel()->_logChanges(array('statusfk' => '4'), 'user_history', 'Meeting cancelled.<br /> &rarr; status changed to [ contacted ]', '',
              array('cp_uid' =>$this->csUid, 'cp_action' => 'ppae', 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $pasCandidate['sl_candidatepk']));
      }

      return true;
    }


    /**
     * Declare a meeting done: update the status. Optional: notify creator and eventually add a note
     * @param integer $pnMeetingPk
     * @return array to be json_encoded
     */
    private function _updateMeetingDone($pnMeetingPk)
    {
      if(!assert('is_key($pnMeetingPk)'))
        return array('error' => 'Could not find the meeting');

      $nCandidatefk = (int)getValue('candidatefk');
      if(!assert('is_key($nCandidatefk)'))
        return array('error' => __LINE__.' - Could not find the candidate data');
      $sNotify = getValue('notify_meeting_done');
      $sNote = trim(getValue('meeting_note'));
      $nCreator = 0;

      if(!empty($sNotify))
      {
        $nCreator = (int)getValue('creatorfk');
        if(!assert('is_key($nCreator)'))
          return array('error' => 'Could not find the meeting creator data.');
      }

      $asCandidate = $this->getCandidateData($nCandidatefk);
      if(empty($asCandidate) || $asCandidate['_sys_status'] > 0)
        return array('error' => __LINE__.' - Could not find the candidate data');

      //field tested, time for update, email and note
      $asData = array('meeting_done' => 1, 'date_met' => date('Y-m-d H:i:s'));
      $bUpdate = $this->_getModel()->update($asData, 'sl_meeting', 'sl_meetingpk = '.$pnMeetingPk);
      if(!$bUpdate)
        return array('error' => __LINE__.' - Could not update the meeting');


      $oLogin = CDependency::getCpLogin();
      $nCurrentUser = $oLogin->getUserPk();


      if(!empty($sNotify) /*&& $nCurrentUser != $nCreator*/)
      {
        $asUserData = $oLogin->getUserDataByPk($nCreator);

        if(isset($asUserData))
        {
          $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatefk);
          $sLink = $this->_oDisplay->getLink('#'.$nCandidatefk, $sURL);

          $sContent = 'Dear '.$asUserData['firstname'].',<br /><br />';
          $sContent.= $oLogin->getUserLink($nCurrentUser).' has met the candidate '.$sLink.' through the meeting you\'ve set for him.<br />';
          $sContent.= 'This meeting has been credited to your KPI stats.' ;

          if(!empty($sNote))
          {
            $sContent.= '<br /><br />A note has been created at the occasion:<br /><br />';
            $sContent.= $sNote;
          }

          $oMail = CDependency::getComponentByName('mail');
          $oMail->createNewEmail();
          $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);

          $oMail->addRecipient($asUserData['email'], $asUserData['lastname'].' '.$asUserData['firstname']);
          $oMail->send('Candidate #'.$nCandidatefk.' - Meeting done', $sContent);
        }
      }

      if(!empty($sNote))
      {
        $oNote = CDependency::getComponentByName('sl_event');
        $asResult = $oNote->addNote($nCandidatefk, 'meeting', $sNote, $nCurrentUser);

        if(isset($asResult['error']))
          return $asResult;
      }

      $this->_meetingUpdateCandiStatus(1, $asCandidate);

      return array('notice' => 'Meeting updated.', 'action' => 'goPopup.removeByType(\'layer\'); refresh_candi('.$nCandidatefk.'); ');
    }


    /**
     * base on the meeting parameters, create the different reminders
     * @param type $pasMeetingData
     * @param type $psNotice
     * @param type $psReminder1
     * @param type $psReminder2
     * @param type $psReminder3
     * @return boolean
     */
    private function _addMeetingReminder($pasMeetingData, $psNotice = '')
    {
      if(!assert('is_array($pasMeetingData) && !empty($pasMeetingData)'))
        return false;


      $oNotify = CDependency::getComponentByName('notification');
      $asSource = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_MEETING, CONST_CP_PK => (int)$pasMeetingData['sl_meetingpk']);
      $sId = $oNotify->initNotifier($asSource);

      $s2Hours = date('Y-m-d H:i:s', strtotime('+2 hours'));
      $sTomorrow = date('Y-m-d').' 23:59:59';

      $pasMeetingData['attendeefk'] = (int)$pasMeetingData['attendeefk'];
      $pasMeetingData['candidatefk'] = (int)$pasMeetingData['candidatefk'];

      //item concerned by the reminder
      $asItem = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => (int)$pasMeetingData['candidatefk']);

      //Notification right now
      if(!empty($psNotice))
      {
        $sReminderText = 'A meeting has been set for you on the '.$pasMeetingData['date_meeting'].' with the candidate #'.$pasMeetingData['candidatefk'];
        $sReminderText.= '<br />Meeting\'s description:<br /><br />'.$pasMeetingData['description'];

        $nReminder = $oNotify->addItemReminder($sId, $pasMeetingData['attendeefk'], $asItem, $sReminderText, 'Meeting notification', date('Y-m-d H:i:s'));
        if(!assert('is_key($nReminder)'))
          return false;
      }

      //Notification the day before the reminder
      if(!empty($pasMeetingData['date_reminder1']) && $pasMeetingData['date_reminder1'] > $sTomorrow)
      {
        $sReminderText = 'You have a meeting set tomorrow with the candidate #'.$pasMeetingData['candidatefk'];
        $sReminderText.= '<br />Meeting\'s description:<br /><br />'.$pasMeetingData['description'];

        $nReminder = $oNotify->addItemReminder($sId, $pasMeetingData['attendeefk'], $asItem, $sReminderText, 'Meeting tomorrow', $pasMeetingData['date_reminder1']);
        if(!assert('is_key($nReminder)'))
          return false;
      }

      //Notification 2 hours before before the reminder

      if(!empty($pasMeetingData['date_reminder2']) && $pasMeetingData['date_reminder2'] > $s2Hours)
      {
        $sReminderText = 'You have a meeting in about 2 hours with the candidate #'.$pasMeetingData['candidatefk'];
        $sReminderText.= '<br />Meeting\'s description:<br /><br />'.$pasMeetingData['description'];

        $nReminder = $oNotify->addItemReminder($sId, $pasMeetingData['attendeefk'], $asItem, $sReminderText, 'Meeting soon', $pasMeetingData['date_reminder2']);
        if(!assert('is_key($nReminder)'))
          return false;
      }


      if(!empty($pasMeetingData['reminder_update']) && $pasMeetingData['reminder_update'] != '0000-00-00 00:00:00')
      {
        //need the meeting pk to create the link
        $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $pasMeetingData['candidatefk'], array('meeting' => 'met'));
        $sReminderText = 'A meeting was supposed to happen on the '.$pasMeetingData['date_meeting'].' with candidate #'.$pasMeetingData['candidatefk'];
        $sReminderText.= '<br />Please remember to <a href="'.$sURL.'">update the candidate</a> profile and status.';

        $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_FASTEDIT, CONST_CANDIDATE_TYPE_CANDI, $pasMeetingData['candidatefk'], array('meeting' => 'met'));
        $sReminderText.= '<br /><br /> - You can simply change the candidate status to "assessed" by clicking <a href="'.$sURL.'&meetingpk='.$pasMeetingData['sl_meetingpk'].'&meeting_status=1">here</a>.';

        $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_CANDIDATE_TYPE_MEETING, $pasMeetingData['sl_meetingpk']);
        $sReminderText.= '<br /> - If you have already updated the candidate, you can update the meeting status :
          <a href="'.$sURL.'&status=1">done</a> - <a href="'.$sURL.'&status=-1">cancelled</a> ';

        $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_MEETING, $pasMeetingData['sl_meetingpk']);
        $sReminderText.= '- <a href="'.$sURL.'&status=0">postponed</a>.';

        $nReminder = $oNotify->addItemReminder($sId, $pasMeetingData['attendeefk'], $asItem, $sReminderText, 'Update candidate', $pasMeetingData['reminder_update'], 3, '3d');
        if(!assert('is_key($nReminder)'))
          return false;
      }

      return true;
    }
















    // ====================================================================================
    // ====================================================================================
    // Start CONTACT section


    private function _getCandidateContactForm($pnCandiPk, $pnContactpk = 0)
    {
      if(!assert('is_key($pnCandiPk)'))
        return array('error' => 'Sorry, an error occured.');

      $bIsAdmin = (bool)$this->casUserData['is_admin'];

      $oDbResult = $this->_getModel()->getContact($pnCandiPk, 'candi', $this->casUserData['pk'], array_keys($this->casUserData['group']), !$bIsAdmin);
      $bRead = $oDbResult->readFirst();

      $nContact = $oDbResult->numRows();
      $nNewFields = 4 - $nContact;
      if($nNewFields <= 0)
        $nNewFields = 1;

      $oPage = CDependency::getCpPage();

      $oForm = $this->_oDisplay->initForm('contactAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_CANDIDATE_TYPE_CONTACT, $pnCandiPk);

      $oForm->setFormParams('addcont', true, array('action' => $sURL, 'class' => 'ContactForm', 'submitLabel'=>'Save contact details'));
      $oForm->setFormDisplayParams(array('noCancelButton' => true, 'columns' => 2));
      $oForm->addField('input', 'candidatepk', array('type' => 'hidden','value'=> $pnCandiPk));
      $oForm->addField('input', 'userfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));

      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add/edit contact details'));
      $oForm->addField('misc', '', array('type' => 'text', 'text' => ''));

      $asTypes = getContactTypes();


      $nCount = 0;
      while($bRead)
      {
        $asData = $oDbResult->getData();
        if($asData['visible'])
        {
          $this->_getContactFormRow($oForm, $nCount, $asTypes, $asData);
          $nCount++;
        }

        $bRead = $oDbResult->readNext();
      }

      for($nCount = $nContact; $nCount < $nContact+$nNewFields; $nCount++)
      {
        $this->_getContactFormRow($oForm, $nCount, $asTypes, array());
      }

      return $oForm->getDisplay();
    }



    private function _getContactFormRow($poForm, $nCount, $asTypes, $pasData)
    {
      if(!empty($pasData))
        $asDefaultparam = array('readonly' => 'readonly', 'style' => 'background-color: #eee;border-color: #e6e6e6; font-style: italic; color: #777;');
      else
        $asDefaultparam = array();


      set_array($pasData['sl_contactpk'], 0);
      set_array($pasData['type'], '');
      set_array($pasData['value'], '');
      set_array($pasData['description'], '');
      set_array($pasData['visibility'], 0);

      $oLogin = CDependency::getCpLogin();
      if($oLogin->isAdmin())
      {
        //admin can always edit
        $asDefaultparam = array();

        //if edition, add delete box
        if(!empty($pasData['sl_contactpk']))
        {
          $poForm->addField('checkbox', 'delete['.$nCount.']', array('textbefore' => 1, 'label' => 'Delete this row ?', 'value' => (int)$pasData['sl_contactpk']));
          $poForm->addField('misc', '', array('type' => 'text', 'text' => '&nbsp;'));
        }
      }

      if(empty($this->casActiveUser))
      {
        //$this->casActiveUser = CDependency::getCpLogin()->getUserList(0, true, true);
        $this->casActiveUser = $oLogin->getGroupMembers(0, '', true, true);
      }

      if(empty($pasData['type']))
      {
        switch($nCount)
        {
          case 0: $pasData['type'] = 1; break;
          case 1: $pasData['type'] = 2; break;
          case 2: $pasData['type'] = 8; break;
          default:
            $pasData['type'] = 5; break;
        }
      }

      $pasData['visibility'] = (int)$pasData['visibility'];
      $asParam = $asDefaultparam;
      $asParam['label']= 'Type';
      $poForm->addField('select', 'contact_type['.$nCount.']', $asParam);

      foreach($asTypes as $nType => $asType)
      {
        if($pasData['type'] == $nType)
          $poForm->addOption('contact_type['.$nCount.']', array('value' => $nType, 'label' => $asType['label'], 'selected' => 'selected'));
        else
          $poForm->addOption('contact_type['.$nCount.']', array('value' => $nType, 'label' => $asType['label']));
      }

      $asParam = $asDefaultparam;
      $asParam['label']= 'Visibility';
      $asParam['onchange'] = 'if($(this).val() == 4){ $(\'.custom_vis'.$nCount.'\').fadeIn(); }else { $(\'.custom_vis'.$nCount.':visible\').fadeOut(); } ';
      $poForm->addField('select', 'contact_visibility['.$nCount.']', $asParam);

      if($pasData['visibility'] == 1)
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 1, 'label' => 'Public', 'selected' => 'selected'));
      else
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 1, 'label' => 'Public'));

      if($pasData['visibility'] == 2)
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 2, 'label' => 'Private', 'selected' => 'selected'));
      else
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 2, 'label' => 'Private'));

      if($pasData['visibility'] == 3)
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 3, 'label' => 'My team', 'selected' => 'selected'));
      else
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 3, 'label' => 'My team'));

      if($pasData['visibility'] == 4)
      {
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 4, 'label' => 'Custom', 'selected' => 'selected'));
        $sClass = '';
      }
      else
      {
        $poForm->addOption('contact_visibility['.$nCount.']', array('value' => 4, 'label' => 'Custom'));
        $sClass = ' hidden ';
      }


      $poForm->addField('input', 'sl_contactpk['.$nCount.']', array('type' => 'hidden', 'value' => (int)$pasData['sl_contactpk']));

      $asParam = $asDefaultparam;
      $asParam['label']= 'Value';
      $asParam['value']= $pasData['value'];
      $poForm->addField('input', 'contact_value['.$nCount.']', $asParam);


      //Group management
      $asParam = $asDefaultparam;
      $asParam['label']= 'Quick select';
      $asParam['onchange'] = '

            $(\'#contact_userfk'.$nCount.'Id\').tokenInput(\'clear\');
            $(\'#contact_userfk'.$nCount.'Id\').css(\'color\', \'red\');

            var asCons = $(this).val().split(\'||\');
            //console.log(asCons);
            $(asCons).each(function(nIndex, sValue)
            {
              var asValue = sValue.split(\'@@\');
              if(asValue.length == 2)
              {
                //console.log(\'adding user \'+asValue[1]);
                $(\'#contact_userfk'.$nCount.'Id\').tokenInput(\'add\', {id: asValue[0], name: asValue[1]});
              }
            });  ';

      $poForm->addField('select', 'groupfk'.$nCount, $asParam);
      $poForm->setFieldDisplayParams('groupfk'.$nCount, array('class' => 'custom_vis'.$nCount.$sClass));

      $poForm->addOption('groupfk'.$nCount, array('label' => '-', 'value' => $this->casUserData['loginpk'].'@@'.$this->casUserData['pseudo']));
      foreach($this->casActiveUser as $asUData)
      {
        $asUserList = array();
        foreach($asUData as $asUdetail)
          $asUserList[] = $asUdetail['loginpk'].'@@'.$asUdetail['pseudo'];

        $poForm->addOption('groupfk'.$nCount, array('label' => $asUdetail['group_label'], 'value' => implode('||', $asUserList)));
      }


      $asParam = array();
      $asParam['label']= 'Description';
      $asParam['value'] = $pasData['description'];
      $poForm->addField('input', 'contact_description['.$nCount.']', $asParam);



      $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('show_id' => 0, 'friendly' => 1, 'active_only' => 1));
      $poForm->addField('selector', 'contact_userfk['.$nCount.']', array('type' => 'text', 'label' => 'Users', 'nbresult' => 10, 'url' => $sURL));
      $poForm->setFieldDisplayParams('contact_userfk['.$nCount.']', array('id' => 'user_block_'.$nCount, 'class' => 'custom_vis'.$nCount.$sClass));

      if(!empty($pasData['custom_visibility']))
      {
        $asCustomUser = explode(',', $pasData['custom_visibility']);
        foreach($asCustomUser as $sLoginPk)
          $poForm->addOption('contact_userfk['.$nCount.']', array('value' => (int)$sLoginPk, 'label' => $oLogin->getUserName((int)$sLoginPk, true)));
      }


      $poForm->addField('misc', '', array('type' => 'br'));
    }


    private function _getCandidateContactSave($pbSave = true, $nCandidatePk = 0)
    {
      if(!empty($nCandidatePk))
        $nCandidatePk = $nCandidatePk;
      else
        $nCandidatePk = (int)getValue('candidatepk', 0);

      $nUserPk = (int)getValue('userfk', 0);
      if(empty($nUserPk))
        $nUserPk = (int)$this->casCandidateData['loginpk'];

      if(empty($nCandidatePk) || empty($nUserPk))
        return array('error' => __LINE__.' - Missing required data.');


      set_array($_POST['contact_value'], array());
      $asContact = array('update' => array(), 'insert' => array(), 'delete' => array());

      $bEmpty = true;
      foreach($_POST['contact_value'] as $nRow => $sValue)
      {
        if(!empty($sValue))
        {
          $bEmpty = false;
          break;
        }
      }

      if($bEmpty)
        return array('error' => 'No contact details input in the form.');

      $bAdmin = $this->_oLogin->isAdmin();

      $nValidRow = 0;
      $anPk = array();
      $asError = array();

      foreach($_POST['contact_value'] as $nRow => $sValue)
      {
        //added to keep crappy data in the database T_T
        if(!$bAdmin && !empty($_POST['sl_contactpk'][$nRow]))
          $sErrorType = 'dba';
        else
          $sErrorType = 'display';

        $_POST['contact_value'][$nRow] = trim($_POST['contact_value'][$nRow]);

        if(empty($sValue) || !empty($_POST['delete'][$nRow]))
        {
          if(isset($_POST['delete'][$nRow]) && !empty($_POST['delete'][$nRow]))
          {
            $asContact['delete'][] = (int)$_POST['delete'][$nRow];
            $nValidRow++;
          }

          unset($_POST['contact_value'][$nRow]);
          unset($_POST['contact_sl_contactpk'][$nRow]);
          unset($_POST['contact_description'][$nRow]);
          unset($_POST['contact_type'][$nRow]);
          unset($_POST['contact_visibility'][$nRow]);
          unset($_POST['contact_userfk'][$nRow]);
        }
        else
        {
          $nValidRow++;

          if(!isset($_POST['contact_type'][$nRow]))
            $_POST['contact_type'][$nRow] = 0;

          if(!isset($_POST['contact_visibility'][$nRow]))
            $_POST['contact_visibility'][$nRow] = 0;

          if(!isset($_POST['contact_userfk'][$nRow]))
            $_POST['contact_userfk'][$nRow] = '';

          if(!isset($_POST['sl_contactpk'][$nRow]))
            $_POST['sl_contactpk'][$nRow] = 0;
          else
            $_POST['sl_contactpk'][$nRow] = (int)$_POST['sl_contactpk'][$nRow];

          if(!isset($_POST['contact_description'][$nRow]))
            $_POST['contact_description'][$nRow] = 0;

          $_POST['contact_type'][$nRow] = (int)$_POST['contact_type'][$nRow];
          $_POST['contact_visibility'][$nRow] = (int)$_POST['contact_visibility'][$nRow];

          //1. controls values
          switch($_POST['contact_type'][$nRow])
          {
            case 1:
            case 2:
            case 4:
            case 6:

              //cleaning data --> crap from [slistem postgresql]
              $_POST['contact_value'][$nRow] = trim(str_replace(array("\n","\r", "\r\n", "\t"), '',  $_POST['contact_value'][$nRow]));
              $sPhone = preg_replace('/[0-9\. \-+()]/', '', $_POST['contact_value'][$nRow]);
              if(!empty($sPhone))
              {
                $asError[$sErrorType][] = 'Contact row #'.($nRow+1).': phone number ['.$_POST['contact_value'][$nRow].'] contains invalid characters.['.$sPhone.']';
              }
              else
              {
                $sPhone = preg_replace('/[^0-9]/', '', $_POST['contact_value'][$nRow]);
                if(strlen($sPhone) < 8)
                  $asError[$sErrorType][] = 'Contact row #'.($nRow+1).': phone number ['.$_POST['contact_value'][$nRow].']  too short.';
              }

              break;

            case 5:

              if(!isValidEmail($_POST['contact_value'][$nRow]))
                $asError[$sErrorType][] = 'Contact row #'.($nRow+1).':  email ['.$_POST['contact_value'][$nRow].']  isn\'t valid.';

              break;

            case 3:
            case 7:
            case 8:

              if(strtolower(substr($_POST['contact_value'][$nRow], 0, 4)) != 'http')
                $_POST['contact_value'][$nRow] = 'http://'.$_POST['contact_value'][$nRow];

              if(!isValidUrl($_POST['contact_value'][$nRow]) || !isValidUrl($_POST['contact_value'][$nRow], true))
              {
                $asError[$sErrorType][] = 'Contact row #'.($nRow+1).':  web url ['.$_POST['contact_value'][$nRow].']  isn\'t valid.';
              }
              else
              {
                if((int)$_POST['contact_type'][$nRow] == 7)
                {
                  if(stripos($_POST['contact_value'][$nRow], 'facebook') === false)
                    $asError[$sErrorType][] = 'Contact row #'.($nRow+1).':  facebook url ['.$_POST['contact_value'][$nRow].']  must contain "facebook" in the url.['.$_POST['contact_value'][$nRow].'] ';
                }

                if((int)$_POST['contact_type'][$nRow] == 8)
                {
                  if(stripos($_POST['contact_value'][$nRow], 'linkedin') === false)
                    $asError[$sErrorType][] = 'Contact row #'.($nRow+1).':  linkedin url ['.$_POST['contact_value'][$nRow].']  must contain "linkedin" in the url.['.$_POST['contact_value'][$nRow].'] ';
                }
              }
              break;

          }


          //2. check visibility
          if($_POST['contact_visibility'][$nRow] == 4)
          {
            if(empty($_POST['contact_userfk'][$nRow]))
              $asError[$sErrorType][] = 'Contact row #'.($nRow+1).':  if visibility is set to "custom", you need to select users.';
          }


          if(empty($_POST['contact_type'][$nRow]) || empty( $_POST['contact_visibility'][$nRow]))
            $asError[$sErrorType][] = 'Contact row #'.($nRow+1).': Contact type and/or visibility invalid.';


          $asTmp = array('sl_contactpk' => $_POST['sl_contactpk'][$nRow],
                'type' => $_POST['contact_type'][$nRow], 'item_type' => 'candi', 'itemfk' => $nCandidatePk,
                'date_create' => date('Y-m-d H:i:s'), 'loginfk' => $nUserPk, 'value' => $_POST['contact_value'][$nRow],
                'description' => $_POST['contact_description'][$nRow], 'visibility' => $_POST['contact_visibility'][$nRow],
                'groupfk' => 0, 'userfk' => $_POST['contact_userfk'][$nRow]);

          if(!empty($_POST['sl_contactpk'][$nRow]))
          {
              $anPk[] = $_POST['sl_contactpk'][$nRow];
              $asContact['update'][] = $asTmp;
          }
          else
            $asContact['insert'][] = $asTmp;
        }
      }

      if(empty($nValidRow))
        return array('error' => 'No contact details to save... Please input contact details in the "value" field.');

      if(!empty($asError['display']))
        return array('error' => 'The forms contains '.count($asError['display']).' error(s).<br /> - '.implode('<br /> - ', $asError['display']));


      // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
      //For existing contact details, send an automatic dba for it to be fixed by the admin.
      if(!empty($asError['dba']))
      {
        $oMail = CDependency::getComponentByName('mail');
        $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatePk);

        $sSubject = 'Automatic DBA request';
        $sContent = 'Dear Admin,<br /><br />
          Slistem has detected invalid contact details on the candidate profile <a href="'.$sURL.'">#'.$nCandidatePk.'</a>.
          Please take actions based on the following errors:<br /><br /> - '.implode('<br /> - ', $asError['dba']);

        $oMail->createNewEmail();
        $oMail->addRecipient('dba_request@slate.co.jp', 'DBA');
        $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
        $oMail->send($sSubject, $sContent);
      }

      // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
      if(empty($asContact['update']) && empty($asContact['insert']) && empty($asContact['delete']))
        return array('notice' => 'No contact details to save...', 'action' => ' goPopup.removeLastByType(\'layer\'); ');

      if($pbSave)
      {
        // 3.Save contacts details
        if(!empty($asContact['update']))
        {
          // Load the previous contact details. Check if everything is still here
          // and get to know if it's been edited
          //$oDbResult = $this->_getModel()->getByWhere('sl_contact', ' sl_contactpk IN ('.implode(',', $anPk).') ');
          $oDbResult = $this->_getModel()->getContactByPk($anPk);
          $bRead = $oDbResult->readFirst();
          $asPrevious = array();
          while($bRead)
          {
            $asPrevious[$oDbResult->getFieldValue('sl_contactpk')] = $oDbResult->getData();
            $bRead = $oDbResult->readNext();
          }

          foreach($asContact['update'] as $asData)
          {
            if(!isset($asPrevious[$asData['sl_contactpk']]))
              return array('error' => 'Error: Editing a contact detail that doesn\'t exist anymore.');

            $asOldData = $asPrevious[$asData['sl_contactpk']];

            if($asOldData['value'] != $asData['value'] || $asOldData['description'] != $asData['description']
            || $asOldData['visibility'] != $asData['visibility'] || $asOldData['loginfk'] != $asData['userfk'])
            {
              logUserHistory($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CONTACT, (int)$asData['sl_contactpk'], $asData, true);

              $asData['date_update'] = date('Y-m-d H:i:s');
              $asData['updated_by'] = $this->casUserData['pk'];
              unset($asData['date_create']);
              unset($asData['loginfk']);

              $this->_getModel()->update($asData, 'sl_contact', 'sl_contactpk = '.$asData['sl_contactpk']);

              //delete  `sl_contact_visibility`
              $this->_getModel()->deleteByFk( (int)$asData['sl_contactpk'], 'sl_contact_visibility', 'sl_contactfk');

              // - - - - - - - - - - - - - - - - - - - -
              //if visibility == 4 (custom) add users in the visibility table
              if($asData['visibility'] == 4 && !empty($asData['userfk']))
              {
                $asViewer = explode(',', $asData['userfk']);
                $asViewerData = array();
                foreach($asViewer as $sViewerPk)
                {
                  $asViewerData['sl_contactfk'][] = (int)$asData['sl_contactpk'];
                  $asViewerData['loginfk'][] = (int)$sViewerPk;
                }

                $this->_getModel()->add($asViewerData, 'sl_contact_visibility');
              }
            }
          }
        }

        foreach($asContact['insert'] as $asData)
        {
          $this->_getModel()->add($asData, 'sl_contact');
        }

        if(!empty($asContact['delete']))
        {
          $this->_getModel()->deleteByWhere('sl_contact', 'sl_contactpk IN('.implode(',', $asContact['delete']).') ');
          $this->_getModel()->deleteByWhere('sl_contact_visibility', 'sl_contactfk IN('.implode(',', $asContact['delete']).') ');
        }
      }

      $sLog = 'Contact details: '.count($asContact['update']).' updated, '.count($asContact['insert']).' added, '.count($asContact['delete']).' deleted';
      $this->_getModel()->_logChanges(array('contact' => 'save'), 'user_history', $sLog, '',
              array('cp_uid' => '555-001', 'cp_action' => 'ppae', 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $nCandidatePk));

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatePk);
      return array('notice' => 'Contact details saved successfully.', 'action' => 'view_candi("'.$sURL.'", "#tabLink2"); goPopup.removeByType(\'layer\'); ');
    }


    // End CONTACT section
    // ====================================================================================










    // ====================================================================================
    // ====================================================================================
    // start CANDIDATE section
    private function _getCandidateAddForm($pnCandidatePk = 0)
    {
      if(!assert('is_integer($pnCandidatePk)'))
        $pnCandidatePk = 0;

      $bDisplayAllTabs = true;
      $asAttribute = array();
      if(empty($pnCandidatePk))
      {

        $nDuplicateId = (int)getValue('duplicate');
        if(!empty($nDuplicateId))
        {
          $oDbResult = $this->_getModel()->getCandidateFormData($nDuplicateId);
          $oDbResult->readFirst();
          $asClone = $oDbResult->getData();

          $oDbResult = new CDbResult();
          $oDbResult->setFieldValue('companyfk', $asClone['companyfk']);
          $oDbResult->setFieldValue('company_name', $asClone['company_name']);
          $oDbResult->setFieldValue('occupationfk', $asClone['occupationfk']);
          $oDbResult->setFieldValue('industryfk', $asClone['industryfk']);
          $oDbResult->setFieldValue('is_client', $asClone['is_client']);
        }
        else
          $oDbResult = new CDbResult();

      }
      else
      {
        $bDisplayAllTabs = false;
        $oDbResult = $this->_getModel()->getCandidateFormData($pnCandidatePk);
        $oDbResult->readFirst();

        $sAttribute = $oDbResult->getFieldValue('attribute_type');
        if(!empty($sAttribute))
        {
          $asAttributeType = explode(',', $sAttribute);
          $asAttributeValue = explode(',', $oDbResult->getFieldValue('attribute_value'));
          $asAttributeLabel = explode(',', $oDbResult->getFieldValue('attribute_label'));
          foreach($asAttributeType as $nKey => $sValue)
            $asAttribute[$sValue][$asAttributeValue[$nKey]] = $asAttributeLabel[$nKey];
        }

        //Adding a candidate with a $pnCandidatePk ==> duplicate the candidate
        //need to remove all the
        if($this->csAction == CONST_ACTION_ADD)
        {
          $bDisplayAllTabs = true;
          $asToKeep = array('department' => $oDbResult->getFieldValue('department'),
              'companyfk' => (int)$oDbResult->getFieldValue('companyfk'),
              'company_name' => $oDbResult->getFieldValue('company_name'),
              'industryfk' => (int)$oDbResult->getFieldValue('industryfk'));

          $oDbResult = new CDbResult();
          foreach($asToKeep as $sField => $vValue)
            $oDbResult->setFieldValue($sField, $vValue);

          $oDbResult->readFirst();
        }
      }



      $this->_oPage->addJsFile(self::getResourcePath().'js/candidate_form.js');
      $this->_oPage->addCssFile(self::getResourcePath().'css/sl_candidate.css');

      $sHTML = '';
      $oForm = $this->_oDisplay->initForm('candidateAddForm');
      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);

      $oForm->setFormParams('addcandidate', true, array('action' => $sURL, 'class' => 'candiAddForm', 'submitLabel'=>'Save candidate', 'ajaxTarget' => 'candi_duplicate'));
      $oForm->setFormDisplayParams(array('noCancelButton' => true, /*'noSubmitButton' => 1,*/ 'columns' => 1));


      $oForm->addField('input', 'userfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));
      $oForm->addField('input', 'check_duplicate', array('id' => 'dup_checked', 'type' => 'hidden', 'value' => 0));
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add/edit contact details'));

      if($bDisplayAllTabs)
      {
        $sTab = '<ul class="candidate_form_tabs"><li onclick="toggleFormTabs(this, \'candi_data\');" class="selected"><div>Candidate data</div></li>';
        $sTab.= '<li onclick="toggleFormTabs(this, \'candi_contact\');"><div>Contact details</div></li>';
        $sTab.= '<li onclick="toggleFormTabs(this, \'candi_note\');"><div>Notes</div></li>';
        $sTab.= '<li onclick="toggleFormTabs(this, \'candi_resume\');"><div>Resume</div></li>';
        $sTab.= '<li onclick="toggleFormTabs(this, \'candi_duplicate\');" class="hidden tab_duplicate"><div>Duplicates</div></li>';
        $sTab.= '</ul>';
        $oForm->addField('misc', 'tabs_row', array('type' => 'text', 'text' => $sTab));
        $oForm->setFieldDisplayparams('tabs_row', array('style' => 'width: 100%;'));
      }


      // =======================================================================
      //candidate data section
      $oForm->addSection('', array('id' => 'candi_data'));

        $oForm->addField('misc', 'title1', array('type' => 'text', 'text'=> 'Candidate details'));
        $oForm->setFieldDisplayParams('title1', array('class' => 'formSectionTitle'));

        $oForm->addSection('', array('class' => 'candidate_inner_section'));

          $nSex = (int)$oDbResult->getFieldValue('sex');
          $oForm->addField('select', 'sex', array('label' => 'gender', 'onchange' => 'toggleGenderPic(this);'));
          $oForm->addOption('sex', array('label' => 'female', 'value' => 2));
          if($nSex === 1)
            $oForm->addOption('sex', array('label' => 'male', 'value' => 1, 'selected' => 'selected'));
          else
            $oForm->addOption('sex', array('label' => 'male', 'value' => 1));

          $oForm->setFieldDisplayParams('sex', array('class' => 'genderField'));

          $sGender = '
            <span class="genderPic">
            <span href="javascript:;" onclick="toggleGenderPic(false, 1);" class="woman '.(($nSex != 1)? '':'hidden').'" ><img src="/common/pictures/slistem/woman_16.png"/></span>
            <span href="javascript:;" onclick="toggleGenderPic(false, 2);" class="man '.(($nSex == 1)? '':'hidden').'" ><img src="/common/pictures/slistem/man_16.png"/></span>
            </span>';


          $oForm->addField('misc', '', array('type' => 'text', 'text'=> $sGender));
          //$oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));
          $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));

          if(empty($pnCandidatePk) || $this->_oLogin->isAdmin())
          {
            $oForm->addField('input', 'lastname', array('label' => 'lastname', 'value' => $oDbResult->getFieldValue('lastname')));
            $oForm->addField('input', 'firstname', array('label' => 'firstname', 'value' => $oDbResult->getFieldValue('firstname')));
          }
          else
          {
            $oForm->addField('input', 'lastname', array('readonly' => 'readonly', 'class' => 'disabled', 'label' => 'lastname', 'value' => $oDbResult->getFieldValue('lastname')));
            $oForm->addField('input', 'firstname', array('readonly' => 'readonly', 'class' => 'disabled', 'label' => 'firstname', 'value' => $oDbResult->getFieldValue('firstname')));
          }

          $sDate = $oDbResult->getFieldValue('date_birth');
          $sDefaultDate = date('Y', strtotime('-30 years')).'-02-02';
          $sYearRange = (date('Y') - 70).':'.(date('Y') - 12);

          $bEstimated = (bool)$oDbResult->getFieldValue('is_birth_estimation');
          if($bEstimated)
          {
            $nAge = date('Y') - date('Y', strtotime($sDate));
            $sLabel = ' <a href="javascript:;" onclick="toggleApproxAge(this);">birth</a> / <a href="javascript:;" onclick="toggleApproxAge(this, \'age\');">age</a>';
            $oForm->addField('input', 'birth_date', array('label' => $sLabel, 'type' => 'date', 'value' => $sDate, 'defaultDate' => $sDefaultDate, 'yearRange' => $sYearRange,
                'class' => 'hidden',
                'legend' => '<input type="text" name="age" value="'.$nAge.'" class="ageField" jsControl="jsFieldTypeIntegerPositive|jsFieldMaxValue@100" />'));

            $oForm->setFieldDisplayParams('birth_date', array('class' => 'age_field_container'));
          }
          else
          {
            $sLabel = '<a href="javascript:;" onclick="toggleApproxAge(this);">birth</a> / <a href="javascript:;" onclick="toggleApproxAge(this, \'age\');">age</a>';
            $oForm->addField('input', 'birth_date', array('label' => $sLabel, 'type' => 'date', 'value' => $sDate, 'defaultDate' => $sDefaultDate, 'yearRange' => $sYearRange));
          }


          $oForm->addField('select', 'language', array('label' => 'language'));
          $oForm->addOptionHtml('language', $this->getVars()->getLanguageOption($oDbResult->getFieldValue('languagefk')));

          $oForm->addField('select', 'nationality', array('label' => 'nationality'));
          $oForm->addOptionHtml('nationality', $this->getVars()->getNationalityOption($oDbResult->getFieldValue('nationalityfk')));

          $oForm->addField('select', 'location', array('label' => 'location'));
          $oForm->addOptionHtml('location', $this->getVars()->getLocationOption($oDbResult->getFieldValue('locationfk')));

       $oForm->closeSection();


       $asCurrency = $this->getVars()->getCurrencies();

       $oForm->addField('misc', 'title2', array('type' => 'text', 'text'=> 'Occupation'));
       $oForm->setFieldDisplayParams('title2', array('class' => 'formSectionTitle'));

       $oForm->addSection('', array('class' => 'candidate_inner_section'));

          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP, 0);
          $oForm->addField('selector', 'companypk', array('label' => 'company', 'url' => $sURL));
          $oForm->setFieldDisplayParams('companypk', array('class' => 'cpAutoComplete', 'style' => 'width: 468px; min-width: 468px;'));

          if($oDbResult->getFieldValue('companyfk'))
            $oForm->addOption('companypk', array('label' => '#'.$oDbResult->getFieldValue('companyfk').' - '.$oDbResult->getFieldValue('company_name'), 'value' => $oDbResult->getFieldValue('companyfk')));

          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_COMP, 0, array('update_field' => '#companypkId',));
          $oForm->addField('misc', 'addCp', array('type' => 'text', 'text'=> '<a href="javascript:;"
            onclick="
            var oConf = goPopup.getConfig(); oConf.height = 600; oConf.width = 900;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">+ add a new company</a>', 'style' => 'float: right; padding-right: 45px; '));
          $oForm->setFieldDisplayParams('addCp', array('style' => 'width: 190px; min-width: 190px;'));

          $oForm->addField('input', 'title', array('label' => 'title', 'value' => $oDbResult->getFieldValue('title')));

          $oForm->addField('paged_tree', 'occupationpk', array('text' => '-- Occupation --', 'label' => 'occupation', 'value' => $oDbResult->getFieldValue('occupationfk')));
          $oForm->addoption( 'occupationpk', $this->_getTreeData('occupation'));


          $oForm->addField('paged_tree', 'industrypk', array('text' => ' -- Industry --', 'label' => 'industry', 'value' => $oDbResult->getFieldValue('industryfk')));
          $oForm->addoption( 'industrypk', $this->_getTreeData('industry'));

          $oForm->addField('input', 'department', array('label' => 'department', 'value' => $oDbResult->getFieldValue('department')));

          $sAmount = formatNumber(round($oDbResult->getFieldValue('salary')), $this->casSettings['candi_salary_format']);
          $oForm->addField('currency', 'salary', array('label' => 'salary', 'value' => $sAmount, 'currency_list' => $asCurrency, 'with_unit' => true, 'with_currency' => true, 'default_unit' => $this->casSettings['candi_salary_format']));

          $sAmount = formatNumber(round($oDbResult->getFieldValue('bonus')), $this->casSettings['candi_salary_format']);
          $oForm->addField('currency', 'bonus', array('label' => 'bonus', 'value' => $sAmount,  'currency_list' => $asCurrency, 'with_unit' => true, 'with_currency' => true, 'default_unit' => $this->casSettings['candi_salary_format'], 'linked_to' => 'salary' ));
          //$oForm->addField('slider', 'target_salary', array('label' => 'target salary', 'range' => 1, 'values' => array('min' => 2, 'max' => 8), 'min' => 1, 'max' => 10));
          $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));

          $sAmount = formatNumber(round($oDbResult->getFieldValue('target_low')), $this->casSettings['candi_salary_format']);
          $oForm->addField('currency', 'target_low', array('label' => 'target sal. from', 'value' => $sAmount, 'currency_list' => $asCurrency, 'with_unit' => true, 'with_currency' => true, 'default_unit' => $this->casSettings['candi_salary_format'], 'linked_to' => 'salary'));

          $sAmount = formatNumber(round($oDbResult->getFieldValue('target_hig')), $this->casSettings['candi_salary_format']);
          $oForm->addField('currency', 'target_high', array('label' => 'to', 'value' => $sAmount,  'currency_list' => $asCurrency, 'with_unit' => true, 'with_currency' => true, 'default_unit' => $this->casSettings['candi_salary_format'], 'linked_to' => 'salary' ));

       $oForm->closeSection();


       $oForm->addField('misc', 'title3', array('type' => 'text', 'text'=> 'Profile'));
       $oForm->setFieldDisplayParams('title3', array('class' => 'formSectionTitle'));
       $oForm->addSection('', array('id' => 'candidate_skill_section', 'class' => 'candidate_inner_section'));


          $oForm->addField('select', 'grade', array('label' => 'grade'));
          $oForm->addOptionHtml('grade', $this->getVars()->getCandidateGradeOption($oDbResult->getFieldValue('grade')));

          $oForm->addField('select', 'status', array('label' => 'status', 'onchange' => 'manageFormStatus(this, '.$pnCandidatePk.'); '));
          //$oForm->addOptionHtml('status', $this->getVars()->getCandidateStatusOption($oDbResult->getFieldValue('statusfk')));


          $nStatus = 0;
          $bInPlay = false;
          $sDatePlayed = '';
          $asDateMeeting = array('meeting' => '', 'met' => '');

          if(!empty($pnCandidatePk))
          {
            $nStatus = (int)$oDbResult->getFieldValue('statusfk');

            $bInPlay = (bool)$oDbResult->getFieldValue('_in_play');

            if(!$bInPlay)
            {
              $sDatePlayed = (bool)$this->_getModel()->getLastPositionPlayed($pnCandidatePk);

              if(empty($sDatePlayed))
                $asDateMeeting = $this->_getModel()->getLastInterview($pnCandidatePk);
            }

            /*dump($nStatus);
            dump($bInPlay);
            dump($sDatePlayed);
            dump($asDateMeeting);*/
          }

          // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
          // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
          // manage status field

          if(CDependency::getCpLogin()->isAdmin())
          {
            $asStatus = '<option value="0"> - </option>
              <option value="1" '.(($nStatus === 1)? ' selected="selected" ':'').'> Name Collect </option>
              <option value="2" '.(($nStatus === 2)? ' selected="selected" ':'').'> Contacted </option>
              <option value="3" '.(($nStatus === 3)? ' selected="selected" ':'').' class="unavailable"> Interview set</option>
              <option value="5" '.(($nStatus === 5)? ' selected="selected" ':'').'> Phone assessed </option>
              <option value="6" '.(($nStatus === 6)? ' selected="selected" ':'').'> Assessed in person </option>';
          }
          elseif($bInPlay)
          {
            $asStatus = '
              <option value="2"> Contacted </option>
              <option value="3" class="unavailable"> Interview set</option>
              <option value="5"> Phone assessed </option>
              <option value="6" selected="selected"> Assessed - [ in play ] </option>';
          }
          elseif(!empty($sDatePlayed) || !empty($asDateMeeting['met']))
          {
            if(!empty($sDatePlayed))
              $sLegend = ' previously in play';
            else
              $sLegend = ' candidates met';

            $asStatus = '
              <option value="2"> Contacted </option>
              <option value="3" class="unavailable"> Interview set</option>
              <option value="5"> Phone assessed </option>
              <option value="6" selected="selected"> Assessed - [ '.$sLegend.' ] </option>';
          }
          else
          {
            if(!empty($asDateMeeting['meeting']) && $nStatus < 3)
            {
              $nStatus = 3;
              $sLegend = ' [ for '.$asDateMeeting['meeting'].' ]';
              $sClass = '';
            }
            else
            {
              $sLegend = '';
              $sClass = ' class="unavailable" ';
            }

            $asStatus = '
              <option value="1" '.(($nStatus === 1)? ' selected="selected" ':'').'> Name Collect </option>
              <option value="2" '.(($nStatus === 2)? ' selected="selected" ':'').'> Contacted </option>
              <option value="3" '.(($nStatus === 3)? ' selected="selected" ':'').' '.$sClass.'> Interview set '.$sLegend.'</option>
              <option value="5" '.(($nStatus === 5)? ' selected="selected" ':'').'> Phone assessed </option>
              <option value="6" '.(($nStatus === 6)? ' selected="selected" ':'').'> Assessed in person </option>';
          }
          $oForm->addOptionHtml('status', $asStatus);

          $oForm->addField('select', 'diploma', array('label' => 'MBA/CPA'));
          $oForm->addOption('diploma', array('value' => '', 'label' => 'none'));

          if($oDbResult->getFieldValue('cpa'))
            $oForm->addOption('diploma', array('value' => 'cpa', 'label' => 'CPA', 'selected' => 'selected'));
          else
            $oForm->addOption('diploma', array('value' => 'cpa', 'label' => 'CPA'));

          if($oDbResult->getFieldValue('mba'))
            $oForm->addOption('diploma', array('value' => 'mba', 'label' => 'MBA', 'selected' => 'selected'));
          else
            $oForm->addOption('diploma', array('value' => 'mba', 'label' => 'MBA'));

          if($oDbResult->getFieldValue('cpa') && $oDbResult->getFieldValue('mba'))
            $oForm->addOption('diploma', array('value' => 'both', 'label' => 'both', 'selected' => 'selected'));
          else
            $oForm->addOption('diploma', array('value' => 'both', 'label' => 'both'));


          $oForm->addField('input', 'keyword', array('label' => 'keyword', 'value' => $oDbResult->getFieldValue('keyword')));

          //candidate or company
          $nClient = (int)$oDbResult->getFieldValue('client') + (int)$oDbResult->getFieldValue('is_client');
          if($nClient > 0)
            $oForm->addField('checkbox', 'client', array('legend' => 'Is client', 'value' => 1, 'checked' => 'checked'));
          else
            $oForm->addField('checkbox', 'client', array('legend' => 'Is client', 'value' => 1));



          $oForm->addField('misc', '', array('type' => 'br'));

          $sOption = '';
          for($nCount = 1; $nCount < 10; $nCount++)
          {
            if($nCount == 5)
              $sOption.= '<option value="'.$nCount.'" selected="selected">'.$nCount.'</option>';
            else
              $sOption.= '<option value="'.$nCount.'">'.$nCount.'</option>';
          }

          $sHTML.= '<script>
              $(\'#candidate_skill_section .skill_field input\').spinner(
              {
                min:-1, max: 10,
                spin: function(event, ui)
                {
                  if(ui.value > 9) { $(this).spinner("value", 0); return false; }
                  else if (ui.value < 0) { $(this).spinner("value", 9); return false; }
                }
              });

            $(\'#candidate_skill_section .skill_field input\').focus(function()
            {
              if($(this).hasClass(\'emptySpinner\'))
              {
                $(this).val(5).removeClass(\'emptySpinner\').unbind(\'focus\');
              }
            });
            </script>';

          if((int)$oDbResult->getFieldValue('skill_ag') == 0)
          {
            $oDbResult->setFieldValue('skill_ag', '-');
            $oDbResult->setFieldValue('skill_ap', '-');
            $oDbResult->setFieldValue('skill_am', '-');
            $oDbResult->setFieldValue('skill_mp', '-');
            $oDbResult->setFieldValue('skill_in', '-');
            $oDbResult->setFieldValue('skill_ex', '-');
            $oDbResult->setFieldValue('skill_fx', '-');
            $oDbResult->setFieldValue('skill_ch', '-');
            $oDbResult->setFieldValue('skill_ed', '-');
            $oDbResult->setFieldValue('skill_pl', '-');
            $oDbResult->setFieldValue('skill_e', '-');
            $sClass = ' emptySpinner';
          }
          else
            $sClass = '';

          $oForm->addField('input', 'skill_ag', array('label' => 'AG', 'value' => $oDbResult->getFieldValue('skill_ag'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_ag', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_ap', array('label' => 'AP', 'value' => $oDbResult->getFieldValue('skill_ap'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_ap', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_am', array('label' => 'AM', 'value' => $oDbResult->getFieldValue('skill_am'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_am', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_mp', array('label' => 'MP', 'value' => $oDbResult->getFieldValue('skill_mp'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_mp', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_in', array('label' => 'IN', 'value' => $oDbResult->getFieldValue('skill_in'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_in', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_ex', array('label' => 'EX', 'value' => $oDbResult->getFieldValue('skill_ex'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_ex', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_fx', array('label' => 'FX', 'value' => $oDbResult->getFieldValue('skill_fx'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_fx', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_ch', array('label' => 'CH', 'value' => $oDbResult->getFieldValue('skill_ch'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_ch', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_ed', array('label' => 'ED', 'value' => $oDbResult->getFieldValue('skill_ed'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_ed', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_pl', array('label' => 'PL', 'value' => $oDbResult->getFieldValue('skill_pl'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_pl', array('class' => 'skill_field'));

          $oForm->addField('input', 'skill_e', array('label' => 'E', 'value' => $oDbResult->getFieldValue('skill_e'), 'class' => $sClass));
          $oForm->setFieldDisplayParams('skill_e', array('class' => 'skill_field'));

        $oForm->closeSection();





        //section for extra attribute
        $oForm->addField('misc', '', array('type' => 'text', 'text' => '<div style="margin-top: 5px;" class="bold italic">Additional data ?</div>', 'onclick' => '$(\'#candidate_more_section\').fadeToggle(function(){ $(this).closest(\'.ui-dialog-content\').scrollTop(5000); });', 'style' => 'cursor: pointer;'));
        $oForm->addSection('', array('id' => 'candidate_more_section', 'class' => 'candidate_inner_section hidden'));

        $oForm->addField('misc', 'more_notice', array('type' => 'text', 'text' => '
          &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Multiple industries ? Speak different languages? Fully and accuratly describing the candidates is a key for '.CONST_APP_NAME.'.
          <br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;It will improve the search functions and increase the candidate profile quality.
          &nbsp;Use this section to add alternative / secondary information about the candidate.<br /><br />', 'onclick' => '$(\'#candidate_more_section\').fadeToggle();'));
        $oForm->setFieldDisplayParams('more_notice', array('class' => 'full_width_msg'));


        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_OCCUPATION);
        $oForm->addField('selector', 'alt_occupationpk', array('label' => 'alt. occupation', 'url' => $sURL, 'nbresult' => 5));
        if(isset($asAttribute['candi_occu']))
        {
          foreach($asAttribute['candi_occu'] as $sValue => $sLabel)
          $oForm->addoption('alt_occupationpk', array('label' => $sLabel, 'value' => $sValue));
        }

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_INDUSTRY);
        $oForm->addField('selector', 'alt_industrypk', array('label' => 'alt. industry', 'url' => $sURL, 'nbresult' => 5));
        if(isset($asAttribute['candi_indus']))
        {
          foreach($asAttribute['candi_indus'] as $sValue => $sLabel)
          $oForm->addoption('alt_industrypk', array('label' => $sLabel, 'value' => $sValue));
        }

        //$oForm->addField('misc', '', array('type' => 'text', 'text' => '&nbsp;'));


        $oForm->addField('select', 'alt_language[]', array('label' => 'language', 'multiple' => 5));

        if(isset($asAttribute['candi_lang']))
          $oForm->addOptionHtml('alt_language[]', $this->getVars()->getLanguageOption($asAttribute['candi_lang']));
        else
          $oForm->addOptionHtml('alt_language[]', $this->getVars()->getLanguageOption());


        if((int)$oDbResult->getFieldValue('_sys_status') > 0 && CDependency::getCpLogin()->isAdmin())
        {
          $oForm->addField('misc', '', array('type' => 'title', 'title' => 'DBA'));
          $oForm->addField('misc', '', array('type' => 'br', 'text' => ''));

          $oForm->addField('select', '_sys_status', array('label' => 'Deleted ?'));
          $oForm->addoption('_sys_status', array('label' => 'Keep deleted', 'value' => $oDbResult->getFieldValue('_sys_status')));
          $oForm->addoption('_sys_status', array('label' => 'Restore candidate', 'value' => 0));

          $oForm->addField('input', '_sys_redirect', array('label' => 'Merged with', 'value' => (int)$oDbResult->getFieldValue('_sys_redirect')));

          $oForm->addField('misc', '', array('type' => 'text', 'text' => '<br /><br />'));
        }



        $oForm->closeSection();


      $oForm->closeSection();


      if($bDisplayAllTabs)
      {
        $oForm->addSection('candi_contact', array('class' => 'hidden', 'id' => 'candi_contact'));
          $oForm->addSection('', array('class' => 'candidate_inner_section'));

          //reuse what ha sbeen done for the standalone form
          $asTypes = getContactTypes();
          for($nCount = 0; $nCount < 4; $nCount++)
          {
            $this->_getContactFormRow($oForm, $nCount, $asTypes, array());
          }

          $oForm->closeSection();
        $oForm->closeSection();
      }



      // =======================================================================
      //note section
      if($bDisplayAllTabs)
      {
        $oForm->addSection('candi_note', array('class' => 'hidden', 'id' => 'candi_note'));
          $oForm->addSection('', array('class' => 'candidate_inner_section'));

          $oForm->addField('misc', '', array('type' => 'br'));
          $oForm->addField('misc', 'note_legend', array('type' => 'text', 'text' => '<span style="font-size: 10px; color: blue;">* If the candidate has been "assessed", the character note is required.<br/>
  * In the other case, one of those fields is required.</span>', 'style' => 'text-align: right;'));
          $oForm->setFieldDisplayparams('note_legend', array('style' => 'width: 100%;'));

          $oForm->addField('misc', '', array('type' => 'br'));

          $oForm->addField('textarea', 'character_note', array('label' => 'character note', 'style' => 'width: 550px;'));
          $oForm->setFieldDisplayparams('character_note', array('style' => 'width: 800px;', 'class' => 'note_field'));

          $oForm->addField('textarea', 'note', array('label' => 'note', 'style' => 'width: 550px;'));
          $oForm->setFieldDisplayparams('note', array('style' => 'width: 800px;', 'class' => 'note_field'));

          $oForm->addField('misc', '', array('type' => 'text', 'text' => '
            <a href="javascript:;"
            onclick="$(this).closest(\'.candidate_inner_section\').find(\'textarea\').each(function()
            {
              initMce($(this).attr(\'name\'));
            });

            $(this).closest(\'.candidate_inner_section\').find(\'#character_html\').val(1);

          ">Advanced editor</a>'));

          $oForm->addField('input', 'character_html', array('type' => 'hidden', 'value' => 0, 'id' => 'character_html'));
          $oForm->closeSection();

        $oForm->closeSection();
      }



      // =======================================================================
      //resume section
      if($bDisplayAllTabs)
      {
        $oForm->addSection('candi_resume', array('class' => 'hidden', 'id' => 'candi_resume'));
          $oForm->addSection('', array('class' => 'candidate_inner_section'));

          $oForm->addField('misc', '', array('type' => 'br'));
          $oForm->addField('misc', '', array('type' => 'br'));

          $oForm->addField('input', 'doc_title', array('label' => 'doc title', 'value' => '', 'style' => 'width: 385px;'));
          $oForm->setFieldDisplayparams('doc_title', array('style' => 'width: 575px;'));

          $oForm->addField('misc', '', array('type' => 'br'));



          $oForm->addField('textarea', 'doc_description', array('label' => 'description', 'style' => 'width: 385px; height: 120px;'));
          $oForm->setFieldDisplayparams('doc_description', array('style' => 'width: 575px;'));

          $oForm->addField('input', 'document', array('type' => 'file', 'style' => 'height: 50px; width: 400px;'));
          $oForm->setFieldDisplayparams('document', array('style' => 'width: 425px;'));

          $oForm->closeSection();
        $oForm->closeSection();
      }



      // =======================================================================
      //duplicate section
      $oForm->addSection('candi_duplicate', array('class' => 'hidden', 'id' => 'candi_duplicate'));

      $oForm->closeSection();

      return $sHTML. $oForm->getDisplay();
    }



    private function _getCompanyForm($pnPk = 0)
    {
      if(!assert('is_integer($pnPk)'))
        return '';

      $asCompanyData = array();

      if(empty($pnPk))
      {
        $asCompanyData['level'] = 1;
        $asCompanyData['is_client'] = 0;
        $asCompanyData['name'] = '';
        $asCompanyData['corporate_name'] = '';
        $asCompanyData['industrypk'] = 0;
        $asCompanyData['description'] = '';

        $asCompanyData['revenue'] = '';
        $asCompanyData['hq'] = '';
        $asCompanyData['hq_japan'] = '';
        $asCompanyData['num_employee_world'] = '';
        $asCompanyData['num_employee_japan'] = '';
        $asCompanyData['num_branch_japan'] = '';
        $asCompanyData['num_branch_world'] = '';

        $asCompanyData['phone'] = '';
        $asCompanyData['fax'] = '';
        $asCompanyData['email'] = '';
        $asCompanyData['website'] = '';
      }
      else
      {
        $asCompanyData = $this->_getModel()->getCompanyData($pnPk, true);
        if(empty($asCompanyData))
          return 'Could not find the company.';
      }

      $sUpdateField = getValue('update_field', '');

      $oForm = $this->_oDisplay->initForm('companyAddForm');
      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_CANDIDATE_TYPE_COMP, $pnPk);

      $oForm->setFormParams('addcompany', true, array('action' => $sURL, 'class' => 'companyAddForm', 'submitLabel'=>'Save company'));
      $oForm->setFormDisplayParams(array('noCancelButton' => true, /*'noSubmitButton' => 1,*/ 'columns' => 1));


      $oForm->addField('input', 'loginfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));
      $oForm->addField('input', 'update_field', array('type' => 'hidden', 'value' => $sUpdateField));

      if(empty($sUpdateField))
        $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add/edit company details'));
      else
        $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add a company - you will be back to the candidate form afterward.'));


       $oForm->addField('select', 'level', array('label'=> 'Level'));
       $oForm->addoption('level', array('label' => 'A', 'value' => '1'));
       $oForm->addoption('level', array('label' => 'B', 'value' => '2', 'selected' => (($asCompanyData['level'] == 2)? 'selected':'')));
       $oForm->addoption('level', array('label' => 'C', 'value' => '3', 'selected' => (($asCompanyData['level'] == 3)? 'selected':'')));

       $oForm->addField('select', 'is_client', array('label'=> 'Client '));
       $oForm->addoption('is_client', array('label' => 'No', 'value' => '0'));
       $oForm->addoption('is_client', array('label' => 'Yes', 'value' => '1', 'selected' => (($asCompanyData['is_client'] == 1)? 'selected':'')));

       $oForm->addField('input', 'company_name', array('label'=> 'Company name', 'value' => $asCompanyData['name']));
       $oForm->setFieldControl('company_name', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => '2'));

       $oForm->addField('input', 'corporate_name', array('label'=> 'Brand / public name', 'value' => $asCompanyData['corporate_name']));

       //$oForm->addField('paged_tree', 'industrypk', array('text' => ' -- Industry --', 'label' => 'industry', 'value' => $oDbResult->getFieldValue('industryfk')));
       //$oForm->addoption('industrypk', $this->_getTreeData('industry'));

       $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_INDUSTRY);
       $oForm->addField('selector', 'industrypk', array('label' => 'Industries', 'url' => $sURL, 'nbresult' => 10));
       if(!empty($asCompanyData['industry']))
       {
         foreach($asCompanyData['industry'] as $nKey => $sIndustry)
           $oForm->addoption('industrypk', array('label' => $sIndustry, 'value' => $asCompanyData['industry_id'][$nKey]));
       }


      $oForm->addField('textarea', 'description', array('label'=> 'Description', 'value' => $asCompanyData['description']));


      $oForm->addField('misc', '', array('type'=> 'title', 'title' => 'Structure & employees'));

      $oForm->addField('input', 'revenue', array('label'=> 'Annual revenue', 'value' => $asCompanyData['revenue']));
      $oForm->setFieldControl('revenue', array('jsFieldMinSize' => '2'));

      $oForm->addField('input', 'hq', array('label'=> 'HQ', 'value' => $asCompanyData['hq']));
      $oForm->addField('input', 'hq_japan', array('label'=> 'HQ in japan', 'value' => $asCompanyData['hq_japan']));

      $oForm->addField('misc', '', array('type'=> 'br'));

      $oForm->addField('input', 'num_employee', array('label'=> '# employees ', 'value' => $asCompanyData['num_employee_world']));
      $oForm->setFieldControl('num_employee', array('jsFieldTypeIntegerPositive' => '1'));

      $oForm->addField('input', 'num_branch_world', array('label'=> '# branch(es)', 'value' => $asCompanyData['num_branch_world']));
      $oForm->setFieldControl('num_branch_world', array('jsFieldTypeIntegerPositive' => '1'));

      $oForm->addField('input', 'num_employee_japan', array('label'=> '# employees in japan', 'value' => $asCompanyData['num_employee_japan']));
      $oForm->setFieldControl('num_employee_japan', array('jsFieldTypeIntegerPositive' => '1'));

      $oForm->addField('input', 'num_branch_japan', array('label'=> '# branch(es) in japan', 'value' => $asCompanyData['num_branch_japan']));
      $oForm->setFieldControl('num_branch_japan', array('jsFieldTypeIntegerPositive' => '1'));



       $oForm->addSection('', array('folded' => 1), 'Contact details');

       $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => $asCompanyData['phone']));
       $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => $asCompanyData['fax']));
       $oForm->addField('input', 'email', array('label'=> 'Email', 'value' => $asCompanyData['email']));
       $oForm->addField('input', 'website', array('label'=> 'website', 'value' => $asCompanyData['website']));
       $oForm->closeSection();



      return $oForm->getDisplay();
    }

    private function _saveCompany($pnPk)
    {
      if(!assert('is_integer($pnPk)'))
        return array('error' => 'bad parameters.');

      $asData = array();
      $asData['name'] = getValue('company_name');
      $asData['corporate_name'] = getValue('corporate_name');
      $asData['description'] = getValue('description');
      $asData['level'] = (int)getValue('level');
      $asData['is_client'] = (int)getValue('is_client');

      $asData['phone'] = getValue('phone', null);
      $asData['fax'] = getValue('fax', null);
      $asData['email'] = getValue('email', null);
      $asData['website'] = getValue('website', null);

      $asData['revenue'] = getValue('revenue');
      $asData['hq'] = getValue('hq', null);
      $asData['hq_japan'] = getValue('hq_japan', null);

      $asData['num_employee_world'] = (int)getValue('num_employee', 0);
      $asData['num_branch_world'] = (int)getValue('num_branch_world', 0);

      $asData['num_employee_japan'] = (int)getValue('num_employee_japan', 0);
      $asData['num_branch_japan'] = (int)getValue('num_branch_japan', 0);

      $nLoginFk = (int)getValue('loginfk');

      if(empty($pnPk))
      {
        $bUpdate = false;

        $asData['date_created'] = date('Y-m-d H:i:s');
        $asData['created_by'] = $nLoginFk;
        $pnPk = $this->_getModel()->add($asData, 'sl_company');
        if(empty($pnPk))
          return array('error' => 'Could not save the company.');
      }
      else
      {
        $bUpdate = true;

        $asData['date_updated'] = date('Y-m-d H:i:s');
        $asData['updated_by'] = $nLoginFk;
        $bUpdated = $this->_getModel()->update($asData, 'sl_company', 'sl_companypk = '.$pnPk);
        if(!$bUpdated)
          return array('error' => 'Could not update the company.');
      }


      $asIndustry = explode(',', getValue('industrypk'));
      $asInsertIndus = array();
      $sNow = date('Y-m-d H:i:s');
      foreach($asIndustry as $nKey => $sIndustryKey)
      {
        $sIndustryKey = (int)$sIndustryKey;
        if(!empty($sIndustryKey))
        {
          $asInsertIndus['itemfk'][$nKey] = $pnPk;
          $asInsertIndus['attributefk'][$nKey] = (int)$sIndustryKey;
          $asInsertIndus['type'][$nKey] ='cp_indus';
          $asInsertIndus['loginfk'][$nKey] = $nLoginFk;
          $asInsertIndus['date_created'][$nKey] = $sNow;
        }
      }

      //if the array ios not empty, we need to save the industry
      if(!empty($asInsertIndus))
      {
        if($bUpdate)
          $this->_getModel()->deleteByWhere('sl_attribute', '`type` = \'cp_indus\' AND itemfk='.$pnPk);

        $nInserted = $this->_getModel()->add($asInsertIndus, 'sl_attribute');
        if(empty($nInserted))
          return array('error' => 'Could not save the company industry.');
      }

      //form opened from candidate form,
      //need to update the company field in the form when when the company is saved
      $sUpdateField = getValue('update_field', '');
      if($sUpdateField)
      {
        if(isset($asInsertIndus['attributefk']))
        {
          $anPK = array_values($asInsertIndus['attributefk']);
          $sPreSelectJs = '
          if($(\'.fieldNameindustrypk input[name=industrypk]\').val()<= 0)
          {
            $(\'.fieldNameindustrypk li[sl_industrypk='.$anPK[0].']\').click();
          }';
        }
        else
          $sPreSelectJs = '';

        return array('data' => 'ok',
          'action' => '$(\''.$sUpdateField.'\').val('.$pnPk.');
          $(\''.$sUpdateField.'\').tokenInput(\'clear\').tokenInput(\'add\', {id: \''.$pnPk.'\', name: \''.addslashes($asData['name']).'\'}); '.$sPreSelectJs.' goPopup.removeLastByType(\'layer\'); ');
      }

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, $pnPk);
      return array('notice' => 'Company saved.', 'action' => 'view_comp(\''.$sURL.'\'); goPopup.removeLastByType(\'layer\'); ');
    }


    private function _getCompanyList($poQB = null)
    {
      //$poQB comes when doing a complex search
      if(empty($poQB))
      {
        $oQb = $this->_getModel()->getQueryBuilder();
        $oQb->setDataType(CONST_CANDIDATE_TYPE_COMP);

        require_once('component/sl_candidate/resources/search/quick_search.class.php5');
        $oQS = new CQuickSearch($oQb);
        $oQS->buildQuickSearch();
      }
      else
        $oQb = $poQB;

      $oQb->addSelect('*, sind.label as industry_list');
      $oQb->setTable('sl_company', 'scom');
      $oQb->addJoin('left', 'sl_attribute', 'satt', 'satt.`type` = \'cp_indus\' AND satt.itemfk = scom.sl_companypk');
      $oQb->addJoin('left', 'sl_industry', 'sind', 'sind.sl_industrypk = satt.attributefk');
      $oQb->addGroup('scom.sl_companypk');
      $oQb->addOrder('scom.sl_companypk DESC');


      // =============================================================

       $asListMsg = array();

      // ============================================
      // search management
      if(empty($this->csSearchId))
      {
        $this->csSearchId = manageSearchHistory($this->csUid, CONST_CANDIDATE_TYPE_COMP);
        $oQb->addLimit('0, 50');
      }
      else
      {
        $nPagerNbResult = getValue('nbresult', 50);
        if($nPagerNbResult < 10)
          $nPagerNbResult = 50;

        $nPagerOffset = getValue('pageoffset', 0);
        if($nPagerOffset < 1)
          $nPagerOffset = 1;

        $oQb->addLimit((($nPagerOffset-1)*$nPagerNbResult).' ,'. $nPagerNbResult);
      }


      // multi industries --> we need to group by companypk --> number result = numrows
      $oDbResult = $this->_getModel()->executeQuery($oQb->getCountSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        return array('data' => $this->_oDisplay->getBlocMessage('no company found.'), 'sql' => $oQb->getSql(), 'action' => 'goPopup.removeLastByType(\'layer\');  ');
      }

      //$nResult = (int)$oDbResult->getFieldValue('nCount');
      $nResult = $oDbResult->numRows();
      if(empty($nResult))
        return array('data' => $this->_oDisplay->getBlocMessage('no company found for '.$oQb->getTitle()), 'nb_result' => $nResult, 'action' => 'goPopup.removeLastByType(\'layer\'); ');


      $oDbResult = $this->_getModel()->executeQuery($oQb->getSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        assert('false; // no company found in select, but count = '.$nResult.' ['.$oQb->getSql().']');
        return array('data' => 'no company found.', 'sql' => $oQb->getSql());
      }

      $oLogin = CDependency::getCpLogin();
      $asRow = array();

      while($bRead)
      {
        $nPk =  $oDbResult->getFieldValue('sl_companypk');
        $asRow[$nPk] = $oDbResult->getData();

        //$asRow[$nPk]['contact'] = $asRow[$nPk]['phone'].' - '.$asRow[$nPk]['fax'].' - '.$asRow[$nPk]['website'].' - '.$asRow[$nPk]['email'];
        $asRow[$nPk]['created_by'] = $oLogin->getUserLink((int)$asRow[$nPk]['created_by']);
        $bRead = $oDbResult->readNext();
      }


      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CComp_row', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/comp_row.tpl.class.php5')))));
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $sActionContainerId = uniqid();
      $sPic = $this->_oDisplay->getPicture(self::getResourcePath().'/pictures/list_action.png');
      $sJavascript = "var oCurrentLi = $(this).closest('li');

        if($('> div.list_action_container', oCurrentLi).length)
        {
          $('> div.list_action_container', oCurrentLi).fadeToggle();
        }
        else
        {
          var oAction = $('#".$sActionContainerId."').clone().show(0);

          $(oCurrentLi).append('<div class=\'list_action_container hidden\'></div><div class=\'floatHack\' />');
          $('div.list_action_container', oCurrentLi).append(oAction).fadeIn();
        }";

      $sActionLink = $this->_oDisplay->getLink($sPic, 'javascript:;', array('onclick' => $sJavascript));
      $oConf->addColumn($sActionLink, 'a', array('id' => 'aaaaaa', 'width' => '20'));
      $oConf->addColumn('ID', 'sl_companypk', array('width' => '43', 'sortable'=> array('javascript' => 'text'), 'style' => 'margin: 0;'));


      $oConf->addColumn('C', 'is_client', array('id' => '', 'width' => '20', 'sortable'=> array('javascript' => 'value_integer')));
      $oConf->addColumn('NC', 'is_nc_ok', array('id' => '', 'width' => '20', 'sortable'=> array('javascript' => 'value_integer')));
      $oConf->addColumn('L', 'level', array('id' => '', 'width' => '20', 'sortable'=> array('javascript' => 'value_integer')));
      $oConf->addColumn('Company name', 'name', array('id' => '', 'width' => '31%', 'sortable'=> array('javascript' => 'value_integer')));
      $oConf->addColumn('Industry', 'industry_list', array('id' => '', 'width' => '18%', 'sortable'=> array('javascript' => 'text')));
      $oConf->addColumn('Description', 'description', array('id' => '', 'width' => '22%', 'sortable'=> array('javascript' => 'text')));
      //$oConf->addColumn('Contact', 'contact', array('id' => '', 'width' => '15%', 'sortable'=> array('javascript' => 'text')));
      $oConf->addColumn('Created by', 'created_by', array('id' => '', 'width' => '10%', 'sortable'=> array('javascript' => 'text')));

      $sTitle = $oQb->getTitle();
      if(!empty($sTitle))
        $asListMsg[] = $sTitle;

      $oConf->addBlocMessage('<span class="search_result_title_nb">'.$nResult.' result(s)</span> '.implode(', ', $asListMsg), array('style' => 'cursor: crossair'), 'title');


      $sURL = $this->_oPage->getAjaxUrl('sl_candidate', $this->csAction, CONST_CANDIDATE_TYPE_COMP, 0, array('searchId' => $this->csSearchId, '__filtered' => 1));
      $oConf->setPagerTop(true, 'right', $nResult, $sURL.'&list=1', array('ajaxTarget' => '#'.$this->csSearchId));
      $oConf->setPagerBottom(true, 'right', $nResult, $sURL.'&list=1', array('ajaxTarget' => '#'.$this->csSearchId));


      $sHTML = $this->_oDisplay->getBlocStart($this->csSearchId, array('class' => 'scrollingContainer'));
      $sHTML.= $oTemplate->getDisplay($asRow);
      $sHTML.= $this->_oDisplay->getBlocEnd();

      return array('data' => $sHTML, 'action' => ' initHeaderManager(); goPopup.removeLastByType(\'layer\'); ');
    }






    /* *********************************************************** */
    /* *********************************************************** */
    //save candidate form

    private function _saveCandidate($pnCandidatePk = 0)
    {
      //buffer to store all the data once checked, to be re-used for saving
      $this->casCandidateData = array();

      if(!empty($pnCandidatePk))
      {
        $asData = $this->_getModel()->getCandidateData($pnCandidatePk, true);
        if(empty($asData))
          return array('popupError' => 'Could not find the candidate you\'re trying to update. It may have been deleted.');


        //Date created is use and overwritten everywhere... so we're using an alias
        $asData['date_created'] = $asData['date_added'];
        $asData['sl_candidatepk'] = (int)$asData['sl_candidatepk'];
        $asData['sl_candidate_profilepk'] = (int)$asData['sl_candidate_profilepk'];
        $asData['created_by'] = (int)$asData['created_by'];
        $asData['is_birth_estimation'] = (int)$asData['is_birth_estimation'];

        $nProfilePk = $asData['sl_candidate_profilepk'];

        //security check
        if(!empty($pnCandidatePk) && empty($nProfilePk))
          assert('false; // we\'ve got a candidate without profile here ['.$pnCandidatePk.'].');

        if(empty($pnCandidatePk) || empty($nProfilePk))
          return array('popupError' => 'Could not find the candidate you\'re trying to update. It may have been deleted.');

        //for candi_profile table update
        $asData['candidatefk'] = $pnCandidatePk;
      }
      else
      {
        $asData = array();
      }


      //check ll the form fields (test mode only
      //dump('1st - saveCandiData ');
      $asError = $this->_saveCandidateData($pnCandidatePk, true, false, $asData);

      if(empty($pnCandidatePk))
      {
        //we re-use a function here, so the way it works and the returned value are a bit different
        //pass a dummy candipk here, will pass the real one when called to save
        $asResult = $this->_getCandidateContactSave(false, 999);
        if(isset($asResult['error']))
          $asError = array_merge($asError, (array)$asResult['error']);

        $asError = array_merge($asError, $this->_saveNotes(true, false, $asData));
        $asError = array_merge($asError, $this->_saveResume(true, false, $asData));
      }

      // - - - - - - - - - - - - - - - - - - - - - - - -
      //All form sections have been checked.
      if(!empty($asError))
      {
        if(isset($this->casCandidateData['dup_tab']))
          return array('popupError' => implode("\n", $asError),  'data' => $this->casCandidateData['dup_tab'], 'action' => ' $(\'li.tab_duplicate\').show(0).click(); ');

        return array('popupError' => implode("\n", $asError));
      }





      //Now the form has been checked, we save... step by step again
      //dump('2nd - saveCandiData ');
      $asError = $this->_saveCandidateData($pnCandidatePk, false, true, $asData);
      if(!empty($asError))
        return array('popupError' => implode("    \n <br/>", $asError));

      if(!is_key($this->casCandidateData['profile']['candidatefk']))
        return array('popupError' => 'An error occured. Data may not have been saved.');


      if(empty($pnCandidatePk))
      {
        $asResult = $this->_getCandidateContactSave(true, $this->casCandidateData['profile']['candidatefk']);
        if(isset($asResult['error']))
          return array('popupError' => $asResult['error']);

        $asError = $this->_saveNotes(false, true, $this->casCandidateData['profile']);
        if(!empty($asError))
          return array('popupError' => implode("\n", $asError));

        $asError =  $this->_saveResume(false, true, $this->casCandidateData['profile']);
        if(!empty($asError))
          return array('popupError' => implode("\n", $asError));
      }


      //calculate quality ration and update profile table (update _in_play and _has_doc on the way)
      $this->updateCandidateProfile($this->casCandidateData['profile']['candidatefk']);

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_CANDI, $this->casCandidateData['profile']['candidatefk']);
      $this->casCandidateData = array();

      if(empty($pnCandidatePk))
        return array('notice' => 'Candidate saved.', 'action' => '
          goPopup.removeLastByType(\'layer\');
          //view_candi(\''.$sURL.'\');
          var asContainer = goTabs.create(\'candi\', \'\',  \'\', \'Candidate list\');
          AjaxRequest(\''.$sURL.'\', \'body\', \'\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); \');
          goTabs.select(asContainer[\'number\']);
          ');

      return array('notice' => 'Candidate saved.', 'action' => '
        goPopup.removeLastByType(\'layer\');
        view_candi(\''.$sURL.'\'); ');
    }


    private function _saveCandidateData($pnCandidatePk = 0, $pbTest = true, $pbSave = false, $pasCandidate = array())
    {
      if(!assert('is_integer($pnCandidatePk)'))
        return array('error' => 'Bad parameters.');

      if(!assert('is_bool($pbTest) && is_bool($pbSave) && is_array($pasCandidate)'))
        return array('error' => 'Missing parameters.');

      $asError = array();

      if(empty($pnCandidatePk))
      {
        $nCandidatePk = $nProfilePk = 0;

        $asData = array();
        $asData['date_created'] = date('Y-m-d H:i:s');
        $asData['created_by'] = (int)$this->casUserData['loginpk'];
      }
      else
      {
        $nCandidatePk = $pasCandidate['sl_candidatepk'];
        $nProfilePk = $pasCandidate['sl_candidate_profilepk'];

        $asData = $pasCandidate;
        $asData['date_updated'] = date('Y-m-d H:i:s');
        $asData['updated_by'] = $this->casUserData['pk'];
      }

      if($pbTest)
      {
        //First form section
        $asData['sex'] = (int)getValue('sex');
        $asData['firstname'] = getValue('firstname');
        $asData['lastname'] = getValue('lastname');

        $asData['date_birth'] = trim(getValue('birth_date'));
        $nAge = (int)getValue('age', 0);
        if(!empty($nAge))
        {
          $asData['date_birth'] = date('Y', strtotime('-'.$nAge.' years')).'-02-02';
          $asData['is_birth_estimation'] = 1;
        }
        else
        {
          $asData['is_birth_estimation'] = 0;
        }


        $asData['languagefk'] = (int)getValue('language');
        $asData['nationalityfk'] = (int)getValue('nationality');
        $asData['locationfk'] = (int)getValue('location');


        if(empty($asData['firstname']) || strlen($asData['firstname']) < 2)
          $asError[] = 'Firstname empty or too short.';

        if(empty($asData['lastname']) || strlen($asData['lastname']) < 2)
          $asError[] = 'Lastname empty or too short.';


        if(empty($asData['date_birth']) || $asData['date_birth'] == '0000-00-00')
        {
          $asData['date_birth'] = 'NULL';
        }
        else
        {
          if(!is_date($asData['date_birth']) || $asData['date_birth'] < '1900-00-00')
            $asError[] = 'Birth date invalid.';
        }

        //Stops right here if firstname lastname are incorect
        if(!empty($asError))
          return $asError;


        $nNewCompanyFk = (int)getValue('companypk');
        if(!empty($pnCandidatePk) && $asData['companyfk'] != $nNewCompanyFk)
        {
          $asData['previous_company'] = (int)$asData['companyfk'];
          $asData['current_company'] = $nNewCompanyFk;
        }

        $asData['companyfk'] = $nNewCompanyFk;
        $asData['title'] = getValue('title');
        $asData['occupationfk'] = (int)getValue('occupationpk');
        $asData['industryfk'] = (int)getValue('industrypk');
        $asData['department'] = getValue('department');

        if(isset($_POST['client']))
          $asData['is_client'] = 1;
        else
          $asData['is_client'] = 0;

        if(empty($pnCandidatePk))
        {
          $asData['_sys_status'] = 0;
          $asData['_sys_redirect'] = null;
        }
        else
        {
          $asData['_sys_status'] = (int)getValue('_sys_status', 0);
          $asData['_sys_redirect'] = (int)getValue('_sys_redirect', 0);
          if(empty($asData['_sys_redirect']))
            $asData['_sys_redirect'] = NULL;
        }


        if(empty($asData['industryfk']))
          $asError[] = 'Industry field is required.';

        if(empty($asData['companyfk']) || !is_key($asData['companyfk']))
          $asError[] = 'Company field is required.';

        if(!empty($asData['title']) && strlen($asData['title']) < 3)
          $asError[] = 'Title must contains at least 3 characters';

        if(!empty($asData['department']) && strlen($asData['department']) < 2)
          $asError[] = 'Department must contains at least 2 characters';


        //---------------------------------------------------------------------------------
        //Salary section
        //Check the field content to look for currency and Multiplier
        $oForm = CDependency::getComponentByName('form');
        $oCurrency = $oForm->getStandaloneField('currency');

        $asSalary = $oCurrency->getCurrencyFromPost('salary');
        $this->_getSalaryInYen($asSalary);

        $asBonus = $oCurrency->getCurrencyFromPost('bonus');
        $this->_getSalaryInYen($asBonus);

        if(!empty($asSalary['value']) && ($asSalary['yen'] > 100000000 || $asSalary['yen'] < 10000))
          $asError[] = 'Salary value is not a valid number. ['.$asSalary['yen'].' '.$asSalary['currency'].']';

        if(!empty($asBonus['value']) && ($asBonus['yen'] > 100000000 || $asBonus['yen'] < 10000))
         $asError[] = 'Bonus value is not a valid number. ['.$asBonus['yen'].' '.$asBonus['currency'].']';

        $asData['salary'] = $asSalary['yen'];
        $asData['currency'] = $asSalary['currency'];
        $asData['currency_rate'] = $asSalary['rate'];
        $asData['bonus'] = $asBonus['value'];
        $asData['salary_search'] = (int)($asSalary['yen'] + $asBonus['yen']);

        $asTargetLow = $oCurrency->getCurrencyFromPost('target_low');
        $this->_getSalaryInYen($asTargetLow);

        $asTargetHigh = $oCurrency->getCurrencyFromPost('target_high');
        $this->_getSalaryInYen($asTargetHigh);

        if(!empty($asTargetLow['value']) && ($asTargetLow['yen'] > 100000000 || $asTargetLow['yen'] < 10000))
          $asError[] = 'Salary value is not a valid number. ['.$asTargetLow['yen'].' '.$asTargetLow['currency'].']';

        if(!empty($asTargetHigh['value']) && ($asTargetHigh['yen'] > 100000000 || $asTargetHigh['yen'] < 10000))
          $asError[] = 'Bonus value is not a valid number. ['.$asTargetHigh['yen'].' '.$asTargetHigh['currency'].']';

        $asData['target_low'] = $asTargetLow['value'];
        $asData['target_high'] = $asTargetHigh['value'];
        //---------------------------------------------------------------------------------


         //third form section
        $asData['grade'] = (int)getValue('grade');
        $asData['statusfk'] = (int)getValue('status');
        //extra test & actions here

        if($asData['statusfk'] >= 4)
        {
          //Assessed candidate needs a character note
          if(empty($pnCandidatePk) && !getValue('character_note'))
          {
            $asError[] = 'Character note is required for any assessed candidate.';
          }
          elseif(!empty($pnCandidatePk))
          {
            $oNote = CDependency::getComponentByName('sl_event');
            $asNote = $oNote->getNotes($pnCandidatePk, CONST_CANDIDATE_TYPE_CANDI, 'character');
            if(empty($asNote))
            {
              //index.php5?uid=555-004&ppa=ppaa&ppt=event&ppk=0&cp_uid=555-001&cp_action=ppav&cp_type=candi&cp_pk=400006&default_type=note&pg=ajx
              $asItem = array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $pnCandidatePk, 'default_type' =>'character', 'no_candi_refresh' => 1);
              $sURL = $this->_oPage->getAjaxUrl('555-004', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asItem);
              $asError[] = 'Character note is required for any assessed candidate.<br />
                Add a <a href="javascript:;" style="color: red;" onclick="goPopup.removeActive(\'message\'); var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550; goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');" >character note now</a> or change back the candidate status. ';
            }
          }
        }

        $sDiploma = getValue('diploma');
        $asData['cpa'] = $asData['mba'] = 0;
        if($sDiploma == 'cpa' || $sDiploma == 'both')
          $asData['cpa'] = 1;

        if($sDiploma == 'mba' || $sDiploma == 'both')
          $asData['mba'] = 1;

        $asData['keyword'] = getValue('keyword');
        $asData['play_for'] = (int)getValue('play_for');
        $asData['play_date'] = null;


        $asData['is_client'] = getValue('client');
        if(empty($asData['is_client']))
          $asData['is_client'] = 0;
        else
          $asData['is_client'] = 1;

        $asData['skill_ag'] = (int)getValue('skill_ag', 0);
        $asData['skill_ap'] = (int)getValue('skill_ap', 0);
        $asData['skill_am'] = (int)getValue('skill_am', 0);
        $asData['skill_mp'] = (int)getValue('skill_mp', 0);
        $asData['skill_in'] = (int)getValue('skill_in', 0);
        $asData['skill_ex'] = (int)getValue('skill_ex', 0);
        $asData['skill_fx'] = (int)getValue('skill_fx', 0);
        $asData['skill_ch'] = (int)getValue('skill_ch', 0);
        $asData['skill_ed'] = (int)getValue('skill_ed', 0);
        $asData['skill_pl'] = (int)getValue('skill_pl', 0);
        $asData['skill_e'] = (int)getValue('skill_e', 0);

        //convert 0 to null
        $asData['skill_ag'] = ((empty($asData['skill_ag']))? 'null': $asData['skill_ag']);
        $asData['skill_ap'] = ((empty($asData['skill_ap']))? 'null': $asData['skill_ap']);
        $asData['skill_am'] = ((empty($asData['skill_am']))? 'null': $asData['skill_am']);
        $asData['skill_mp'] = ((empty($asData['skill_mp']))? 'null': $asData['skill_mp']);
        $asData['skill_in'] = ((empty($asData['skill_in']))? 'null': $asData['skill_in']);
        $asData['skill_ex'] = ((empty($asData['skill_ex']))? 'null': $asData['skill_ex']);
        $asData['skill_fx'] = ((empty($asData['skill_fx']))? 'null': $asData['skill_fx']);
        $asData['skill_ch'] = ((empty($asData['skill_ch']))? 'null': $asData['skill_ch']);
        $asData['skill_ed'] = ((empty($asData['skill_ed']))? 'null': $asData['skill_ed']);
        $asData['skill_pl'] = ((empty($asData['skill_pl']))? 'null': $asData['skill_pl']);
        $asData['skill_e'] =  ((empty($asData['skill_e']))? 'null': $asData['skill_e']);


        //save all the profile data
        $this->casCandidateData['profile'] = $asData;

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // check duplicates when creating new candidate
        if(empty($pasCandidate))
        {
          $sDuplicate = getValue('check_duplicate');
          if(empty($sDuplicate) || $sDuplicate != $asData['lastname'].'_'.$asData['firstname'])
          {
            $sDuplicate = $this->_checkDuplicate($asData);
            if(!empty($sDuplicate))
            {
              $asError[] = 'There may be duplicates. Please check the duplicate tab.';
              $this->casCandidateData['dup_tab'] = $sDuplicate;
            }
          }
        }
      }

      if(!empty($asError))
      {
        $this->casCandidateData['profile'] = array(); //don't save profiles with errors
        return $asError;
      }


      if($pbSave)
      {
        //dump($this->casCandidateData['profile']);
        if(empty($nCandidatePk))
        {
          $bNewCandidate = true;
          $nKey = $this->_getModel()->add($this->casCandidateData['profile'], 'sl_candidate');
          if(!$nKey)
          {
            assert('false; // Could not add the candidate.');
            return array('error' => __LINE__.' - An error occurred. Could not add the candidate.');
          }

          if(empty($asData['locationfk']))
            $sLocation = 'TOK';
          else
          {
            $asLocation = $this->getVars()->getLocationList();
            $sLocation = $asLocation[$asData['locationfk']];
          }

          $sUid = sprintf("%'#4s", substr($this->casUserData['id'], 0, 4));
          $sUid.= sprintf("%'03s", substr($sLocation, 0, 3));
          $sUid.= date('y') . chr( (64+date('m')) ) . $nKey;

          $this->casCandidateData['profile']['candidatefk'] = $nKey;
          $this->casCandidateData['profile']['uid'] = strtoupper($sUid);
        }
        else
        {
          $bNewCandidate = false;

          //dump($this->casCandidateData['profile']);
          $bQuery = $this->_getModel()->update($this->casCandidateData['profile'], 'sl_candidate', 'sl_candidatepk = '.$nCandidatePk);
          if(!$bQuery)
          {
            assert('false; // Could not update the candidate.');
            return array('error' => __LINE__.' - An error occurred. Could not add the candidate.');
          }
        }

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        //candidate table added or update... deal with the business profile
        if($bNewCandidate)
        {
          //$asMonth = array('A','B','C','D','E','F','G','H','I','J','K','L');
          //$this->casCandidateData['profile']['uid'] = substr($this->casUserData['id'], 0, 4);
          //$this->casCandidateData['profile']['uid'].= 'LOC'.date('y').$asMonth[(int)date('m')].$this->casCandidateData['profile']['candidatefk'];

          $bSaved = (bool)$this->_getModel()->add($this->casCandidateData['profile'], 'sl_candidate_profile');
        }
        else
        {
          $this->casCandidateData['profile']['date_updated'] = date('Y-m-d H:i:s');
          $this->casCandidateData['profile']['updated_by'] = (int)$this->casUserData['loginpk'];

          $bSaved = $this->_getModel()->update($this->casCandidateData['profile'], 'sl_candidate_profile', 'sl_candidate_profilepk = '.$nProfilePk);

          if(isset($this->casCandidateData['profile']['previous_company']))
          {
            //need to log the company changing
            $oNote = CDependency::getComponentByName('sl_event');

            $nCompany = (int)$this->casCandidateData['profile']['previous_company'];
            $asCompany = $this->_getModel()->getCompanyData($nCompany);
            $sFrom = $asCompany['name'];
            $sNote = 'Candidate has been updated. Company changed from [ #'.$nCompany.' - '.$sFrom.'] ';

            $nCompany = $this->casCandidateData['profile']['companyfk'];
            $asCompany = $this->_getModel()->getCompanyData($nCompany);
            $sNote.= 'to [ #'.$nCompany.' - '.$asCompany['name'].' ]<br />';

            //add a note from  system user
            $oNote->addNote($nCandidatePk, 'cp_history', $sNote, (int)$this->casUserData['pk']);
            $oNote->addNote($nCandidatePk, 'cp_hidden', $sFrom, (int)$this->casUserData['pk']);
          }
        }


        if(!$bSaved)
        {
          assert('false; // Could not save the candidate profile.');
          return array('error' => __LINE__.' - An error occurred. Could not save the candidate data.');
        }

        //A candidate has been updated... we create a detailed log entry
        if(!empty($pasCandidate))
        {
          $this->_customLogUpdate($pasCandidate, $this->casCandidateData['profile']);
          //$pasCandidate  VS $this->casCandidateData['profile']
        }

        //-------------------------------------------------------------------------------------
        //-------------------------------------------------------------------------------------
        // candidate saved/updated... i can now manage the linked attributes

        $asAllAttribute = array();
        $sNow = date('Y-m-d H:i:s');

        $sAltOccupation = getValue('alt_occupationpk');
        if(!empty($sAltOccupation))
        {
          $asAttribute = explode(',', $sAltOccupation);
          foreach($asAttribute as $sAttributeFk)
          {
            $asAllAttribute['type'][] = 'candi_occu';
            $asAllAttribute['itemfk'][] = $this->casCandidateData['profile']['candidatefk'];
            $asAllAttribute['attributefk'][] = (int)$sAttributeFk;
            $asAllAttribute['loginfk'][] = $this->casUserData['pk'];
            $asAllAttribute['date_created'][] = $sNow;
          }
        }

        $sAltIndustry = getValue('alt_industrypk');
        if(!empty($sAltIndustry))
        {
          $asAttribute = explode(',', $sAltIndustry);
          foreach($asAttribute as $sAttributeFk)
          {
            $asAllAttribute['type'][] = 'candi_indus';
            $asAllAttribute['itemfk'][] = $this->casCandidateData['profile']['candidatefk'];
            $asAllAttribute['attributefk'][] = (int)$sAttributeFk;
            $asAllAttribute['loginfk'][] = $this->casUserData['pk'];
            $asAllAttribute['date_created'][] = $sNow;
          }
        }

        $asAttribute = @$_POST['alt_language'];
        if(!empty($asAttribute))
        {
          foreach($asAttribute as $sAttributeFk)
          {
            $asAllAttribute['type'][] = 'candi_lang';
            $asAllAttribute['itemfk'][] = $this->casCandidateData['profile']['candidatefk'];
            $asAllAttribute['attributefk'][] = (int)$sAttributeFk;
            $asAllAttribute['loginfk'][] = $this->casUserData['pk'];
            $asAllAttribute['date_created'][] = $sNow;
          }
        }

        if(!empty($asAllAttribute))
        {
          if(!$bNewCandidate)
            $this->_getModel()->deleteByWhere('sl_attribute', '`type` IN ("candi_occu", "candi_indus", "candi_lang") AND itemfk='.$this->casCandidateData['profile']['candidatefk']);

          $nInserted = $this->_getModel()->add($asAllAttribute, 'sl_attribute');
          if(empty($nInserted))
            return array('error' => 'Could not save the alternative data.');
        }

      }

      return $asError;
    }

    private function _checkDuplicate($pasData)
    {

      $oDbResult = $this->_getModel()->getDuplicate($pasData, 0, true);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return '';

      $sHTML = $this->_oDisplay->getCR();
      $asDuplicate = array();
      while($bRead)
      {
        $asData = $oDbResult->getData();
        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$oDbResult->getFieldValue('sl_candidatepk'));
        $asData['candidate'] = '<a href="javascript:;" onclick="popup_candi(this, \''.$sURL.'\');">'.$oDbResult->getFieldValue('lastname').' '.$oDbResult->getFieldValue('firstname').'</a>';

        $asDuplicate[] = $asData;
        $bRead = $oDbResult->readNext();
      }


      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');
      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $oConf->addColumn('refId', 'sl_candidatepk', array('width' => 45, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Candidate', 'candidate', array('width' => 210, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Company', 'company_name', array('width' => 250, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Industry', 'industry', array('width' => 150, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Occupation', 'occupation', array('width' => 150, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Contacts', 'contacts', array('width' => 170, 'sortable'=> array('javascript' => 1)));

      $sHTML.= $oTemplate->getDisplay($asDuplicate);


      $sDupString = $pasData['lastname'].'_'.$pasData['firstname'];

      $sHTML.= $this->_oDisplay->getCR(2);
      $sLink = '>>&nbsp;&nbsp;&nbsp;&nbsp;Click here if none of the above is a duplicate !&nbsp;&nbsp;&nbsp;&nbsp;<< &nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="Not a duplicate"/>';

      $sHTML.= $this->_oDisplay->getLink($sLink, 'javascript:;', array( 'style' => 'font-weight: bold; color: #CC7161; font-size: 14px; ',
          'onclick' => '$(\'#dup_checked\').val(\''.$sDupString.'\'); $(\'.tab_duplicate\').hide(); $(\'.candidate_form_tabs li:first\').click();'));

      return $sHTML;
    }

    /* a pickle ?
     * private function _saveContactDetails($pbTest = true, $pbSave = false, $pasCandidate = array())
    {
      $asError = array();
      if($pbTest)
      {
        $asRowChecked = array();

        for($nCount = 0; $nCount < 4; $nCount++)
        {
          $sType = (int)$_POST['contact_type'][$nCount];
          $sValue = $_POST['contact_value'][$nCount];
          $nVisibility = (int)$_POST['contact_visibility'][$nCount];
          $sDescription = $_POST['contact_description'][$nCount];
          $asUser = $_POST['userfk'][$nCount];
          $bRowOk = true;

          if(!empty($sValue))
          {
            if(empty($sType) || empty($nVisibility))
            {
              $asError[] = 'row #'.($nCount++).': type or visibility invalid.';
              $bRowOk = false;
            }

            if($nVisibility == 4 && empty($asUser))
            {
              $asError[] = 'row #'.($nCount++).': if visibility is set on custom, you need to select the user who can access the contact data.';
              $bRowOk = false;
            }

            if($bRowOk)
              $asRowChecked[] = array('type' => $sType, 'value' => $sValue, 'description' => $sDescription, 'visibility' => $nVisibility, 'users' => $asUser);
          }
        }

        $this->casCandidateData['contact'] = $asRowChecked;
      }

      if(!empty($asError))
        return $asError;

      if($pbSave)
      {
        //$this->_
      }
      return $asError;
    }*/

    private function _saveNotes($pbTest = true, $pbSave = false, $pasCandidate = array())
    {
      $asError = array();

      $sCharacter = getValue('character_note');
      $nCharacterHtml = getValue('character_html');

      if(!$nCharacterHtml)
        $sCharacter = strip_tags($sCharacter);

      $sNote = getValue('note');

      if(!$nCharacterHtml)
        $sNote = strip_tags($sNote);

      if(empty($sCharacter) && empty($sNote))
        $asError[] = 'You have to input at least a note or a character note.';

      if(!empty($asError))
        return $asError;

      if($pbSave)
      {
        $oEvent = CDependency::getComponentByName('sl_event');

        if(!empty($sCharacter))
        {
          $asResult = $oEvent->addNote((int)$pasCandidate['candidatefk'], 'character', $sCharacter);
          if(isset($asResult['error']))
            return $asResult;
        }

        if(!empty($sNote))
        {
          $asResult = $oEvent->addNote((int)$pasCandidate['candidatefk'], 'note', $sNote);
          if(isset($asResult['error']))
            return $asResult;
        }
      }

      return $asError;
    }

    private function _saveResume($pbTest = true, $pbSave = false, $pasCandidate = array())
    {
      $asError = array();

      if(empty($_FILES) || empty($_FILES['document']['name']))
        return array();

      if($pbTest)
      {
        if(empty($_FILES['document']['tmp_name']))
          $asError[] = 'No resume uploaded. It could be a transfer error, or you\'ve forgotten to select a file.';
      }

      if(!empty($asError))
        return array('error' => implode('<br />', $asError));

      if($pbSave)
      {
        $sTitle = getValue('doc_title');
        $sDescription = getValue('doc_description');

        if(empty($sTitle))
          $sTitle = $pasCandidate['lastname'].'_'.$pasCandidate['firstname'].'_resume';

        $sTitle = str_replace(' ', '_', $sTitle);

        $oSharedspace = CDependency::getComponentByName('sharedspace');
        $asItemLink = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pasCandidate['candidatefk']);
        $asResult = $oSharedspace->quickAddDocument($asItemLink, $sTitle, $sDescription);

        if(isset($asResult['error']))
          $asError[] = $asResult['error'];
      }

      return $asError;
    }




    private function _getSalaryInYen(&$pasSalaryData)
    {
      if(!assert('is_array($pasSalaryData) && !empty($pasSalaryData)'))
        return -1;

      if(!assert('isset($pasSalaryData[\'value\']) && isset($pasSalaryData[\'currency\']) && !empty($pasSalaryData[\'currency\'])'))
        return -1;

      $pasSalaryData['yen'] = 0;
      $pasSalaryData['rate'] = 1;

      if(empty($pasSalaryData['value']))
        return 0;

      if(empty($pasSalaryData['currency']) == 'jpy')
        return 0;

      //convert the value in yen
      $asCurrencyRate = $this->getVars()->getCurrencies();

      if(!isset($asCurrencyRate[$pasSalaryData['currency']]))
        return -1;

      $fRate = (float)$asCurrencyRate[$pasSalaryData['currency']];
      $pasSalaryData['rate'] = $fRate;
      $pasSalaryData['yen'] = $this->_roundSalary((int)$pasSalaryData['value'] / $fRate);

      /*dump('currency');
      dump($pasSalaryData['currency']);
      dump('currency rate');
      dump($fRate);
      dump('calculated value:  '.$fRate.' / '.$pasSalaryData['value']);
      dump($pasSalaryData['yen']);*/

      return 1;
    }

    private function _roundSalary($pvNumber, $pnPrecision = 1)
    {
      if(!assert('is_integer($pvNumber) || is_float($pvNumber)'))
        return 0;

      if(!assert('is_integer($pnPrecision)'))
        return 0;

      $nDivisor = pow(10, $pnPrecision);
      return round($pvNumber/$nDivisor) * $nDivisor;
    }




    public function updateCandidateProfiles()
    {
      $nLimit = (int)getValue('limit', 0);
      if(!empty($nLimit))
        $sLimit = '1, '.$nLimit;
      else
        $sLimit = '1, 250';

      $oDbResult = $this->_getModel()->getByWhere('sl_candidate_profile', '1', 'candidatefk', '_date_updated, candidatefk DESC', $sLimit);
      $bRead = $oDbResult->readFirst();
      $fStart = microtime(true);

      $nCount = 0;
      while($bRead)
      {
        $nCandidate = (int)$oDbResult->getFieldValue('candidatefk');
        $this->updateCandidateProfile($nCandidate);
        //echo 'candidate '.$nCandidate.' updated<br />';
        usleep(100);

        if($nCount == 0)
          echo ' Starts with candidate '.$nCandidate.'<br />';

        if(($nCount%500) == 0)
        {
          echo $nCount.' candidates updated<br />';
          flush();
          ob_flush();
        }

        $bRead = $oDbResult->readNext();
        $nCount++;
      }
      echo ' Ends with candidate '.$nCandidate.'<br />';

      $fStop = microtime(true);
      dump('Took '.round((($fStop-$fStart)), 3).'s to treat candidates '.$sLimit.' <br />');
      return true;
    }

    public function updateCandidateProfile($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array();

      $asData = $this->_getModel()->getCandidateData($pnCandidatePk, true);
      if(empty($asData))
        return array();

      $nScore = 0;
      if(!empty($asData['languagefk']))
        $nScore+= 3;

      if(!empty($asData['nattionalityfk']))
        $nScore+= 3;

      if(!empty($asData['locationfk']))
        $nScore+= 3;

      if(!empty($asData['date_birth']))
      {
        if($asData['is_birth_estimation'])
          $nScore+= 3;
        else
          $nScore+= 5;
      }


      if($asData['cpa'] != null || $asData['mba'] != null)
        $nScore+= 3;

      if(!empty($asData['skill_ag']))
        $nScore+= 3;
      if(!empty($asData['skill_ap']))
        $nScore+= 3;
      if(!empty($asData['skill_am']))
        $nScore+= 3;
      if(!empty($asData['skill_mp']))
        $nScore+= 3;
      if(!empty($asData['skill_in']))
        $nScore+= 3;
      if(!empty($asData['skill_ex']))
        $nScore+= 3;
      if(!empty($asData['skill_fx']))
        $nScore+= 3;
      if(!empty($asData['skill_ch']))
        $nScore+= 3;
      if(!empty($asData['skill_ed']))
        $nScore+= 3;
      if(!empty($asData['skill_pl']))
        $nScore+= 3;
      if(!empty($asData['skill_e']))
        $nScore+= 3;

      if(!empty($asData['title']))
        $nScore+= 5;

      if(!empty($asData['department']))
        $nScore+= 5;

      if(!empty($asData['keyword']))
        $nScore+= 3;
      if(!empty($asData['salary']))
        $nScore+= 5;
      if(!empty($asData['bonus']))
        $nScore+= 5;
      if(!empty($asData['target_low']))
        $nScore+= 3;
      if(!empty($asData['target_high']))
        $nScore+= 3;


      //Update _has_doc and used for quality ratio
      $asItem = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pnCandidatePk);
      $oShareSpace = CDependency::getComponentByName('sharedspace');
      $asDocument = $oShareSpace->getDocuments(-1, $asItem);
      //dump($asDocument);
      $nDocument = (int)!empty($asDocument);
      if($nDocument == 0)
        $nScore+= -10;
      else
        $nScore+= 10;

      //calculating ratio
      $oNote = CDependency::getComponentByName('sl_event');
      $asCharNote = $oNote->getNotes($pnCandidatePk, CONST_CANDIDATE_TYPE_CANDI, 'character');
      //dump($asCharNote);
      $asNote = $oNote->getNotes($pnCandidatePk, CONST_CANDIDATE_TYPE_CANDI, '', array('character'));
      //dump($asNote);

      $nCharNote = count($asCharNote);
      if($nCharNote > 3)
        $nCharNote = 3;

      $nNote = count($asNote);
      if($nNote > 3)
        $nNote = 3;

      if(empty($nCharNote) && empty($nNote))
        $nScore-= 15;
      else
        $nScore+= ($nCharNote * 7) + ($nNote * 7);


      $sWhere = ' item_type = "'.CONST_CANDIDATE_TYPE_CANDI.'" AND itemfk = "'.$pnCandidatePk.'" ';
      $oDbResult = $this->_getModel()->getByWhere('sl_contact', $sWhere, 'count(*) as nb_contact');
      $oDbResult->readFirst();
      $nContact = (int)$oDbResult->getFieldValue('nb_contact');
      if($nContact > 3)
        $nContact = 3;
      elseif($nContact == 0)
        $nContact = -1;

      $nScore+= ($nContact * 10);

      //Update _in_play (nb of active positions)
      $oPosition = CDependency::getComponentByName('sl_position');
      $nPlay = $oPosition->isCandidateInPlay($pnCandidatePk);
      $sLimit = date('Y-m-d', strtotime('-1 year'));
      $sLimit2 = date('Y-m-d', strtotime('-2 years'));
      $sLimit2 = date('Y-m-d', strtotime('-2 years'));
      $sLimit3 = date('Y-m-d', strtotime('6 months'));

      // store the most relevant position activity
      //look for active status first... More priority than any other
      $nMaxActiveStatus = $oPosition->getMaxActiveStatus($pnCandidatePk, 100);

      /*
      * Getting to complicated, let's make a dedicated query. See below

      //if nothiing active, lets extend to "pitched but inactive" status
      if(empty($nMaxActiveStatus))
        $nMaxActiveStatus = $oPosition->getLastInactiveStatus($pnCandidatePk, 101, $sLimit);

      $sLimit = date('Y-m-d', strtotime('-6 months'));

      //if nothiing active, lets extend to "pitched but inactive" status
      if(empty($nMaxActiveStatus))
        $nMaxActiveStatus = $oPosition->getMaxActiveStatus($pnCandidatePk, 201, $sLimit);

      //then if there's nothing... let's use the last status of any kind
      if(empty($nMaxActiveStatus))
        $nMaxActiveStatus = $oPosition->getLastInactiveStatus($pnCandidatePk, 250, $sLimit);*/

      if(empty($nMaxActiveStatus))
      {
        /*
         * ,
          IF(`status` = 101 AND `date_created` >= \''.$sLimit.'\', 1, 0) as placed,
          IF(`status` = 101 AND `date_created` >= \''.$sLimit2.'\', 1, 0) as placed2,
          IF(`active` = 0 AND `date_created` >= \''.$sLimit3.'\', 1, 0) as considered
         */
        $sQuery = 'SELECT *
          FROM `sl_position_link`
          WHERE `candidatefk` = '.$pnCandidatePk.'
          ORDER BY
          IF(`status` = 101 AND `date_created` >= \''.$sLimit.'\', 1, 0) DESC,
          active DESC,
          IF(`status` = 101 AND `date_created` >= \''.$sLimit2.'\', 1, 0) DESC,
          IF(`active` = 0 AND `date_created` >= \''.$sLimit3.'\', 1, 0) DESC,
          `date_created` DESC

          LIMIT 1 ';
        $oDbResult = $this->_getModel()->executeQuery($sQuery);
        $oDbResult->readFirst();
        $nMaxActiveStatus = (int)$oDbResult->getFieldValue('status');
      }

      //dump($nPlay);
      //dump($nMaxActiveStatus);

      if($nScore > 116)
        $nRating = 100;
      else
      {
        $nRating = round(($nScore / 116)*100, 2);

        if($nRating < 0)
          $nRating = 1;
      }

      $asUpdate = array('_has_doc' => $nDocument, '_in_play' => $nPlay, '_pos_status' => $nMaxActiveStatus,
          'profile_rating' => $nRating, '_date_updated' => date('Y-m-d H:i:s'));
      $bUpdated = $this->_getModel()->update($asUpdate, 'sl_candidate_profile', 'candidatefk = '.$pnCandidatePk);

      if(!$bUpdated)
      {
        assert('false; /* could not update candidate profile - cron updateProfile */');
      }

      return $asUpdate;
    }

    //END save candidate form
    /* *********************************************************** */



    private function _getCandidateFastEdit($pnCandidatePk, $pbAjax = false)
    {
      if(!assert('is_key($pnCandidatePk) && is_bool($pbAjax)'))
        return '';

      return 'gaaaaaa fast edit';
    }


    public function _getTreeData($psType)
    {
      if(!assert('$psType == \'occupation\' || $psType == \'industry\' '))
        return array();

      $sTable = 'sl_'.$psType;
      $sKey = 'sl_'.$psType.'pk';

      if($psType == 'occupation')
        $asItemList = $this->getVars()->getOccupationList(true, true);
      else
        $asItemList = $this->getVars()->getIndustryList(true, true);

      //$oDbResult = $this->_getModel()->getByWhere($sTable);

      $sQuery = 'SELECT main.* FROM '.$sTable.' as main
        LEFT JOIN '.$sTable.' as parent ON (parent.'.$sKey.' = main.parentfk)
        ORDER BY parent.label, main.label ';
      $oDbResult = $this->_getModel()->executeQuery($sQuery);

      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array();

      $asTree = array();
      while($bRead)
      {
        $asData = $oDbResult->getData();
        //make the field generic usic parent/value attributes
        $asData['parent'] = $asData['parentfk'];
        $asData['value'] = $asData[$sKey];

        if($asData['parentfk'] == 0 || !isset($asItemList[$asData['parentfk']]))
          $asData['level'] = 0;
        else
        {
          if($asItemList[$asData['parentfk']]['parentfk'] == 0)
            $asData['level'] = 1;
          else
            $asData['level'] = 2;
        }

        $asTree[$asData[$sKey]] = $asData;
        $bRead = $oDbResult->readNext();
      }

      //dump($asTree);

      return $asTree;
    }


    public function getCandidateRm($pnCandidatePk, $pbActiveOnly = true, $pbFriendly = false)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array();

      return $this->_getModel()->getCandidateRm($pnCandidatePk, $pbActiveOnly, $pbFriendly);
    }

    private function _accessRmContactDetails($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return false;


      $asCandidate = $this->_getModel()->getCandidateData($pnCandidatePk, true, true);
      if(!assert('!empty($asCandidate)'))
        return false;

      $asRm = $this->_getModel()->getCandidateRm($pnCandidatePk);
      if(!assert('!empty($asRm)'))
        return false;


      $asCandidate = $asCandidate[$pnCandidatePk];
      $oLogin = CDependency::getCpLogin();


      $sUrl = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);

      $sSubject = 'Slistem RM - Access to '.$asCandidate['firstname'].' '.$asCandidate['lastname'].' (#'.$pnCandidatePk.') contact details';

      $sContent = 'Hello,<br /><br />
        '.$oLogin->getCurrentUserName().' has accessed <strong style="color: #555555;">'.$asCandidate['firstname'].' '.$asCandidate['lastname'].'</strong> (#'.$pnCandidatePk.') contact details.<br />
        <br />
        Candidate:<br />
        refId = '.$this->_oDisplay->getLink('#'.$pnCandidatePk, $sUrl).'<br />
        Company: '.$asCandidate['company_name'].'<br />
        Title: '.$asCandidate['title'].'<br />
        Department: '.$asCandidate['department'].'<br />
        Created: '.$asCandidate['date_created'].'<br />';

      //$sContent.= '<hr /> '.var_export($asRm, true);

      $oMail = CDependency::getComponentByName('mail');
      $oMail->createNewEmail();
      $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);

      $nCount = 0;
      foreach($asRm as $asUser)
      {
        if($nCount == 0)
          $oMail->addRecipient($asUser['email'], $asUser['name']);
        else
          $oMail->addCCRecipient($asUser['email'], $asUser['name']);

        $nCount++;
      }

      $bNotified = $oMail->send($sSubject, $sContent);
      if($bNotified)
        $_SESSION['sl_candidate']['contact_acccess'][$pnCandidatePk] = time();

      return true;
    }



    private function _getResumeAddForm()
    {
      $nCandidatePk = (int)getValue('cp_pk');
      if(!assert('is_key($nCandidatePk)'))
        return '';

      $oPage = CDependency::getCpPage();
      $oPage->addCustomJs('
        function loadTinyMce(psUrl, psFieldId, pbIsHtml)
        {
          $.ajax(
          {
            url: psUrl,
            dataType: "html",
            success: function(sData)
            {
              tinymce.get(psFieldId).setProgressState(1);

              if(pbIsHtml)
              {
                sData = sData+"<p />" + tinymce.get(psFieldId).getContent();
                tinymce.get(psFieldId).setContent(sData, {format : "raw"});
              }
              else
              {
                sData = sData+"\n" + tinymce.get(psFieldId).getContent();
                tinymce.get(psFieldId).setContent(sData);
              }
              tinymce.get(psFieldId).save();
              tinymce.get(psFieldId).setProgressState(0);
            }
          });
        }
      ');


      $sTitle = getValue('document_title');

      $oForm = $this->_oDisplay->initForm('resumeAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_CANDIDATE_TYPE_DOC, $nCandidatePk);

      $oForm->setFormParams('addresume', true, array('action' => $sURL, 'class' => 'resumeCreateForm', 'submitLabel'=>'Create a resume'));
      $oForm->setFormDisplayParams(array('noCancelButton' => true, 'columns' => 1));

      $oForm->addField('input', 'candidatepk', array('type' => 'hidden','value'=> $nCandidatePk));
      $oForm->addField('input', 'userfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));
      $oForm->addField('input', 'pclose', array('type' => 'hidden', 'value' => getValue('pclose')));

      $oForm->addField('input', 'cp_uid', array('type' => 'hidden', 'value' => getValue('cp_uid')));
      $oForm->addField('input', 'cp_action', array('type' => 'hidden', 'value' => getValue('cp_action')));
      $oForm->addField('input', 'cp_type', array('type' => 'hidden', 'value' => getValue('cp_type')));
      $oForm->addField('input', 'cp_pk', array('type' => 'hidden', 'value' => getValue('cp_pk')));

      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Create a resume'));
      $oForm->addField('misc', '', array('type' => 'br'));


      $oForm->addField('input', 'title', array('type' => 'text', 'label' => 'Title', 'value' => $sTitle));

      $oForm->addField('textarea', 'content', array('type' => 'br', 'label' => 'Content', 'isTinymce' => 1, 'class' => 'resume_mce', 'style' => 'height: 410px;'));
      $oForm->setFieldControl('content', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => '50'));
      $oForm->setFieldDisplayParams('content', array('class' => 'fullWidthMce'));

      $sURL = $this->getResourcePath().'/resume/resume_template.html';
      $sJavascript = 'var sMceId = $(this).closest(\'form\').find(\'.resume_mce\').attr(\'id\'); loadTinyMce(\''.$sURL.'\', sMceId, true); ';
      $oForm->addField('misc', '', array('type' => 'text', 'text' => '<a href="javascript:;" onclick="'.$sJavascript.'">Load template 1</a>'));

      return $oForm->getDisplay();
    }


    private function _getResumeSaveAdd()
    {
      // check form, create a html file from it
      $sTitle = trim(getValue('title'));
      $sContent = getValue('content');
      if(empty($sTitle) || empty($sContent))
        return array('error' => 'Title and resume content are required.');

      $asCpLink = array();
      $asCpLink['cp_uid'] = getValue('cp_uid');
      $asCpLink['cp_action'] = getValue('cp_action');
      $asCpLink['cp_type'] = getValue('cp_type');
      $asCpLink['cp_pk'] = (int)getValue('cp_pk');

      if(!assert('is_cpValues($asCpLink)'))
        return array('error' => 'Missing parameters.');

      //save the file in the temp folder
      $sFileName = uniqid('resume_html_').'.html';
      $sFilePath = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.$sFileName;
      try
      {
        $oFs = fopen($sFilePath, 'a+');
        fputs($oFs, $sContent);
      }
      catch(Exception $oExcept)
      {
        return array('error' => __LINE__.' - Error saving the resume. '.$oExcept->getMessage());
      }

      if($oFs)
        fclose($oFs);

      $asToRemove = array('?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '¥', ' ');
      $sDisplayFileName = str_replace($asToRemove, '_', $sTitle).'.html';


      //can't use curl here (session issue ... need to re identify myself :/
      //so call straight shared space
      $oSharedspace = CDependency::getComponentByName('sharedspace');
      $sError = $oSharedspace->saveLocalDocument($sDisplayFileName, $sFilePath, $sTitle, 'resume', $asCpLink);

      if(!empty($sError))
        return array('error' => $sError);


      $this->_getModel()->_logChanges(array('sl_document' => 'new'), 'document', 'new document', '', $asCpLink);

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $asCpLink['cp_pk'], array('check_profile' => 1));

      if(getValue('pclose'))
        return array('notice' => 'Resume saved.', 'action' => 'view_candi("'.$sURL.'", "#tabLink3");');

      return array('notice' => 'Resume saved.', 'action' => 'view_candi("'.$sURL.'", "#tabLink3"); goPopup.removeActive(\'layer\');  ');
    }


    private function _getViewLastDocument($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => __LINE__.' - Can not find the candidate profile.');


      $oShareSpace = CDependency::getComponentByName('sharedspace');


      $asItem = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $pnCandidatePk);
      $asDocument = $oShareSpace->getDocuments(0, $asItem);
      if(empty($asDocument))
      {
        $sMessage = '<div style="padding: 10px; margin: 10px; border: 1px solid red; background-color: #FFC6BC;">An error occured, no document found for this candidate.</div>';
        exit($sMessage);
        //return array('error' => 'Not document found for this candidate.');
      }

      $asFirst = array_first($asDocument);
      return $oShareSpace->viewDocument((int)$asFirst['documentpk']);
    }


    /**
     * Check if a specific sql array is empty
     * @param type $pasArray
     * @return boolean
     */
    private function _sqlArrayEmpty($pasArray)
    {
      if(empty($pasArray['required']) && empty($pasArray['optional']))
        return true;

      return false;
    }

    /**
     * Implode a specific sql array
     * @param type $pasArray
     * @return string
     */
    private function _sqlImplode($psGlue, $pasArray)
    {
      if(empty($pasArray['optional']))
        return implode($psGlue, $pasArray['required']);

      if(empty($pasArray['required']))
        return implode($psGlue, $pasArray['optional']);

      return implode($psGlue, $pasArray['required']).$psGlue.implode($psGlue, $pasArray['optional']);
    }



    private function _autocompleteSearch($psType)
    {
      $sSearchString = getValue('q');
      if(empty($sSearchString))
        return array();

      if($psType == CONST_CANDIDATE_TYPE_INDUSTRY)
        $sTable = 'sl_industry';
      else
        $sTable = 'sl_occupation';


      $oDb = CDependency::getComponentByName('database');

      if($sSearchString == 'all' || $sSearchString == 'more')
      {
        $sQuery = 'SELECT item.*,
          parent.'.$sTable.'pk as parentId, parent.label as parentLabel, parent.parentfk as parentParent,
          child.'.$sTable.'pk as childId, child.label as childLabel ';

        $sQuery.= ' FROM '.$sTable.' as item';
        $sQuery.= ' LEFT JOIN '.$sTable.' as parent ON (parent.'.$sTable.'pk = item.parentfk) ';
        $sQuery.= ' LEFT JOIN '.$sTable.' as child ON (child.parentfk = item.'.$sTable.'pk) ';
        $sQuery.= ' ORDER BY item.parentfk ASC, item.label ASC ';

      }
      else
      {
        $sQuery = 'SELECT item.*,
          parent.'.$sTable.'pk as parentId, parent.label as parentLabel, parent.parentfk as parentParent,
          child.'.$sTable.'pk as childId, child.label as childLabel,

          IF(item.label LIKE '.$oDb->dbEscapeString($sSearchString).', 1, 0) as nEqual,
          IF(item.label LIKE '.$oDb->dbEscapeString('%'.$sSearchString).', 1, 0) as nStart ';

        $sQuery.= ' FROM '.$sTable.' as item
          LEFT JOIN '.$sTable.' as parent ON (parent.'.$sTable.'pk = item.parentfk)
          LEFT JOIN '.$sTable.' as child ON (child.parentfk = item.'.$sTable.'pk)
          WHERE ( item.label LIKE '.$oDb->dbEscapeString('%'.$sSearchString.'%').'
          OR item.label LIKE '.$oDb->dbEscapeString(trim(substr($sSearchString, 0, 3)).'%').' )

          ORDER BY item.parentfk ASC, nEqual DESC, nStart DESC, item.label ASC ';
      }


      //$oDbResult = $this->_getModel()->getByWhere($sTable, $sWhere, $sSelect, ', '50');
      $oDbResult = $oDb->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      $asJsonData = array();
      while($bRead)
      {
        $asData = $oDbResult->getData();
        $asEntry = array();

        if(!empty($asData['parentId']))
        {
          $asEntry['id'] = $asData['parentId'];
          $asEntry['name'] = $asData['parentLabel'];
          $asEntry['parent'] = 1;

          if(empty($asData['parentParent']))
            $asEntry['level'] = 0;
          else
            $asEntry['level'] = 1;

          $asJsonData[$asEntry['id']] = json_encode($asEntry);
        }

        $asEntry['id'] = $asData[$sTable.'pk'];
        $asEntry['name'] = $asData['label'];
        $asEntry['parent'] = (int)empty($asData['childId']);
        $asEntry['level'] = 0;

        if(!empty($asData['parentfk']))
          $asEntry['level']++;

        if(!empty($asData['parentParent']))
            $asEntry['level']++;

        $asJsonData[$asEntry['id']] = json_encode($asEntry);

        if(!empty($asData['childId']))
        {
          $asEntry['id'] = $asData['childId'];
          $asEntry['name'] = $asData['childLabel'];
          $asEntry['parent'] = 0;

          $asEntry['level'] = 0;
          if(!empty($asData['parentId']))
            $asEntry['level']++;

          if(empty($asData['parentParent']))
            $asEntry['level']++;

          $asJsonData[$asEntry['id']] = json_encode($asEntry);
        }

        $bRead = $oDbResult->readNext();
      }

      exit('['.implode(',', $asJsonData).']');

    }


    private function _isActiveConsultant($pnConsultant)
    {
      if(!assert('is_key($pnConsultant)'))
        return false;

      if(empty($_SESSION['sl_candidate_active_user']))
      {
        $oLogin = CDependency::getCpLogin();
        $_SESSION['sl_candidate_active_user'] = $oLogin->getUserList(0, true);
      }

      if(isset($_SESSION['sl_candidate_active_user'][$pnConsultant]))
        return true;

      return false;
    }


    /**
     * Candidate selector; searching by name or refID
     */
    function _autocompleteCandidate()
    {
      $sSearchString = trim(getValue('q'));
      if(empty($sSearchString))
      {
        $asEntry = array();
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'Nothing to search for';
        $asJson[$asEntry['id']] = json_encode($asEntry);
        exit('['.implode(',', $asJson).']');
      }

      $asWords = explode(' ', trim($sSearchString));

      foreach($asWords as $nKey => $sWord)
      {
        if(empty($sWord) || strlen($sWord) < 2)
          unset($asWords[$nKey]);
      }


      $nWord = count($asWords);
      if($nWord < 1)
      {
        $asEntry = array();
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'A refId, firstname and/or lastname are required. (2 character min each)';
        $asJson[$asEntry['id']] = json_encode($asEntry);
        exit('['.implode(',', $asJson).']');
      }

      $poQB = $this->_getModel()->getQueryBuilder();
      $poQB->setTable('sl_candidate', 'scan');


      $sSearchString = str_replace(array('#', '"', ',', '.'), '', $sSearchString);
      $sRefId = preg_replace('/[^0-9\#]/i', '', $sSearchString);
      if((int)$sRefId == (int)$sSearchString && (int)$sRefId > 0)
      {
        $poQB->addSelect('scan.*');
        $poQB->addWhere('scan.sl_candidatepk = '.(int)$sRefId);
      }
      else
      {
        if($nWord == 1)
        {
          //must be the lastname
          $poQB->addSelect('scan.*, IF(scan.lastname LIKE '.$this->_getModel()->dbEscapeString($asWords[0]).', 1, 0) as exact_lastname ');
          $poQB->addWhere('scan.lastname LIKE '.$this->_getModel()->dbEscapeString($asWords[0].'%'));
          $poQB->addOrder('exact_lastname DESC, scan.lastname ASC, scan.firstname ASC');
        }
        else
        {
          //We don't know soooo... we try different combinaisons with the words we have
          $poQB->addSelect('scan.*');
          $_POST['qs_super_wide'] = 0;
          $_POST['qs_wide'] = 0;
          $_POST['qs_name_format'] = 'none';
          $_POST['candidate'] = $sSearchString;
          $_POST['data_type'] = 'candi';

          require_once('component/sl_candidate/resources/search/quick_search.class.php5');
          $oQS = new CQuickSearch($poQB);
          $oQS->buildQuickSearch();
        }
      }

      $poQB->addLIMIT('0,100');

      $asJsonData = array();
      $oDbResult = $this->_getModel()->executeQuery($poQB->getSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        $asEntry = array();
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'No candidate matching.';
        $asJsonData[$asEntry['id']] = json_encode($asEntry);
        exit('['.implode(',', $asJsonData).']');
      }

      if($oDbResult->numRows() >= 100)
      {
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'Only 100 results displayed. Please refine your search...';
        $asJsonData[$asEntry['id']] = json_encode($asEntry);
      }

      while($bRead)
      {
        $asCandidate = $oDbResult->getData();
        $asEntry = array();

        $asEntry['id'] = $asCandidate['sl_candidatepk'];
        $asEntry['name'] = '  #'.$asCandidate['sl_candidatepk'].'  - '.$asCandidate['lastname'].' '.$asCandidate['firstname'];
        $asJsonData[$asEntry['id']] = json_encode($asEntry);

        $bRead = $oDbResult->readNext();
      }



      exit('['.implode(',', $asJsonData).']');
    }

    function _autocompleteCompany()
    {
      $sSearchString = getValue('q');
      if(empty($sSearchString))
      {
        $asEntry = array();
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'Nothing to search for';
        $asJson[$asEntry['id']] = json_encode($asEntry);
        exit('['.implode(',', $asJson).']');
      }

      $poQB = $this->_getModel()->getQueryBuilder();
      $poQB->setTable('sl_company', 'scom');


      $sRefId = preg_replace('/[^0-9]/i', '', $sSearchString);
      if((int)$sRefId == (int)$sSearchString && (int)$sRefId > 0)
      {
        $poQB->addSelect('scom.*');
        $poQB->addWhere('scom.sl_companypk = '.(int)$sRefId);
      }
      else
      {

        $asWords = explode(' ', trim($sSearchString));

        foreach($asWords as $nKey => $sWord)
        {
          if(empty($sWord) || strlen($sWord) < 2)
            unset($asWords[$nKey]);
        }

        if(empty($asWords))
        {
          $asEntry = array();
          $asEntry['id'] = 'token_clear';
          $asEntry['name'] = 'Company name should be at least 2 character long.';
          $asJson[$asEntry['id']] = json_encode($asEntry);
          exit('['.implode(',', $asJson).']');
        }


        $poQB->addSelect('scom.*, IF(scom.name LIKE '.$this->_getModel()->dbEscapeString($sSearchString).', 1, 0) as exact_name ');

        foreach($asWords as $nKey => $sWord)
          $asWords[$nKey] = '(scom.name LIKE '.$this->_getModel()->dbEscapeString($sWord.'%').' )';

        $poQB->addWhere(implode(' OR ',$asWords));

        $poQB->addOrder('exact_name DESC, scom.name ASC');
      }

      $oDbResult = $this->_getModel()->executeQuery($poQB->getSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        $asEntry = array();
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = 'No company matching.';
        $asJson[$asEntry['id']] = json_encode($asEntry);
        exit('['.implode(',', $asJson).']');
      }

      $asJsonData = array();
      while($bRead)
      {
        $asCandidate = $oDbResult->getData();
        $asEntry = array();

        $asCandidate['name'] = preg_replace('/[^a-z0-9\.,#\'" ]/i', '', $asCandidate['name']);

        $asEntry['id'] = $asCandidate['sl_companypk'];
        $asEntry['name'] = '  #'.$asCandidate['sl_companypk'].' - '.mb_strimwidth($asCandidate['name'], 0, 38, '...');
        $asEntry['title'] = $asCandidate['name'];
        $asJsonData[$asEntry['id']] = json_encode($asEntry);

        $bRead = $oDbResult->readNext();
      }

      exit('['.implode(',', $asJsonData).']');
    }


    private function _getNoScoutList()
    {
      $oPage = CDependency::getCpPage();
      $oLogin = CDependency::getCpLogin();
      $oHTML = CDependency::getCpHtml();

      $oPage->addCssFile(self::getResourcePath().'css/no_scout_list.css');
      $nLevel = (int)getValue('filter_level', 0);

      if(!empty($nLevel))
        $sQuery = 'SELECT * FROM sl_company WHERE level = '.$nLevel.' AND (is_client = 1 OR is_nc_ok = 0) ORDER BY name ASC';
      else
        $sQuery = 'SELECT * FROM sl_company WHERE is_client = 1 OR is_nc_ok = 0 ORDER BY name ASC';

      $oDbResult = $this->_getModel()->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      $asLetter = array(1=>'A', 2=>'B', 3=>'C');
      $asCompany = array();
      $asLetters = array();
      $nCount = 0;
      while($bRead)
      {
        $asCpData = $oDbResult->getData();
        $asCpData['level_letter'] = $asLetter[$asCpData['level']];
        $sFirstLetter = strtoupper(substr($asCpData['name'], 0, 1));
        if(is_numeric($sFirstLetter))
          $sFirstLetter = '#';

        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$asCpData['sl_companypk']);

        $sCompany = '<div class="cp_ns_row">
            <div class="cp_quality qlt_'.$asCpData['level_letter'].'">'.$asCpData['level_letter'].'</div>
            <div class="cp_id">#'.$asCpData['sl_companypk'].'</div>
            <div class="cp_name"><a href="javascript:;" onclick="popup_candi(this, \''.$sURL.'\');">'.$asCpData['name'].'('.$asCpData['level'].')</div>
            <div class="cp_consultant">'.$oLogin->getUserLink((int)$asCpData['created_by']).'</div>
            <div class="cp_update">'.substr($asCpData['date_updated'], 0, 10).'&nbsp;</div>
            <div class="cp_employee">'.$asCpData['num_employee'].'&nbsp;</div>
          </div>';


        $asCompany[$sFirstLetter][] = $sCompany;
        $asLetters[$sFirstLetter] = $oHTML->getLink($sFirstLetter, '#'.$sFirstLetter);

        $nCount++;
        $bRead = $oDbResult->readNext();
      }

      $sHTML = $oHTML->getTitle($nCount.' Companies in the list', 'h3', true);

        $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_COMP);
        $sHTML.= $oHTML->getBlocStart('',  array('style' => 'line-height: 20px; font-style: italic; color: #777;'));
        $sHTML.= 'Filter by company level&nbsp;&nbsp;<select onchange="document.location.href = $(this).val();" >';
        $sHTML.= '<option value="'.$sURL.'" > - </option>';
        $sHTML.= '<option value="'.$sURL.'&filter_level=1" '.(($nLevel == 1)? 'selected="selected"' : '').'> A </option>';
        $sHTML.= '<option value="'.$sURL.'&filter_level=2" '.(($nLevel == 2)? 'selected="selected"' : '').'> B </option>';
        $sHTML.= '<option value="'.$sURL.'&filter_level=3" '.(($nLevel == 3)? 'selected="selected"' : '').'> C </option>';
        $sHTML.= '</select>';
        $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getCR();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'ns_list_container'));

        $asTabs = array();
        foreach($asCompany as $sLetter => $asCompany)
        {
          $sBlock = $oHTML->getBlocStart('', array('class' => 'ns_list_block'));
          $sBlock.= $oHTML->getTitle($sLetter.' ('.count($asCompany).')', 'h3', true);

          $sBlock.= '<div class="cp_ns_row header">
            <div class="cp_quality">Level</div>
            <div class="cp_id">refId</div>
            <div class="cp_name">Company name</div>
            <div class="cp_consultant">Lead consultant</div>
            <div class="cp_update">Last update</div>
            <div class="cp_employee">Nb employee</div>
          </div>';
          $sBlock.= implode('', $asCompany);
          $sBlock.= $oHTML->getFloatHack();
          $sBlock.= $oHTML->getLink('back to top &uarr;', 'javascript:;', array('onclick' => '$(this).closest(\'.scrollingContainer\').animate({scrollTop: 0}, 450);'));
          $sBlock.= $oHTML->getBlocEnd();

          $asTabs[] = array('label' => 'tab_'.$sLetter, 'title' => $sLetter, 'content' => $sBlock);
        }

      $sHTML.= $oHTML->getTabs('ns_list', $asTabs, '', 'inline', true);
      $sHTML.= $oHTML->getBlocEnd();

      return $sHTML;
    }

    //------------------------------------------------------
    //  Public methods
    //------------------------------------------------------

    /**
     * return an array with all the candidate profile data
     * @param integer $pnPk
     * @param boolean $pbFullProfile
     * @return array()
     */
    public function getCandidateData($pnPk, $pbFullProfile = false)
    {
      if(!assert('is_key($pnPk) && is_bool($pbFullProfile)'))
        return array();

      return $this->_getModel()->getCandidateData($pnPk, $pbFullProfile);
    }


    /**
     * Update candidate data and update profile status if requested
     *
     * @param array $asUpdateData
     * @param integer $pnCandidatePk
     * @param boolean $pbUpdateStatus
     * @return boolean
     */
    public function quickUpdateProfile($asUpdateData, $pnCandidatePk, $pbUpdateStatus = false)
    {
      if(!assert('is_key($pnCandidatePk) && is_array($asUpdateData) && !empty($asUpdateData)'))
        return false;


      $vResult = $this->_getModel()->update($asUpdateData, 'sl_candidate_profile', 'candidatefk = '.$pnCandidatePk);

      // if company changed, or active positions... need to refresh all statuses
      if($pbUpdateStatus)
        $this->updateCandidateProfile($pnCandidatePk);

      return $vResult;
    }













    private function _getRmList($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => 'Sorry, an error occured.');

      $sHTML = $this->_oDisplay->getBlocStart('', array('style' => 'float: right; padding: 3px 5px; background-color: #f0f0f0; border-color: #4C7696; color: #4C7696;'));
        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_CANDIDATE_TYPE_RM, $pnCandidatePk);
        $sHTML.= '<a href="javascript:;" onclick="AjaxRequest(\''.$sURL.'\')">make me RM</a>';
        $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getFloathack();

      $sHTML.= $this->_oDisplay->getTitle('RM list', 'h3', true);


      $oDbResult = $this->_getModel()->getByWhere('sl_candidate_rm', 'candidatefk = '.$pnCandidatePk, '*', 'date_expired ASC,  date_started ASC');
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        $sHTML.= 'No RM for this candidate';
        return array('data' => $sHTML);
      }



      $oLogin = CDependency::getCpLogin();
      $bIsAdmin = $oLogin->isAdmin();
      $nCurrentUserPk = $oLogin->getUserPk();
      $sPic = $this->_oDisplay->getPicture($this->getResourcePath().'/pictures/delete_16.png', 'cancel RM');
      $sPic2 = $this->_oDisplay->getPicture($this->getResourcePath().'/pictures/reload_16.png', 'extend RM period');
      $this->_oPage->addCssFile($this->getResourcePath().'css/rm.css');

      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'rm_container'));

      //List header
      $sRow = $this->_oDisplay->getBloc('', 'Status', array('class' => 'rm_status'));
      $sRow.= $this->_oDisplay->getBloc('', 'User', array('class' => 'rm_user'));
      $sRow.= $this->_oDisplay->getBloc('', 'Start', array('class' => 'rm_start'));
      $sRow.= $this->_oDisplay->getBloc('', 'End', array('class' => 'rm_end'));
      $sRow.= $this->_oDisplay->getBloc('', 'Actions', array('class' => 'rm_end'));
      $sHTML.= $this->_oDisplay->getBloc('', $sRow, array('class' => 'rm_row rm_header'));


      while($bRead)
      {
        $nLoginfk = (int)$oDbResult->getFieldValue('loginfk');
        $sStart = date('d M', strtotime($oDbResult->getFieldValue('date_started')));
        $sEnd = date('d M', strtotime($oDbResult->getFieldValue('date_ended')));
        $sAction = '&nbsp;';

        if($oDbResult->getFieldValue('date_expired'))
        {
          $sRow = $this->_oDisplay->getBloc('', 'expired', array('class' => 'rm_status'));
        }
        else
        {
          $sRow = $this->_oDisplay->getBloc('', 'active', array('class' => 'rm_status'));

          if($bIsAdmin || $nLoginfk == $nCurrentUserPk)
          {
            $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_RM, $pnCandidatePk, array('loginfk' => $nLoginfk));
            $sAction = '<a href="javascript:;" onclick="AjaxRequest(\''.$sURL.'\');">'.$sPic2.'</a>&nbsp;&nbsp; ';

            $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_CANDIDATE_TYPE_RM, $pnCandidatePk, array('loginfk' => $nLoginfk));
            $sAction.= '<span class="cancel_rm"><a href="javascript:;" onclick="AjaxRequest(\''.$sURL.'\');">'.$sPic.'</a>';
          }
        }

        $sRow.= $this->_oDisplay->getBloc('', $oLogin->getuserLink($nLoginfk), array('class' => 'rm_user'));
        $sRow.= $this->_oDisplay->getBloc('', $sStart, array('class' => 'rm_start'));
        $sRow.= $this->_oDisplay->getBloc('', $sEnd, array('class' => 'rm_end'));
        $sRow.= $this->_oDisplay->getBloc('', $sAction, array('class' => 'rm_end'));


        $sHTML.= $this->_oDisplay->getBloc('', $sRow, array('class' => 'rm_row'));
        $bRead = $oDbResult->readNext();
      }
      $sHTML.= $this->_oDisplay->getBloc('', '&nbsp;', array('class' => 'rm_row'));
      $sHTML.= $this->_oDisplay->getFloatHack();
      $sHTML.= $this->_oDisplay->getBlocEnd();
      return array('data' => $sHTML);

    }


    private function _cancelCandidateRm($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => 'Sorry, an error occured.');

      $nLoginFk = (int)getValue('loginfk', 0);
      if(!is_key($nLoginFk))
         return array('error' => 'Sorry, an error occured.');


      //check if not already RM
      $sWhere = 'candidatefk = '.$pnCandidatePk.' AND loginfk = '.$nLoginFk.' AND date_expired IS NULL ';
      $asData = array('date_expired' => date('Y-m-d H:i:s'));

      $bUpdated = $this->_getModel()->update($asData, 'sl_candidate_rm', $sWhere);
      if($bUpdated)
        return array('notice' => 'Rm candelled', 'action' => 'goPopup.removeByType(\'layer\'); $(\'#rm_link_id\').click(); ');

      return array('error' => 'Sorry, an error occured.');
    }


    private function _addCandidateRm($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => 'Sorry, an error occured.');

      $oLogin = CDependency::getCpLogin();
      $nCurrentUserPk = $oLogin->getUserPk();

      //check if not already RM
      $sWhere = 'candidatefk = '.$pnCandidatePk.' AND loginfk = '.$nCurrentUserPk.' AND date_expired IS NULL ';
      $oDbResult = $this->_getModel()->getByWhere('sl_candidate_rm', $sWhere);
      $bRead = $oDbResult->readFirst();
      if($bRead)
        return array('message' => 'You are already RM for this candidate.');


      //add current user as RM
      $asData = array('loginfk' =>  $oLogin->getUserPk(), 'candidatefk' => $pnCandidatePk, 'date_started' => date('Y-m-d H:i:s'),
          'date_ended' => date('Y-m-d', strtotime('+3 month')).' 00:00:00') ;

      $nPk = $this->_getModel()->add($asData, 'sl_candidate_rm');
      if($nPk > 0)
        return array('notice' => 'You have been added as RM.', 'action' => 'goPopup.removeByType(\'layer\'); $(\'#rm_link_id\').click();');

      return array('error' => 'Sorry, an error occured.');
    }

    private function _extendCandidateRm($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => 'Sorry, an error occured.');

      $nLoginFk = (int)getValue('loginfk', 0);
      if(!is_key($nLoginFk))
         return array('error' => 'Sorry, an error occured.');

      //check if not already RM
      $sWhere = 'candidatefk = '.$pnCandidatePk.' AND loginfk = '.$nLoginFk.' AND date_expired IS NULL ';
      $asData = array('date_ended' => date('Y-m-d H:i:s', strtotime('+3 month')), 'nb_extended' => 'nb_extended+1');

      $bUpdated = $this->_getModel()->update($asData, 'sl_candidate_rm', $sWhere);
      if($bUpdated)
        return array('notice' => 'Rm renewd. <br/>Following the candidate until '.date('d M Y', strtotime('+3 month')), 'action' => 'goPopup.removeByType(\'layer\'); $(\'#rm_link_id\').click(); ');

      return array('error' => 'Sorry, an error occured.');
    }


    private function _manageRmExpiration($pbForce = false)
    {
      $oSetting = CDependency::getComponentByName('settings');
      $asSetting = $oSetting->getSystemSettings('notify_rm');

      $nWeek = date('W');
      if($pbForce || empty($asSetting['notify_rm']) || $asSetting['notify_rm'] < $nWeek)
      {
        //fetch expiring RMs
        $sNow = date('Y-m-d').' 00:00:00';
        $sExpireDate = date('Y-m-d', strtotime('+15 days'));

        $sQuery = 'SELECT scrm.*, slog.*, CONCAT(scan.firstname, " ", scan.lastname) as candidate  FROM sl_candidate_rm as scrm
          INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = scrm.candidatefk)
          INNER JOIN shared_login as slog ON (slog.loginpk = scrm.loginfk)
          WHERE (scrm.date_expired IS NULL OR scrm.date_expired = "")
          AND scrm.date_ended <= "'.$sExpireDate.'"
            ';  // AND slog.status > 0

        $oDbResult = $this->_getModel()->executeQuery($sQuery);
        $bRead = $oDbResult->readFirst();
        if(!$bRead)
        {
          echo 'CRON RM notification - no rm notfication to send';
        }
        else
        {
          $asUserNotification = array();
          $anExpired = array();
          $asUserData = array();
          while($bRead)
          {
            $nLoginFk = (int)$oDbResult->getFieldValue('loginfk');
            $nUserStatus = (int)$oDbResult->getFieldValue('status');

            if($nUserStatus > 0)
            {
              $asUserData[$nLoginFk]['email'] = $oDbResult->getFieldValue('email');
              $asUserData[$nLoginFk]['name'] = $oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');
              $asUserData[$nLoginFk]['firstname'] = $oDbResult->getFieldValue('firstname');

              $nCandidatefk = (int)$oDbResult->getFieldValue('candidatefk');
              $sURL = $this->_oPage->getUrl('sl_candidate', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatefk);
              $sDate = substr($oDbResult->getFieldValue('date_ended'), 0, 10);
              $asUserNotification[$nLoginFk][$nCandidatefk] = 'expires on the '.$sDate.' for candidate <a href="'.$sURL.'">#'.$nCandidatefk.' - '.$oDbResult->getFieldValue('candidate').'</a>';
            }

            //set expired all reminders for inactive users and the ones which really expired this today
            if($nUserStatus == 0 || $oDbResult->getFieldValue('date_ended') < $sNow)
              $anExpired[] = (int)$oDbResult->getFieldValue('sl_candidate_rmpk');

            $bRead = $oDbResult->readNext();
          }

          $oMail = CDependency::getComponentByName('mail');
          foreach($asUserNotification as $nUser => $asRows)
          {
            $oMail->createNewEmail();
            $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);

            $oMail->addRecipient($asUserData[$nUser]['email'], $asUserData[$nUser]['name']);

            $sSubject = 'Sl[i]stem - RM expiration';
            $sContent = 'Dear '.$asUserData[$nUser]['firstname'].', <br /><br />
              Your RM status will expire soon for '.count($asRows).' candidate(s) you are following. See below the details.<br /><br />
              <div style="border: 1px solid #ddd; margin: 5px 10px; padding: 15px; line-height: 20px;">'.implode('<br />', $asRows).'</div>';

            $oMail->send($sSubject, $sContent);
          }


          if(!empty($anExpired))
          {
            dump('rm exipiring now or user inactive:<br /> '. implode(',', $anExpired));
            $asUpdate = array('date_expired' => date('Y-m-d H:i:s'));
            $this->_getModel()->update($asUpdate, 'sl_candidate_rm', 'sl_candidate_rmpk IN ('.implode(',', $anExpired).')');
          }
        }

        $oSetting->setSystemSettings('notify_rm', $nWeek);
      }
      else
        echo 'CRON RM notification already sent today';

      return true;
    }

    private function _getMergeForm($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => 'Wrong parameters');

      $nManualTarget = (int)getValue('target');

      $sHTML = $this->_oDisplay->getTitle('Duplicates for candidate of #'.$pnCandidatePk, 'h3', true);
      $sHTML.= $this->_oDisplay->getCR(2);


      $oDbResult = $this->_getModel()->getDuplicate($pnCandidatePk, $nManualTarget);


      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        $sHTML.= '<span style="font-size: 15px; color: green; ">&rarr; No duplicate found for this candidate.</span><br /><br />';
      }
      else
      {
        while($bRead)
        {
          $asData = $oDbResult->getData();

          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);
          $sHTML.= '#<a href="javascript:;"  onclick="popup_candi(this, \''.$sURL.'\');" >'.$asData['sl_candidatepk'].' - '.$asData['lastname'].' '.$asData['firstname'].'</a><br />';
          $sHTML.= 'Working at '.$asData['company_name'].'';

          $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_TRANSFER, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk, array('merge_to' => $asData['sl_candidatepk']));
          $sHTML.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="javascript:;" onclick="if(window.confirm(\'Are you sure you want to merge #'.$pnCandidatePk.' data to this profile (#'.$asData['sl_candidatepk'].') ?\'))
            {
              AjaxRequest(\''.$sURL.'\');
            }
            ">-=[ Merge on this candidate profile ]=-</a><br /><br />';
          $bRead = $oDbResult->readNext();
        }
      }

      $sHTML.= $this->_oDisplay->getCR(1);

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_MANAGE, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);
      $sHTML.= 'Looking for a specific duplicate ? You can manually input a refId here: <br /><br />
        <input type="text" id="lookForDup" />&nbsp;&nbsp;
        <a href="javascript:;" onclick="
        var nRefId = $(\'#lookForDup\').val();
        if(nRefId.trim().length < 2)
          return alert(\'RefId is wrong\');

        goPopup.removeLastByType(\'layer\');

        var oConf = goPopup.getConfig();
        oConf.width = 1080;
        oConf.height = 725;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'&target=\'+nRefId);

        " > >> Search</a>';

      $sHTML.= $this->_oDisplay->getCR(4);
      $sHTML.= $this->_oDisplay->getTitle('Delete candidate', 'h3', true);

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_TRANSFER, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);
      $sHTML.= '<div style="font-size: 15px;">
        <br />No duplicates, empty or useless profile... <br />
        <a href="javascript:;" style="font-size: 15px;"
        onclick="
        if(window.confirm(\'Are you sure you want to delete this candidate ?\'))
        {
          AjaxRequest(\''.$sURL.'\');
        }
        ">Do you want to <span style="color: #A72A19; font-size: 15px;">delete this candidate</span> ?</a>
        </div>';

      return array('data' => $sHTML);
    }


    private function _mergeDeleteCandidate($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return array('error' => __LINE__.' - Wrong parameters');

      $asCandidate = $this->_getModel()->getCandidateData($pnCandidatePk, true);
      if(empty($asCandidate))
        return array('error' => __LINE__.' - Could not find the candidate.');

      if($asCandidate['_in_play'])
        return array('error' => __LINE__.' - The candidate is in play. Update position(s) status before deleting.');


      $nTarget = (int)getValue('merge_to');
      if(!empty($nTarget))
      {
        $asTarget = $this->_getModel()->getCandidateData($nTarget, true);
        if(empty($asTarget))
          return array('error' => __LINE__.' - Could not find the target candidate.');
      }


      // - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - --
      // - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - --
      //merge with nothing ==> simple delete

      if(empty($nTarget))
      {
        $asData = array('_sys_status' => 1, '_sys_redirect' => NULL, '_date_updated' => date('Y-md H:i:s'));
        $this->_getModel()->update($asData, 'sl_candidate', 'sl_candidatepk = '.$pnCandidatePk);

        $sUrl = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $pnCandidatePk);
        return array('notice' => 'Candidate has been deleted.', 'action' => 'goPopup.removeLastByType(\'layer\'); view_candi(\''.$sUrl.'\');');
      }


      // - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - --
      // - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - -- - -- - - - --
      //move all data across


      //load a genric model to update multi component
      $oModel = new CModel(true);
      $asSummary = array();

      //1. move reminders / dba req / notifications
      $asData = array('cp_pk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'notification_link', 'cp_uid = "555-001" AND cp_action = "ppav" AND cp_type = "candi" AND cp_pk = '.$pnCandidatePk, true);
      $asSummary['reminders'] = $oDbResult->getFieldValue('_affected_rows');

      //2. move meetings
      $asData = array('candidatefk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'sl_meeting', 'candidatefk = '.$pnCandidatePk, true);
      $asSummary['meetings'] = $oDbResult->getFieldValue('_affected_rows');


      //3. move positions_link (for history)
      $asData = array('candidatefk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'sl_position_link', 'candidatefk = '.$pnCandidatePk, true);
      $asSummary['positions'] = $oDbResult->getFieldValue('_affected_rows');

      //4. documents
      $asData = array('cp_pk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'document_link', 'cp_uid = "555-001" AND cp_action = "ppav" AND cp_type = "candi" AND cp_pk = '.$pnCandidatePk, true);
      $asSummary['documents'] = $oDbResult->getFieldValue('_affected_rows');

      //5. contact
      $asData = array('itemfk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'sl_contact', 'item_type = "candi" AND itemfk = '.$pnCandidatePk, true);
      $asSummary['contacts'] = $oDbResult->getFieldValue('_affected_rows');

      //6. attribute
      $asData = array('itemfk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'sl_attribute', 'type LIKE "candi%" AND itemfk = '.$pnCandidatePk, true);
      $asSummary['attributes'] = $oDbResult->getFieldValue('_affected_rows');

      //7. RM
      $asData = array('candidatefk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'sl_candidate_rm', 'candidatefk = '.$pnCandidatePk, true);
      $asSummary['rm'] = $oDbResult->getFieldValue('_affected_rows');

      //8. notes
      $asData = array('cp_pk' => $nTarget);
      $oDbResult = $oModel->update($asData, 'event_link', 'cp_uid = "555-001" AND cp_action = "ppav" AND cp_type = "candi" AND cp_pk = '.$pnCandidatePk, true);
      $asSummary['notes'] = $oDbResult->getFieldValue('_affected_rows');

      //9. add note summura, copy UID
      $oEvent = CDependency::getComponentByName('sl_event');
      $sNote = 'The candidate #'.$pnCandidatePk.' has been merge on this candidate profile.<br />';
      $sNote.= 'All data have been moved accross, previous UID : '.$asCandidate['uid'].'<br />';

      foreach($asSummary as $sType => $nUpdate)
        $sNote.= '-> #'.$nUpdate.' '.$sType.' transfered<br />';

      $oEvent->addNote($nTarget, 'merge_summary', $sNote);


      $asData = array('_sys_status' => 2, '_sys_redirect' => $nTarget, '_date_updated' => date('Y-md H:i:s'));
      $this->_getModel()->update($asData, 'sl_candidate', 'sl_candidatepk = '.$pnCandidatePk);

      $sUrl = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nTarget);
      return array('notice' => 'Candidate has been merged with #'.$pnCandidatePk.'.', 'action' => 'goPopup.removeLastByType(\'layer\'); view_candi(\''.$sUrl.'\');');
    }

    private function _customLogUpdate($pasOldData, $pasNewData)
    {
      $asBLFields = array('updated_by', 'date_updated', 'sl_candidatepk', '	date_created', '_sys_status', '_sys_redirect',
          'currency', 'currency_rate', 'salary_search', '	_has_doc', '	_in_play', '	_date_updated', 'uid', 'rating');

      $asProfessional = array('companyfk' => 'company', 'industryfk' => 'industry', '	occupationfk' => 'occupation',
          'title' => 'title', 'department' => 'department', 'salary' => 'salary', 'bonus' => 'bonus', 'target_low' => 'target salary from'
          , 'target_high' => 'target salary to');

      $asStatus = array('statusfk' => 'status', 'play_for' => 'playing for', 'play_date' => 'playing date',
          'is_client' => 'is a client', 'is_collaborator' => 'is a collaborator');

      $asPersonal = array('sex' => 'gender', 'firstname' => 'firstname', 'lastname' => 'lastname',
          'nationalityfk' => 'nationality', 'languagefk' => 'language',
          'locationfk' => 'location', 'languagefk' => 'language', 'grade' => 'grade', 'keyword' => 'keywords',
          'date_birth' => 'birthday', 'is_birth_estimation' => 'birthday',
          'cpa' => 'cpa', 'mba' => 'mba', 'skill_ag' => 'AG', 'skill_ap' => 'AP', 'skill_am' => 'AM', 'skill_mp' => 'MP'
           ,'skill_in' => 'IN', 'skill_ex' => 'EX', 'skill_fx' => 'FX', 'skill_ch' => 'CH', 'skill_ed' => 'ED', 'skill_pl' => 'PL', 'skill_e' => 'e');

      $asLog = array('Business data' => array(), 'Status' => array(), 'Personal data' => array());
      foreach($pasOldData as $sField => $vValue)
      {
        //ignore black listed fields
        if(!in_array($sField, $asBLFields))
        {
          if($vValue === 'null' || $vValue == '0000-00-00 00:00:00')
            $vValue = null;

          if($pasNewData[$sField] === 'null' || $pasNewData[$sField] == '0000-00-00 00:00:00')
            $pasNewData[$sField] = null;

          //we can have different version of empty 0, null, ''
          if( (empty($vValue) && empty($pasNewData[$sField])) || $vValue == $pasNewData[$sField])
          {
            //nothing to do
          }
          else
          {
            if(isset($asProfessional[$sField]))
            {
              $sType = 'Business data';
              $sLabel = $asProfessional[$sField];
            }
            elseif(isset($asStatus[$sField]))
            {
              $sType = 'Status';
              $sLabel = $asStatus[$sField];
            }
            else
            {
              $sType = 'Personal data';
              if(isset($asPersonal[$sField]))
                $sLabel = $asPersonal[$sField];
              else
                $sLabel = ' - ';
            }


            if(empty($vValue) && !empty($pasNewData[$sField]))
            {
              //dump('new data added [prev: '.var_export($vValue, true).']--> '.$sField.' = '.var_export($pasNewData[$sField], true));
              $asLog[$sType][] = '['.$sLabel.'] has been added : '.$pasNewData[$sField];
            }
            else
            {
              //dump('edit data [prev: '.var_export($vValue, true).']--> '.$sField.' = '.var_export($pasNewData[$sField], true));
              $asLog[$sType][] = '['.$sLabel.'] changed from '.$vValue.' -> to: '.$pasNewData[$sField];
            }
          }
        }
      }

      if(!empty($asLog))
      {
        //dump($asLog);
        $pasOldData['log_detail'] = '';
        foreach($asLog as $sType => $asLogs)
        {
          if(!empty($asLogs))
            $pasOldData['log_detail'].= '<span class="log-title">'.$sType.'</span><span class="log-desc">'.implode('<br />', $asLogs).'</span><br />';
        }


         //dump($pasOldData);
        $sTitle = 'candidate #'.$pasOldData['sl_candidatepk'].' has been updated.';
        $this->_getModel()->_logChanges($pasOldData, 'sl_candidate', $sTitle);
      }
    }
}