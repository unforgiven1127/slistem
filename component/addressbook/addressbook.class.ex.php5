<?php

require_once('component/addressbook/addressbook.class.php5');

class CAddressbookEx extends CAddressbook
{
  private $casCompanyCache = array();
  private $coPage = null;
  private $coHTML = null;

  public function __construct()
  {
    $this->coPage = CDependency::getCpPage();
    $this->coHTML = CDependency::getCpHtml();
    return true;
  }

  public function getDefaultType()
  {
    return CONST_AB_TYPE_CONTACT;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_LIST;
  }

  //====================================================================
  //  accessors
  //====================================================================

  //====================================================================
  //  interfaces
  //====================================================================

  public function declareUserPreferences()
  {
    $oMngList = CDependency::getComponentByName('manageablelist');
    $aOptionsCp = $oMngList->getListValues('company_tabs');
    $aPrefs[] = array(
        'fieldname' => 'company_tabs',
        'fieldtype' => 'sortable',
        'options' => $aOptionsCp,
        'label' => 'Company tabs display',
        'description' => 'Which tabs do you want to display on company profile page ?',
        'value' => 'a:5:{i:0;s:13:"cp_tab_detail";i:1;s:18:"cp_tab_opportunity";i:2;s:15:"cp_tab_employee";i:3;s:15:"cp_tab_document";i:4;s:12:"cp_tab_event";}'
    );

    $aOptionsCt = $oMngList->getListValues('contact_tabs');
    $aPrefs[] = array(
        'fieldname' => 'contact_tabs',
        'fieldtype' => 'sortable',
        'options' => $aOptionsCt,
        'label' => 'Contact tabs display',
        'description' => 'Which tabs do you want to display on contact profile page ?',
        'value' => 'a:5:{i:0;s:13:"ct_tab_detail";i:1;s:14:"ct_tab_profile";i:2;s:15:"ct_tab_coworker";i:3;s:12:"ct_tab_event";i:4;s:15:"ct_tab_document";}'
    );

    return $aPrefs;
  }

  public function getSearchFields($psType = '')
  {
    $asFields = array(
          CONST_AB_TYPE_COMPANY => array(
              'table' => 'addressbook_company',
              'label' => 'Companies',
              'fields' => array(
                  'text' => array('company_name', 'comments'),
                  'address' => array('address_1', 'address_2'),
                  'date' => array('date_create')
                  )
              ),
          CONST_AB_TYPE_CONTACT => array(
            'table' => 'addressbook_contact',
            'label' => 'Connexions',
            'fields' => array(
                'text' => array('firstname', 'lastname', 'comments'),
                'email' => array('email')
                )
              )
        );

    if(isset($asFields[$psType]))
      return $asFields[$psType];

    return $asFields;
  }

  public function getSearchResultMeta($psType = '')
  {
    $asResultMeta =
    array(
        CONST_AB_TYPE_COMPANY =>
        array(
            'pk' => 'addressbook_companypk',
            'title' => '%company_name%',
            'excerpt' => '%comments%',
            'more-data' => 'Address : %address_1% %address_2% | Telephone : %phone% | Created on : %date_create%'
        ),
        CONST_AB_TYPE_CONTACT =>
        array(
            'pk' => 'addressbook_contactpk',
            'title' => '%courtesy% %lastname% %firstname% ',
            'excerpt' => '%comments%',
            'more-data' => 'Phone : %phone% | Telephone : %cellphone% | Email : %email%'
        )
    );

    if(!empty($asResultMeta[$psType]))
      return $asResultMeta[$psType];

    return $asResultMeta;
  }


  /**
   * Return an array that MUST contain 4 fields: label. description, url, link
   * @param variant $pvItemPk (integer or array of int)
   * @param string $psAction
   * @param string $psItemType
   * @return array of string
  */
  public function getItemDescription($pvItemPk, $psAction = '', $psItemType = 'cp')
  {
    if(!assert('is_arrayOfInt($pvItemPk) || is_key($pvItemPk)'))
      return array();

    if(!assert('!empty($psItemType)'))
      return array();

    switch($psItemType)
    {
      case 'ct':
      case 'cp':

        if(is_key($pvItemPk))
          $aIds = array($pvItemPk);
        else
          $aIds = $pvItemPk;

        $asItem = array();
        foreach($aIds as $nPk)
        {
          //$sName = $this->getItemName($psItemType, $nPk);
          $asItem = $this->getItemCardByPk($psItemType, $nPk);

          if(!empty($asItem))
          {
            $asItem[$nPk] = $asItem;
            $asItem[$nPk]['label'] = $asItem['item_label'];
            $asItem[$nPk]['url'] = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, $psItemType, $nPk);
            $asItem[$nPk]['link'] = $this->coHTML->getLink($asItem[$nPk]['label'], $asItem[$nPk]['url']);

            /*$asDesc = array();
            $asDesc[0] = 'RefId : #<a href="'.$asItem[$nPk]['url'].'">'.$nPk.'</a>';*/
            $asItem[$nPk]['description'] = $asItem['html'];
          }
        }
        break;

      default:
        assert('false; // unknown type');
        return array();
        break;
    }

    return $asItem;
  }

  public function getSearchResult($psDatatype, $psKeywords, $psFieldType = 'all', $pnDisplayPage=0)
  {
    if(!assert('is_string($psKeywords) && !empty($psKeywords)'))
      return array();

    if(!assert('is_integer($pnDisplayPage)'))
      return array();

    $psKeywords = trim($psKeywords);

    $aSearchFields = $this->getSearchFields();

    if(!assert('is_array($aSearchFields[$psDatatype]) && !empty($aSearchFields[$psDatatype])'))
      return array();

    // Skiping search is the field type asked doesnt exist in fields
    if (($psFieldType!='all') && (!isset($aSearchFields[$psDatatype]['fields'][$psFieldType])))
      return array('total' => 0);

    switch($psFieldType)
    {
      case 'all':

        $nCount=0;
        $sWhere = '';
        foreach ($aSearchFields[$psDatatype]['fields'] as $aFieldType) {
          foreach($aFieldType as $sField)
          {
            if($nCount>0)
              $sWhere .= ' OR ';

            $sWhere .= $sField.' LIKE \'%'.$psKeywords.'%\'';
            $nCount++;
          }
        }
        break;

       default:

        $nCount=0;

        $sWhere = '';
        foreach ($aSearchFields[$psDatatype]['fields'][$psFieldType] as $sField)
        {
          if($nCount>0)
            $sWhere .= ' OR ';

          $sWhere .= $sField.' LIKE \'%'.$psKeywords.'%\'';
          $nCount++;
        }

        break;
    }

    $aResults = array();
    if($pnDisplayPage==0)
    {
      $oCountResult = $this->_getModel()->getByWhere($aSearchFields[$psDatatype]['table'], $sWhere, 'COUNT(*) as total', 'date_create DESC');
      $aResults = $oCountResult->getData();
    }

    $oResult = $this->_getModel()->getByWhere($aSearchFields[$psDatatype]['table'], $sWhere, '*', 'date_create DESC', ($pnDisplayPage*10).',5');
    $aResults['results'] = $oResult->getRawData();

    return $aResults;
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $oRight = CDependency::getComponentByName('right');

    $asActions = array();
    $sAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_DELETE,$this->getType(),0);

    switch($psType)
    {
      case CONST_AB_TYPE_CONTACT:

        $sPictureMenuPath = $this->getResourcePath().'/pictures/menu/';

        switch($psAction)
        {
          case CONST_ACTION_VIEW:
            if(!empty($pnPk))
            {
              $oDbResult = $this->_getModel()->getContactData($pnPk);
              $asParam = array('cppk' => (int)$oDbResult->getFieldvalue('companyfk'));

              $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'ct_list_32.png','title'=>'Back to the connection list', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
              $asActions['ppaa'][] = array('picture' => $sPictureMenuPath.'ct_add_32.png','title'=>'Add connection', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, $asParam));
              $asActions['ppae'][] = array('picture' => $sPictureMenuPath.'ct_edit_32.png','title'=>'Edit this connection', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_EDIT, CONST_AB_TYPE_CONTACT, $pnPk));
              if($sAccess)
                $asActions['ppad'][] = array('picture' => $sPictureMenuPath.'ct_delete_32.png','title'=>'Delete connection', 'url' => $this->coPage->getAjaxUrl($this->_getUid(), CONST_ACTION_DELETE, CONST_AB_TYPE_CONTACT, $pnPk), 'option' => array('onclick' => "if(!window.confirm('You are about to permanently delete this connection with all its linked data. \\nDo you really want to proceed ?')){ return false; }"));
            }
            break;

            case CONST_ACTION_FULL_LIST:
            case CONST_ACTION_LIST:
              $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'ct_list_32.png','title'=>'Connections List', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
              $asActions['ppaa'][] = array('picture' => $sPictureMenuPath.'ct_add_32.png','title'=>'Add Connection', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT));

              $sSearchId = getValue('searchId');
              $sURL = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT, 0, array('searchId' => $sSearchId, 'sortfield' => 'id'));
              $asActions['ppaasort'][] = array('picture' => $sPictureMenuPath.'list_sort_desc_32.png','title'=>'Sort by date', 'url' => 'javascript:;', 'option' => array('onclick'=> ' AjaxRequest(\''.$sURL.'\', \'body\', \'\', \'contactListContainer\'); '));
              $asActions['ppas'][] = array('picture' => CONST_PICTURE_MENU_SEARCH, 'title' => 'Search connections', 'url' => 'javascript:;', 'option' => array('onclick' => '$(\'.searchContainer\').fadeToggle();') );
              break;

            case CONST_ACTION_ADD:
              $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'ct_list_32.png','title'=>'Connections List', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
              break;

            case CONST_ACTION_EDIT:
              $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'ct_list_32.png','title'=>'Connections List', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
              if(!empty($pnPk))
              {
                $asActions['ppav'][] = array('picture' => $sPictureMenuPath.'ct_view_32.png','title'=>'View connection', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
              }
              break;

            default: break;
        }
      break;

      case CONST_AB_TYPE_COMPANY:

        $sPictureMenuPath = $this->getResourcePath().'/pictures/menu/';

        //always displayed: list, add
        $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'cp_list_32.png', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY),'title'=>'Back to the company list');
        $asActions['ppaa'][] = array('picture' => $sPictureMenuPath.'cp_add_32.png', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_AB_TYPE_COMPANY),'title'=>'Add company');

        switch($psAction)
        {
          case CONST_ACTION_VIEW:
            if(!empty($pnPk))
            {
              if($sAccess)
                $asActions['ppad'][] = array('picture' => $sPictureMenuPath.'cp_delete_32.png', 'url' => $this->coPage->getAjaxUrl($this->_getUid(), CONST_ACTION_DELETE, CONST_AB_TYPE_COMPANY, $pnPk ),'title'=>'Delete company','option' => array('onclick' => 'if(!window.confirm(\'Delete this company ?\')){ return false; }'));
              $asActions['ppae'][] = array('picture' => $sPictureMenuPath.'cp_edit_32.png', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_EDIT, CONST_AB_TYPE_COMPANY,$pnPk),'title'=>'Edit this company');
              $asActions['ppaa'][] = array('picture' => $sPictureMenuPath.'ct_add_32.png', 'url' => $this->coPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, array('cppk' => $pnPk)),'title'=>'Add a connection to this company');
            }
            break;

          case CONST_ACTION_FULL_LIST:
          case CONST_ACTION_LIST:
            $sSearchId = getValue('searchId');
            $sURL = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY, 0, array('searchId' => $sSearchId, 'sortfield' => 'id'));
            $asActions['ppaasort'][] = array('picture' => $sPictureMenuPath.'list_sort_desc_32.png','title'=>'Sort by date', 'url' => 'javascript:;', 'option' => array('onclick'=> ' AjaxRequest(\''.$sURL.'\', \'body\', \'\', \'contactListContainer\'); '));
            $asActions['ppas'][] = array('picture' => CONST_PICTURE_MENU_SEARCH, 'title' => 'Search companies', 'url' => 'javascript:;', 'option' => array('onclick' => '$(\'.searchContainer\').fadeToggle();') );
            break;
        }
      break;

      default: break;
    }

    return $asActions;
  }


  public function getComponentPublicItems($psInterface = '')
  {
    $asItem = array();

    $sURL = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_AB_TYPE_CONTACT, 0, array('autocomplete' => 1));
    $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
        CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, 'label' => 'Connection', 'search_url' => $sURL);

    $sURL = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_AB_TYPE_COMPANY);
    $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
        CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, 'label' => 'Company', 'search_url' => $sURL);

    return $asItem;
  }



  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_AB_TYPE_CONTACT:

        switch($this->csAction)
        {
          case CONST_ACTION_SEARCH:
            /* custom json encoding in function for token input selector */
            return $this->_getSelectorContact();
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:
           return json_encode($this->_saveContact($this->cnPk));
            break;

          case CONST_ACTION_DELETE:
           return json_encode($this->_deleteContact($this->cnPk));
            break;

          case CONST_ACTION_TRANSFER:
           return json_encode($this->_getContactTransfer($this->cnPk));
            break;

          case CONST_ACTION_SAVETRANSFER:
           return json_encode($this->_saveContactTransfer($this->cnPk));
            break;

          /*case CONST_ACTION_SAVEMANAGE:
            return json_encode($this->_saveContactProfil($this->cnPk));
            break;*/

          case CONST_ACTION_LIST:
            return json_encode($this->_getAjaxContactSearchResult());
              break;

          case CONST_ACTION_SEARCHDUPLICATES:
            $sFirstname = getValue('firstname', '');
            $sLastname = getValue('lastname', '');
            $sEmail = getValue('email', '');
            $sPhone = getValue('phone', '');
            $sFax = getValue('fax', '');

            $aContent = $this->_checkContactDuplicates($sFirstname, $sLastname, $sEmail, $sPhone, $sFax);

            return json_encode($aContent);

            break;
          }
        break;

      case CONST_AB_TYPE_COMPANY:
        switch($this->csAction)
        {
          case CONST_ACTION_SEARCH:
            /* custom json encoding in function for token input selector */
            return $this->_getSelectorCompany();
            break;

          case CONST_ACTION_ADD:
            return json_encode($this->coPage->getAjaxExtraContent(array('data' => $this->_formCompany(0, true))));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_saveCompany($this->cnPk));
             break;

          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_saveCompanyContact($this->cnPk));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($this->_deleteCompany($this->cnPk));
            break;

          /*case CONST_ACTION_MANAGE:
            return json_encode($this->_getLinkCompanyContact($this->cnPk));
             break;*/

          case CONST_ACTION_TRANSFER:
            return json_encode($this->_getCompanyTransfer($this->cnPk));
             break;

          case CONST_ACTION_SAVETRANSFER:
            return json_encode($this->_saveCompanyTransfer($this->cnPk));
             break;

           case CONST_ACTION_LIST:
            return json_encode($this->_getAjaxCompanySearchResult());
              break;

          case CONST_ACTION_SEARCHDUPLICATES:
            $sEmail = getValue('email', '');
            $sCompanyName = getValue('name', '');
            $sCorporateName = getValue('corporate', '');
            $sAddress = getValue('address_1', '');
            $sPhone = getValue('phone', '');
            $sFax = getValue('fax', '');

            $aContent = $this->_checkCompanyDuplicates($sCompanyName, $sCorporateName, $sAddress, $sPhone, $sFax);

            return json_encode($aContent);

         }

        case CONST_AB_TYPE_COMPANY_RELATION:

          switch($this->csAction)
          {
            case CONST_ACTION_ADD:
            case CONST_ACTION_EDIT:
              return json_encode($this->coPage->getAjaxExtraContent(array('data' => $this->_getLinkCompanyForm($this->cnPk))));
              break;

            case CONST_ACTION_SAVEEDIT:
            case CONST_ACTION_SAVEADD:
              return json_encode($this->_saveCompanyRelation($this->cnPk));
              break;

            case CONST_ACTION_DELETE:
              return json_encode($this->_deleteProfile($this->cnPk));
              break;
          }
          break;
      }
    }

  //====================================================================
  //  public methods
  //====================================================================

  public function getHtml()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_AB_TYPE_CONTACT:

        switch($this->csAction)
        {
          case CONST_ACTION_VIEW:
            return $this->_displayContact($this->cnPk);
             break;

          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return $this->_formContact($this->cnPk);
             break;

          case CONST_ACTION_MANAGE:
            return $this->_displayFormContactLink($this->cnPk);
             break;

          default:
          case CONST_ACTION_LIST:
            return $this->_getContactList();
             break;

           case CONST_ACTION_FULL_LIST:
             return $this->_getContactList('', true);
             break;
        }
        break;

      case CONST_AB_TYPE_DOCUMENT:

      switch($this->csAction)
      {
       case CONST_ACTION_SAVEADD:
         return $this->_saveDocument($this->cnPk);
          break;

        default:
          break;
      }
    case CONST_AB_TYPE_COMPANY:

      switch($this->csAction)
      {
        case CONST_ACTION_VIEW:
         return $this->_displayCompany($this->cnPk);
          break;

        case CONST_ACTION_ADD:
          return $this->_formCompany(0);
           break;

        case CONST_ACTION_EDIT:
         return $this->_formCompany($this->cnPk);
          break;

        case CONST_ACTION_SAVEEDIT:
         return $this->_saveCompany($this->cnPk);
          break;

        default:
        case CONST_ACTION_LIST:
         return $this->_getCompanyList();
          break;

        case CONST_ACTION_FULL_LIST:
          return $this->_getCompanyList('', true);
          break;
      }
    }
  }

  public function getCronJob()
  {
    $this->_sendProspectReminders();
  }

  //====================================================================
  //  generic data access methods
  //====================================================================

  public function getCompanyDataByPk($pvPk)
  {
    if(!assert('!empty($pvPk)'))
      return array();

    if(!assert('is_integer($pvPk) || is_array($pvPk)'))
      return array();

    return $this->_getModel()->getCompanyByPk($pvPk);
  }

  public function getContactDataByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array();

    return $this->_getModel()->getContactData($pnPk)->getData();
  }



  //====================================================================
  //  Component core
  //====================================================================

  /**
   * Save the contact relation with company
   * @param int $pnContactPk
   * @return array message
   */

  private function _saveCompanyRelation($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk) && !empty($pnContactPk)'))
      return array('error' => 'Wrong data provided.');

    $pnCompanyfk = (int)getValue('parent', 0);
    $pnCityfk= getValue('cityfk');
    $pnCountryfk= getValue('countryfk');
    $sEmail = getValue('email');
    $sPhone = getValue('phone');
    $sFax = getValue('fax');
    $sAddress = getValue('address');
    $sPostcode = getValue('postcode');
    $sPosition = getValue('position');
    $sDepartment = getValue('department');

    if(empty($sEmail) && empty($sPhone) && empty($sFax) && empty($sAddress) && empty($pnCompanyfk) && empty($sPosition))
      return array('alert' => 'You have to input at least one value');

    $oDB = CDependency::getComponentByName('database');
    $this->coPage->addCssFile(array($this->getResourcePath().'css/addressbook.css'));

    $nProfilePk = getValue('profilePk');

    if($nProfilePk)
    {
      $sQuery = 'UPDATE addressbook_profile SET email ='.$oDB->dbEscapeString($sEmail).',phone='.$oDB->dbEscapeString($sPhone).',fax='.$oDB->dbEscapeString($sFax).',';
      $sQuery.= 'address_1 = '.$oDB->dbEscapeString($sAddress).',postcode = '.$oDB->dbEscapeString($sPostcode).',cityfk='.$oDB->dbEscapeString($pnCityfk).',department='.$oDB->dbEscapeString($sDepartment).',';
      $sQuery.= 'countryfk='.$oDB->dbEscapeString($pnCountryfk).',companyfk='.$oDB->dbEscapeString($pnCompanyfk).',position='.$oDB->dbEscapeString($sPosition).' WHERE addressbook_profilepk ='.$nProfilePk;

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array();
    }
    else
    {
      $sQuery = 'INSERT INTO addressbook_profile (contactfk,companyfk,email,phone,fax,address_1,postcode,cityfk,countryfk,position,department)';
      $sQuery.= 'VALUES("'.$pnContactPk.'","'.$pnCompanyfk.'",'.$oDB->dbEscapeString($sEmail).','.$oDB->dbEscapeString($sPhone).','.$oDB->dbEscapeString($sFax).','.$oDB->dbEscapeString($sAddress).','.$oDB->dbEscapeString($sPostcode).','.$oDB->dbEscapeString($pnCityfk).','.$oDB->dbEscapeString($pnCountryfk).','.$oDB->dbEscapeString($sPosition).','.$oDB->dbEscapeString($sDepartment).')';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array();
    }

    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnContactPk);
    return array('notice'=>'Profile Information has been updated.', 'timedUrl' => $sURL);
  }

  /**
   * Update the account manager of contact
   * @param integer $pnContactPk
   * @return array
   */

  private function _saveContactTransfer($pnContactPk)
  {
    if(!assert('is_key($pnContactPk)'))
      return array('error' => __LINE__.' - Wrong parameters.');

    $asFollowers  = getValue('account_manager');
    $pnNewFollowerFk = (int)$asFollowers[0];

    if(!assert('is_key($pnContactPk) && is_key($pnNewFollowerFk)' ))
        return array('error' => __LINE__.' - Wrong parameters.');

    $oDbResult = $this->_getModel()->getByPk($pnContactPk, 'addressbook_contact');
    if(!$oDbResult || !$oDbResult->readFirst())
      return array('error' => __LINE__.' - Could not find the connection.');

    $asData = $oDbResult->getData();
    $asData['followerfk'] = (int)$pnNewFollowerFk;
    $asData['date_update'] = date('Y-m-d H:i:s');
    $asData['addressbook_contactpk'] = $pnContactPk;

    $oDbResult = $this->_getModel()->update($asData, 'addressbook_contact');
    if(!$oDbResult)
      return array('error' => __LINE__.' - Could not update the connection.<br />['.$this->_getModel()->getErrors(true).']');

    $this->_getModel()->deleteByFk($pnContactPk, 'addressbook_account_manager', 'contact');

    array_shift($asFollowers);

    foreach($asFollowers as $sManagerPk)
      $this->_getModel()->add(array('contactfk' =>$pnContactPk, 'loginfk' => (int)$sManagerPk), 'addressbook_account_manager');

    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnContactPk);
    return (array('notice'=>'Account Manager has been changed', 'timedUrl' => $sURL));
  }

   /**
   * Update the account manager of company
   * @param integer $pnCompanyPk
   * @return array
   */

  private function _saveCompanyTransfer($pnCompanyPk)
  {
    $asFollowers = getValue('account_manager');
    $sCheckBox = getValue('cascading');
    $nNewFollowerFk = (int)$asFollowers[0];

   if(!assert('is_key($pnCompanyPk) && is_key($nNewFollowerFk)' ))
      return array();

    $this->_getModel()->update(array('followerfk' => $nNewFollowerFk, 'date_update' => date('Y-m-d H:i:s'), 'addressbook_companypk' => $pnCompanyPk), 'addressbook_company');

    array_shift($asFollowers);
    $this->_getModel()->deleteByFk($pnCompanyPk, 'addressbook_account_manager', 'company');

    foreach($asFollowers as $asManagerData)
      $this->_getModel()->add(array('companyfk' =>$pnCompanyPk, 'loginfk' => (int)$asManagerData), 'addressbook_account_manager');

    if($sCheckBox)
    {
      $asQuery = 'SELECT p.contactfk AS contactfk FROM addressbook_profile p, addressbook_contact c  WHERE p.contactfk = c.addressbook_contactpk AND p.companyfk='.$pnCompanyPk;
      $oResult = $this->_getModel()->ExecuteQuery($asQuery);
      $bRead = $oResult->readFirst();
      while($bRead)
      {
        $arsQuery = 'UPDATE addressbook_contact SET followerfk = '.$this->_getModel()->dbEscapeString($nNewFollowerFk).', date_update = '.$this->_getModel()->dbEscapeString(date('Y-m-d H:i:s')).' ';
        $arsQuery.= ' WHERE addressbook_contactpk = '.$oResult->getFieldValue('contactfk');

         $this->_getModel()->ExecuteQuery($arsQuery);
         $bRead = $oResult->readNext();
      }
    }

    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $pnCompanyPk);
    return array('notice'=>'Account Manager has been changed.','timedUrl'=>$sURL);

  }
  /**
   * Search for the keyword
   * @param string $psSearchWord
   * @return array
   */

  public function search($psSearchWord)
  {
    if(!assert('!empty($psSearchWord)'))
      return array();

    $psSearchWord = trim($psSearchWord);

    if(strlen($psSearchWord) < 3)
      return array('Search query is too short.');

    $oDB = CDependency::getComponentByName('database');

    //===============================================================
    //Company & contact PK search first (exclusive search)
    if(preg_match('/^(cp_[0-9]{1,6})|(cp[0-9]{1,6})/i', $psSearchWord))
    {
      $sWord = (int)preg_replace('/[^0-9]/', '', $psSearchWord);

      $sQuery = 'SELECT * FROM addressbook_company as cp WHERE (addressbook_companypk = '.$oDB->dbEscapeString($sWord).') ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->ReadFirst();
      if($bRead)
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $sWord);
        return array('url' => $sURL);
      }
      else
        return array('notice' => 'No company matches this id.');
    }
    if(preg_match('/^(ct_[0-9]{1,6})|(ct[0-9]{1,6})/i', $psSearchWord))
    {
      $sWord = (int)preg_replace('/[^0-9]/', '', $psSearchWord);

      $sQuery = 'SELECT * FROM addressbook_contact as ct WHERE (addressbook_contactpk = '.$oDB->dbEscapeString($sWord).') ';
      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->ReadFirst();
      if($bRead)
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $sWord);
        return array('url' => $sURL);
      }
      else
        return array('notice' => 'No contact matches this id.');
    }

    //===========================================================
    //Non exclusive cp and ct searches
    $asQueryFilter = array();

    if(preg_match('/^[0-9.+ ]{3,}$/i', $psSearchWord))
    {
      $sWord = preg_replace('/[^0-9]/', '', $psSearchWord);
      $sWord = $oDB->dbEscapeString('%'.$sWord.'%');
      $asQueryFilter['cp'] = 'WHERE (phone LIKE '.$sWord.' OR fax LIKE '.$sWord.')';
    }
    elseif(isValidEmail($psSearchWord))
    {
      $asQueryFilter['cp'] = 'WHERE (email LIKE '.$oDB->dbEscapeString('%'.$psSearchWord.'%').')';
    }
    else
    {
      $asWords = explode(' ', $psSearchWord);
      $asWhere = array();
      foreach($asWords as $sWord)
      {
        $sWord = $oDB->dbEscapeString('%'.$sWord.'%');
        $asWhere[] = '(company_name LIKE '.$sWord.' OR corporate_name LIKE '.$sWord.' OR address_1 LIKE '.$sWord.' OR address_2 LIKE '.$sWord.' OR postcode LIKE '.$sWord.')';
      }

      $asQueryFilter['cp'] = 'WHERE ('.implode('OR', $asWhere).')';
    }

    //===============================================================
    //Contact search
    $nNbResult = 0;
    $asResult = array();

    if(preg_match('/^[0-9.+ ]{3,}$/i', $psSearchWord))
    {
      $sWord = preg_replace('/[^0-9]/', '', $psSearchWord);
      $sWord = $oDB->dbEscapeString('%'.$sWord.'%');
      $asQueryFilter['ct'] = 'WHERE (phone LIKE '.$sWord.' OR fax LIKE '.$sWord.')';
    }
    elseif(isValidEmail($psSearchWord))
    {
      $asQueryFilter['ct'] = 'WHERE (email LIKE '.$oDB->dbEscapeString('%'.$psSearchWord.'%').')';
    }
    else
    {
      $asWords = explode(' ', $psSearchWord);
      $asWhere = array();
      foreach($asWords as $sWord)
      {
        $sWord = $oDB->dbEscapeString('%'.$sWord.'%');
        $asWhere[] = '(firstname LIKE '.$sWord.' OR lastname LIKE '.$sWord.' OR address_1 LIKE '.$sWord.' OR address_2 LIKE '.$sWord.' OR postcode LIKE '.$sWord.')';
      }

      $asQueryFilter['ct'] = 'WHERE ('.implode('OR', $asWhere).')';
    }

    if(isset($asQueryFilter['cp']))
    {
      $asSearch = $this->_getModel()->getSearchCompanyList($asQueryFilter['cp']);
      if($asSearch['nb'] > 0)
      {
        $nNbResult+= $asSearch['nb'];
        $asResult[] = $asSearch['data'];
      }
    }


    if(isset($asQueryFilter['ct']))
    {
      $asSearch = $this->_getModel()->getSearchContactList($asQueryFilter['ct']);
      if($asSearch['nb'] > 0)
      {
        $nNbResult+= $asSearch['nb'];
        $asResult[] = $asSearch['data'];
      }
    }

    return array('nb' => $nNbResult, 'data' => implode(' ', $asResult));
  }

  /**
   * Display the link company form
   * @param integer $pnPk
   * @return HTML structure
   */

  private function _getLinkCompanyForm($pnLoginPk)
  {
    if(!assert('is_integer($pnLoginPk) && !empty($pnLoginPk)' ))
      return '';

    $nProfilePk = (int)getValue('profileId');
    $this->coPage->addCssFile(array($this->getResourcePath().'css/addressbook.css'));

    if(!empty($nProfilePk))
      $oDbResult = $this->_getModel()->getByPk($nProfilePk, 'addressbook_profile');
    else
      $oDbResult = new CDbResult();

     if(isset($nProfilePk))
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEEDIT, CONST_AB_TYPE_COMPANY_RELATION, $pnLoginPk,array('profilePk'=>$nProfilePk));
     else
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEADD, CONST_AB_TYPE_COMPANY_RELATION, $pnLoginPk);

    $sHTML= $this->coHTML->getBlocStart();
    $sHTML.= $this->coHTML->getBlocStart('');
    $oForm = $this->coHTML->initForm('ctLinkForm');

    $oForm->setFormParams('', true, array('submitLabel' => 'Save','action' => $sURL));
    $oForm->setFormDisplayParams(array('noCancelButton' => 1));

    $sTitle = '<div class="ctLinkNote"><div class="h3">Profile types</div><br />
    <div>
    &loz; Link this connection to a company: set professional contact details and describe his/her job in the company.<br/><br/>
    &loz; Create a personal profile (no company): input alternative contact details for this person.<br /><br />
    </div>
    </div>';

    $oForm->addField('misc', '', array('type' => 'text', 'text'=> $sTitle));
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Create a new profile'));

    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, CONST_AB_TYPE_COMPANY);
    $oForm->addField('selector', 'parent', array('label'=> 'Company', 'url' => $sURL, 'value' => $oDbResult->getFieldvalue('companyfk')));
    $oForm->setFieldControl('parent', array('jsFieldTypeIntegerPositive' => ''));

    if($oDbResult->getFieldvalue('companyfk'))
    {
      $asCompany = $this->_getModel()->getCompanyByPk((int)$oDbResult->getFieldvalue('companyfk'));
      $oForm->addOption('parent', array('label' => $asCompany['company_name'], 'value' => $oDbResult->getFieldvalue('companyfk')));
    }

    $oForm->addField('input', 'position', array('label'=> 'Position', 'value' => $oDbResult->getFieldvalue('position')));
    $oForm->setFieldControl('position', array('jsFieldMinSize' =>2));
    $oForm->addField('input', 'email', array('label'=> 'Email Address', 'value' => $oDbResult->getFieldvalue('email')));
    $oForm->setFieldControl('email', array('jsFieldTypeEmail' => '','jsFieldMaxSize' => 255));
    $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => $oDbResult->getFieldvalue('phone')));
    $oForm->setFieldControl('phone', array('jsFieldMinSize' => 8));
    $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => $oDbResult->getFieldvalue('fax')));
    $oForm->setFieldControl('fax', array('jsFieldMinSize' => 8));
    $oForm->addField('textarea', 'address', array('label'=> 'Address ', 'value' =>$oDbResult->getFieldvalue('address_1')));
    $oForm->setFieldControl('address', array('jsFieldMinSize' => 8));
    $oForm->addField('input', 'postcode', array('label'=> 'Postcode', 'value' => $oDbResult->getFieldvalue('postcode')));
    $oForm->setFieldControl('postcode', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 12));
    $oForm->addField('input', 'department', array('label'=> 'Department', 'value' => $oDbResult->getFieldvalue('department')));
    $oForm->setFieldControl('department', array('jsFieldMaxSize' => 255));
    $oForm->addField('selector_city', 'cityfk', array('label'=> 'City', 'url' => CONST_FORM_SELECTOR_URL_CITY));
    $oForm->setFieldControl('cityfk', array('jsFieldTypeIntegerPositive' => ''));

    if($oDbResult->getFieldvalue('cityfk'))
    {
      $asCity = $oForm->getCityData((int)$oDbResult->getFieldvalue('cityfk'));
      $oForm->addOption('cityfk', array('label' => $asCity['name_full'].$asCity['name_kanji'], 'value' => $oDbResult->getFieldvalue('cityfk')));
    }

    $oForm->addField('selector_country', 'countryfk', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
    $oForm->setFieldControl('countryfk', array('jsFieldTypeIntegerPositive' => ''));
    if($oDbResult->getFieldvalue('countryfk'))
    {
      $asCountry = $oForm->getCountryData((int)$oDbResult->getFieldvalue('countryfk'));
      $oForm->addOption('countryfk', array('label' => $asCountry['country_name'], 'value' => $oDbResult->getFieldvalue('countryfk')));
    }

    $oForm->addField('misc', '', array('type'=> 'br'));
    $sHTML.= $oForm->getDisplay();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Save the company contacts
   * @param integer $psPnPk
   * @return string
   */

  private function _saveCompanyContact($pnCompanyPk)
  {
    if(!assert('is_integer($pnCompanyPk)'))
      return array('error'=> 'Bad data');

    if(!assert('is_integer($psPnPk) && !empty($psPnPk)'))
     return array('error'=>__LINE__.' - Can not connect the user');

    $oDB = CDependency::getComponentByName('database');

    $nContact = (int)getValue('contactfk');
    if(empty($nContact))
      return array('error'=>'Connection data missing');

    $sPosition = getValue('position');
    $sEmail = getValue('email');
    $sPhone = getValue('phone');
    $sFax = getValue('fax');
    $sAddress1 = getValue('address');
    $sPostcode = getValue('postcode');
    $nCountryfk = (int)getValue('countryfk', 0);
    $nCityfk = (int)getValue('cityfk', 0);


    $sQuery = 'INSERT INTO `profil` (`contactfk` ,`companyfk` ,`position` ,`email` ,`phone` ,`fax`,`address_1` ,`postcode` ,`cityfk` ,`countryfk`) ';
    $sQuery.= ' VALUES('.$oDB->dbEscapeString($nContact).','.$oDB->dbEscapeString($pnCompanyPk).','.$oDB->dbEscapeString($sPosition).', '.$oDB->dbEscapeString($sEmail).','.$oDB->dbEscapeString($sPhone).', '.$oDB->dbEscapeString($sFax).',';
    $sQuery.= $oDB->dbEscapeString($sAddress1).', '.$oDB->dbEscapeString($sPostcode).', '.$oDB->dbEscapeString($nCityfk).', ';
    $sQuery.= $oDB->dbEscapeString($nCountryfk).')';

    $oResult = $oDB->ExecuteQuery($sQuery);
    if(!$oResult)
      return array('error' => __LINE__.' - Couldn\'t save the profile.');

    return array('notice' => 'Connection has been added', 'action' => 'goPopup.removeActive();', 'reload' => 1);
   }

  /**
   * List all the companies
   * @param string $psQueryFilter
   * @return array of records
   */

  private function _getCompanyList($psQueryFilter = '', $pbRefreshSearch = false)
  {
    if(!assert('is_string($psQueryFilter)'))
      return '';

    $this->coPage->addCssFile($this->getResourcePath().'/css/addressbook.css');

    // Check the session things here.
    $sSetTime =  getValue('settime');
    showHideSearchForm($sSetTime, 'cp');

    $sTitle = 'Company Search';
    if($pbRefreshSearch)
      $sTitle = 'My companies';

    $sHTML = $this->coHTML->getTitleLine($sTitle, $this->getResourcePath().'/pictures/company_48.png');
    $sHTML.= $this->coHTML->getCR();
    $sHTML.= $this->coHTML->getBloc('','', array('class'=>'searchTitle'));

    //===============================================================================
    // Insert the search form in the Contact list page
    $gbNewSearch = true;

    //if clear search: do not load anything from session and generate a new searchId
    //if do_search: do not load the last search, save a new one with new parameters
    if($pbRefreshSearch)
    {
      $sSearchId = '';
      $_POST['cpfollowerfk'] = CDependency::getCpLogin()->getUserPk();
    }
    else
    {
      if((getValue('clear') == 'clear_cp') || getValue('do_search', 0))
        $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_COMPANY, true);
      else
      {
        //reload the last search using the ID passed in parameters, ou the last done
        if(getValue('searchId'))
          $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_COMPANY);
        else
          $sSearchId = reloadLastSearch($this->csUid, CONST_AB_TYPE_COMPANY);
       }
    }
    //$gbNewSearch = true only if it's a new search
    if($gbNewSearch)
    {
      $sDisplay = 'block';
      $sExtraClass = '';
    }
    else
    {
      $sDisplay = 'none';
      $sExtraClass = ' searchFolded ';
    }

    $avResult = $this->_getCompanySearchResult($psQueryFilter, $sSearchId);

    $sCompanyMessage = $this->_getCompanySearchMessage($avResult['nNbResult'],'');
    // This is the search block
    $sHTML.= $this->_getCompanySearchBloc($sSearchId, $avResult, $gbNewSearch);

    $sJavascript = " $(document).ready(function(){ $('.searchTitle').html('".$sCompanyMessage."') }); ";
    $this->coPage->addCustomJs($sJavascript);

    $sHTML.= $this->coHTML->getBlocStart('contactListContainer');
    $sHTML.= $this->_getCompanyResultList($avResult, $sSearchId);
    $sHTML.= $this->coHTML->getBlocEnd();


    return $sHTML;
  }

  /**
   * Search Company with the parameters
   * @param string $psQueryFilter
   * @param string $psSearchId
   * @return array
   */

  private function _getCompanySearchResult($psQueryFilter, $psSearchId = '')
  {
    $oEvent =  CDependency::getComponentByName('event');
    $asEventQuery = array('select' => '1');
    if(!empty($oEvent))
    {
      $asEventQuery = $oEvent->getCompanyActivitySql();
    }


    $sQuery = 'SELECT count(DISTINCT cp.addressbook_companypk) as nCount FROM addressbook_company as cp LEFT JOIN addressbook_profile as prf ON prf.companyfk = cp.addressbook_companypk ';

    if(!empty($asEventQuery['join']))
      $sQuery.= $asEventQuery['join'];

    if($psQueryFilter)
      $sQuery.= $psQueryFilter;
    else
    {
      $asFilter = $this->_getSqlCompanySearch();
      if(!empty($asFilter['join']))
        $sQuery.= $asFilter['join'];

      if(!empty($asFilter['where']))
        $sQuery.= ' WHERE '.$asFilter['where'];
    }

    $oDb = CDependency::getComponentByName('database');
    $oDbResult = $oDb->ExecuteQuery($sQuery);
    $oDbResult->ReadFirst();
    $nNbResult = $oDbResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);

    if($nNbResult == 0)
      return array('nNbResult' => 0, 'oData' => null);

    $oPager = CDependency::getComponentByName('pager');
    $oPager->initPager();



    $sQuery = 'SELECT cp.*,'.$asEventQuery['select'].', cp_parent.company_name as parent_company,
                group_concat(DISTINCT lg.lastname SEPARATOR ",") as follower_lastname,
                group_concat(DISTINCT lg.firstname SEPARATOR ",") as follower_firstname,
                group_concat(DISTINCT cp_child.addressbook_companypk SEPARATOR ",") as child_pk,
                group_concat(DISTINCT cp_child.company_name SEPARATOR ",") as child_name,
                ind.industry_name as industry_name
                FROM addressbook_company as cp USE INDEX (PRIMARY,company_name) ';
    $sQuery.= ' LEFT JOIN  addressbook_company as cp_parent ON(cp_parent.addressbook_companypk = cp.parentfk) ';
    $sQuery.= ' LEFT JOIN  addressbook_company as cp_child ON(cp_child.parentfk = cp.addressbook_companypk) ';
    $sQuery.= ' LEFT JOIN  addressbook_company_industry as ci on ci.companyfk=cp.addressbook_companypk ';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON ind.addressbook_industrypk = ci.industryfk ';
    $sQuery.= ' LEFT JOIN addressbook_profile as prf ON prf.companyfk=cp.addressbook_companypk and prf.date_end is NULL ';
    $sQuery.= ' LEFT JOIN shared_login AS lg ON (lg.loginpk = cp.followerfk)';

    if(!empty($asEventQuery['join']))
      $sQuery.= $asEventQuery['join'];

    if($psQueryFilter)
      $sQuery.= $psQueryFilter;
    else
    {
      if(!empty($asFilter['join']))
        $sQuery.= $asFilter['join'];

      if(!empty($asFilter['where']))
        $sQuery.= ' WHERE '.$asFilter['where'];
    }
    $sQuery.= ' GROUP BY cp.addressbook_companypk ';
    if(!empty($psSearchId))
      $sQuery.= $this->_getCompanySearchOrder($psSearchId);

    $oPager = CDependency::getComponentByName('pager');
    $oPager->initPager();
    $sQuery.= ' LIMIT '.$oPager->getSqlOffset().','.$oPager->getLimit();

    $oDbResult = $oDb->ExecuteQuery($sQuery);
    if(!$oDbResult->readFirst())
    {
      assert('false; /* no result but count query was ok ['.addslashes($sQuery).'] */ ');
      return array('nNbResult' => 0, 'oData' => null);
    }

    return array('nNbResult' => $nNbResult, 'oData' => $oDbResult);
  }

  /**
   * Get the company Search Message
   * @global boolean $gbNewSearch
   * @param type $pnNbResult
   * @param type $pasOrderDetail
   * @param type $pbOnlySort
   * @return type
   */

  private function _getCompanySearchMessage($pnNbResult = 0, $pasOrderDetail = array(), $pbOnlySort = false)
  {
    $sMessage = '';

    global $gbNewSearch;

    if(isset($pasOrderDetail['sortfield']) && !empty($pasOrderDetail['sortfield']))
    {
      $sSortMsg = $this->coHTML->getText(' - sorted by '.$pasOrderDetail['sortfield'].' '.$pasOrderDetail['sortorder'], array('class'=>'searchTitleSortMsg'));
      if($pbOnlySort)
        return $sSortMsg;
    }
    else
      $sSortMsg = $this->coHTML->getText('', array('class'=>'searchTitleSortMsg'));

    $sField = getValue('company_name');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' Company : '.$sField, array('class'=>'normalText'));

    $sField = getValue('phone_cp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' phone : '.$sField, array('class'=>'normalText'));

    $sField = getValue('email_cp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' email : '.$sField, array('class'=>'normalText'));

    $sField = getValue('cpfollowerfk');
    if(!empty($sField))
    {
      $oLogin = CDependency::getCpLogin();
      $asLoginData= $oLogin->getUserDataByPk((int)$sField);
      $sMessage.= $this->coHTML->getText(' Account Manager : '.$oLogin->getUserNameFromData($asLoginData, true), array('class'=>'normalText'));
    }

    $sField = getValue('address');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' Address : '.$sField, array('class'=>'normalText'));

    $asField = (array)getValue('company_industry');
     if(!empty($asField) && !empty($asField[0]))
       $sMessage.= $this->coHTML->getText(count($asField).' industries selected', array('class'=>'normalText'));

    $sField = getValue('synopsis');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' synopsis : '.$sField, array('class'=>'normalText'));

    $sField = getValue('company_relation');
    if(!empty($sField))
    {
      $sRelation = getCompanyRelation((int)$sField);
      $sMessage.= $this->coHTML->getText(' Company Relation : '.$sRelation['Label'],array('class'=>'normalText'));
    }

    $sField = getValue('event_cp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' activity : '.$sField, array('class'=>'normalText'));

    $sField = getValue('date_eventStartcp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' activities from: '.$sField, array('class'=>'normalText'));

    $sField = getValue('date_eventEndcp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' to : '.$sField, array('class'=>'normalText'));

    $sField = getValue('event_type_cp');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' activity type : '.$sField, array('class'=>'normalText'));

    if(!empty($gbNewSearch) && !empty($sMessage))
    {
      $sMessage = $this->coHTML->getText(' for ').$sMessage;
    }

    return $this->coHTML->getText($pnNbResult.' results') . $sMessage.' '.$sSortMsg;
  }

/**
 * Display company result lists
 * @param object array $pavResult
 * @param string $psSearchId
 * @return string HTML
 */

  private function _getCompanyResultList($pavResult, $psSearchId)
  {
    /* if(!assert('!empty($pavResult) && !empty($psSearchId)'))
      return 'No data found';*/

    $oPager = CDependency::getComponentByName('pager');
    $this->coPage->addJsFile($this->getResourcePath().'js/addressbook.js');

    $sHTML = $this->coHTML->getBlocStart('', array('style'=>'padding: 0px;background-color:#FFFFFF;width: 100%;'));
    $sHTML.= $this->coHTML->getListStart('', array('class' => 'ablistContainer '));

    if($pavResult['nNbResult'] == 0)
    {
       $sHTML.= $this->coHTML->getListItemStart();
       $sHTML.= "Couldn't find company.";
       $sHTML.= $this->coHTML->getListItemEnd();
       $sHTML.= $this->coHTML->getListEnd();
       return $sHTML;
    }

    $sUrl = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY);
    $asPagerUrlOption = array('ajaxTarget' => 'contactListContainer', 'ajaxCallback' => ' jQuery(\'.searchContainer\').fadeOut(); ');
    $sHTML.= $oPager->getCompactDisplay($pavResult['nNbResult'], $sUrl, $asPagerUrlOption);

    $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'ablistHeader'));
    $sHTML.=  $this->_getCompanyRowHeader();
    $sHTML.= $this->coHTML->getListItemEnd();

    $nCount = 1;
    $oDbResult = $pavResult['oData'];
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $sRowId = 'cpId_'.$oDbResult->getFieldValue('addressbook_companypk');
      $asCompanyData = $oDbResult->getData();
      $sHTML.= $this->coHTML->getListItemStart($sRowId);
      $sHTML.=  $this->_getCompanyRow($asCompanyData, $nCount);
      $sHTML.= $this->coHTML->getListItemEnd();

      $nCount++;
      $bRead = $oDbResult->ReadNext();
    }

    $sHTML.= $this->coHTML->getListEnd();
    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();

    if($pavResult['nNbResult'] > 0)
      $sHTML.= $oPager->getDisplay($pavResult['nNbResult'], $sUrl, $asPagerUrlOption);

    return $sHTML;
  }

  /**
   * Display details of the company
   * @param integer $pnPK companyId
   * @return array
   */

  private function _displayCompany($pnPK)
  {
    if(!assert('is_key($pnPK)'))
      return 'No data found';

    $oResult = $this->_getModel()->getCompany($pnPK);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return $this->coHTML->getBlocMessage('No result found.');

    $aCpValues = $this->getCpValues();
    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_TRANSFER,$this->getType(),0);


    $oLogin = CDependency::getCpLogin();
    $this->coPage->addCssFile($this->getResourcePath().'/css/addressbook.css');
    $this->coPage->addCssFile($this->getResourcePath().'/css/event.css');

    $oEvent = CDependency::getComponentByName('event');
    $oOpportunity = CDependency::getComponentByName('opportunity');

    $aDocuments = array();
    $oSharedSpace = CDependency::getComponentByName('sharedspace');
    if(!empty($oSharedSpace))
      $aDocuments = $oSharedSpace->getTabContent($this->getCpValues());

    $asAction = array();

    $asCompanyData =  $oResult->getData();

    $this->coPage->setPageTitle($asCompanyData['company_name']);

    //manage here a set of action launched when the page is displayed
    $sReloadAction = getValue('relact');
    switch($sReloadAction)
    {
      case 'opportunity':

        if($oOpportunity && $oRight->canAccess('555-123', CONST_ACTION_ADD, CONST_OPPORTUNITY))
        {
          $sReturnURL = urlencode($this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $pnPK));
          $sURL = $this->coPage->getAjaxUrl('opportunity', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, array('cp_uid' => $this->csUid, 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_COMPANY, 'cp_pk' => $pnPK, CONST_URL_ACTION_RETURN => $sReturnURL));
          $this->coPage->addCustomJs(' $(document).ready(function()
            {
              var oConf = goPopup.getConfig();
              oConf.height = 660;
              oConf.width = 980;
              oConf.modal = true;
              goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
            }); ');
        }
        break;

      case 'event':
        if($oEvent)
        {
          $sURL = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array('cp_uid' => $this->csUid, 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_COMPANY, 'cp_pk' => $pnPK));
          $this->coPage->addCustomJs(' $(document).ready(function()
            {
              var oConf = goPopup.getConfig();
              oConf.height = 700;
              oConf.width = 980;
              oConf.modal = true;
              goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
            }); ');
        }
        break;
    }

    $sLink = $this->coPage->getUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY,$pnPK);
    $oLogin->logUserActivity($oLogin->getUserPk(), $this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $pnPK, 'view company details ', $asCompanyData['company_name'], $sLink);

    //count employees
    $oResult = $this->_getModel()->getCompanyEmployeesCount($pnPK);
    $bRead = $oResult->readFirst();
    $nEmployee = $oResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);

    //Count events
    if(!empty($oEvent))
    {
      $nEvents = $oEvent->getCount($aCpValues);
      $sEventUid = $oEvent->getComponentUid();

      // put in the array all the "items" we should fetch events from:
      // the company, and all its employees
      $asEmployee = $this->_getModel()->getEmployeeList((int)$asCompanyData['addressbook_companypk']);
      $asEventItem[] = array('type' => CONST_AB_TYPE_COMPANY, 'pk' => $asCompanyData['addressbook_companypk']);
      foreach($asEmployee as $nContactPk => $avUseless)
      {
        $asEventItem[] = array('type' => CONST_AB_TYPE_CONTACT, 'pk' => $nContactPk);
      }
    }
    else
    {
      $nEvents = '';
      $sEventUid = '';
    }

    // Count opportunities
    $nOpportunity = '';
    $sOpportunityUid = '';
    if(!empty($oOpportunity))
    {
      $nOpportunity = $oOpportunity->getCount($aCpValues);
      $sOpportunityUid = $oOpportunity->getComponentUid();
    }

    // Count documents
    $nDocuments = 0;
    if(!empty($aDocuments))
    {
      $nDocuments = $aDocuments['count'];
      $sDocumentsTabs = $aDocuments['html'];
      $aLastDocument = $aDocuments['last'];
    }

    $sHTML = $this->getCompanyCard(0, $asCompanyData);

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'top_right_activity'));

    if(isset($asCompanyData['follower_lastname']) && !empty($asCompanyData['follower_lastname']))
    {
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/manager.png', 'Account manager', '', array('style' => 'height: 24px;'));
      $sHTML.= $this->coHTML->getText(' Account manager: ', array('class' => 'ab_account_manager'));
      if($sAccess)
      {
        $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_TRANSFER, CONST_AB_TYPE_COMPANY,(int)$asCompanyData['addressbook_companypk']);
        $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body','','310','550',1);
        $sHTML.= $this->coHTML->getLink($asCompanyData['follower_firstname'].' '.$asCompanyData['follower_lastname'],'javascript:;', array('onclick'=>$sAjax));
      }
      else
        $sHTML.= $this->coHTML->getText($asCompanyData['follower_firstname'].' '.$asCompanyData['follower_lastname']);

      if($asCompanyData['followers'])
      {
        $asFollowers = $asCompanyData['followers'];
        $asData = explode(',',$asFollowers);
        $sHTML.= $this->coHTML->getSpace(1);
        foreach($asData as $asFollow)
        {
          $sHTML.= $this->coHTML->getText(', ');
          $asRecords = $oLogin->getUserDataByPk((int)$asFollow);

          if($sAccess)
            $sHTML.= $this->coHTML->getLink($asRecords['firstname'].' '.$asRecords['lastname'],'javascript:;', array('onclick'=>$sAjax));
          else
            $sHTML.= $this->coHTML->getText($asRecords['firstname'].' '.$asRecords['lastname']);

          $sHTML.= $this->coHTML->getSpace(1);
        }
      }
    }
    else
      $sHTML.= $this->coHTML->getLink('< Define Manager >','javascript:;', array('onclick'=>$sAjax));

    $sHTML.= $this->coHTML->getBlocEnd();


    //-------------------------------------
    //Start the bloc for recent activities
    $nActivityCounter = 0;
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'cp_top_activity'));
    if(!empty($oEvent))
    {
      //TODO: replace this function by a generic one. Amit put something specific to AB into Event (again)
      $asLatestEmails = $oEvent->getEventDetail('email', (int)$asCompanyData['addressbook_companypk'],'cp');

      $asLatestConnectionEvent = $oEvent->getEvents($this->csUid, CONST_ACTION_VIEW, '', 0, $asEventItem, 1);
      $asLatestConnectionEvent = current($asLatestConnectionEvent);

      if(!empty($asLatestConnectionEvent))
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT,(int)$asLatestConnectionEvent[CONST_CP_PK], array(''),'ct_tab_eventId');

        $sHTML.= $this->coHTML->getText('Latest Activity : ', array('class' => 'ab_view_strong'));
        $asUserData = $oLogin->getUserDataByPk((int)$asLatestConnectionEvent['created_by']);
        $sHTML.= $this->coHTML->getText('by '.$asUserData['firstname'].' '.$asUserData['lastname']);
        $sHTML.= $this->coHTML->getText(' - ');
        $sHTML.= $this->coHTML->getNiceTime($asLatestConnectionEvent['date_display'],0,true);

        $sHTML.= $this->coHTML->getBlocStart('', array('class' => '','style'=>'width:100%; border:none;'));
        $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'width:100%;'));
        $sHTML.= $this->coHTML->getExtendableBloc('latestconevent',$asLatestConnectionEvent['content']);
        $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getBlocEnd();

        $nActivityCounter++;
      }

      if(!empty($asLatestConnectionEvent))
        $asLatestEvents = $oEvent->getEventDetail('other',(int)$asCompanyData['addressbook_companypk'],'cp');
      else
        $asLatestEvents = $oEvent->getEventDetail('other',(int)$asCompanyData['addressbook_companypk'],'cp', 2);

      if(!empty($asLatestEmails))
      {
        foreach ($asLatestEmails as $asLatestEmail)
        {
          $sHTML.= $this->coHTML->getText('Latest Email: ', array('class' => 'ab_view_strong'));
          $asUserData= $oLogin->getUserDataByPk((int)$asLatestEmail['created_by']);
          $sHTML.= $this->coHTML->getText('by  '.$asUserData['firstname'].' '.$asUserData['lastname']);
          $sHTML.= $this->coHTML->getText(' - ');
          $sHTML.= $this->coHTML->getNiceTime($asLatestEmail['date_display'],0,true);

          $sHTML.= $this->coHTML->getBlocStart('', array('class' => '','style'=>' 100%; border:none;'));
          $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'width:100%;'));
          $sHTML.= $this->coHTML->getExtendableBloc('latestemail', $asLatestEmail['content']);
          $sHTML.= $this->coHTML->getBlocEnd();
          $sHTML.= $this->coHTML->getBlocEnd();

          $nActivityCounter++;
        }
      }

      if(!empty($asLatestEvents))
      {
        foreach ($asLatestEvents as $asLatestEvent)
        {
          $sHTML.= $this->coHTML->getFloatHack();

          $sHTML.= $this->coHTML->getText('Latest Update: ', array('class' => 'ab_view_strong'));
          $asUserData= $oLogin->getUserDataByPk((int)$asLatestEvent['created_by']);
          $sHTML.= $this->coHTML->getText('by  '.$asUserData['firstname'].' '.$asUserData['lastname']);
          $sHTML.= $this->coHTML->getText(' - ');
          $sHTML.= $this->coHTML->getNiceTime($asLatestEvent['date_display'],0,true);

          $sHTML.= $this->coHTML->getBlocStart('', array('class' => '','style'=>'width:450px;border:none;margin-bottom:10px;'));
          $sHTML.= $this->coHTML->getExtendableBloc('latestevent', $asLatestEvent['content']);
          $sHTML .= $this->coHTML->getBlocEnd();
          $nActivityCounter++;
        }
      }

    }


    if($nActivityCounter < 2 && !empty($asCompanyData['date_update']) && date('Y',strtotime($asCompanyData['date_update']) == date('Y')) && (int)$asCompanyData['updated_by'] !=  $oLogin->getUserPk() )
    {
      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'margin-top:10px;'));
      $sHTML.= $this->coHTML->getText('Last Edited: ', array('class' => 'ab_view_strong'));
      $sHTML.= ' - ';

      $asUserData = $oLogin->getUserList((int)$asCompanyData['updated_by'], false, true);
      $sUser = $oLogin->getUserNameFromData(current($asUserData));
      $sHTML.= $this->coHTML->getNiceTime($asCompanyData['date_update'],0,true). $this->coHTML->getText(' - by '.$sUser);
      $sHTML.= $this->coHTML->getBlocEnd();
      $nActivityCounter++;
    }

    if($nActivityCounter < 2)
    {
      if(!empty($aLastDocument['title']) && (int)$aLastDocument['loginfk'] !=  $oLogin->getUserPk())
      {
        $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'margin-top:10px;'));
        $sHTML.= $this->coHTML->getText('Latest Document: ', array('class' => 'ab_view_strong'));
        $sHTML.= ' - ';

        $sHTML.= $this->coHTML->getText($aLastDocument['title'].' - ');
        $sHTML.= $this->coHTML->getNiceTime($aLastDocument['date_creation'],0,true);
        $sHTML.= $this->coHTML->getBlocEnd();
        $nActivityCounter++;
      }
    }

    if($nActivityCounter < 2)
    {
      $sHTML.= $this->coHTML->getCR();

      $sHTML.= $this->coHTML->getText('Company has been created ');
      $sHTML.= $this->coHTML->getSpace(2);
      $sHTML.= $this->coHTML->getNiceTime($asCompanyData['date_create'],0,true);
    }

    $sHTML.= $this->coHTML->getCR(2);


    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'cp_top_action'));

    if(!empty($oEvent))
    {
      //Add a event
      $sUrl = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, CONST_CP_PK => $this->cnPk));
      $asAction[] = array('url' => '', 'pic' => $oEvent->getResourcePath().'pictures/add_event_16.png', 'label' => 'Add a note/activity',
          'onclick' => 'var oConf = goPopup.getConfig();
            oConf.height = 700;
            oConf.width = 980;
            oConf.title = \'Add an activity...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ');
    }


    if(!empty($oOpportunity) && $oRight->canAccess('555-123', CONST_ACTION_ADD, CONST_OPPORTUNITY))
    {
      //Add an opportunity
      $sURL = $this->coPage->getAjaxUrl('opportunity', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $aCpValues);
      $sAjax = 'var oConf = goPopup.getConfig();
                oConf.height = 660;
                oConf.width = 980;
                oConf.modal = true;
                goPopup.setLayerFromAjax(oConf, \''.$sURL.'\'); ';

      $asAction[] = array('url' => '', 'pic' => CONST_PICTURE_OPPORTUNITY, 'label' => 'Add a business opportunity', 'onclick' => $sAjax);
    }

    if(!empty($oSharedSpace))
    {
      // Upload a document
      $sAjax = $oSharedSpace->displayAddLink($aCpValues, true, true);

      if(!empty($sAjax))
        $asAction[] = array('url' => '', 'pic' => CONST_PICTURE_UPLOAD, 'label' => 'Add a document', 'onclick' => $sAjax);
    }

    $sHTML.= $this->coHTML->getActionButtons($asAction, 2, '', array('width' => 450));

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getFloatHack();

    // ####################################################
    // INITIALIZING TABS :
    // Order is important as it will be used for display
    // ####################################################

    $sTabSelected = '';
    $asTabs = array();
    $oSetting = CDependency::getComponentByName('settings');
    $asCpTabs = $oSetting->getSettingValue('company_tabs');

    if(empty($asCpTabs))
    {
      $sUrl = $this->coPage->getUrl('login', CONST_ACTION_EDIT, CONST_LOGIN_TYPE_USER, 0);
      $asTabs[] = array('label' => 'notice', 'title' => 'Notice', 'content' => 'No tab is selected for display. <br />
        Please choose the tabs you wish to display using the '.$this->coHTML->getLink('My Account section', $sUrl).' > Preferences');
    }
    else
    {
      foreach ($asCpTabs as $sTabId)
      {
        switch($sTabId)
        {
          case 'cp_tab_opportunity':
            $sOpportunityTitle = ($nOpportunity > 0 ? 'Opportunities ('.$nOpportunity.')' : '<i>Opportunities</i>');
            $asTabs[] = array('label' => CONST_TAB_CP_OPPORTUNITY, 'title' => $sOpportunityTitle, 'content' => $oOpportunity->getTabContent($this->getCpValues()));
            break;
          case 'cp_tab_event':
            $sEventTitle = ($nEvents > 0 ? 'Activites ('.$nEvents.')' : '<i>Activities</i>');
            $asTabs[] = array('label' => CONST_TAB_CP_EVENT, 'title' => $sEventTitle, 'content' => $this->_getCompanyEventTab($asCompanyData));
            break;
          case 'cp_tab_employee':
            $sEmployeeTitle = ($nEmployee > 0 ? 'Employees ('.$nEmployee.')' : '<i>Employees</i>');
            $asTabs[] = array('label' => CONST_TAB_CP_EMPLOYEES, 'title' => $sEmployeeTitle, 'content' => $this->_getCompanyEmployeeTab($asCompanyData));
            break;
          case 'cp_tab_document':
            if(!empty($oSharedSpace))
            {
              $sDocumentTitle = ($nDocuments > 0 ? 'Documents ('.$nDocuments.')' : '<i>Documents</i>');
              $asTabs[] = array('label' => CONST_TAB_CP_DOCUMENT, 'title' => $sDocumentTitle, 'content' => $sDocumentsTabs);
            }
            break;
          case 'cp_tab_detail':
            $asTabs[] = array('label' => CONST_TAB_CP_DETAIL, 'title' => 'Detail', 'content' => $this->_getCompanyDetailTab($asCompanyData));
            break;
        }
      }
    }

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'bottom_container'));
    $sHTML.= $this->coHTML->getTabs('compagny', $asTabs, $sTabSelected);
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Get the company card information
   * @param integer $pnCompanyPk
   * @param array $pasCompanyData
   * @return HTML structure
   */

  public function getCompanyCard($pnCompanyPk = 0, $pasCompanyData = array())
  {
    if(!assert('is_integer($pnCompanyPk) && is_array($pasCompanyData)'))
      return '';

    if(empty($pnCompanyPk) && empty($pasCompanyData))
    {
      assert('false; // need company pk or company data to display the card');
      return '';
    }

    if(!empty($pasCompanyData))
      $asCompanyData = $pasCompanyData;
    else
      $asCompanyData = $this->_getModel()->getCompanyByPk($pnCompanyPk);


    $sHTML = '';
    $sHTML = $this->coHTML->getBlocStart('', array('class' => 'cp_top_container shadow'));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cp_card_container'));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cp_top_name'));

    $sHTML.= $this->coHTML->getBlocStart('');
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'left'));
    $sHTML.= $this->coHTML->getTitle($asCompanyData['company_name'], 'h3', false);
    $oChilds = $this->_getModel()->getCompanyChildsByPk((int)$asCompanyData['addressbook_companypk']);

    $bRead = $oChilds->readFirst();
    if($bRead)
    {
      $nChild = $oChilds->numRows();
      if($nChild <= 2)
      {
        $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'width:100%;'));
        while($bRead)
        {
          $sURL = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$oChilds->getFieldValue('addressbook_companypk'));
          $sHTML.= 'Holds: '.$this->coHTML->getLink($oChilds->getFieldValue('company_name'), $sURL, array('class' => 'h4'));
          $sHTML.= $this->coHTML->getCR();
          $bRead = $oChilds->readNext();
        }
        $sHTML.= $this->coHTML->getBlocEnd();
      }
      else
      {
        $nFullWidth = ($nChild*280);

        $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cpChildContainer'));
        $sHTML.= $this->coHTML->getBloc('', $nChild.' childs:', array('class' => 'cpChildTitle'));

        $sHTML.= '<a class="buttons prev" href="#" onclick="

         var oPosition = $(\'.cpScrollerInner\').position();
        if((oPosition.left +280) >= 0)
          oPosition.left = -'.$nFullWidth.';
        else
          oPosition.left = (oPosition.left +280)

        $(\'.cpScrollerInner\').animate({left:  oPosition.left, }, 450);"><img src="/component/addressbook/resources/pictures/previous_24.png" /></a>';

        $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cpScroller'));
        $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cpScrollerInner', 'style' => 'width: '.$nFullWidth.'px;'));

        while($bRead)
        {
          $sURL = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$oChilds->getFieldValue('addressbook_companypk'));

          $sHTML.= $this->coHTML->getBlocStart();
          $sHTML.= $this->coHTML->getLink($oChilds->getFieldValue('company_name'), $sURL, array('class' => 'h4'));
          $sHTML.= $this->coHTML->getBlocEnd();

          $bRead = $oChilds->readNext();
        }
        $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getBlocEnd();

         $sHTML.= '<a class="buttons prev" href="#" onclick="

        var oPosition = $(\'.cpScrollerInner\').position();
        if((oPosition.left -280) <= -'.$nFullWidth.')
          oPosition.left = 0;
        else
          oPosition.left = (oPosition.left -280)

        $(\'.cpScrollerInner\').animate({left:  oPosition.left, }, 450);"><img src="/component/addressbook/resources/pictures/next_24.png" /></a>';
        $sHTML.= $this->coHTML->getBlocEnd();

      }
    }

    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'right'));

    if($asCompanyData['corporate_name'])
      $sHTML.= $this->coHTML->getTitle($asCompanyData['corporate_name'], 'h4', false);

    if($asCompanyData['parentfk'])
    {
      $asParentCompanyData = $this->_getModel()->getCompanyByPk((int)$asCompanyData['parentfk']);
      if(!empty($asParentCompanyData))
      {
        $sURL = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$asCompanyData['parentfk']);

        $sHTML.= 'Holding: '.$this->coHTML->getLink($asParentCompanyData['company_name'], $sURL, array('title' => 'holding company', 'class' => 'h4'));
      }
    }
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getFloatHack();

    if(isset($asCompanyData['industry_name']) && !empty($asCompanyData['industry_name']))
    {
      $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'left industryList '));
      $sHTML.= $this->coHTML->getText($asCompanyData['industry_name']);
      $sHTML.= $this->coHTML->getBlocEnd();
    }

    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_relation_row'));
    $sCompanyRelation = getCompanyRelation($asCompanyData['company_relation']);
    $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/'.$sCompanyRelation['icon'], 'Relation', '', array('style' => 'height: 24px'));
    $sHTML.= $this->coHTML->getBlocStart().' '.$sCompanyRelation['Label']. $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    /*$sHTML.=  $this->coHTML->getText('Synopsis: ', array('class' => 'ab_view_strong'));
    $sHTML.= $this->coHTML->getCR();*/
    $sHTML.=  $this->coHTML->getBlocStart('', array('class' => 'ab_card_comment'));
    $sHTML.=  $this->coHTML->getText('Synopsis: ', array('class' => 'ab_view_strong'));

    if(!empty($asCompanyData['comments']))
      $sHTML.= $this->coHTML->getText(($asCompanyData['comments']));
    else
      $sHTML.= $this->coHTML->getText('No Synopsis', array('class'=>'light italic'));

    $sHTML.=  $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'floatHack'));
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Search Form for the company
   * @return HTML structure
   */

   private function _getCompanySearchForm($psSearchId, $pbNewSearch = true)
   {
    $nLoginPk = (int)getValue('loginpk', 0);

    $asFormFields = array('company_name', 'followerfk', 'company_relation',  'phone_cp', 'email_cp','synopsis', 'event_cp', 'event_type_cp', 'date_eventStartcp', 'date_eventEndcp');

    $nFieldDisplayed = 0;
    foreach($asFormFields as $sFieldName)
    {
      $vValue = getValue($sFieldName);
      if(!empty($vValue))
        $nFieldDisplayed++;
    }
    $nFieldToDisplay = (6 - $nFieldDisplayed);

    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY);
    $this->coPage->addJsFile($this->getResourcePath().'js/addressbook.js');

    /* @var $oForm CFormEx */
    $oForm = $this->coHTML->initForm('queryForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'submitLabel' => 'Search', 'ajaxTarget' => 'contactListContainer'));
    $oForm->setFormDisplayParams(array('columns' => 2, 'noCancelButton' => '1','fullFloating' => true));

    //Company Name

    $vField = getValue('company_name');
    $oForm->addField('input', 'company_name', array('label' =>'Company Name', 'value' => $vField ));
    $oForm->setFieldControl('company_name', array('jsFieldMinSize' => 2, 'jsFieldMaxSize' => 255));

    if(!$vField && $nFieldToDisplay)
    {
      //force displaying this field if less than 4 fields displayed
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('company_name', array('class' => 'search_cname', 'fieldname' => 'search_cname'));
    }
    else
      $oForm->setFieldDisplayParams('company_name', array('class' => (($vField || $nFieldDisplayed++ < 4)?'':'hidden ').' company_name', 'fieldname' => 'search_cname'));

    $vField = ($nLoginPk || getValue('cpfollowerfk', 0));
    $sURL = $this->coPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1, 'friendly' => 1));
    $oForm->addField('selector', 'cpfollowerfk', array('label'=> 'Account Manager', 'url' => $sURL, 'onchange' =>'$(\'#cascading_id\').parent().parent().find(\'div\').show();'));
    $oForm->setFieldControl('cpfollowerfk', array('jsFieldTypeIntegerPositive' => ''));
    if(!$vField && $nFieldToDisplay)
    {
      //force displaying this field if less than 4 fields displayed
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('cpfollowerfk', array('class' => 'search_manager', 'fieldname' => 'search_manager'));
    }
    else
      $oForm->setFieldDisplayParams('cpfollowerfk', array('class' => (($vField || $nFieldToDisplay < 1)?'':'hidden ').' search_manager', 'fieldname' => 'search_manager'));

    if(!empty($nLoginPk))
    {
      $oLogin = CDependency::getCpLogin();
      $asFolowerData = $oLogin->getUserDataByPk($nLoginPk);

      if(!empty($asFolowerData))
        $oForm->addOption('cpfollowerfk', array('value' => $nLoginPk, 'label' => $oLogin->getUsernameFromData($asFolowerData)));
    }
    else
    {
      $nFollwerfk = (int)getValue('cpfollowerfk', 0);
      if(!empty($nFollwerfk))
      {
        $oLogin =  CDependency::getCpLogin();
        $asFollowerData = $oLogin->getUserDataByPk($nFollwerfk);
        if(!empty($asFollowerData))
          $oForm->addOption('cpfollowerfk', array('value' => $nFollwerfk, 'label' => $oLogin->getUserNameFromData($asFollowerData)));
      }
    }

    //Company Relation

    $vField = getValue('company_relation');
    $oForm->addField('select', 'company_relation', array('label' => ' Relation'));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('company_relation', array('class' => 'search_relation', 'fieldname' => 'search_relation'));
    }
    else
      $oForm->setFieldDisplayParams('company_relation', array('class' => (($vField)?'':'hidden ').' search_relation', 'fieldname' => 'search_relation'));

    $asCompanyRel= getCompanyRelation();
    $sRelation = getValue('company_relation');
    $oForm->addOption('company_relation', array('value'=>'', 'label' => 'Select'));
    foreach($asCompanyRel as $sType=>$vType)
    {
       if($sRelation==$sType)
       $oForm->addOption('company_relation', array('value'=>$sType, 'label' => $vType['Label'],'selected'=>'selected'));
       else
       $oForm->addOption('company_relation', array('value'=>$sType, 'label' => $vType['Label']));
    }


    $vField = (array)getValue('company_industry');
    $oForm->addField('select', 'company_industry[]', array('label' =>' Industry', 'multiple' => 'multiple'));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('company_industry[]', array('class' => 'search_industry', 'fieldname' => 'search_industry'));
    }
    else
      $oForm->setFieldDisplayParams('company_industry[]', array('class' => (($vField)?'':'hidden ').' search_industry', 'fieldname' => 'search_industry'));

    $asIndustry = $this->_getModel()->getIndustry();
    foreach($asIndustry as $nIndustryPk => $asIndustryData)
    {
      if(in_array($nIndustryPk, $vField))
       $oForm->addOption('company_industry[]', array('value'=> $nIndustryPk, 'label' => $asIndustryData['industry_name'], 'selected' => 'selected'));
      else
       $oForm->addOption('company_industry[]', array('value'=> $nIndustryPk, 'label' => $asIndustryData['industry_name']));
    }

    $vField = getValue('phone_cp');
    $oForm->addField('input', 'phone_cp', array('label' => 'Phone', 'value' => $vField));
    $oForm->setFieldControl('phone_cp', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 20));
    $oForm->setFieldDisplayParams('phone_cp', array('class' => 'hidden search_phone'));
    $oForm->setFieldDisplayParams('phone_cp', array('class' => (($vField)?'':'hidden ').' search_phone', 'fieldname' => 'search_phone'));


    $vField = getValue('email_cp');
    $oForm->addField('input', 'email_cp', array('label' => 'Email', 'value' => $vField));
    $oForm->setFieldControl('email_cp', array('jsFieldMinSize' => 2));
    $oForm->setFieldDisplayParams('email_cp', array('class' => 'hidden search_email'));
    $oForm->setFieldDisplayParams('email_cp', array('class' => (($vField)?'':'hidden ').' search_email', 'fieldname' => 'search_email'));

    $vField = getValue('synopsis');
    $oForm->addField('input', 'synopsis', array('label' => 'Synopsis', 'value' => $vField));
    $oForm->setFieldControl('synopsis', array('jsFieldMinSize' => 2));
    $oForm->setFieldDisplayParams('synopsis', array('class' => 'hidden search_synopsis'));
    $oForm->setFieldDisplayParams('synopsis', array('class' => (($vField)?'':'hidden ').' search_synopsis', 'fieldname' => 'search_synopsis'));

    $vField = getValue('address');
    $oForm->addField('input', 'address', array('label' => 'Address', 'value' => $vField));
    $oForm->setFieldControl('address', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 20));
    $oForm->setFieldDisplayParams('address', array('class' => (($vField)?'':'hidden ').' search_address', 'fieldname' => 'search_address'));

    $oEvent = CDependency::getComponentUidByName('event');
    if(!empty($oEvent))
    {
      $vField = getValue('event_type_cp');
      $oForm->addField('select', 'event_type_cp', array('label' => ' Type'));
      $oForm->setFieldDisplayParams('event_type_cp', array('class' => (($vField)?'':'hidden ').' search_evt_type', 'fieldname' => 'search_evt_type'));
      $oForm->addOption('event_type_cp', array('value'=>'', 'label' => 'Select'));

      $asEvent= getEventTypeList();
      $sEventTypes = getValue('event_type_cp');
      foreach($asEvent as $asEvents)
      {
        if($asEvents['value'] == $sEventTypes)
          $oForm->addOption('event_type_cp', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group'], 'selected'=>'selected'));
        else
          $oForm->addOption('event_type_cp', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group']));
      }

      $vField = getValue('event_cp');
      $oForm->addField('input', 'event_cp', array('label' => ' Activity Content', 'value' => $vField));
      $oForm->setFieldControl('event_cp', array('jsFieldMinSize' => 2));
      $oForm->setFieldDisplayParams('event_cp', array('class' => (($vField)?'':'hidden ').' search_evt_content', 'fieldname' => 'search_evt_content'));

      $vField = getValue('date_eventStartcp');
      $oForm->addField('input', 'date_eventStartcp', array('type' => 'date', 'label'=>'Activity From', 'value' => $vField));
      $oForm->setFieldDisplayParams('date_eventStartcp', array('class' => (($vField)?'':'hidden ').' search_evt_from', 'fieldname' => 'search_evt_from'));

      $vField = getValue('date_eventEndcp');
      $oForm->addField('input', 'date_eventEndcp', array('type' => 'date', 'label'=>' Activity To', 'value' => $vField));
      $oForm->setFieldDisplayParams('date_eventEndcp', array('class' => (($vField)?'':'hidden ').' search_evt_to', 'fieldname' => 'search_evt_to'));
    }

    $oCField = CDependency::getComponentByName('customfields');
    if(!empty($oCField))
    {
      $asCField = $oCField->getCustomfields($this->csUid, '', CONST_AB_TYPE_COMPANY);

      if(!empty($asCField))
      {
        $sOption = '<option value="">Custom field</option>';
        $vField = getValue('search_cf');

        foreach($asCField as $asFieldData)
        {
          if($vField == $asFieldData['customfieldpk'])
            $sOption.= '<option value="'.$asFieldData['customfieldpk'].'" selected="selected">'.$asFieldData['label'].'</option>';
          else
            $sOption.= '<option value="'.$asFieldData['customfieldpk'].'">'.$asFieldData['label'].'</option>';
        }

        $sLabel = '<select name="search_cf">'.$sOption.'</select>';
        $vField = getValue('search_cf_value');
        $oForm->addField('input', 'search_cf_value', array('type' => 'text', 'label'=> $sLabel, 'value' => $vField));
        $oForm->setFieldDisplayParams('search_cf_value', array('class' => (($vField)?'':'hidden ').' search_cf', 'fieldname' => 'search_cf'));
      }
    }

    if(isset($_POST['sortfield']))
      $sSortField = $_POST['sortfield'];
    else
      $sSortField = '';

    if(isset($_POST['sortorder']))
      $sSortOrder = $_POST['sortorder'];
    else
      $sSortOrder = '';

    $oForm->addField('hidden', 'sortfield', array('value' =>$sSortField));
    $oForm->addField('hidden', 'sortorder', array('value' => $sSortOrder));
    $oForm->addField('hidden', 'do_search', array('value' => 1));

    return $oForm->getDisplay();
  }

  /**
   * Get the query for company search
   * @return array
   */

  private function _getSqlCompanySearch()
 {

    $sName = getValue('company_name');
    $sPhone = getValue('phone_cp');
    $sEmail = getValue('email_cp');
    $anIndustry = getValue('company_industry',array());
    $nFollower = getValue('cpfollowerfk');
    $sSynopsis = getValue('synopsis');
    $sAddress = getValue('address');
    $sRelation = getValue('company_relation');
    $sEvent = getValue('event_cp');
    $sEventType = getValue('event_type_cp');
    $sStartDate = getValue('date_eventStartcp');
    $sEndDate = getValue('date_eventEndcp');
    $nLoginPk = getValue('loginpk');

    $sCFieldPk = getValue('search_cf');
    $sCFieldValue = getValue('search_cf_value');

    $sSearchMode = getValue('search_mode');

    $oDb = CDependency::getComponentByName('database');
    $asResult = array();
    $asResult['join'] = '';
    $asResult['where'] = '';
    $asWhereSql = array();

    if(!empty($sName))
      $asWhereSql[] = '(lower(cp.company_name) LIKE '.$oDb->dbEscapeString('%'.strtolower($sName).'%').' OR cp.corporate_name LIKE '.$oDb->dbEscapeString('%'.strtolower($sName).'%').')';

    if(!empty($sPhone))
      $asWhereSql[] = ' cp.phone LIKE '.$oDb->dbEscapeString('%'.$sPhone.'%');

    if(!empty($sSynopsis))
      $asWhereSql[] = ' cp.comments LIKE '.$oDb->dbEscapeString('%'.$sSynopsis.'%');

    if(!empty($sRelation))
      $asWhereSql[] = ' cp.company_relation = '.$oDb->dbEscapeString($sRelation);

    if(!empty($sEmail))
      $asWhereSql[] = ' lower(cp.email) LIKE '.$oDb->dbEscapeString('%'.strtolower($sEmail).'%');

    if(!empty($sAddress))
       $asWhereSql[] = ' lower(cp.address_1) LIKE '.$oDb->dbEscapeString('%'.strtolower($sAddress).'%').' OR lower(cp.address_2) LIKE '.$oDb->dbEscapeString('%'.strtolower($sAddress).'%');


    if(!empty($nFollower))
    {
      $asResult['join'].= ' LEFT JOIN addressbook_account_manager as acmn ON (acmn.companyfk = cp.addressbook_companypk AND acmn.loginfk='.$nFollower.') ';
      $asWhereSql[] = ' (acmn.loginfk='.$nFollower.' OR  cp.followerfk = '.$oDb->dbEscapeString($nFollower).')';
    }

    if(!empty($nLoginPk))
      $asWhereSql[] = ' cp.followerfk = '.$oDb->dbEscapeString($nLoginPk);

     if(!empty($anIndustry))
     {
       $asResult['join'].= 'INNER JOIN addressbook_company_industry as cid ON (cid.companyfk = cp.addressbook_companypk)';
       $asResult['join'].= 'INNER JOIN addressbook_industry as indr ON (indr.addressbook_industrypk = cid.industryfk)';

      foreach($anIndustry as $vKey => $nIndustry)
        $anIndustry[$vKey] = $oDb->dbEscapeString($nIndustry);

      $asWhereSql[] = ' indr.addressbook_industrypk IN ('.implode(',', $anIndustry).') ';
    }

    if(!empty($sEvent) || !empty($sEventType) || (!empty($sStartDate) && !empty($sEndDate)))
    {
      $asResult['join'].= 'LEFT JOIN event_link as evelnk ON (evelnk.cp_pk = cp.addressbook_companypk and evelnk.cp_type="cp")';
      $asResult['join'].= 'LEFT JOIN event as even ON (even.eventpk = evelnk.eventfk)';
      $asResult['join'].= 'LEFT JOIN event_link as evelnk2 ON (evelnk2.cp_pk = prf.contactfk and evelnk2.cp_type="ct")';
      $asResult['join'].= 'LEFT JOIN event as even2 ON (even2.eventpk = evelnk2.eventfk)';

      if(!empty($sEvent))
        $asWhereSql[] = ' lower(even.title) like '.$oDb->dbEscapeString('%'.strtolower($sEvent).'%').' OR lower(even.content) like '.$oDb->dbEscapeString('%'.strtolower($sEvent).'%').' OR lower(even2.title) like '.$oDb->dbEscapeString('%'.strtolower($sEvent).'%').' OR lower(even2.content) like '.$oDb->dbEscapeString('%'.strtolower($sEvent).'%');

      if(!empty($sEventType))
      {
        $asWhereSql[] = 'lower(even.type) like '.$oDb->dbEscapeString('%'.strtolower($sEventType).'%');
        $asWhereSql[] = 'lower(even2.type) like '.$oDb->dbEscapeString('%'.strtolower($sEventType).'%');
      }
      if(!empty($sStartDate))

        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") >= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sStartDate)));
      if(!empty($sEndDate))
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") >= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sEndDate)));
      if(!empty($sStartDate) && !empty($sEndDate))
      {
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") >= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sStartDate)));
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") <= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sEndDate)));
      }
    }

    if(!empty($sCFieldPk) && !empty($sCFieldValue))
    {
      $oCField = CDependency::getComponentByName('customfields');
      if(!empty($oCField))
      {
        $asCFSql = $oCField->getSearchSql((int)$sCFieldPk, $sCFieldValue);
        $asResult['join'].= $asCFSql['join'];
        if(!empty($asCFSql['where']))
          $asWhereSql[] = $asCFSql['where'];
      }
    }

    if($sSearchMode == 'or')
      $asResult['where'] =  implode(' OR ', $asWhereSql);
    else
      $asResult['where'] = implode(' AND ', $asWhereSql);

    return $asResult;
  }

   /**
   * Display the company detail information
   * @param array $pasCompanyData
   * @return string HTML
   */

  private function _getCompanyDetailTab($pasCompanyData)
  {
    $oLogin = CDependency::getCpLogin();
    $oCustomFields = CDependency::getComponentByName('customfields');

    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
      return $this->coHTML->getBlocMessage('No data available.');

    $sHTML =  $this->coHTML->getBlocStart('',array('class'=>'containerClass'));

    $asUserData = $oLogin->getUserList((int)$pasCompanyData['creatorfk'], false,true);
    $sUser = $oLogin->getUserNameFromData(current($asUserData));

    $sHTML.= $this->coHTML->getField('','Creation date',getFormatedDate('Y-m-d',$pasCompanyData['date_create']).' - by '.$sUser);
    $sHTML.= $this->coHTML->getField('','Phone',$pasCompanyData['phone']);

    $asUserData = $oLogin->getUserList((int)$pasCompanyData['updated_by'],false,true);
    $sUser = $oLogin->getUserNameFromData(current($asUserData));

    $sHTML.= $this->coHTML->getField('','Edited date',getFormatedDate('Y-m-d',$pasCompanyData['date_update']).' - by '.$sUser);
    $sHTML.= $this->coHTML->getField('','Fax',$pasCompanyData['fax']);
    $sHTML.= $this->coHTML->getField('','Address',$this->_getAddress($pasCompanyData,','));
    $sHTML.= $this->coHTML->getField('','Website',$this->coHTML->getText('<a href='.$pasCompanyData['website'].' target="_blank;">'.$pasCompanyData['website'].'</a>'));

    $sHTML.= $oCustomFields->displayCustomFields($this->getCpValues());

    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Display the company employees
   * @param array $pasCompanyData
   * @return string HTML
   */

  private function _getCompanyEmployeeTab($pasCompanyData)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
      return $this->coHTML->getBlocMessage('No data available.');


    //Search in database all the contacts in this company
    $nCompanyPk = (int)$pasCompanyData['addressbook_companypk'];

    $oResult = $this->_getModel()->getCompagnyContacts($nCompanyPk);
    $bRead = $oResult->readFirst();

    $sURL  = $this->coPage->getUrl('addressbook', CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, array('cppk' => $nCompanyPk));
    $sHTML = $this->coHTML->getActionButton('Add connection', $sURL, $this->getResourcePath().'pictures/ct_add_16.png');
    $sHTML.= $this->coHTML->getCR(2);

    if(!$bRead)
    {
      $sHTML.= $this->coHTML->getBlocMessage('No employee found in this company.', true);
      return $sHTML;
    }

    $sHTML.= $this->_getContactRowSmallHeader();

    $nCount = 0;
    while($bRead)
    {
      $asContactData = $oResult->getData();
      $sHTML.= $this->_getContactRow($asContactData, $nCount,1);
      $nCount++;
      $bRead = $oResult->readnext();
    }
    return $sHTML;
  }

  /**
   * Display event tab for the company
   * @param array $pasCompanyData
   * @return string HTML
   */

  private function _getCompanyEventTab($pasCompanyData)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
      return $this->coHTML->getBlocMessage('No data available to fetch activities.');

    $oEvent = CDependency::getComponentByName('event');
    $sHTML = $this->coHTML->getBlocStart();

    $asCpValues = array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, CONST_CP_PK => (int)$pasCompanyData['addressbook_companypk']);

    if(!empty($oEvent))
    {
      $sEventList = $oEvent->getEventList($asCpValues, 0);
      $sUrl = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, $asCpValues);
      $sHTML.= $this->coHTML->getActionButton('Add a new activity', '', $oEvent->getResourcePath().'pictures/add_event_16.png',
        array('onclick' => 'var oConf = goPopup.getConfig();
            oConf.height = 700;
            oConf.width = 980;
            oConf.title = \'Add an activity...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); '));
      $sHTML.= $this->coHTML->getCR(2);

      if(empty($sEventList))
      {
        $sHTML.= $this->coHTML->getBlocMessage('No activities found for this company.', true);
      }
      else
      {
        $sHTML.= $sEventList;
      }
    }

    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
  }

  /**
   * STEF: WTF?? we've got a full profile management, don't know  what is that?
   * TODO: Will remove soon if no bug spotted
   * Link the company to the connection
   * @param integer $pnCompanyPk
   * @return ajax data
   */
  /*private function _getLinkCompanyContact($pnCompanyPk)
  {
     if(!assert('is_integer($pnCompanyPk) && !empty($pnCompanyPk)'))
      return $this->coHTML->getBlocMessage('No company found.');


    $sHTML = $this->coHTML->getBlocStart();
    //Start the form
    $oForm = $this->coHTML->initForm('linkContactForm');
    $sFormId = $oForm->getFormId();

    //Get javascript for the popup
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEEDIT, CONST_AB_TYPE_COMPANY, $pnCompanyPk);
    $sJs = $this->coHTML->getAjaxJs($sURL, 'body', $sFormId);
    $oForm->setFormParams('', false, array('action' => '','inajax'=> 1, 'onsubmit' => 'event.preventDefault(); '.$sJs));

    //Close button on popup and remove cancel button
    $oForm->setFormDisplayParams(array('noCancelButton' => '1','noCloseButton' => '1'));
    $sHTML.= $this->coHTML->getBlocStart();
    $asCompanyData = $this->_getModel()->getCompanyByPk($pnCompanyPk);
    $oForm->addField('misc', '', array('type' => 'title', 'title' => '<span class="h4">Connect connection to '.$asCompanyData['company_name'].'</span><br />'));
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, CONST_AB_TYPE_CONTACT);
    $oForm->addField('selector', 'contactfk', array('label'=> 'Connection', 'url' => $sURL, 'nbresult' =>1));
    $oForm->setFieldControl('contactfk', array('jsFieldNotEmpty' => ''));
    $oForm->addField('input', 'position', array('label'=> 'Position', 'value' =>''));
    $oForm->setFieldControl('position', array('jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));
    $oForm->addField('input', 'email', array('label'=> 'Email Address', 'value' =>''));
    $oForm->setFieldControl('email', array('jsFieldTypeEmail' => '','jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => ''));
    $oForm->setFieldControl('phone', array('jsFieldNotEmpty' => '','jsFieldMinSize' => 4));
    $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => ''));
    $oForm->setFieldControl('fax', array('jsFieldMinSize' => 8));
    $oForm->addField('textarea', 'address', array('label'=> 'Address ', 'value' =>''));
    $oForm->setFieldControl('address', array('jsFieldNotEmpty' => ''));
    $oForm->addField('input', 'postcode', array('label'=> 'Postcode', 'value' => ''));
    $oForm->setFieldControl('postcode', array('jsFieldTypeIntegerPositive' => '', 'jsFieldMaxSize' => 12));
    $oForm->addField('selector_city', 'cityfk', array('label'=> 'City', 'url' => CONST_FORM_SELECTOR_URL_CITY));
    $oForm->setFieldControl('cityfk', array('jsFieldTypeIntegerPositive' => ''));
    $oForm->addField('selector_country', 'countryfk', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
    $oForm->setFieldControl('countryfk', array('jsFieldTypeIntegerPositive' => ''));
    $oForm->addField('misc', '', array('type'=> 'br'));
    $sHTML.= $oForm->getDisplay();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    $asresult = array('data' => $sHTML);
    return $this->coPage->getAjaxExtraContent($asresult);
  }

  private function _saveContactProfil($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk) && !empty($pnContactPk)'))
      return array('error' => 'No connection found.');

    if(empty($_POST['companyfk']))
      return array('alert'=>'Please select the company');

    $oResult = $this->_getModel()->add($_POST,'profil');

    if($oResult==0)
      return array('error' => __LINE__.' - Couldn\'t save the connection details');

    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnContactPk, CONST_ACTION_DELETE);
    return array('notice' => 'Connection details has been updated', 'url' => $sURL);
  }
   *
   */

  /**
   * Display the Header for the company listing
   * @param string $psSearchId
   * @return string HTML
   */

  private function _getCompanyRowHeader($psSearchId = '')
  {
    $sHTML = $this->coHTML->getBlocStart('', array('class' =>'listCp_row '));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'listCp_row_data'));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'leftMedium'));

    //fetch sortorder from the history
    $asOrder = $this->_getHistorySearchOrder($psSearchId, $this->csUid, CONST_AB_TYPE_COMPANY);
    $sSortField = strtolower($asOrder['sortfield']);
    $sSortOrder = strtolower($asOrder['sortorder']);

    $sUrl = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY, 0, array('searchId' => $psSearchId));

    $sHTML.= '<input type="checkbox" />';
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('cpName', array('class' => 'ab_list_cell cp_list_cell cp_search','sort_name'=>'company_name','style' =>'width:12%; padding-left: 2px;'));
    $sHTML.= $this->coHTML->getText('Name');
    $sHTML.= $this->coHTML->getSpace(2);

    if($sSortField == 'company_name' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z', '', array('class'=>'moveupCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=company_name&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveupCp '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'company_name' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedownCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=company_name&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedownCp'));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cp_list_cell','style' =>'width:25%;'));
    $sHTML.= $this->coHTML->getText('Account Manager');
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('cpIndustry', array('class' => ' ab_list_cell cp_list_cell cp_search','sort_name'=>'industry_name','style' =>'width:15%;'));
    $sHTML.= $this->coHTML->getText('Industry');
    $sHTML.= $this->coHTML->getSpace(2);

    if($sSortField == 'industry' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z', '', array('class'=>'moveupCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=industry&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveupCp'));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'industry' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedownCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=industry&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedownCp'));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell', 'style' =>'width:20%;float:left;'));
    $sHTML.= $this->coHTML->getText('Recent activity');

    if($sSortField == 'activity' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'Oldest First', '', array('class'=>'moveupCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=activity&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'Oldest First', '', array('class'=>'moveupCp'));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }


    if($sSortField == 'activity' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Recent First','',array('class'=>'movedownCp'));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=activity&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Recent First', '', array('class'=>'movedownCp'));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'cp_list_cell', 'style' =>'float:right;'));
    $sHTML.= $this->coHTML->getText('Action');
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'floatHack'));
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Return a company list row line with company details
   * @param type $asCompanyData
   * @param type $nRow
   * @return type string(html)
   */

  private function _getCompanyRow($pasCompanyData, $pnRow)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
      return 'No company found.';

    $sId = 'id_'.$pasCompanyData['addressbook_companypk'];

    if(($pnRow%2) == 0)
      $sRowClass = '';
    else
      $sRowClass = 'list_row_data_odd';

    if(!empty($pasCompanyData['child_pk']))
      $sChildClass = ' list_smaller_row ';
    else
      $sChildClass = '';

    $sHTML= $this->coHTML->getBlocStart($sId, array('class' =>'list_row '.$sChildClass));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_row_data '.$sRowClass));

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'leftMedium '.$sRowClass));
    $sHTML.= '<input type="checkbox" />';
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_cell cp_list_name '.$sRowClass, 'style' =>'width:20%; padding-left: 2px;'));
    $sCompanyRelation = getCompanyRelation($pasCompanyData['company_relation']);
    $sHTML.= $this->coHTML->getBlocStart('',array('class' => 'imgClass '.$sRowClass));
    $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/'.$sCompanyRelation['icon_small'], 'Relation', '');
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->_getCompanyRow_companyName($pasCompanyData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_cell '.$sRowClass,'style' =>'width:20%;'));
    $sHTML.= $this->_getCompanyRow_accountManager($pasCompanyData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_cell '.$sRowClass,'style' =>'width:15%;'));
    $sHTML.= $this->_getCompanyRow_IndustryInfo($pasCompanyData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_cell '.$sRowClass, 'style' =>'width:20%;float:left;'));
    $sHTML.= $this->_getCompanyRow_companyActivity($pasCompanyData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_cell '.$sRowClass, 'style' =>'float:right;'));
    $sHTML.= $this->_getCompanyRow_companyAction($pasCompanyData);
    $sHTML.= $this->coHTML->getBlocEnd();

    //display child companies links
    if(!empty($pasCompanyData['child_pk']))
    {
      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'float:left; width: 80%; margin: 0 0 5px 35px; padding: 2px 5px; border-left: 2px solid #ccc; '));
      $sHTML.= $this->coHTML->getText('Child company: ', array('style' => 'cursor: help;', 'title' => 'Display the child/subsidery companies.'));

        $asChildCompany = explode(',', $pasCompanyData['child_pk']);
        $asChildCpName = explode(',', $pasCompanyData['child_name']);

        foreach($asChildCompany as $nKey => $sPk)
        {
          if(!isset($asChildCpName[$nKey]))
            $asChildCpName[$nKey] = ' ## ';

          $sUrl = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$sPk);
          $asChildCompany[$nKey] = $this->coHTML->getLink($asChildCpName[$nKey], $sUrl);
        }

        $sHTML.= implode(', ', $asChildCompany);

      $sHTML.= $this->coHTML->getBlocEnd();
    }


    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
  }

  /**
   * Get the  name while listing company records
   * @param array $asCompanyData
   * @return string HTML
   */

  private function _getCompanyRow_companyName($asCompanyData)
  {
    if(!assert('is_array($asCompanyData) && !empty($asCompanyData)'))
      return 'Can not find the company';

    $nCompanyPK = (int)$asCompanyData['addressbook_companypk'];

    if(isset($asCompanyData['company_name']) && !empty($asCompanyData['company_name']))
      $asCompany[] = $asCompanyData['company_name'];

    if(isset($asCompanyData['corporate_name']) && !empty($asCompanyData['corporate_name']))
      $asCompany[] = $asCompanyData['corporate_name'];

    $nParentfk = 0;
    if(isset($asCompanyData['parentfk']) && !empty($asCompanyData['parentfk']))
      $nParentfk = (int)$asCompanyData['parentfk'];

    if(!assert('!empty($asCompany)'))
       return 'Can not find the company';

    $sHTML = '';

    $sURL = $this->coPage->getUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPK);
    $sHTML.= $this->coHTML->getLink($asCompany[0], $sURL, array('class' => 'h4'));

    if(isset($asCompany[1]))
      $sHTML.= $this->coHTML->getCR() . $this->coHTML->getLink($asCompany[1], $sURL, array('class' => 'h5'));

    if(!empty($nParentfk))
    {
      $sHTML.= $this->coHTML->getCR();
      $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'margin: 3px 0 5px 0; padding: 2px 5px; border-left: 2px solid #ccc;'));
        $sURL = $this->coPage->getUrl($this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nParentfk);
        $sHTML.= $this->coHTML->getText('Holding: ', array('class' => 'text_normal text_small ', 'style' =>'cursor: help;',  'title' => 'Display the current holding company.'));
        $sHTML.= $this->coHTML->getLink($asCompanyData['parent_company'], $sURL);
      $sHTML.= $this->coHTML->getBlocEnd();
    }

    return $sHTML;
  }

/**
 * Display the full name of the account manager of the company
 * @param array $pasCompanyData
 * @return string HTML
 */

  private function _getCompanyRow_accountManager($pasCompanyData)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
     return '';

    $oLogin = CDependency::getCpLogin();

    $sHTML = $oLogin->getUserAccountName($pasCompanyData['follower_lastname'], $pasCompanyData['follower_firstname'], true);
    return $sHTML;
  }

  /**
   * Display the activity of the company
   * @param array $asCompanyData
   * @return string HTML
   */

  private function _getCompanyRow_companyActivity($pasCompanyData)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
     return '';

    $oEvent = CDependency::getComponentByName('event');
    if(empty($oEvent))
      return '';

    $sHTML = '';

    if(!empty($pasCompanyData['title']))
       $sEventTitle = $pasCompanyData['title'];
    else
       $sEventTitle = '';

    if(!empty($pasCompanyData['content']))
       $sEventContent = $this->coHTML->utf8_strcut(strip_tags($pasCompanyData['content']),200);
    else
       $sEventContent = '';

    if(!empty($pasCompanyData['date_display']))
       $sDateDisplay = $pasCompanyData['date_display'];
    else
       $sDateDisplay = '';

    if($pasCompanyData['itemtype'] == 'ct')
    {
        $nContactPk = $pasCompanyData['itempk'];
        $oContactDetails = $this->_getModel()->getByPk((int)$nContactPk, 'addressbook_contact');
     }

     if(!empty($oContactDetails))
       $sEvent = '<strong>Latest Activity on '.$oContactDetails->getFieldValue('firstname').' '.$oContactDetails->getFieldValue('lastname').'</strong><br/>';
      else
       $sEvent = '';

     if(!empty($sEventTitle))
       $sEvent.= $sEventTitle.'<br/>';

      $sEvent.= $sEventContent;


    if(!empty($sEvent))
    {
     $sHTML.= $this->coHTML->getBlocStart('',array('style'=>'float:left;width:40px;'));
     $sHTML.= $this->coHTML->getText(date('m/y',strtotime($sDateDisplay)));
     $sHTML.= $this->coHTML->getBlocEnd();

     $sHTML.= $this->coHTML->getBlocStart('',array('class' => 'imgClass  activityClass','title'=>$sEvent));
     $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/list_event.png', 'Activities', '',array('onmouseover'=>'showActivityPopup(this);','onmouseout'=>"hideActivityPopup();"));
     $sHTML.= $this->coHTML->getBlocEnd();
    }
    else
     $sHTML.=  $this->coHTML->getText('-', array('class' => 'light italic spanCenteredCompany'));

    return $sHTML;

  }

  /**
   * Display the action buttons for company records
   * @param array $asCompanyData
   * @return string HTML
   */

  private function _getCompanyRow_companyAction($asCompanyData)
  {
     if(!assert('is_array($asCompanyData) && !empty($asCompanyData)'))
      return '';

    $oOpportunity = CDependency::getComponentByName('opportunity');
    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_DELETE,$this->getType(),0);
    $oEvent = CDependency::getComponentByName('event');

    $nCompanyPk = (int)$asCompanyData['addressbook_companypk'];
    $sHTML = '';

    $aCpValues = array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, CONST_CP_PK => $nCompanyPk);


    if(!empty($oOpportunity) && $oRight->canAccess('555-123', CONST_ACTION_ADD, CONST_OPPORTUNITY) )
    {
      $sHTML.= ' '.$oOpportunity->displayAddLink($aCpValues, false);
      $sHTML.= $this->coHTML->getSpace(2);
    }

    $sPic = $this->getResourcePath().'/pictures/ct_add_16.png';
    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, array('cppk' => $nCompanyPk));
    $sHTML.= $this->coHTML->getPicture($sPic, 'Add connection', $sURL);
    $sHTML.= $this->coHTML->getSpace(2);

    if(!empty($oEvent))
    {
     $sUrl = $this->coPage->getUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, CONST_CP_PK => $nCompanyPk));
     $sHTML.= $this->coHTML->getLink($this->coHTML->getPicture($oEvent->getResourcePath().'pictures/add_event_16.png', 'Add activity'),$sUrl,array('title'=>'Add activity'));
     $sHTML.= $this->coHTML->getSpace(2);
     }

     $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_EDIT, CONST_AB_TYPE_COMPANY, $nCompanyPk, array(CONST_URL_ACTION_RETURN => CONST_ACTION_LIST));
     $sHTML.= $this->coHTML->getPicture(CONST_PICTURE_EDIT, 'Edit company', $sURL);
     $sHTML.= $this->coHTML->getSpace(2);

    if($sAccess)
    {
     $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_DELETE, CONST_AB_TYPE_COMPANY, $nCompanyPk);
     $sPic= $this->coHTML->getPicture(CONST_PICTURE_DELETE,'Delete company');
     $sHTML.= ' '.$this->coHTML->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\'Delete this company ?\')){ return false; }'));
    }


    return $sHTML;
  }

  /**
   * Display the form to add/edit the company details
   * @param integer $pnPK
   * @return string HTML
   */

  private function _formCompany($pnPK, $pbAjax = false)
  {
    if(!assert('is_integer($pnPK)'))
      return '';

    $bIsEdition = !empty($pnPK);

    $oLogin = CDependency::getCpLogin();
    $this->coPage->addCssFile(array($this->getResourcePath().'css/addressbook.css'));
    $this->coPage->addJsFile($this->getResourcePath().'js/formCompany.js');

    $oDB = CDependency::getComponentByName('database');
    $oDB->dbConnect();

    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_TRANSFER,$this->getType(),0);

    $sQuery = 'SELECT * FROM addressbook_industry ORDER BY industry_name asc';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asIndustries = array();
    $asSelectIndustry = array();
    while($bRead)
    {
      $asIndustries[] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }
    $asSelectManager = array();

    //If editing the company
    if($bIsEdition)
    {
      $sQuery = 'SELECT cp.*, l.lastname as follower_lastname, l.firstname as follower_firstname FROM `addressbook_company` as cp';
      $sQuery.= ' LEFT JOIN shared_login as l ON (l.loginpk = cp.followerfk) ';
      $sQuery.= ' WHERE addressbook_companypk = '.$this->cnPk.' ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      if(!$bRead)
        return __LINE__.' - The company doesn\'t exist.';

      $sQuery = 'SELECT industryfk FROM addressbook_company_industry WHERE companyfk='.$pnPK;
      $oResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oResult->readFirst();

      $asSelectIndustry = array();
      while($bRead)
      {
        $asSelectIndustry[] = $oResult->getFieldValue('industryfk');
        $bRead = $oResult->readNext();
      }
    }
    else
      $oDbResult = new CDbResult();

    if($this->coPage->getActionReturn())
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEADD, CONST_AB_TYPE_COMPANY, $pnPK, array(CONST_URL_ACTION_RETURN => $this->coPage->getActionReturn()));
    else
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEADD, CONST_AB_TYPE_COMPANY,$pnPK);

    $sHTML= $this->coHTML->getBlocStart();

    //div including the form
    $sHTML.= $this->coHTML->getBlocStart('');

    $oForm = $this->coHTML->initForm('cpAddForm');
    $oForm->setFormParams('', true, array('submitLabel' => 'Save', 'action' => $sURL, 'id' => 'formCompany'));

    if($pbAjax)
    {
      //replace cancel by a close button
      $oForm->setFormDisplayParams(array('noCancelButton' => true));

      $sHTML.= $this->coHTML->getBlocStart('',array('style'=>'border:1px solid #CECECE;float:right;width:240px;font-size:13px;position:absolute;top:165px;right:39px;background-color:#FAF9FB;padding:10px;border-radius:20px;'));
    }
    else
    {
      //in a normal page and when adding, we remove buttons to offer actions
      if(empty($pnPK))
        $oForm->setFormDisplayParams(array('noCancelButton' => true, 'noSubmitButton' => true));

      $sHTML.= $this->coHTML->getBlocStart('',array('style'=>'float:right;width:290px;font-size:12px;position:absolute;top:165px;right:15px;background-color:#FAF9FB;padding:10px 0;'));
    }

    $sHTML.= $this->coHTML->getBloc('', 'Synopsis tips', array('class'=>'h3'));
    $sHTML.= $this->coHTML->getCR();

      $sHTML.= $this->coHTML->getBlocStart('', array('style'=>'background-color: #f4f4f4; padding: 10px 5px; border: 1px solid #e0e0e0;'));
      $sHTML.= $this->coHTML->getText('Synopsis should include the following points:');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('1. What kind of media buying has the company done in the past (print, digital)');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('2. Does it have a sophisticated understanding of media ');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('3. Would they be interested to purchase; SEO, wed design, ad campaigns, co-branded weekender, etc. ');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('4. What would they like to see change in the Weekender ');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('5. What are the other ways in which we can work with them?');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('6. Are they high potential client? ');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getText('7. Are they a potential collaborator? ');
      $sHTML.= $this->coHTML->getCR(2);
      $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();

    $oForm->addField('misc', '', array('type' => 'title','title'=> 'Company details'));
    $oForm->addField('input', 'doubleChecked', array('type' => 'hidden', 'value' => (int)!empty($pnPK), 'id' => 'doubleCheckedId'));
    $oForm->addField('input', 'inpopup', array('type' => 'hidden', 'value' => (int)$pbAjax));

    $oForm->addField('input', 'name', array('label'=>'<strong>Public Name</strong>', 'class' => '', 'value' => $oDbResult->getFieldValue('company_name')));
    $oForm->setFieldControl('name', array('jsFieldMinSize' => '2', 'jsFieldMaxSize' => 255, 'jsFieldNotEmpty' => ''));

    $oForm->addField('input', 'corporate', array('label'=> 'Legal Name', 'value' => $oDbResult->getFieldValue('corporate_name')));
    $oForm->setFieldControl('corporate', array('jsFieldMinSize' => '2', 'jsFieldMaxSize' => 255));

    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, CONST_AB_TYPE_COMPANY);
    $oForm->addField('selector', 'parent', array('label'=> 'Holding company', 'url' => $sURL));
    $nParentFk = $oDbResult->getFieldValue('parentfk', CONST_PHP_VARTYPE_INT);
    if($nParentFk > 0)
    {
      $asCompanyData = $this->_getModel()->getCompanyByPk($nParentFk);
      $sLabel = $asCompanyData['company_name'];
      if(isset($asCompanyData['corporate_name']))
        $sLabel.= ' - '.$asCompanyData['corporate_name'];

      $oForm->addOption('parent', array('label' => $sLabel, 'value' => $nParentFk));
    }

    $oForm->addField('select', 'industries[]', array('label' => 'Industries', 'multiple' => 'multiple'));
    $oForm->setFieldControl('industries[]', array('jsFieldNotEmpty' => ''));

    foreach($asIndustries as $asIndustryData)
    {
      if(in_array($asIndustryData['addressbook_industrypk'],$asSelectIndustry))
      $oForm->addOption('industries[]', array('value'=>$asIndustryData['addressbook_industrypk'], 'label' => $asIndustryData['industry_name'], 'selected' => 'selected'));
      else
       $oForm->addOption('industries[]', array('value'=>$asIndustryData['addressbook_industrypk'], 'label' => $asIndustryData['industry_name']));
     }


    $sURL = $this->coPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1, 'friendly' => 1));
    $oForm->addField('selector', 'cp_account_manager', array('label' => 'Account Manager', 'nbresult' => 5, 'url' => $sURL));
    $oForm->setFieldControl('cp_account_manager', array('jsFieldNotEmpty' => ''));

    $asManagers = $oLogin->getUserList(0, false, true);

    if($oDbResult->getFieldValue('addressbook_companypk'))
      $asSelectManager = $this->_getModel()->getAccountManager((int)$oDbResult->getFieldValue('addressbook_companypk'), 'addressbook_company');
    else
     $asSelectManager[0] = $oLogin->getUserPk();

    foreach($asSelectManager as $nLoginPk)
      $oForm->addOption('cp_account_manager', array('value'=> $nLoginPk, 'label' => $asManagers[$nLoginPk]['firstname'].' '.$asManagers[$nLoginPk]['lastname']));


    //we need to hide the field if an user EDIT the company is NOT an accountmanager of
    if(!$sAccess && !empty($pnPK) && !in_array($oLogin->getuserPk(), $asSelectManager))
    {
      $oForm->setFieldDisplayParams('cp_account_manager', array('class' => 'hidden'));
    }



    // Drop down for the company relation
    $oForm->addField('select', 'type', array('label' => 'Company Type'));
    $oForm->setFieldControl('type', array('jsFieldNotEmpty' => ''));
    $asCompanyRel= getCompanyRelation();
    $oForm->addOption('type', array('value'=>'', 'label' => 'Select'));
    foreach($asCompanyRel as $sType=>$vType)
    {
      if($sType == $oDbResult->getFieldValue('company_relation'))
      $oForm->addOption('type', array('value'=>$sType, 'label' => $vType['Label'],'selected'=>'selected'));
      else
      $oForm->addOption('type', array('value'=>$sType, 'label' => $vType['Label']));
    }
    $oForm->addField('textarea', 'comments', array('label'=> 'Synopsis ', 'value' =>$oDbResult->getFieldValue('comments')));
    $oForm->setFieldControl('comments', array('jsFieldMinSize' => 5));

    $oForm->addField('misc', '', array('type' => 'br'));
    $oForm->addField('misc', '', array('type' => 'title','title'=> 'Contact information'));
    $oForm->addField('input', 'email', array('label'=> 'Email', 'value' => $oDbResult->getFieldValue('email')));
    $oForm->setFieldControl('email', array('jsFieldTypeEmail' => ''));

    $oForm->addField('input', 'website', array('label'=> 'Website', 'value' => $oDbResult->getFieldValue('website')));
    $oForm->setFieldControl('website', array('jsFieldTypeUrl' => ''));

    $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => $oDbResult->getFieldValue('phone')));
    $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => $oDbResult->getFieldValue('fax')));
    $oForm->addField('misc', '', array('type'=> 'br'));
    $oForm->addField('input', 'address_1', array('label'=> 'Adress 1', 'value' => $oDbResult->getFieldValue('address_1')));
    $oForm->addField('input', 'address_2', array('label'=> 'Adress 2', 'value' => $oDbResult->getFieldValue('address_2')));
    $oForm->addField('input', 'postcode', array('label'=> 'Postcode', 'value' => $oDbResult->getFieldValue('postcode')));
    $oForm->setFieldControl('postcode', array('jsFieldJpPostCode' => 1));

    $oForm->addField('selector_city', 'cp_cityfk', array('label'=> 'City', 'url' => CONST_FORM_SELECTOR_URL_CITY));
    $oForm->setFieldControl('cp_cityfk', array('jsFieldTypeIntegerPositive' => ''));
    $nCityFk = $oDbResult->getFieldValue('cityfk', CONST_PHP_VARTYPE_INT);
    if(!empty($nCityFk))
      $oForm->addCitySelectorOption('cp_cityfk', $nCityFk);

    $oForm->addField('selector_country', 'cp_countryfk', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
    $oForm->setFieldControl('cp_countryfk', array('jsFieldTypeIntegerPositive' => ''));
    $nCountryFk = $oDbResult->getFieldValue('cp_countryfk', CONST_PHP_VARTYPE_INT);

    if(!empty($nCountryFk))
      $oForm->addCountrySelectorOption('cp_countryfk', $nCountryFk);
    else
       $oForm->addCountrySelectorOption('cp_countryfk',107);

    $oForm->addField('misc', '', array('type'=> 'br'));

    if(!$pbAjax && empty($pnPK))
    {
      $oForm->addField('input', 'relact', array('type'=> 'hidden'));

      //classic save button
      $sButtons = $this->coHTML->getBlocStart('', array('style' =>'width: 390px; margin: 0 auto;'));
      $sButtons.= $this->coHTML->getActionButton('Save & add connection', 'javascript:;', '', array('style' => 'float: left; height: 16px; line-height: 16px;', 'onclick' => '$(this).closest(\'form\').find(\'input[name=relact]\').val(\'\'); $(this).closest(\'form\').submit();'));

      $sButtons.= $this->coHTML->getText('&nbsp;or&nbsp;&nbsp;&nbsp;', array('style' => 'float: left; display: block;'));

      //independent buttons displayed base on an array of actions
      $sUid = CDependency::getComponentUidByName('event');
      if(!empty($sUid))
        $asButtons[] = array('url' => '', 'label' => 'Save & add an activity',  'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=relact]\').val(\''.$sUid.'\'); $(this).closest(\'form\').submit();'));

      $sUid = CDependency::getComponentUidByName('opportunity');
      if(!empty($sUid) && $oRight->canAccess('555-123', CONST_ACTION_ADD, CONST_OPPORTUNITY))
        $asButtons[] = array('url' => '', 'label' => 'Save & add an opportunity', 'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=relact]\').val(\''.$sUid.'\'); $(this).closest(\'form\').submit();'));

      $asButtons[] = array('url' => '', 'label' => 'Save & view company', 'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=relact]\').val(\''.$this->csUid.'\'); $(this).closest(\'form\').submit();'));

      $sButtons.= $this->coHTML->getActionButtons($asButtons, 1, 'Save and ...', array('style' => 'float: left;'));

      $sButtons.= $this->coHTML->getBlocEnd();
      $oForm->addCustomButton($sButtons);
    }

    $oForm->addCustomFields($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $pnPK, 'folded');

    $sHTML.= $oForm->getDisplay();
    $sUrlCheck = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCHDUPLICATES, CONST_AB_TYPE_COMPANY, $pnPK);
    $sHTML.= $this->coHTML->getBloc('duplicates', $this->coHTML->getTitle('Checking duplicates', 'h4'). $this->coHTML->getPicture(CONST_PICTURE_SMALL_LOADING), array('url' => $sUrlCheck));

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Save the company information
   * @param integer $pnPK
   * @return string
   */
  private function _saveCompany($pnPK = 0)
  {
    if(!assert('is_integer($pnPK)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $oLogin = CDependency::getCpLogin();

    $sCompanyName = getValue('name');
    $sCorporateName = getValue('corporate');
    $sAddress = getValue('address_1');
    $sEmail = getValue('email');
    $sWebsite = getValue('website');
    $sPhone = getValue('phone');
    $sFax = getValue('fax');
    $sComments = getValue('comments');
    $sFollowers = getValue('cp_account_manager');
    $asIndustry = getValue('industries');
    $sCompanyRelation = getValue('type');
    $bDoubleEntryControl = (bool)getValue('doubleChecked', 0);
    $bInpopup = (bool)getValue('inpopup', 0);

    if(empty($asIndustry) || !is_arrayOfInt($asIndustry))
      return array('error' => 'At least one industry is required.');

    if(empty($sFollowers))
      return array('error' => __LINE__.' - At least one account manager is required.');

    $asFollowers = explode(',', $sFollowers);
    if(!is_arrayOfInt($asFollowers))
      return array('error' => __LINE__.' - At least one account manager is required.');

    /*  if(empty($pnPK) && $bDoubleEntryControl == 0)
        $sPopupHtml= $this->_getCheckDuplicates('cp', $sEmail, $sCompanyName, $sCorporateName, $sAddress, $sPhone, $sFax);
  */
    if(!empty($sPopupHtml))
    {
      $sJavascript = '
        var oConf = goPopup.getConfig();
        oConf.width = 500;
        oConf.height = 475;
        oConf.modal = true;
        sPopupId = goPopup.setLayerByConfig("", oConf, "'.addslashes($sPopupHtml).'");
        ';
      return array('action' => $sJavascript);
    }

    //TODO: check parameters !!!!
    if(!empty($sEmail) && !isValidEmail($sEmail))
      return array('error' => 'The email address is not valid. ');

    if(!empty($sWebsite) && !getValue('forceUrl'))
    {
      $sWebsite = formatUrl($sWebsite);
      $nUrlCheck = isValidUrl($sWebsite, false, true);

      if($nUrlCheck === 0)
        return array('error' => 'The url is not valid. ['.$sWebsite.']');
      elseif($nUrlCheck === -1)
      {
        return array('action' => 'if(window.confirm(\'The site is not responding. Want to save anyway ?\'))
          {
            var sAction = $(\'#cpAddFormId\').attr(\'action\');
            $(\'#cpAddFormId\').attr(\'action\', sAction+\'&forceUrl=1\');
            $(\'#cpAddFormId input[type=submit]\').click();
          }');
        //return array('error' => 'The url points towards a site that is not responding. ['.$sWebsite.']');
      }
    }

    //TODO: check parameters !!!!

    //Editing the company
    if(!empty($pnPK))
    {
      $oDbResult = $this->_getModel()->getByPk($pnPK, 'addressbook_company');
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('error' => __LINE__.' - Company doesn\'t exist.');

      $asCompanyData = $oDbResult->getData();
      $bCascading = (bool)getValue('cascading', false);

      $sCompanyName = getValue('name');
      $nFollowerFk = (int)$asFollowers[0];


      //If the user changes the synopsis, we log it in the activities
      $asSetting = CDependency::getComponentByName('settings')->getSystemSettings('ab_log_comments');
      if(!empty($asSetting['ab_log_comments']) && !empty($sComments) && $sComments != $asCompanyData['comments'])
      {
        $oEvent = CDependency::getComponentByName('event');
        if($oEvent)
        {
          $sContent = 'Previous entry was: <br /><br />';
          $sContent.= $asCompanyData['comments'];

          $oEvent->quickAddEvent('new-synopsis', 'Synopsis changed', $sContent, $this->csUid, CONST_AB_TYPE_COMPANY, CONST_ACTION_VIEW, $pnPK, true);
        }
      }

      $sQuery = 'UPDATE addressbook_company SET  ';
      $sQuery.= ' company_name = '.$oDB->dbEscapeString($sCompanyName).', ';
      $sQuery.= ' corporate_name = '.$oDB->dbEscapeString(getValue('corporate')).', ';
      $sQuery.= ' email = '.$oDB->dbEscapeString(getValue('email')).', ';
      $sQuery.= ' parentfk = '.$oDB->dbEscapeString(getValue('parent', 0)).', ';
      $sQuery.= ' phone = '.$oDB->dbEscapeString(getValue('phone')).', ';
      $sQuery.= ' fax = '.$oDB->dbEscapeString(getValue('fax')).', ';
      $sQuery.= ' website = '.$oDB->dbEscapeString($sWebsite).', ';
      $sQuery.= ' address_1 = '.$oDB->dbEscapeString(getValue('address_1')).', ';
      $sQuery.= ' address_2 = '.$oDB->dbEscapeString(getValue('address_2')).', ';
      $sQuery.= ' postcode = '.$oDB->dbEscapeString(getValue('postcode')).', ';
      $sQuery.= ' cityfk = '.$oDB->dbEscapeString(getValue('cp_cityfk', 0)).', ';
      $sQuery.= ' countryfk = '.$oDB->dbEscapeString(getValue('cp_countryfk', 0)).', ';
      $sQuery.= ' date_update = '.$oDB->dbEscapeString(date('Y-m-d H:i:s')).',';
      $sQuery.= ' company_relation = '.$oDB->dbEscapeString($sCompanyRelation).',';
      $sQuery.= ' updated_by = '.$oDB->dbEscapeString($oLogin->getUserPk()).',';
      $sQuery.= ' comments = '.$oDB->dbEscapeString($sComments).',';
      $sQuery.= ' followerfk ='.$nFollowerFk.'';
      $sQuery.= ' WHERE addressbook_companypk = '.$pnPK.' ';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      if(!$oDbResult)
        return array('error' => __LINE__.' - Cant edit the company');

      //add alternative account managers. (first one in the company detail, so we remove it from the array)
      if(count($asFollowers) > 1)
      {
        array_shift($asFollowers);
        $sQuery = 'DELETE FROM addressbook_account_manager WHERE companyfk='.$pnPK;
        $oDB->ExecuteQuery($sQuery);

        $asManagerQuery = array();
        foreach($asFollowers as $asManagerData)
          $asManagerQuery[] = '('.$pnPK.','.$asManagerData.')';

        $sQuery = 'INSERT INTO addressbook_account_manager(companyfk,loginfk) VALUES ';
        $sQuery.= implode(',', $asManagerQuery);
        $oDB->ExecuteQuery($sQuery);
      }

      //update company industries
      $sQuery = 'DELETE FROM addressbook_company_industry WHERE companyfk='.$pnPK;
      $oDB->ExecuteQuery($sQuery);
      $sMysqlQuery = array();
      foreach($asIndustry as $asIndustryData)
      {
        $sMysqlQuery[] = '('.$pnPK.','.$asIndustryData.')';
      }

      $sQuery = 'INSERT INTO addressbook_company_industry(companyfk,industryfk) VALUES';
      $sQuery.= implode(',',$sMysqlQuery);
      $oDB->ExecuteQuery($sQuery);


      if($bCascading && (int)$asCompanyData['followerfk'] != $nFollowerFk)
      {
        $bUpdated = $this->_updateEmployeesFollower($pnPK, $nFollowerFk, (int)$asCompanyData['followerfk']);
        if(!$bUpdated)
          return array('error' => __LINE__.' - Can\'t update contact follower');
      }
      $nCompanyPk = $pnPK;


      if($this->coPage->getActionReturn())
        $sURL = $this->coPage->getUrl('addressbook', $this->coPage->getActionReturn(), CONST_AB_TYPE_COMPANY, $nCompanyPk);
      else
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);


      $sLink = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
      $oLogin->logUserActivity($oLogin->getUserPk(), $this->_getUid(), CONST_ACTION_SAVEEDIT, CONST_AB_TYPE_COMPANY, $nCompanyPk, 'Update company data ', $sCompanyName, $sLink);

      $oCustomField = CDependency::getComponentByName('customfields');
      if($oCustomField)
      {
        $oCustomField->saveCustomFields($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
      }

      return array('notice' => 'Company saved successfully.', 'timedUrl' => $sURL);
    }

    /* @var $oLogin CLoginEx */
    $oLogin = CDependency::getCpLogin();
    $nUserPk = $oLogin->getUserPk();

    $nFollowerFk = (int)$asFollowers[0];
    $sCompanyName = getValue('name');

    $sQuery = 'INSERT INTO addressbook_company (company_name, corporate_name, email, parentfk, followerfk, phone, fax, website, address_1, address_2, postcode, cityfk, countryfk, creatorfk, date_create,company_relation,comments,updated_by) ';
    $sQuery.= ' VALUES('.$oDB->dbEscapeString($sCompanyName).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('corporate')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('email')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('parent', 0)).', ';
    $sQuery.= ''.$oDB->dbEscapeString($nFollowerFk).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('phone')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('fax')).', ';
    $sQuery.= ''.$oDB->dbEscapeString($sWebsite).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('address_1')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('address_2')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('postcode')).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('cp_cityfk', 0)).', ';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('cp_countryfk', 0)).', ';
    $sQuery.= ''.$oDB->dbEscapeString($nUserPk).', ';
    $sQuery.= ''.$oDB->dbEscapeString(date('Y-m-d H:i:s')).',';
    $sQuery.= ''.$oDB->dbEscapeString(getValue('type')).',';
    $sQuery.= ''.$oDB->dbEscapeString($sComments).',';
    $sQuery.= ''.$oDB->dbEscapeString($nUserPk).'';
    $sQuery.= ') ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);

    if(!$oDbResult)
      return array('error' =>__LINE__.' - Can\'t save company. '.$sQuery);

    $oDbResult->readFirst();
    if(!$oDbResult->getFieldValue('pk'))
      return array('error' =>__LINE__.' - Can\'t save company. '.$sQuery);


    $nCompanyPk = (int)$oDbResult->getFieldValue('pk');
    if(count($asFollowers) > 1)
    {
      array_shift($asFollowers);
      $asFollower = array();
      foreach($asFollowers as $nManagerPk)
      {
        $asFollower[] =' ('.$nCompanyPk.','.(int)$nManagerPk.')';
      }

      $sQuery = 'INSERT INTO addressbook_account_manager(companyfk, loginfk) VALUES '.implode(',', $asFollower);
      $oDB->ExecuteQuery($sQuery);
    }

    $sMysqlQuery = array();
    foreach($asIndustry as $asIndustryData)
    {
      $sMysqlQuery[] = '('.$nCompanyPk.','.$asIndustryData.')';
    }

    $sQuery = 'INSERT INTO addressbook_company_industry(companyfk,industryfk) VALUES '.implode(',',$sMysqlQuery);
    $oDB->ExecuteQuery($sQuery);


    $sURL = $this->coPage->getUrl('addressbook',CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, array('cppk' => $nCompanyPk));

    $sLink = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
    $oLogin->logUserActivity($oLogin->getUserPk(), $this->_getUid(), CONST_ACTION_SAVEADD, CONST_AB_TYPE_COMPANY, $nCompanyPk, 'Added a new company ', $sCompanyName, $sLink);


    $oCustomField = CDependency::getComponentByName('customfields');
    if($oCustomField)
    {
      $oCustomField->saveCustomFields($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
    }


    //user asked for a specific redirection in the form
    $sRedirectUid = getValue('relact');
    if(!empty($sRedirectUid))
    {
      if($this->csUid == $sRedirectUid)
      {
        $sURL = $this->coPage->getUrl('addressbook',CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
        return array('notice' => 'Company saved. View detail page...', 'url' => $sURL);
      }

      $sUid = CDependency::getComponentUidByName('event');
      if($sUid == $sRedirectUid)
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk, array('relact' => 'event'));
        return array('notice' => 'Company saved. Add an activity...', 'url' => $sURL);
      }

      $sUid = CDependency::getComponentUidByName('opportunity');
      if($sUid == $sRedirectUid)
      {
        $sReturnUrl = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk, array('relact'=> 'opportunity', CONST_URL_ACTION_RETURN => $sReturnUrl));
        return array('notice' => 'Company saved. Create an opportunity...', 'url' => $sURL);
      }
    }

    if($bInpopup)
      return array('notice' => 'Company saved. Back to connection.', 'action' => 'goPopup.removeByType("layer"); ');

    $sURL = $this->coPage->getUrl('addressbook',CONST_ACTION_ADD, CONST_AB_TYPE_CONTACT, 0, array('cppk' => $nCompanyPk));
    return array('notice' => 'Company saved. Please add connection.', 'timedUrl' => $sURL);
  }

  /*
   * When updating a company follower, we apply the follower modification to all the employees
   * If previous follower specified, only to contact having this follower (keep custom contact followers).
   * @return boolean
   */
  private function _updateEmployeesFollower($pnCompanyPK, $pnNewFollowerFk, $pnPreviousFollowerFk = 0)
  {
    if(!assert('is_integer($pnCompanyPK) && !empty($pnCompanyPK) && is_integer($pnNewFollowerFk) && !empty($pnNewFollowerFk)'))
      return false;

    $oDB = CDependency::getComponentByName('database');

    $sQuery = ' SELECT ct.addressbook_contactpk FROM addressbook_contact as ct ';
    $sQuery.= ' INNER JOIN addressbook_profile as p ON (p.contactfk  = ct.addressbook_contactpk AND p.companyfk = '.$oDB->dbEscapeString($pnCompanyPK).') ';
    if(!empty($pnPreviousFollowerFk))
      $sQuery.= ' WHERE followerfk = 0 OR followerfk = '.$oDB->dbEscapeString($pnPreviousFollowerFk);

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return true;

    $asContactToUpdate = array();
    while($bRead)
    {
      $asContactToUpdate[] = $oDbResult->getFieldValue('addressbook_contactpk', CONST_PHP_VARTYPE_INT);
      $bRead = $oDbResult->readNext();
    }

    if(empty($asContactToUpdate))
      return true;

    $sQuery = 'UPDATE addressbook_contact SET followerfk = '.$oDB->dbEscapeString($pnNewFollowerFk).', date_update = '.$oDB->dbEscapeString(date('Y-m-d H:i:s')).' ';
    $sQuery.= ' WHERE addressbook_contactpk IN ('.implode(',',$asContactToUpdate).' ) ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    if(!$oDbResult)
      return false;

    return true;
  }

  /**
   * Remove the company
   * @param integer $pnPK
   * @return array
   */

  private function _deleteCompany($pnPK)
  {
    if(!assert('is_integer($pnPK) && !empty($pnPK)'))
      return array('error' => __LINE__.' - No company identifier.');

    $oDbResult = $this->_getModel()->getByPk($pnPK, 'addressbook_company');
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => __LINE__.' - No company to delete.');

    $oDelete = $this->_getModel()->deleteByPk($pnPK, 'addressbook_company');
    if(!$oDelete)
      return array('error' => __LINE__.' - Could\'t delete the company');

    CDependency::notifyListeners($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $pnPK, CONST_ACTION_DELETE);

    return array('notice' => 'Company deleted.', 'timedUrl' => $this->coPage->getUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_COMPANY));
  }

  /**
   * Get the Company in the autocomplete
   * @return jsondata
   */

  private function _getSelectorCompany()
  {
    $sSearch = getValue('q');
    if(empty($sSearch))
      return json_encode(array());

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM addressbook_company WHERE company_name LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR corporate_name LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' ORDER BY company_name ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return json_encode(array());

    $asJsonData = array();
    while($bRead)
    {
      $asData['id'] = $oDbResult->getFieldValue('addressbook_companypk');
      $asData['name'] = '#'.$asData['id'].' - '.$oDbResult->getFieldValue('company_name').' - '.$oDbResult->getFieldValue('corporate_name');
      $asJsonData[] = json_encode($asData);
      $bRead = $oDbResult->readNext();
    }
    echo '['.implode(',', $asJsonData).']';
  }

  /**
   * Display the company account manager transfer form
   * @param integer $pnCompanyPk
   * @return array of ajax data
   */

  private function _getCompanyTransfer($pnCompanyPk)
  {
    if(!assert('is_integer($pnCompanyPk) && !empty($pnCompanyPk)'))
      return 'No data found.';

    $oLogin = CDependency::getCpLogin();

    $asCompanyData = $this->_getModel()->getCompanyByPk($pnCompanyPk);
    if(empty($asCompanyData))
      return array('error'=> 'Company doesn\'t exist');

    $oForm = $this->coHTML->initForm('companyTransferForm');
    $sFormId = $oForm->getFormId();
    //Get javascript for the popup
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVETRANSFER, CONST_AB_TYPE_COMPANY, $pnCompanyPk);
    $sJs = $this->coHTML->getAjaxJs($sURL, 'body', $sFormId);
    $oForm->setFormParams('', false, array('submitLabel' => 'Save','action' => '', 'onsubmit' => 'event.preventDefault(); '.$sJs, 'inajax' => 1));

    $oForm->setFormDisplayParams(array('noCancelButton' => '1','noCloseButton' => '1'));
    $sHTML= $this->coHTML->getBlocStart();
    $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Assign account manager'));
    $oForm->addField('select', 'account_manager[]', array('label' => 'Account Manager', 'multiple' => 'multiple'));
    $oForm->setFieldControl('account_manager[]', array('jsFieldNotEmpty' => ''));

    $asManagers = $oLogin->getUserList(0, true, true);

    if(!empty($pnCompanyPk))
      $asSelectManager = $this->_getModel()->getAccountManager((int)$pnCompanyPk, 'addressbook_company');
    else
      $asSelectManager[0] = $oLogin->getUserPk();

    foreach($asManagers as $asManagerData)
    {
      if(in_array($asManagerData['loginpk'], $asSelectManager))
       $oForm->addOption('account_manager[]', array('value'=>$asManagerData['loginpk'], 'label' => $asManagerData['firstname'].' '.$asManagerData['lastname'], 'selected' => 'selected'));
      else
       $oForm->addOption('account_manager[]', array('value'=>$asManagerData['loginpk'], 'label' => $asManagerData['firstname'].' '.$asManagerData['lastname']));
     }

    $oForm->addField('checkbox', 'cascading', array('type' => 'misc', 'label'=> 'Apply manager to employees ?', 'value' => 1, 'id' => 'cascading_id'));
    $oForm->addField('misc', '', array('type'=> 'br'));
    $oForm->addField('misc', '', array('type'=> 'br'));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $this->coHTML->getBlocEnd();

    return $this->coPage->getAjaxExtraContent(array('data'=>$sHTML));
   }

    /* ******************************************************************************** */
    /* ************************ C O N T A C T ***************************************** */
    /* ******************************************************************************** */

    /**
     * Display the connection event tab
     * @param type $pasContactData
     * @return type
     */

  private function _getContactEventTab($pasContactData)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return $this->coHTML->getBlocMessage('No data available to fetch activity.');

    $asCpValues = array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => $this->cnPk);

    $oEvent = CDependency::getComponentByName('event');
    if(!empty($oEvent))
    {
      $sEventList = $oEvent->getEventList($asCpValues, 0);

      $sUrl = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => $this->cnPk));
      $sHTML = ' '.$this->coHTML->getActionButton('Add a new activity', '', $oEvent->getResourcePath().'pictures/add_event_16.png',
            array('onclick' => 'var oConf = goPopup.getConfig();
            oConf.height = 700;
            oConf.width = 980;
            oConf.title = \'Add an activity...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); '));
      $sHTML.= $this->coHTML->getCR(2);

      if(empty($sEventList))
        return $sHTML. $this->coHTML->getBlocMessage('No activities for this contact.', true);

      $sHTML.= $this->coHTML->getBlocStart();
      $sHTML.= $sEventList;
      $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
    }
  }
  /**
   * return the form fom an ajax popup that allow user to  change follower
   * @param integer $pnContactPk
   * @return array to be encode in json
   */
  private function _getContactTransfer($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk) && !empty($pnContactPk)'))
      return array('error' => 'No data found.');

    $oLogin = CDependency::getCpLogin();

    $oForm = $this->coHTML->initForm('contactTransferForm');
    $sFormId = $oForm->getFormId();

    //Get javascript for the popup
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVETRANSFER, CONST_AB_TYPE_CONTACT, $pnContactPk);
    $sJs = $this->coHTML->getAjaxJs($sURL, 'body', $sFormId);
    $oForm->setFormParams('', false, array('submitLabel' => 'Save','action' => '', 'onsubmit' => 'event.preventDefault(); '.$sJs));

    $oForm->setFormDisplayParams(array('noCancelButton' => '1','noCloseButton' => '1'));
    $sHTML= $this->coHTML->getBlocStart();
    $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Assign the account manager'));
    $oForm->addField('select', 'account_manager[]', array('label' => 'Account Manager', 'multiple' => 'multiple'));
    $oForm->setFieldControl('account_manager[]', array('jsFieldNotEmpty' => ''));
    $asManagers = $oLogin->getUserList(0,true,true);
    if($pnContactPk)
     $asSelectManager = $this->_getModel()->getAccountManager((int)$pnContactPk, 'addressbook_contact');
    else
     $asSelectManager[0] = $oLogin->getUserPk();

    foreach($asManagers as $asManagerData)
    {
      if(in_array($asManagerData['loginpk'],$asSelectManager))
       $oForm->addOption('account_manager[]', array('value'=>$asManagerData['loginpk'], 'label' => $asManagerData['firstname'].' '.$asManagerData['lastname'], 'selected' => 'selected'));
      else
       $oForm->addOption('account_manager[]', array('value'=>$asManagerData['loginpk'], 'label' => $asManagerData['firstname'].' '.$asManagerData['lastname']));
     }

    $oForm->addField('misc', '', array('type'=> 'br'));
    $oForm->addField('misc', '', array('type'=> 'br'));

    $sHTML.= $oForm->getDisplay();
    $sHTML.= $this->coHTML->getBlocEnd();

    $asFormData = $this->coPage->getAjaxExtraContent(array('data'=>$sHTML));

    return $asFormData;
  }

   /**
   * Small Header for the Connection listing displayed in detail pages
   * @param string $psSearchId
   * @return string HTML
   */

  private function _getContactRowSmallHeader()
  {
    $sHTML = $this->coHTML->getBlocStart('ct_coworker_header');

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:22px; float:left;'));
      $sHTML.= '';
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:18%;float:left;text-align:center;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Name');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:14%;float:left;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Department');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:12%;float:left;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Position');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:13%;float:left;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Industry');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array( 'style' =>'width:10%;float:left;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Recent activity');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array( 'style' =>'width:20%;float:left;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Account Manager');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'float:right;padding-right:30px;color:#FFFFFF;'));
      $sHTML.= $this->coHTML->getText('Action');
      $sHTML.= $this->coHTML->getBlocEnd();

      $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'floatHack'));
      $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
  }

  /**
   * Header for the Connection listing
   * @param string $psSearchId
   * @return string HTML
   */

  private function _getContactRowHeader($psSearchId = '')
  {
    $oEvent = CDependency::getComponentUidByName('event');

    $sHTML = $this->coHTML->getBlocStart('', array('class' =>'listCp_row '));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'listCp_row_data'));

    //fetch sortorder from the history
    $asOrder = $this->_getHistorySearchOrder($psSearchId, $this->csUid, CONST_AB_TYPE_CONTACT);
    $sSortField = strtolower($asOrder['sortfield']);
    $sSortOrder = strtolower($asOrder['sortorder']);


    $sUrl = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT, 0, array('searchId' => $psSearchId));

    $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'width:12px; float:left;'));
    $sHTML.= '<input type="checkbox" />';
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'lastname','style' =>'width:8%; padding-left: 2px;'));
    $sHTML.= $this->coHTML->getText('Lastname ');
    $sHTML.= $this->coHTML->getSpace(2);

    if($sSortField == 'lastname' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z', '', array('class'=>'moveup '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=lastname&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'lastname' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=lastname&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }
    $sHTML.= $this->coHTML->getBlocEnd();


    $sHTML.= $this->coHTML->getBlocStart('cpName', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'lastname','style' =>'width:4%; padding-left: 2px;'));
    $sHTML.= $this->coHTML->getText(' First ');
    $sHTML.= $this->coHTML->getSpace(2);

    if($sSortField == 'firstname' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z','',array('class'=>'moveup', 'sortfield' => ''));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=firstname&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'firstname' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=firstname&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }
    $sHTML.= $this->coHTML->getBlocEnd();


    $sHTML.= $this->coHTML->getBlocStart('cpCompany', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'company_name','style' =>'width:8%; padding-left: 2px;'));
    $sHTML.= $this->coHTML->getText('Company');
    $sHTML.= $this->coHTML->getSpace(2);

    if($sSortField == 'company' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z','',array('class'=>'moveup', 'sortfield' => ''));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=company&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'company' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=company&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('cpDepartment', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'department','style' =>'width:13%;'));
    $sHTML.= $this->coHTML->getText('Department');
    $sHTML.= $this->coHTML->getSpace(2);
    if($sSortField == 'department' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z','',array('class'=>'moveup', 'sortfield' => ''));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=department&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'department' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=department&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'position','style' =>'width:10%;'));
    $sHTML.= $this->coHTML->getText('Position');
    $sHTML.= $this->coHTML->getSpace(2);
    if($sSortField == 'position' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z','',array('class'=>'moveup', 'sortfield' => ''));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=position&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
      }

    if($sSortField == 'position' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z - A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=position&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
     }
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('cpIndusty', array('class' => 'ab_list_cell cp_list_cell ct_search','sort_name'=>'industry_name','style' =>'width:10%;'));
    $sHTML.= $this->coHTML->getText('Industry');
    $sHTML.= $this->coHTML->getSpace(2);
    if($sSortField == 'industry' && $sSortOrder == 'asc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'A - Z','',array('class'=>'moveup', 'sortfield' => ''));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=industry&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'A - Z', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
      }

    if($sSortField == 'industry' && $sSortOrder == 'desc')
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Z- A','',array('class'=>'movedown '));
    else
    {
      $sSortUrl = $sUrl.'&sortfield=industry&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Z - A', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
      }
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell', 'style' =>'width:11%;'));
    if(!empty($oEvent))
      $sHTML.= $this->coHTML->getText('Recent activity');

    $sHTML.= $this->coHTML->getSpace(2);
    if($sSortField == 'activity' && $sSortOrder == 'asc')
    {
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/up_orange.png', 'Oldest First','',array('class'=>'moveup', 'sortfield' => ''));
    }
    else
    {
      $sSortUrl = $sUrl.'&sortfield=activity&sortorder=asc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/up.png', 'Oldest First', '', array('class'=>'moveup '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    if($sSortField == 'activity' && $sSortOrder == 'desc')
    {
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/down_orange.png', 'Recent First','',array('class'=>'movedown '));
    }
    else
    {
      $sSortUrl = $sUrl.'&sortfield=activity&sortorder=desc';
      $sPic = $this->coHTML->getPicture($this->getResourcePath().'pictures/down.png', 'Recent First', '', array('class'=>'movedown '));
      $sHTML.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' =>  "AjaxRequest('".$sSortUrl."', 'body', '', 'contactListContainer'); ") );
    }

    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell', 'style' =>'width:8%;'));
    $sHTML.= $this->coHTML->getText('Account Manager');
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_list_cell cp_list_cell', 'style' =>'float:right;'));
    $sHTML.= $this->coHTML->getText('Action');
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'floatHack'));
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Display the male or female icon
   * @param array $pasContactData
   * @return string
   */

  private function _getDisplayIcon($pasContactData)
  {
    if(!assert('is_array($pasContactData)&& !empty($pasContactData)'))
      return 'Incorrect or empty data found';

    if($pasContactData['courtesy'] == 'ms')
      $sHTML = $this->coHTML->getPicture($this->getResourcePath().'/pictures/ct_f_10.png');
    else
      $sHTML = $this->coHTML->getPicture($this->getResourcePath().'/pictures/ct_m_10.png');

    return $sHTML;
  }

  /**
   * Display the connection records
   * @param array $pasContactData
   * @param integer $pnRow
   * @param string $psVariable
   * @return string HTML
   */

  private function _getContactRow($pasContactData, $pnRow,$psVariable='')
  {
    if(!assert('is_array($pasContactData)&& !empty($pasContactData)'))
      return '';

    $oEvent = CDependency::getComponentUidByName('event');

    $nContactPk = (int)$pasContactData['addressbook_contactpk'];
    $sId = 'id_'.$nContactPk;

    if(($pnRow%2) == 0)
      $sRowClass = '';
    else
     $sRowClass = 'listCt_row_data_odd';

    if($psVariable==1)
     $sPaddingTop= 'padding-top: 0px;';
    else
     $sPaddingTop= '';

    $sHTML= $this->coHTML->getBlocStart($sId, array('class' =>'listCt_row '));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'listCt_row_data '.$sRowClass));

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'list_checkbox '.$sRowClass));
    $sHTML.= '<input type="checkbox" />';
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell ct_list_name  '.$sRowClass, 'style' =>'width:13%; padding-left: 2px; '.$sPaddingTop.''));
    $sContactRelation = getCompanyRelation($pasContactData['relationfk']);
    $sHTML.= $this->coHTML->getBlocStart('',array('style'=>'width:38px;','class' => 'imgClass '.$sRowClass));
    $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/'.$sContactRelation['icon_small'], 'Relation', '');
    $sHTML.= $this->coHTML->getSpace();
    $sHTML.= $this->_getDisplayIcon($pasContactData);
    $sHTML.= $this->coHTML->getBlocEnd();
    $sURL =  $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk);
    $sHTML.= $this->coHTML->getLink($pasContactData['lastname'].' '.$pasContactData['firstname'], $sURL);
    $sHTML.= $this->coHTML->getBlocEnd();

    if($psVariable<>1)
    {
     $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass,'style' =>'width:10%; '.$sPaddingTop.''));
     $sHTML.= $this->_getContactRow_companyDetail($pasContactData);
     $sHTML.= $this->coHTML->getBlocEnd();
    }

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass,'style' =>'width:12%; '.$sPaddingTop.''));
    if(!empty($pasContactData['department']))
    $sHTML.= $this->coHTML->getText($pasContactData['department']);
    else
    $sHTML.= $this->coHTML->getText('-', array('class' => 'light italic spanCentered'));
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass,'style' =>'width:9%; '.$sPaddingTop.''));
    if(!empty($pasContactData['position']))
    $sHTML.= $this->coHTML->getText($pasContactData['position']);
    else
    $sHTML.= $this->coHTML->getText('-', array('class' => 'light italic spanCentered'));
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass,'style' =>'width:9%; '.$sPaddingTop.''));
    $sHTML.= $this->_getContactRow_IndustryInfo($pasContactData);
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass,'style' =>'width:8%; '.$sPaddingTop.''));
    if(!empty($oEvent))
      $sHTML.= $this->_getContactRow_Activity($pasContactData);

    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass, 'style' =>'width;10% '.$sPaddingTop.''));
    $sHTML.= $this->_getContactAccountManager($pasContactData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_list_cell '.$sRowClass, 'style' =>'float:right; '.$sPaddingTop.''));
    $sHTML.= $this->_getContactRowAction($pasContactData);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getFloatHack();

    return $sHTML;
  }

   /**
   * Get the company industry information
   * @param array $pasContactData
   * @return string HTML
   */

  private function _getCompanyRow_IndustryInfo($pasCompanyData)
  {
    if(!assert('is_array($pasCompanyData) && !empty($pasCompanyData)'))
      return '';

    if(!empty($pasCompanyData['industry_name']))
      return $this->coHTML->getText($pasCompanyData['industry_name']);

    return $this->coHTML->getText('-', array('class' => 'light italic spanCentered'));
  }

  /**
   * Get the connection industry information
   * @param array $pasContactData
   * @return string HTML
   */

  private function _getContactRow_IndustryInfo($pasContactData)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return '';

    if(!empty($pasContactData['industry_name']))
     return $this->coHTML->getText($pasContactData['industry_name']);


    return $this->coHTML->getText('-', array('class' => 'light italic spanCentered'));
  }

  /**
   * Display the activities of the connection
   * @param array $pasContactData
   * @return string HTML
   */

  private function _getContactRow_Activity($pasContactData)
  {
   if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
     return '';

    //$sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW,CONST_AB_TYPE_CONTACT, (int)$pasContactData['addressbook_contactpk']);
    $sHTML = '';

    if(!empty($pasContactData['title']))
       $sEventTitle = $pasContactData['title'];
    else
       $sEventTitle = '';

    if(!empty($pasContactData['content']))
       $sEventContent =  $this->coHTML->utf8_strcut(strip_tags($pasContactData['content']),200);
    else
       $sEventContent = '';

     if(!empty($sEventTitle))
       $sEvent = $sEventTitle.'<br/>';
     else
       $sEvent = '';

     $sEvent.= $sEventContent;

    if(!empty($sEvent))
    {
     $sHTML.= $this->coHTML->getBlocStart('',array('style'=>'float:left;width:40px;'));
     $sHTML.= $this->coHTML->getText(date('m/y',strtotime($pasContactData['date_display'])));
     $sHTML.= $this->coHTML->getBlocEnd();

     $sHTML.= $this->coHTML->getBlocStart('',array('class' => 'imgClass  activityClass','title'=>$sEvent));
     $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/list_event.png', 'Activities', '', array('onmouseover'=>'showActivityPopup(this);','onmouseout'=>"hideActivityPopup();"));
     $sHTML.= $this->coHTML->getBlocEnd();
    }
    else
     $sHTML.=  $this->coHTML->getText('-', array('class' => 'light italic spanCentered'));

    return $sHTML;
  }

  /**
   * Get Full name of the account manager of connection
   * @param type $pasContactData
   * @return type
   */

  private function _getContactAccountManager($pasContactData)
  {
     if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
     return '';

     assert('isset($pasContactData[\'follower_lastname\'])');

    $oLogin = CDependency::getCpLogin();
    return $oLogin->getUserAccountName($pasContactData['follower_lastname'], $pasContactData['follower_firstname'], true);
  }

  /**
   * Get company of the connection
   * @param array $pasContactData
   * @return string HTML
   */

  private function _getContactRow_companyDetail($pasContactData)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return '';

    if(!empty($pasContactData['ncount']))
    {
      if($pasContactData['ncount']<'2')
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$pasContactData['addressbook_companypk']);
        return $this->coHTML->getLink($pasContactData['company_name'], $sURL);
      }


      $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, (int)$pasContactData['addressbook_contactpk']);
      return $this->coHTML->getLink( $pasContactData['ncount'].' Companies', $sURL);

    }

    return $this->coHTML->getText('No companies ', array('class' => 'light italic'));
  }

  /** This function is not used at the moment */

  private function _getContactRow_contactInfo($pasContactData)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
     return '';

    $sHTML = '';

    if(isset($pasContactData['cellphone']) && !empty($pasContactData['cellphone']))
     $sPhone=$pasContactData['cellphone'];
    else if(isset($pasContactData['phone']) && !empty($pasContactData['phone']))
     $sPhone=$pasContactData['phone'];
    else if(isset($pasContactData['profilePhone'])&&!empty($pasContactData['profilePhone']))
      $sPhone=$pasContactData['profilePhone'];
    else
     $sPhone = '';

    $sHTML.= $this->coHTML->getLink($sPhone, 'callto:'.$sPhone);
    if(!empty($pasContactData['fax']))
    {
      if(!empty($sHTML))
        $sHTML.= ' / ';

      $sFax = $pasContactData['fax'];
    }
    else if(!empty($pasContactData['profileFax']))
    {
      if(!empty($sHTML))
        $sHTML.= ' / ';
      $sFax = $pasContactData['profileFax'];
    }
    else
      $sFax = '';

    $sHTML.= $this->coHTML->getText($sFax);

    if(!empty($sHTML))
      $sHTML.= $this->coHTML->getCR();

    if(isset($pasContactData['email']) && !empty($pasContactData['email']))
     $sEmail = $pasContactData['email'];
    else if(isset($pasContactData['profileEmail'])&&!empty($pasContactData['profileEmail']))
     $sEmail = $pasContactData['profileEmail'];
    else
     $sEmail= '';

    $sHTML.= $this->coHTML->getLink($sEmail, 'mailto:'.$sEmail);
    if(empty($sHTML))
      $sHTML.= $this->coHTML->getSpace();

    return $sHTML;
  }

  /**
   * Get the connection list
   * @param string $psQueryFilter
   * @return string HTML
   */

  private function _getContactList($psQueryFilter = '', $pbRefreshSearch = false)
  {
    if(!assert('is_string($psQueryFilter)'))
     return '';

    $this->coPage->addCssFile($this->getResourcePath().'/css/addressbook.css');

    $sSetTime =  getValue('settime');
    showHideSearchForm($sSetTime, 'ct');

    $sTitle = 'Connection Search';
    if($pbRefreshSearch)
      $sTitle = 'My connections';

    $sHTML = $this->coHTML->getTitleLine($sTitle, $this->getResourcePath().'/pictures/contact_48.png');
    $sHTML.= $this->coHTML->getCR();

    $sHTML.= $this->coHTML->getBloc('', '', array('class'=>'searchTitle'));

   // Insert the search form in the Contact list page
    $gbNewSearch = true;

    //if clear search: do not load anything from session and generate a new searchId
    //if do_search: do not load the last search, save a new one with new parameters
    if($pbRefreshSearch)
    {
      $sSearchId = '';
      $_POST['followerfk'] = CDependency::getCpLogin()->getUserPk();
    }
    else
    {
      if((getValue('clear') == 'clear_ct') || getValue('do_search', 0))
        $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_CONTACT, true);
      else
      {
        //reload the last search using the ID passed in parameters, ou the last done
        if(getValue('searchId'))
          $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_CONTACT);
        else
          $sSearchId = reloadLastSearch($this->csUid, CONST_AB_TYPE_CONTACT);
      }
    }

    //execute the search and bring a multi dimension array with context data and search result
    $avResult = $this->_getContactSearchResult($psQueryFilter, $sSearchId);
    $sMessage = $this->_getSearchMessage($avResult['nNbResult'],'');

    //get the search bloc: title, floating icon, form
    $sHTML.= $this->_getContactSearchBloc($sSearchId, $avResult, $gbNewSearch);
    $sJavascript = " $(document).ready(function(){ $('.searchTitle').html('".$sMessage."') }); ";
    $this->coPage->addCustomJs($sJavascript);

    //display the result
    $sHTML.= $this->coHTML->getBlocStart('contactListContainer');
    $sHTML.= $this->_getContactResultList($avResult, $sSearchId);
    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
  }

   /**
   * Ajax function to get the company search records
   * @global boolean $gbNewSearch
   * @return array
   */

  private function _getAjaxContactSearchResult()
  {
    global $gbNewSearch;

    //if clear search: do not load anything from session and generate a new searchId
    //if do_search: do not load the last search, save a new one with new parameters
    if((getValue('clear') == 'clear_ct') || getValue('do_search', 0))
    {
      $gbNewSearch = true;
      unset($_POST['clear']); unset($_POST['do_search']);
      unset($_GET['clear']);  unset($_GET['do_search']);
      $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_CONTACT, true);
    }
    else
    {
      //reload the last search using the ID passed in parameters, ou the last done
      if(getValue('searchId'))
        $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_CONTACT);
      else
        $sSearchId = reloadLastSearch($this->csUid, CONST_AB_TYPE_CONTACT);
    }
    $avResult = $this->_getContactSearchResult('', $sSearchId);
    $asOrder = $this->_getHistorySearchOrder($sSearchId, $this->csUid, CONST_AB_TYPE_CONTACT);

    if(empty($avResult) || empty($avResult['nNbResult']) || empty($avResult['oData']))
    {
      $sMessage = $this->_getSearchMessage($avResult['nNbResult'], $asOrder);
      $oDisplay = CDependency::getCpHtml();
      return array('data' => $oDisplay->getBlocMessage('No result to your search query'), 'action' => '$(\'.searchTitle\').html(\''.addslashes($sMessage).'\'); jQuery(\'.searchContainer:not(:visible)\').fadeIn(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\'); $(\'body\').scrollTop(0);');
    }

    $sData = $this->_getContactResultList($avResult, $sSearchId, true);

    if(empty($sData) || $sData == 'null' || $sData == null)
      return array('data' => 'Sorry, an error occured while refreshing the list.');

    if($gbNewSearch)
    {
       $sMessage = $this->_getSearchMessage($avResult['nNbResult'], $asOrder, true);
         return array('data' => mb_convert_encoding($sData,'utf8'), 'action' => '$(\'.searchTitle\').html(\''.addslashes($sMessage).'\'); jQuery(\'.searchContainer\').fadeOut(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\');');
    }

    $sMessage = $this->_getSearchMessage($avResult['nNbResult'], $asOrder, true);
    return array('data' => mb_convert_encoding($sData, 'utf8'), 'action' => '$(\'.searchTitle .searchTitleSortMsg\').html(\''.addslashes($sMessage).'\'); jQuery(\'.searchContainer\').fadeOut(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\'); $(\'body\').scrollTop(0);');
  }

  /**
   * Ajax function to get the company search records
   * @global boolean $gbNewSearch
   * @return array
   */

  private function _getAjaxCompanySearchResult()
  {
    global $gbNewSearch;

    //if clear search: do not load anything from session and generate a new searchId
    //if do_search: do not load the last search, save a new one with new parameters
    if((getValue('clear') == 'clear_cp') || getValue('do_search', 0))
    {
      $gbNewSearch = true;
      unset($_POST['clear']); unset($_POST['do_search']);
      unset($_GET['clear']);  unset($_GET['do_search']);
      $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_COMPANY, true);
    }
    else
    {
      //reload the last search using the ID passed in parameters, ou the last done
      if(getValue('searchId'))
        $sSearchId = manageSearchHistory($this->csUid, CONST_AB_TYPE_COMPANY);
      else
        $sSearchId = reloadLastSearch($this->csUid, CONST_AB_TYPE_COMPANY);
    }

    //Do the search and return an array with all the data
    $avResult = $this->_getCompanySearchResult('', $sSearchId);
    $asOrder = $this->_getHistorySearchOrder($sSearchId, $this->csUid, CONST_AB_TYPE_COMPANY);

    if(empty($avResult) || empty($avResult['nNbResult']) || !$avResult['oData'])
      return array('message' => 'No result to your search query', 'action' => '$(\'.searchTitle\').html(\' No result \');jQuery(\'.searchContainer:not(:visible)\').fadeIn(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\'); $(\'body\').scrollTop(0);');

    $sData = $this->_getCompanyResultList($avResult, $sSearchId, true);

    if(empty($sData) || $sData == 'null' || $sData == null)
      return array('message' => 'Sorry, an error occured while refreshing the list.');

    if($gbNewSearch)
    {
      $sMessage = $this->_getCompanySearchMessage($avResult['nNbResult'], $asOrder);
      return array('data' => mb_convert_encoding($sData, 'utf8'), 'action' => '$(\'.searchTitle \').html(\''.addslashes($sMessage).'\');jQuery(\'.searchContainer\').fadeOut(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\');');
      }

      $sMessage = $this->_getCompanySearchMessage($avResult['nNbResult'], $asOrder, true);
      return array('data' => mb_convert_encoding($sData, 'utf8'), 'action' => '$(\'.searchTitle .searchTitleSortMsg\').html(\''.addslashes($sMessage).'\'); jQuery(\'.searchContainer\').fadeOut(); $(\'body\').scrollTop(0);jQuery(\'.searchContainer\').fadeOut(); $(\'.searchMenuIcon span\').html(\''.$avResult['nNbResult'].'\');');

  }

  /**
   * List the connection results
   * @param type $pavResult: search data formated nbressult / odbresult
   * @param type $pbNewSearch, if it s a new search (or sorting, comoing back if false)
   * @return type
   */
  private function _getContactResultList($pavResult, $psSearchId = '', $pbNewSearch = false)
  {
    $oPager = CDependency::getComponentByName('pager');

    $nNbResult = $pavResult['nNbResult'];
    $oDbResult = $pavResult['oData'];

    if(!$oDbResult)
      $bRead = false;
    else
      $bRead = $oDbResult->readFirst();

    $sHTML = '';

    if($nNbResult > 0)
    {
      $sUrl = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT);
      $asPagerUrlOption = array('ajaxTarget' => 'contactListContainer', 'ajaxCallback' => ' jQuery(\'.searchContainer\').fadeOut(); ');
      $sHTML.= $oPager->getCompactDisplay($nNbResult, $sUrl, $asPagerUrlOption);
     }

    $sHTML.= $this->coHTML->getBlocStart('', array('class'=>'homePageContainer','style' =>'padding: 0px;background-color:#FFFFFF;width: 100%;'));
    $sHTML.= $this->coHTML->getListStart('', array('class' => 'ablistContainer'));

    if($nNbResult == 0 || !$bRead)
    {
      $sHTML.= $this->coHTML->getListItemStart();
      $sHTML.= "No connection matching your search parameters.";
      $sHTML.= $this->coHTML->getListItemEnd();
     }
    else
    {
      $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'ablistHeader'));
      $sHTML.= $this->_getContactRowHeader($psSearchId);
      $sHTML.= $this->coHTML->getListItemEnd();

      $nCount = 1;
      while($bRead)
      {
        $sRowId = 'ctId_'.$oDbResult->getFieldValue('addressbook_contactpk');
        $asContactData = $oDbResult->getData();
        $sHTML.= $this->coHTML->getListItemStart($sRowId);
        $sHTML.= $this->_getContactRow($asContactData, $nCount);
        $sHTML.= $this->coHTML->getListItemEnd();

        $nCount++;
        $bRead = $oDbResult->ReadNext();
      }
    }
    $sHTML.= $this->coHTML->getListEnd();
    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();

    if($nNbResult > 0)
      $sHTML.= $oPager->getDisplay($nNbResult, $sUrl, $asPagerUrlOption);

    return $sHTML;
  }
  /**
   *
   * @param type $psQueryFilter
   * @return an array formatted as follow:  nNbResult => global nb of result , oData: dbObject containing the results
   */
  private function _getContactSearchResult($psQueryFilter = '',$psSearchId = '')
  {
    $sQuery = 'SELECT count(DISTINCT ct.addressbook_contactpk) as nCount FROM addressbook_contact as ct ';
    $sQuery.= ' LEFT JOIN addressbook_profile AS prf ON (ct.addressbook_contactpk = prf.contactfk and prf.date_end IS NULL)';
    $sQuery.= ' LEFT JOIN addressbook_company AS cp ON (cp.addressbook_companypk = prf.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_company_industry AS cmpid ON (cp.addressbook_companypk = cmpid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON (cmpid.industryfk = ind.addressbook_industrypk)';
    $sQuery.= ' LEFT JOIN shared_login AS lg ON (lg.loginpk = ct.followerfk )';

    if($psQueryFilter)
      $sQuery.= $psQueryFilter;
    else
    {
      $asFilter = $this->_getSqlContactSearch();
      if(!empty($asFilter['join']))
        $sQuery.= $asFilter['join'];

      if(!empty($asFilter['where']))
        $sQuery.= ' WHERE '.$asFilter['where'];
    }

    trace($sQuery);

    $oDb = CDependency::getComponentByName('database');
    $oEvent = CDependency::getComponentByName('event');

    $oDbResult = $oDb->ExecuteQuery($sQuery);
    $bRead = $oDbResult->ReadFirst();
    $nNbResult = $oDbResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);

    if($nNbResult == 0)
      return array('nNbResult' => 0, 'oData' => null);

    $asEventQuery = array('select' => 1);
    if($oEvent)
    {
      $asEventQuery = $oEvent->getActivitySql();
    }

    $sQuery = ' SELECT ct.*,cp.*,'.$asEventQuery['select'].',group_concat(DISTINCT CONCAT(lg.lastname) SEPARATOR ",") as follower_lastname,group_concat(DISTINCT CONCAT(lg.firstname) SEPARATOR ",") as follower_firstname ,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT prf.position) AS position, GROUP_CONCAT(DISTINCT prf.email) AS profileEmail,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT prf.department) AS department, GROUP_CONCAT(DISTINCT prf.phone) AS profilePhone, GROUP_CONCAT(DISTINCT prf.fax) AS profileFax,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT ind.industry_name) AS industry_name,GROUP_CONCAT(DISTINCT cp.company_name) AS company_name, COUNT( DISTINCT cp.company_name) AS ncount';
    $sQuery.= ' FROM addressbook_contact AS ct USE INDEX (lastname_idx)';
    $sQuery.= ' LEFT JOIN addressbook_profile AS prf ON (ct.addressbook_contactpk = prf.contactfk and prf.date_end IS NULL)';
    $sQuery.= ' LEFT JOIN addressbook_company AS cp ON (cp.addressbook_companypk = prf.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_company_industry AS cmpid ON (cp.addressbook_companypk = cmpid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON (cmpid.industryfk = ind.addressbook_industrypk)';
    $sQuery.= ' LEFT JOIN shared_login AS lg ON (lg.loginpk = ct.followerfk ) ';

    if(!empty($asEventQuery['join']))
      $sQuery.= $asEventQuery['join'];

    if($psQueryFilter)
      $sQuery.= 'WHERE '.$psQueryFilter;
    else
    {
      if(!empty($asFilter['join']))
        $sQuery.= $asFilter['join'];

      if(!empty($asFilter['where']))
        $sQuery.= ' WHERE '.$asFilter['where'];
      }

    $sQuery.= ' GROUP BY ct.addressbook_contactpk ';
    if(!empty($psSearchId))
      $sQuery.= $this->_getContactSearchOrder($psSearchId);

    //Debugging going from here //

    $oPager = CDependency::getComponentByName('pager');
    $oPager->initPager();
    $nOffset = $oPager->getSqlOffset();
    if($nOffset > $nNbResult)
      $nOffset = 0;

    $sQuery.= ' LIMIT '.$nOffset.', '.$oPager->getLimit();
    trace($sQuery);

    $oDbResult = $oDb->ExecuteQuery($sQuery);
    $bRead= $oDbResult->readFirst();

    if(!$bRead)
    {
      assert('false; // no result but count query was ok ');
      return array('nNbResult' => 0, 'oData' => null);
    }
    return array('nNbResult' => $nNbResult, 'oData' => $oDbResult);
  }

  /**
   * Display the contact informations
   * @param integer $pnPK
   * @return array of contact data
   */

  private function _displayContact($pnPK)
  {
    if(!assert('is_key($pnPK)'))
      return '';

    $oResult = $this->_getModel()->getContact($pnPK);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return $this->coHTML->getBlocMessage('No connection found. ');

    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(), CONST_ACTION_TRANSFER, $this->getType(), 0);


    $oWEBMAIL = CDependency::getComponentByName('webmail');
    $oEvent = CDependency::getComponentByName('event');
    $oOpportunity = CDependency::getComponentByName('opportunity');
    $oLogin = CDependency::getCpLogin();
    $oWebmail = CDependency::getComponentByName('webmail');

    $this->coPage->addCssFile($this->getResourcePath().'/css/addressbook.css');
    $aCpValues = array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => $pnPK);

    $aDocuments = array();
    $oSharedSpace = CDependency::getComponentByName('sharedspace');
    if(!empty($oSharedSpace))
      $aDocuments = $oSharedSpace->getTabContent($this->getCpValues());

    $asContactData =  $oResult->getData();
    //dump($asContactData);

    $this->coPage->setPageTitle($asContactData['firstname'].' '.$asContactData['lastname']);

    if(!empty($oEvent))
      $asContactEventData = $oEvent->getEventInformation($this->_getUid(), CONST_ACTION_VIEW, $this->getType());

    if(!empty($asContactEventData[$oResult->getFieldValue('addressbook_contactpk')]))
      $asContactData = array_merge($asContactEventData[$oResult->getFieldValue('addressbook_contactpk')],$asContactData);

    $oOpportunity = CDependency::getComponentByName('opportunity');
    if(!empty($oOpportunity) && $oRight->canAccess('555-123', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0))
    {
      $bAccessOpp = true;
    }
    else
      $bAccessOpp = false;


    //manage here a set of action launched when the page is displayed
    $sReloadAction = getValue('relact');
    switch($sReloadAction)
    {
      case 'email':
        $sURL = $this->coPage->getAjaxUrl('webmail', CONST_ACTION_ADD, CONST_WEBMAIL, 1, array('ppaty' => 'ct', 'ppaid' => $pnPK));
        $this->coPage->addCustomJs(' $(document).ready(function()
          {
            var oConf = goPopup.getConfig();
            oConf.height = 660;
            oConf.width = 980;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\', true);
          });');
        break;

      case 'opportunity':
        if($bAccessOpp)
        {
          $sReturnURL = urlencode($this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnPK));
          $sURL = $this->coPage->getAjaxUrl('opportunity', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, array('cp_uid' => $this->csUid, 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_CONTACT, 'cp_pk' => $pnPK, CONST_URL_ACTION_RETURN => $sReturnURL));
          $this->coPage->addCustomJs(' $(document).ready(function()
          {
            var oConf = goPopup.getConfig();
            oConf.height = 660;
            oConf.width = 980;
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
          }); ');
        }
        break;

        case 'event':
        if($oEvent)
        {
          $sURL = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array('cp_uid' => $this->csUid, 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_CONTACT, 'cp_pk' => $pnPK));
          $this->coPage->addCustomJs(' $(document).ready(function()
          {
            var oConf = goPopup.getConfig();
            oConf.height = 700;
            oConf.width = 980;
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
          }); ');
        }
        break;
    }

    //fetch all the profiles and store those in an array
    $oResult = $this->_getModel()->fetchProfiles($pnPK);
    $bRead = $oResult->readFirst();
    if(!$bRead)
    {
      $nProfil = 0;
      $asProfil = array();
    }
    else
    {
      while($bRead)
      {
        $asProfil[] = $oResult->getData();
        $bRead = $oResult->readNext();
      }
      $nProfil = count($asProfil);
    }

    $nCoworkers = 0;
    $asCoworkers = array();

    //Fetch co-workers if the connection has a company
    if(isset($asContactData['companyfk']) && $asContactData['companyfk'] != 0)
    {
      $oDbResult = $this->_getModel()->fetchCoWorkers((int)$asContactData['companyfk']);
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $asCoworkers[] = $oDbResult->getData();
        $bRead = $oDbResult->readNext();
      }

      $nCoworkers = $asCoworkers[0]['nCount'];
    }
    $nCoworkers--;

    // Count documents
    $nDocuments = 0;
    if(!empty($aDocuments))
    {
      $nDocuments = $aDocuments['count'];
      $sDocumentsTabs = $aDocuments['html'];
      $aLastDocument = $aDocuments['last'];
    }

    //For the logging the activity
    $sLink = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnPK);
    $oLogin->logUserActivity($oLogin->getUserPk(), $this->_getUid(), CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnPK, 'View profile ', $asContactData['firstname'].' '.$asContactData['lastname'], $sLink);


    //Count events
    $oEvent = CDependency::getComponentByName('event');
    if(!empty($oEvent))
    {
      $nEvents = $oEvent->getCount($aCpValues);
      $sEventUid = $oEvent->getComponentUid();
    }
    else
    {
      $nEvents = '';
      $sEventUid = '';
    }

    // Count opportunities
    $nOpportunity = '';
    $sOpportunityUid = '';
    if($bAccessOpp)
    {
      $nOpportunity = $oOpportunity->getCount($aCpValues);
      $sOpportunityUid = $oOpportunity->getComponentUid();
    }


    $asCpCard = array();

    if(trim($asContactData['courtesy']) == 'ms')
     $sHTML = $this->coHTML->getBlocStart('', array('class' => 'ct_top_container_female shadow'));
    else
     $sHTML = $this->coHTML->getBlocStart('', array('class' => 'ct_top_container_male shadow'));

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_card_container'));

    if(trim($asContactData['courtesy'])=='ms')
        $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_top_name_female'));
    else
       $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_top_name_male'));

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'left'));
    $sHTML.= $this->coHTML->getTitle(ucfirst($asContactData['courtesy']).' '.$asContactData['lastname'].' '.$asContactData['firstname'], 'h3', false, array('float' => 'left;'));
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'right'));

    foreach($asCoworkers as $asCompanyData)
    {
      if(!empty($asCompanyData['addressbook_companypk']))
      {
        $nCompanyPk = (int)$asCompanyData['addressbook_companypk'];
        $asCpCard[$nCompanyPk] = $this->getCompanyCard($nCompanyPk, $asCompanyData);
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
        $sPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/detail_16.png', 'View company informations', 'javascript:;', array('onclick' => '$(\'.popupCpCard:not(#cp_card_'.$nCompanyPk.')\').hide(0); $(\'#cp_card_'.$nCompanyPk.'\').fadeToggle(); ', 'style' => 'height:14px;'));
        $sLink = $this->coHTML->getLink($asCompanyData['company_name'], $sURL);
        $sHTML.= $this->coHTML->getTitle($sPic.' '.$sLink, 'h4', false, array('style' => 'float:right;', 'isHtml' => 1));
      }
    }

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getFloatHack();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'left;'));
    $sHTML.= $this->coHTML->getText($asContactData['position'], array('style'=>'float:left;padding-left:5px;color:#0D79BC;','class'=>'h4'));
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'right'));
    $sHTML.= $this->coHTML->getText($asContactData['department_name']);
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getFloatHack();
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ct_top_contact','style'=>'height:160px; '));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_relation_row'));
    $asContactRelation = getCompanyRelation($asContactData['relationfk']);
    $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/'.$asContactRelation['icon'], 'Relation', '', array('style' => 'height: 24px'));
    if($asContactData['relationfk'] == CONST_AB_PROSPECT_PK)
    {
      $sDate = date('Y-m-d', strtotime('+ 2 months', strtotime($asContactData['date_create'])));
      $sLabel = ' '.$asContactRelation['Label'].'<span class="light italic"> - expires on : '.$sDate.'</span>';
    }
    else
      $sLabel = ' '.$asContactRelation['Label'];

    $sHTML.= $this->coHTML->getBloc('',$sLabel, arraY('style' => $asContactRelation['style']));
    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getText('Character: ', array('class' => 'ab_view_strong'));
    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'ab_card_comment'));

    if(!empty($asContactData['comments']))
      $sHTML.= $this->coHTML->getText(($asContactData['comments']));
    else
      $sHTML.= $this->coHTML->getText('No Character', array('class'=>'light italic'));

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getFloatHack();

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    foreach($asCpCard as $nProfileCpPk => $sCard)
    {
      $sId = 'cp_card_'.$nProfileCpPk;
      $sHTML.= $this->coHTML->getBlocStart($sId, array('class' => 'popupCpCard hidden'));
      $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'float:right; '));
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'/pictures/close_16.png', 'Close', 'javascript:;', array('onclick' => '$(\'#'.$sId.'\').fadeOut(); ', 'style' => ' position:relative; top: 8px; right: 24px; '));
      $sHTML.= $this->coHTML->getBlocEnd();
      $sHTML.= $sCard;
      $sHTML.= $this->coHTML->getBlocEnd();
    }

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'top_right_activity'));

    if(isset($asContactData['follower_lastname']) && !empty($asContactData['follower_lastname']))
    {
      $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/manager.png', 'Account manager', '', array('style' => 'height: 24px;'));
      $sHTML.= $this->coHTML->getText(' Account manager: ', array('class' => 'ab_account_manager'));

      if($sAccess)
      {
        $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_TRANSFER, CONST_AB_TYPE_CONTACT,(int)$asContactData['addressbook_contactpk']);
        $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body','','310','550', 1);
        $sHTML.= $this->coHTML->getLink($oLogin->getUserAccountName($asContactData['follower_lastname'],$asContactData['follower_firstname']),'javascript:;', array('onclick'=>$sAjax));
      }
      else
        $sHTML.= $this->coHTML->getText($oLogin->getUserAccountName($asContactData['follower_lastname'],$asContactData['follower_firstname']));

      if($asContactData['followers'])
      {
        $asFollowers = $asContactData['followers'];
        $asData = explode(',',$asFollowers);
        $sHTML.= $this->coHTML->getSpace(1);
        foreach($asData as $asFollow)
        {
          $sHTML.= $this->coHTML->getText(',');
          $sHTML.= $this->coHTML->getSpace(1);
          $asRecords = $oLogin->getUserDataByPk((int)$asFollow);
          if($sAccess)
            $sHTML.= $this->coHTML->getLink($oLogin->getUserAccountName($asRecords['lastname'],$asRecords['firstname']),'javascript:;', array('onclick'=>$sAjax));
          else
            $sHTML.= $this->coHTML->getText($oLogin->getUserAccountName($asRecords['lastname'],$asRecords['firstname']));

          $sHTML.= $this->coHTML->getSpace(1);
        }
      }
    }
    else
    {
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_TRANSFER, CONST_AB_TYPE_CONTACT,(int)$asContactData['addressbook_contactpk']);
      $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body','','310','550', 1);
      $sHTML.= $this->coHTML->getLink('< Define Manager >','javascript:;', array('onclick' => $sAjax));
    }

    $sHTML.= $this->coHTML->getBlocEnd();


    $nActivityCounter = 0;
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'cp_top_activity'));

      if(!empty($oEvent))
      {
        $asEventEmails = $oEvent->getEventDetail('email', (int)$asContactData['addressbook_contactpk'],'ct');

        foreach($asEventEmails as $asEventEmail)
        {
          $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW,CONST_AB_TYPE_CONTACT,(int)$asContactData['addressbook_contactpk'],array('class'=>''),'ct_tab_eventId');

          $sHTML.= $this->coHTML->getText('Latest email: ', array('class' => 'ab_view_strong'));
          $asUserData= $oLogin->getUserDataByPk((int)$asEventEmail['created_by']);

          $sHTML.= $this->coHTML->getText('by  ');
          $sHTML.= $oLogin->getUserNameFromData($asUserData);
          $sHTML.= $this->coHTML->getText(' - ');
          $sHTML.= $this->coHTML->getNiceTime($asEventEmail['date_display'],0,true);
          $sHTML.= $this->coHTML->getBlocStart('', array('class' => ''));

          $sContentEmail = '<strong>'.$asEventEmail['title'].'</strong><br/> '.$asEventEmail['content'];
          $sHTML.= $this->coHTML->getExtendableBloc('lastemail', $sContentEmail,130);
          $sHTML.= $this->coHTML->getBlocEnd();
          $nActivityCounter++;
        }

        $sHTML.= $this->coHTML->getFloatHack();

        if(!empty($asEventEmails))
          $asLatestEvents = $oEvent->getEventDetail('other',(int)$asContactData['addressbook_contactpk'],'ct');
        else
          $asLatestEvents = $oEvent->getEventDetail('other',(int)$asContactData['addressbook_contactpk'],'ct', 2);

        if(!empty($asLatestEvents))
        {
          foreach($asLatestEvents as $asLatestEvent)
          {
            $sHTML.= $this->coHTML->getText('Latest Activity: ', array('class' => 'ab_view_strong'));
            $asUserData= $oLogin->getUserDataByPk((int)$asLatestEvent['created_by']);
            $sHTML.= $this->coHTML->getText('by ');
            $sHTML.= $oLogin->getUserNameFromData($asUserData);
            $sHTML.= $this->coHTML->getText(' - ');

            $sHTML.= $this->coHTML->getNiceTime($asLatestEvent['date_display'],0,true);

            $sHTML.= $this->coHTML->getBlocStart('', array('class' => '','style'=>'width:100%; border:none;'));
            $sHTML.= $this->coHTML->getBlocStart('', array('style' => 'width: 100%;'));

            $sContentEvent = '<strong>'.$asLatestEvent['title'].'</strong><br/> '.$asLatestEvent['content'];
            $sHTML.= $this->coHTML->getExtendableBloc('lastevent', $sContentEvent,130);

            $sHTML.= $this->coHTML->getBlocEnd();
            $sHTML.= $this->coHTML->getBlocEnd();

            $sHTML.= $this->coHTML->getCR();
            $nActivityCounter++;
          }
        }
      }

      if($nActivityCounter < 2 && !empty($asContactData['updated_by']))
      {
        $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'margin-top:5px;'));
        $sHTML.= $this->coHTML->getText('Last Edited: ', array('class' => 'ab_view_strong'));
        $sHTML.= ' - ';
        $asUserData = $oLogin->getUserList((int)$asContactData['updated_by'],false,true);
        $sUser = $oLogin->getUserNameFromData(current($asUserData));
        $sHTML.= $this->coHTML->getNiceTime($asContactData['date_update'], 0, true).$this->coHTML->getText(' - by '.$sUser);
        $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getCR();
        $nActivityCounter++;
      }

      if($nActivityCounter < 2)
      {
        if(!empty($aLastDocument['title']) && (int)$aLastDocument['loginfk'] !=  $oLogin->getUserPk())
        {
          $sHTML.= $this->coHTML->getCR(2);
          $sHTML.= $this->coHTML->getBlocStart('', array('style' =>'margin-top:10px;'));
          $sHTML.= $this->coHTML->getText('Latest Document: ', array('class' => 'ab_view_strong'));
          $sHTML.= ' - ';

          $sHTML.= $this->coHTML->getText($aLastDocument['title']. ' - '.$this->coHTML->getNiceTime($aLastDocument['date_creation'],0,true));
          $sHTML.= $this->coHTML->getBlocEnd();
          $sHTML.= $this->coHTML->getCR();
          $nActivityCounter++;
        }
      }

      if(!empty($aLastDocument['title'])&&(int)$aLastDocument['loginfk'] !=  $oLogin->getUserPk())
      {
        $sHTML.= $this->coHTML->getCR(2);
        $sUrl = $this->coPage->getUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => $this->cnPk));
        $sHTML.= $this->coHTML->getLink(' Add notes / activities to this connection', $sUrl);
        $sHTML.= $this->coHTML->getCR(2);
      }
    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'cp_top_action'));

    $asAction = array();
    if(!empty($oWebmail))
    {
      //Send Email
      $sURL = $oWEBMAIL->getURL('webmail', CONST_ACTION_ADD, CONST_WEBMAIL,(int)$asContactData['addressbook_contactpk'],array('ppaty'=>CONST_AB_TYPE_CONTACT,'ppaid'=>$asContactData['addressbook_contactpk']));
      $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body','','700','800',1);

      $asAction[] = array('label' => 'Send Email to this connection', 'url' => '', 'pic' => $oWebmail->getResourcePath().'pictures/email.png', 'onclick'=> $sAjax);
    }

    if(!empty($oEvent))
    {
      //Add a event
      $sUrl = $this->coPage->getAjaxUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_CP_UID => $this->_getUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => $this->cnPk));
      $asAction[] = array('label' => 'Add a note/activity', 'url' => '', 'pic' => $oEvent->getResourcePath().'pictures/add_event_16.png',
          'onclick' => 'var oConf = goPopup.getConfig();
            oConf.height = 700;
            oConf.width = 980;
            oConf.title = \'Add an activity...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ');
    }

    //Suggest to link the connection to a company
    if(empty($asContactData['companyfk']))
    {
      $sUrl = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_ADD, CONST_AB_TYPE_COMPANY_RELATION, (int)$asContactData['addressbook_contactpk']);
      //$asAction[] = array('label' => 'Link the connection to a company', 'url' => $sUrl, 'pic' => CONST_PICTURE_LINK, 'ajaxLayer' => 1);
      $asAction[] = array('label' => 'Link the connection to a company', 'url' => '', 'pic' => CONST_PICTURE_LINK,
            'onclick' => 'var oConf = goPopup.getConfig();
            oConf.width = 880;
            oConf.height = 640;
            oConf.title = \'Link connection to a company...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ');
    }

    if($bAccessOpp)
    {
      //Send Email
      $sUrl = $this->coPage->getAjaxURL('opportunity', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $aCpValues);
      $asAction[] = array('label' => 'Add a business opportunity', 'url' => '', 'pic' => CONST_PICTURE_OPPORTUNITY,
            'onclick' => 'var oConf = goPopup.getConfig();
            oConf.height = 660;
            oConf.width = 980;
            oConf.title = \'Add a business opportunity...\';
            oConf.modal = true;
            goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ');
    }

    //if the coinnection is a temporary prospect, offer a link to edit it (still prospect)
    if($asContactData['relationfk'] == CONST_AB_PROSPECT_PK)
    {
      $sUrl = $this->coPage->getUrl('addressbook', CONST_ACTION_EDIT, CONST_AB_TYPE_CONTACT, (int)$asContactData['addressbook_contactpk'], array('prospect' => 1));
      $asAction[] = array('label' => 'Edit prospect (keep prospect status)', 'url' => $sUrl, 'pic' => CONST_PICTURE_EDIT);
    }

    if(!empty($oSharedSpace))
    {
      // Upload a document
      $sAjax = $oSharedSpace->displayAddLink($aCpValues, true, true);

      if(!empty($sAjax))
        $asAction[] = array('url' => '', 'pic' => CONST_PICTURE_UPLOAD, 'label' => 'Add a document', 'onclick' => $sAjax);
    }

    if(count($asAction) > 2)
      $sHTML.= $this->coHTML->getActionButtons($asAction, 3, '', array('width' => 300, 'vertical' => 1));
    else
      $sHTML.= $this->coHTML->getActionButtons($asAction, 3, '', array('min-width' => 490));


    $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getFloatHack();


    // ####################################################
    // INITIALIZING TABS :
    // Order is important as it will be used for display
    // ####################################################

    $asTabs = array();
    $oSetting = CDependency::getComponentByName('settings');
    $asCpTabs = $oSetting->getSettingValue('contact_tabs');

    if(empty($asCpTabs))
    {
      $sUrl = $this->coPage->getUrl('login', CONST_ACTION_EDIT, CONST_LOGIN_TYPE_USER, 0);
      $asTabs[] = array('label' => 'notice', 'title' => 'Notice', 'content' => 'No tab is selected for display. <br />
        Please choose the tabs you wish to display using the '.$this->coHTML->getLink('My Account section', $sUrl).' > Preferences');
    }
    else
    {
      foreach ($asCpTabs as $sTabId)
      {
        switch($sTabId)
        {
          case 'ct_tab_profile':
            if(($nProfil < 2))
              $nProfil = 0;
            $sProfilTitle = ($nProfil > 0 ? 'Profiles ('.$nProfil.')' : '<i>Profiles</i>');
            $asTabs[] = array('label' => CONST_TAB_CT_PROFILE, 'title' => $sProfilTitle, 'content' => $this->_getContactProfileTab($asContactData, $asProfil));
            break;
          case 'ct_tab_event':
            $sEventTitle = ($nEvents > 0 ? 'Activites ('.$nEvents.')' : '<i>Activities</i>');
            $asTabs[] = array('label' => CONST_TAB_CT_EVENT, 'title' => $sEventTitle, 'content' => $this->_getContactEventTab($asContactData));
            break;
          case 'ct_tab_coworker':
            $sCoWorkerTitle = ($nCoworkers > 0 ? 'Co Workers ('.$nCoworkers.')' : '<i>Co workers</i>');
            $asTabs[] = array('label' => CONST_TAB_CT_COWORKERS, 'title' => $sCoWorkerTitle, 'content' => $this->_getContactCoworkersTab($asContactData, $asCoworkers));
            break;
          case 'ct_tab_document':
            if(!empty($aDocuments))
            {
              $sDocumentTitle = ($nDocuments > 0 ? 'Documents ('.$nDocuments.')' : '<i>Documents</i>');
              $asTabs[] = array('label' => CONST_TAB_CT_DOCUMENT, 'title' => $sDocumentTitle, 'content' => $sDocumentsTabs);
            }
            break;
          case 'ct_tab_detail':
            $asTabs[] = array('label' => CONST_TAB_CT_DETAIL, 'title' => 'Detail', 'content' => $this->_getContactDetailTab($asContactData));
            break;
        }
      }
    }

    if($sOpportunityUid)
    {
       $sOppTitle = ($nOpportunity > 0 ? 'Opportunities ('.$nOpportunity.')' : '<i>Opportunity</i>');
       $asTabs[] = array('label' => CONST_TAB_CT_OPPORTUNITY, 'title' => $sOppTitle, 'content' => $oOpportunity->getTabContent($aCpValues));

    }

    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'bottom_container'));
    $sHTML.= $this->coHTML->getTabs('contact', $asTabs);
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }


  /**
   * Get the search order for connection
   * @param string $psSearchId
   * @param boolen $pbWithKeyword
   * @return string
   */

  private function _getContactSearchOrder($psSearchId = '', $pbWithKeyword = true)
  {
    if($pbWithKeyword)
      $sKeyword = ' ORDER BY ';
    else
      $sKeyword = '';

    //try to find the previous sort field/order from saved history, if not, try to get it from the url
    $asOrder = $this->_getHistorySearchOrder($psSearchId, $this->csUid, CONST_AB_TYPE_CONTACT);

    switch($asOrder['sortfield'])
    {
      case 'id':
      case 'pk': return $sKeyword.' addressbook_contactpk '.$asOrder['sortorder'].' '; break;
      case 'relation': return $sKeyword.' relationfk '.$asOrder['sortorder'].' '; break;
      case 'firstname': return $sKeyword.' firstname '.$asOrder['sortorder'].', lastname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'lastname': return $sKeyword.' lastname '.$asOrder['sortorder'].', firstname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'company': return $sKeyword.' company_name '.$asOrder['sortorder'].', firstname ASC, lastname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'department': return $sKeyword.' department '.$asOrder['sortorder'].', firstname ASC, lastname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'position': return $sKeyword.' position '.$asOrder['sortorder'].', firstname ASC, lastname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'industry': return $sKeyword.' industry_name '.$asOrder['sortorder'].', firstname ASC, lastname ASC, addressbook_contactpk ASC ,event.date_display DESC'; break;
      case 'activity': return $sKeyword.' event.date_display '.$asOrder['sortorder'].', firstname ASC, lastname ASC, addressbook_contactpk ASC '; break;

      default:
        return $sKeyword.' ct.addressbook_contactpk desc '; break;
        break;
    }
    return '';
  }

  /**
   * Get the search order for company
   * @param string $psSearchId
   * @param boolen $pbWithKeyword
   * @return string
   */

  private function _getCompanySearchOrder($psSearchId = '', $pbWithKeyword = true)
  {
    if($pbWithKeyword)
      $sKeyword = ' ORDER BY ';
    else
      $sKeyword = '';

    //try to find the previous sort field/order from saved history, if not, try to get it from the url
    $asOrder = $this->_getHistorySearchOrder($psSearchId, $this->csUid, CONST_AB_TYPE_COMPANY);

    switch($asOrder['sortfield'])
    {
      case 'id':
      case 'pk': return $sKeyword.' addressbook_companypk '.$asOrder['sortorder'].' '; break;
      case 'company_name': return $sKeyword.' company_name '.$asOrder['sortorder'].', addressbook_companypk ASC,date_display DESC'; break;
      case 'industry': return $sKeyword.' industry_name '.$asOrder['sortorder'].', company_name ASC, addressbook_companypk ASC,date_display DESC'; break;
      case 'activity': return $sKeyword.' date_display '.$asOrder['sortorder'].', company_name ASC, addressbook_companypk ASC'; break;

      default:
        return $sKeyword.' cp.addressbook_companypk desc '; break;
        break;
      }
    return '';
  }

  /**
   * Get the search order if the search has been already done
   * @param string $psSearchId
   * @param boolen $pbWithKeyword
   * @return string
   */

  private function _getHistorySearchOrder($psSearchId = '', $psGuid = '', $psType = '')
  {
    $asSortHistory = getSearchHistory($psSearchId, $psGuid, $psType);
    if(!empty($psSearchId) && !empty($asSortHistory) && !empty($asSortHistory['sortfield']))
    {
      $sOrderField = $asSortHistory['sortfield'];
      $sOrderType = $asSortHistory['sortorder'];
     }
    else
    {
      $sOrderField = getValue('sortfield', '');
      $sOrderType = getValue('sortorder', '');
      }

    return array('sortfield' => $sOrderField, 'sortorder' => $sOrderType);
  }

 /**
   * Display the full search bloc including title, search form, flded icon for company
   * @param string $psSearchId
   * @param array $pavSearchResult
   * @param boolean $pbNewSearch
   * @return string html
   */

  private function _getCompanySearchBloc($psSearchId, $pavSearchResult, $pbNewSearch = true)
  {
    global $gbNewSearch;

    $sExtraClass = ($pbNewSearch) ? '' : ' hidden';

    $sHTML = '';
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOutterContainer'));
/*
     $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchMenuIcon', 'onclick' => 'jQuery(this).parent().find(\'.searchContainer\').fadeToggle();'));
       $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/main_search_icon.png', 'Search Companies');
        $sHTML.= $this->coHTML->getCR();
        $sHTML.= $this->coHTML->getSpanStart();
        $sHTML.= $this->coHTML->getText($pavSearchResult['nNbResult']);
       $sHTML.= $this->coHTML->getSpanEnd();
      $sHTML.= $this->coHTML->getBlocEnd();
*/
      $sHTML.= $this->coHTML->getBlocStart('searchResult_'.$psSearchId, array('class' =>'searchContainer '.$sExtraClass));

        //Search title.
        $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchFormHeader'));
          $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchTitle'));
            $sSearchMessage = $this->_getCompanySearchMessage($pavSearchResult['nNbResult']);
            if(!empty($sSearchMessage))
              $sHTML.= $sSearchMessage;
            else
              $sHTML.= $this->coHTML->getText('Search Companies');
          $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getBlocEnd();

        $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOption'));

         $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOptionClose', 'onclick' => 'jQuery(\'.searchContainer\').fadeOut();'));
          $sHTML.= $this->coHTML->getBlocStart();
            $sHTML.= $this->coHTML->getText('Close');
          $sHTML.= $this->coHTML->getBlocEnd();
         $sHTML.= $this->coHTML->getBlocEnd();

         $sHTML.= $this->coHTML->getListStart('', array('onclick' => 'jQuery(\'li:not(:first)\', this).fadeToggle();'));

           $sHTML.= $this->coHTML->getListItemStart('', array('class' => ''));
            $sHTML.= 'Options ';
           $sHTML.= $this->coHTML->getListItemEnd();

            $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'hidden', 'onclick' => 'alert(\'Coming soon.\');'));
             $sHTML.= '- Save search';
            $sHTML.= $this->coHTML->getListItemEnd();

            $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'hidden', 'onclick' => 'alert(\'Coming soon.\');'));
             $sHTML.= '- Preferences';
            $sHTML.= $this->coHTML->getListItemEnd();

          $sHTML.= $this->coHTML->getListEnd();
        $sHTML.= $this->coHTML->getBlocEnd();

        //display search form
        $sHTML.= $this->coHTML->getBlocStart('companysearchForm', array('class' =>'queryBuilderContainer'));
          $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'searchFormSidebar'));
            $sHTML.= $this->_getCompanySearchFormSidebar();
          $sHTML.= $this->coHTML->getBlocEnd();
           $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'searchFormContainer'));

            $sHTML.= $this->_getCompanySearchForm($psSearchId);

          $sHTML.= $this->coHTML->getBlocEnd();
         $sHTML.= $this->coHTML->getFloatHack();
       $sHTML.= $this->coHTML->getBlocEnd();
      $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Company search side bar
   * @return type
   */

  private function _getCompanySearchFormSidebar()
  {
    $sJavascript = 'jQuery(document).ready(function(){ ';
    $sJavascript.= '  jQuery(\'.searchFormFieldSelector li\').click(function(){ ';
    $sJavascript.= '    if(!jQuery(this).hasClass(\'fieldUsed\')) ';
    $sJavascript.= '      jQuery(this).addClass(\'fieldUsed\'); ';
    $sJavascript.= '    else ';
    $sJavascript.= '      jQuery(this).fadeOut(350, function(){ jQuery(this).css(\'border\', \'1px solid orange\'); }).
      fadeIn(350).fadeOut(350, function(){ jQuery(this).css(\'border\', \'1px solid orange\'); })
      .fadeIn(350, function(){ jQuery(this).css(\'border\', \'\'); }); ';
    $sJavascript.= '    var sFieldContainer = jQuery(this).attr(\'fieldname\'); ';
    $sJavascript.= '    var oFormContainer = jQuery(\'.\'+sFieldContainer).closest(\'.innerForm\'); ';
    $sJavascript.= '   if(sFieldContainer == \'none\') ';
    $sJavascript.= '   { removeFormField(null, \'.formFieldContainer\'); return true; }';
    $sJavascript.= '    var sFieldContainer = sFieldContainer.split(\' \').join(\', .\'); ';
    $sJavascript.= '    jQuery(oFormContainer).find(\'script\').html(\'\'); ';
    $sJavascript.= '    var oFieldContainer = jQuery(\'.\'+sFieldContainer+\':not(.formFieldHidden)\'); ';
    $sJavascript.= '    jQuery(oFieldContainer).each(function() ';
    $sJavascript.= '    { ';
    $sJavascript.= '      if(sFieldContainer == \'formFieldContainer\' ) ';
    $sJavascript.= '      {  displayFormField(this, null, true); } ';
    $sJavascript.= '      else ';
    $sJavascript.= '      { displayFormField(this);  jQuery(this).find(\'input,select,textarea\').focus(); } ';
    $sJavascript.= '    }); ';
    $sJavascript.= '  }); ';

    //when loading the page in php, we refresh the sidebar and add X link
    $sJavascript.= ' refreshFormField(); ';
    $sJavascript.= '}); ';
    $this->coPage->addCustomJs($sJavascript);

    //$sConnectionPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/connection.png');
    $sCompanyPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/company.png');
    $sEventPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/event.png');

    $sHTML = $this->coHTML->getListStart('', array('class' =>'searchFormFieldSelector'));

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_cname '));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Company name', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_manager'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Account manager', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_relation'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.'Relation', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_industry'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.'Industry', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_phone'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Phone', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_email'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Email', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_synopsis'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Synopsis', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_address'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Address', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_type'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activity type', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_content'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activity content', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_from search_evt_to'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activities date', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_cf'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Custom field', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'formFieldContainer', 'onclick' => 'jQuery(this).siblings(\':not(#clear_value)\').addClass(\'fieldUsed\'); ')); //$(\'#clear_value\').removeClass(\'fieldUsed\');
      $sHTML.= $this->coHTML->getLink('All Fields', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'none', 'onclick' => 'jQuery(this).siblings().removeClass(\'fieldUsed\');'));
      $sHTML.= $this->coHTML->getLink('Hide all Fields', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('clear_value', array('fieldname' => 'formFieldContainer', 'onclick' => ' jQuery(this).siblings().removeClass(\'fieldUsed\');resetContactSearch();'));
      $sHTML.= $this->coHTML->getLink('Clear Values', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemEnd();

    $sHTML.= $this->coHTML->getListEnd();

    return $sHTML;
  }

  /**
   * Display the full search bloc including title, search form, flded icon
   * @param string $psSearchId
   * @param array $pavSearchResult
   * @param boolean $pbNewSearch
   * @return string html
   */
  private function _getContactSearchBloc($psSearchId, $pavSearchResult, $pbNewSearch = true)
  {
    //$gbNewSearch = true only if it's a new search (opposite of paging up/down and sorting)
    global $gbNewSearch;

    $sExtraClass = ($pbNewSearch) ? '' : ' hidden';

    $sHTML = '';
    $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOutterContainer'));

    /*  OLD Version. Now handled by action menu.
     *  $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchMenuIcon', 'onclick' => 'jQuery(this).parent().find(\'.searchContainer\').fadeToggle();'));
        $sHTML.= $this->coHTML->getPicture($this->getResourcePath().'pictures/main_search_icon.png', 'Search connections');
        $sHTML.= $this->coHTML->getCR();
        $sHTML.= $this->coHTML->getSpanStart();
        $sHTML.= $this->coHTML->getText($pavSearchResult['nNbResult']);
        $sHTML.= $this->coHTML->getSpanEnd();
      $sHTML.= $this->coHTML->getBlocEnd();
     */
      $sHTML.= $this->coHTML->getBlocStart('searchResult_'.$psSearchId, array('class' =>'searchContainer '.$sExtraClass));

        //Search title.
        $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchFormHeader'));
          $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchTitle'));
            $sSearchMessage = $this->_getSearchMessage($pavSearchResult['nNbResult']);
            if(!empty($sSearchMessage))
              $sHTML.= $sSearchMessage;
            else
              $sHTML.= $this->coHTML->getText('Search Connections');
          $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getBlocEnd();

        $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOption'));

         $sHTML.= $this->coHTML->getBlocStart('', array('class' =>'searchOptionClose', 'onclick' => 'jQuery(\'.searchContainer\').fadeOut();'));
          $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Close'));
         $sHTML.= $this->coHTML->getBlocEnd();

          $sHTML.= $this->coHTML->getListStart('', array('onclick' => 'jQuery(\'li:not(:first)\', this).fadeToggle();'));

            $sHTML.= $this->coHTML->getListItemStart('', array('class' => ''));
            $sHTML.= 'Options ';
            $sHTML.= $this->coHTML->getListItemEnd();

            $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'hidden', 'onclick' => 'alert(\'Coming soon.\');'));
            $sHTML.= '- Save search';
            $sHTML.= $this->coHTML->getListItemEnd();

            $sHTML.= $this->coHTML->getListItemStart('', array('class' => 'hidden', 'onclick' => 'alert(\'Coming soon.\');'));
            $sHTML.= '- Preferences';
            $sHTML.= $this->coHTML->getListItemEnd();

          $sHTML.= $this->coHTML->getListEnd();

        $sHTML.= $this->coHTML->getBlocEnd();

        //display search form
        $sHTML.= $this->coHTML->getBlocStart('contactsearchForm', array('class' =>'queryBuilderContainer'));

          $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'searchFormSidebar'));
            $sHTML.= $this->_getContactSearchFormSidebar();
          $sHTML.= $this->coHTML->getBlocEnd();

          $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'searchFormContainer'));
            $sHTML.= $this->_getContactSearchForm($psSearchId);
          $sHTML.= $this->coHTML->getBlocEnd();

          $sHTML.= $this->coHTML->getFloatHack();

        $sHTML.= $this->coHTML->getBlocEnd();
      $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Search form for the contact
   * @return array of HTML
   */
  private function _getContactSearchForm($psSearchId, $pbNewSearch = true)
  {
    $nLoginPk = (int)getValue('loginpk', 0);
    $asFormFields = array('firstname', 'lastname', 'company', 'followerfk', 'contact_relation', 'position', 'tel', 'email', 'refID','search_bcmid', 'event', 'event_type', 'date_eventStart', 'date_eventEnd');

    $nFieldDisplayed = 0;
    foreach($asFormFields as $sFieldName)
    {
      $vValue = getValue($sFieldName);
      if(!empty($vValue))
        $nFieldDisplayed++;
    }
    $nFieldToDisplay = (6 - $nFieldDisplayed);

    $oEvent = CDependency::getComponentUidByName('event');
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT);

    $this->coPage->addJsFile($this->getResourcePath().'js/addressbook.js');

    /* @var $oForm CFormEx */
    $oForm = $this->coHTML->initForm('queryForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'submitLabel' => 'Search', 'ajaxTarget' => 'contactListContainer', 'ajaxCallback' => ' $(\".searchTitle\").click();'));
    $oForm->setFormDisplayParams(array('columns' => 2, 'noCancelButton' => '1', 'fullFloating' => true));

    $vField = getValue('firstname');
     $oForm->addField('input', 'firstname', array('label' => 'Firstname', 'value' =>$vField));
    $oForm->setFieldControl('firstname', array('jsFieldMinSize' => 2, 'jsFieldMaxSize' => 255));
    if(!$vField && $nFieldToDisplay)
    {
      //force displaying this field if less than 4 fields displayed
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('firstname', array('class' => 'search_fname', 'fieldname' => 'search_fname'));
    }
    else
      $oForm->setFieldDisplayParams('firstname', array('class' => (($vField || $nFieldDisplayed++ < 4)?'':'hidden ').' search_fname', 'fieldname' => 'search_fname'));

    $vField = getValue('lastname');
    $oForm->addField('input', 'lastname', array('label' => 'Lastname', 'value' => $vField));
    $oForm->setFieldControl('lastname', array('jsFieldMinSize' => 2, 'jsFieldMaxSize' => 255));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('lastname', array('class' => 'search_lname', 'fieldname' => 'search_lname'));
    }
    else
      $oForm->setFieldDisplayParams('lastname', array('class' => (($vField || $nFieldToDisplay < 3)?'':'hidden ').' search_lname', 'fieldname' => 'search_lname'));


    $vField = getValue('company');
    $oForm->addField('input', 'company', array('label' => 'Company', 'value' => $vField));
    $oForm->setFieldControl('company', array('jsFieldMinSize' => 2, 'jsFieldMaxSize' => 255));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('company', array('class' => 'search_company', 'fieldname' => 'search_company'));
    }
    else
      $oForm->setFieldDisplayParams('company', array('class' => (($vField || $nFieldToDisplay < 2)?'':'hidden ').' search_company', 'fieldname' => 'search_company'));

    $vField = ($nLoginPk || getValue('followerfk', 0));
    $sURL = $this->coPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1, 'friendly' => 1));
    $oForm->addField('selector', 'followerfk', array('label'=>'Account Manager', 'url' => $sURL, 'onchange' =>'$(\'#cascading_id\').parent().parent().find(\'div\').show();'));
    $oForm->setFieldControl('followerfk', array('jsFieldTypeIntegerPositive' => ''));
    if(!$vField && $nFieldToDisplay)
    {
      //force displaying this field if less than 4 fields displayed
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('followerfk', array('class' => 'search_manager', 'fieldname' => 'search_manager'));
    }
    else
      $oForm->setFieldDisplayParams('followerfk', array('class' => (($vField || $nFieldToDisplay < 1)?'':'hidden ').' search_manager', 'fieldname' => 'search_manager'));

    if(!empty($nLoginPk))
    {
      $oLogin = CDependency::getCpLogin();
      $asFolowerData = $oLogin->getUserDataByPk($nLoginPk);

      if(!empty($asFolowerData))
        $oForm->addOption('followerfk', array('value' => $nLoginPk, 'label' => $oLogin->getUsernameFromData($asFolowerData)));
    }
    else
    {
      $nFollwerfk = (int)getValue('followerfk', 0);
      if(!empty($nFollwerfk))
      {
        $oLogin =  CDependency::getCpLogin();
        $asFollowerData = $oLogin->getUserDataByPk($nFollwerfk);
        if(!empty($asFollowerData))
          $oForm->addOption('followerfk', array('value' => $nFollwerfk, 'label' => $oLogin->getUserNameFromData($asFollowerData)));
      }
    }

    $vField = getValue('contact_relation');
    $oForm->addField('select', 'contact_relation', array('label' => ' Relation'));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('contact_relation', array('class' => 'search_relation', 'fieldname' => 'search_relation'));
    }
    else
      $oForm->setFieldDisplayParams('contact_relation', array('class' => (($vField)?'':'hidden ').' search_relation', 'fieldname' => 'search_relation'));

    $vField = (array)getValue('contact_industry');
    $oForm->addField('select', 'contact_industry[]', array('label' =>' Industry', 'multiple' => 'multiple'));
    if(!$vField && $nFieldToDisplay)
    {
      $nFieldToDisplay--;
      $oForm->setFieldDisplayParams('contact_industry[]', array('class' => 'search_industry', 'fieldname' => 'search_industry'));
    }
    else
      $oForm->setFieldDisplayParams('contact_industry[]', array('class' => (($vField)?'':'hidden ').' search_industry', 'fieldname' => 'search_industry'));

    $asIndustry = $this->_getModel()->getIndustry();

    foreach($asIndustry as $nIndustryPk => $asIndustryData)
    {
      if(in_array($nIndustryPk, $vField))
        $oForm->addOption('contact_industry[]', array('value'=> $nIndustryPk, 'label' => $asIndustryData['industry_name'], 'selected' => 'selected'));
      else
        $oForm->addOption('contact_industry[]', array('value'=> $nIndustryPk, 'label' => $asIndustryData['industry_name']));
    }

    $asCompanyRel = getCompanyRelation();
    $sRelation = getValue('contact_relation');
    $oForm->addOption('contact_relation', array('value'=>'', 'label' => 'Select'));
    foreach($asCompanyRel as $sType => $vType)
    {
      if($sRelation==$sType)
        $oForm->addOption('contact_relation', array('value'=>$sType, 'label' => $vType['Label'],'selected'=>'selected'));
      else
        $oForm->addOption('contact_relation', array('value'=>$sType, 'label' => $vType['Label']));
    }

    $vField = getValue('position');
    $oForm->addField('input', 'position', array('label' => 'Position', 'value' => $vField));
    $oForm->setFieldControl('position', array('jsFieldMinSize' => 2, 'jsFieldMaxSize' => 255));
    $oForm->setFieldDisplayParams('position', array('class' => (($vField)?'':'hidden ').' search_position', 'fieldname' => 'search_position'));

    $vField = getValue('tel');
    $oForm->addField('input', 'tel', array('label' => 'Phone', 'value' => $vField));
    $oForm->setFieldControl('tel', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 20));
    $oForm->setFieldDisplayParams('tel', array('class' => (($vField)?'':'hidden ').' search_phone', 'fieldname' => 'search_phone'));

    $vField = getValue('address');
    $oForm->addField('input', 'address', array('label' => 'Address', 'value' => $vField));
    $oForm->setFieldControl('address', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 20));
    $oForm->setFieldDisplayParams('address', array('class' => (($vField)?'':'hidden ').' search_address', 'fieldname' => 'search_address'));

    $vField = getValue('email');
    $oForm->addField('input', 'email', array('label' => 'Email', 'value' => $vField));
    $oForm->setFieldControl('email', array('jsFieldMinSize' => 2));
    $oForm->setFieldDisplayParams('email', array('class' => 'hidden search_email'));
    $oForm->setFieldDisplayParams('email', array('class' => (($vField)?'':'hidden ').' search_email', 'fieldname' => 'search_email'));

    $vField = getValue('refID');
    $oForm->addField('input','refID', array('label' => 'Old CRM Ref. ID', 'value' => $vField));
    $oForm->setFieldControl('refID', array('jsFieldMinSize' => 1, 'jsFieldMaxSize' => 10));
    $oForm->setFieldDisplayParams('refID', array('class' => (($vField)?'':'hidden ').' search_refid', 'fieldname' => 'search_refid'));

    $vField = getValue('bcmPK');
    $oForm->addField('input','bcmPK', array('label' => 'BCM ConnectionID', 'value' => $vField));
    $oForm->setFieldControl('bcmPK', array('jsFieldMinSize' => 1, 'jsFieldMaxSize' => 10));
    $oForm->setFieldDisplayParams('bcmPK', array('class' => (($vField)?'':'hidden ').' search_bcmid', 'fieldname' => 'search_bcmid'));


    $oForm->addField('hidden', 'do_search', array('value' => 1));
    $oForm->addField('hidden', 'hidden_first'); $oForm->addField('hidden', 'hidden_second');

    if(!empty($oEvent))
    {
      $vField = getValue('event_type');
      $oForm->addField('select', 'event_type', array('label' => ' Type'));
      $oForm->setFieldDisplayParams('event_type', array('class' => (($vField)?'':'hidden ').' search_evt_type', 'fieldname' => 'search_evt_type'));
      $oForm->addOption('event_type', array('value'=>'', 'label' => 'Select'));

      $asEvent= getEventTypeList();
      $sEventTypes = getValue('event_type');
      foreach($asEvent as $asEvents)
      {
        if($asEvents['value'] == $sEventTypes)
          $oForm->addOption('event_type', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group'], 'selected'=>'selected'));
        else
          $oForm->addOption('event_type', array('value'=>$asEvents['value'], 'label' => $asEvents['label'], 'group' => $asEvents['group']));
      }

      $vField = getValue('event');
      $oForm->addField('input', 'event', array('label' => ' Activity Content', 'value' => $vField));
      $oForm->setFieldControl('event', array('jsFieldMinSize' => 2));
      $oForm->setFieldDisplayParams('event', array('class' => (($vField)?'':'hidden ').' search_evt_content', 'fieldname' => 'search_evt_content'));

      $vField = getValue('date_eventStart');
      $oForm->addField('input', 'date_eventStart', array('type' => 'date', 'label'=>'Activity From', 'value' => $vField));
      $oForm->setFieldDisplayParams('date_eventStart', array('class' => (($vField)?'':'hidden ').' search_evt_from', 'fieldname' => 'search_evt_from'));

      $vField = getValue('date_eventEnd');
      $oForm->addField('input', 'date_eventEnd', array('type' => 'date', 'label'=>'Activity To', 'value' => $vField));
      $oForm->setFieldDisplayParams('date_eventEnd', array('class' => (($vField)?'':'hidden ').' search_evt_to', 'fieldname' => 'search_evt_to'));
    }

    if(isset($_POST['sortfield']))
        $sSortField = $_POST['sortfield'];
    else
        $sSortField = '';

    if(isset($_POST['sortorder']))
        $sSortOrder = $_POST['sortorder'];
    else
        $sSortOrder = '';

    $oForm->addField('hidden', 'sortfield', array('value' =>$sSortField));
    $oForm->addField('hidden', 'sortorder', array('value' => $sSortOrder));
    $oForm->addField('hidden', 'sortItem', array('value' =>'0'));

    return $oForm->getDisplay();
  }


  private function _getContactSearchFormSidebar()
  {
    $sJavascript = 'jQuery(document).ready(function(){ ';

    $sJavascript.= '  jQuery(\'.searchFormFieldSelector li\').click(function(){ ';

    $sJavascript.= '    if(!jQuery(this).hasClass(\'fieldUsed\')) ';
    $sJavascript.= '      jQuery(this).addClass(\'fieldUsed\'); ';
    $sJavascript.= '    else ';
    $sJavascript.= '      jQuery(this).fadeOut(350, function(){ jQuery(this).css(\'border\', \'1px solid orange\'); }).
      fadeIn(350).fadeOut(350, function(){ jQuery(this).css(\'border\', \'1px solid orange\'); })
      .fadeIn(350, function(){ jQuery(this).css(\'border\', \'\'); }); ';

    $sJavascript.= '    var sFieldContainer = jQuery(this).attr(\'fieldname\'); ';
    $sJavascript.= '    var oFormContainer = jQuery(\'.\'+sFieldContainer).closest(\'.innerForm\'); ';

    $sJavascript.= '   if(sFieldContainer == \'none\') ';
    $sJavascript.= '   { removeFormField(null, \'.formFieldContainer\'); return true; }';

    $sJavascript.= '    var sFieldContainer = sFieldContainer.split(\' \').join(\', .\'); ';
    $sJavascript.= '    jQuery(oFormContainer).find(\'script\').html(\'\'); ';

    $sJavascript.= '    var oFieldContainer = jQuery(\'.\'+sFieldContainer+\':not(.formFieldHidden)\'); ';

    $sJavascript.= '    jQuery(oFieldContainer).each(function() ';
    $sJavascript.= '    { ';
    $sJavascript.= '      if(sFieldContainer == \'formFieldContainer\' ) ';
    $sJavascript.= '      {  displayFormField(this, null, true); } ';
    $sJavascript.= '      else ';
    $sJavascript.= '      { displayFormField(this);  jQuery(this).find(\'input,select,textarea\').focus(); } ';
    $sJavascript.= '    }); ';

    $sJavascript.= '  }); ';

    //when loading the page in php, we refresh the sidebar and add X link
    $sJavascript.= ' refreshFormField(); ';

    $sJavascript.= '}); ';
    $this->coPage->addCustomJs($sJavascript);
    $sConnectionPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/connection.png');
    $sCompanyPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/company.png');
    $sEventPic = $this->coHTML->getPicture($this->getResourcePath().'/pictures/event.png');

    $sHTML = $this->coHTML->getListStart('', array('class' =>'searchFormFieldSelector'));

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_lname search_fname'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Connection name', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_refid'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.'Old CRM RefID', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_bcmid'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.'Connection ID', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_company'));
      $sHTML.= $this->coHTML->getLink($sCompanyPic.' Company', 'javascript:;');

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_manager'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Account manager', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_relation'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Relation', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_industry'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Industry', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_position'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Position', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_phone'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Phone', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_address'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Address', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_email'));
      $sHTML.= $this->coHTML->getLink($sConnectionPic.' Email', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_type'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activity type', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_content'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activity content', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'search_evt_from search_evt_to'));
      $sHTML.= $this->coHTML->getLink($sEventPic.' Activities date', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'formFieldContainer', 'onclick' => 'jQuery(this).siblings(\':not(#clear_value)\').addClass(\'fieldUsed\'); ')); //$(\'#clear_value\').removeClass(\'fieldUsed\');
      $sHTML.= $this->coHTML->getLink('All Fields', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('', array('fieldname' => 'none', 'onclick' => 'jQuery(this).siblings().removeClass(\'fieldUsed\');'));
      $sHTML.= $this->coHTML->getLink('Hide all Fields', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemStart('clear_value', array('fieldname' => 'formFieldContainer', 'onclick' => ' jQuery(this).siblings().removeClass(\'fieldUsed\');resetContactSearch();'));
      $sHTML.= $this->coHTML->getLink('Clear Values', 'javascript:;');
      $sHTML.= $this->coHTML->getListItemEnd();

      $sHTML.= $this->coHTML->getListItemEnd();

    $sHTML.= $this->coHTML->getListEnd();

    return $sHTML;
  }

  /**
   * Based on search Parameters, return a string explaining the search
   * @return string: message
  */
  private function _getSearchMessage($pnNbResult = 0, $pasOrderDetail = array(), $pbOnlySort = false)
  {
    $sMessage = '';
    global $gbNewSearch;

    if(isset($pasOrderDetail['sortfield']) && !empty($pasOrderDetail['sortfield']))
    {
      $sSortMsg = $this->coHTML->getText(' - sorted by '.$pasOrderDetail['sortfield'].' '.$pasOrderDetail['sortorder'], array('class'=>'searchTitleSortMsg'));
      if($pbOnlySort)
        return $sSortMsg;
     }
     else
      $sSortMsg = $this->coHTML->getText('', array('class'=>'searchTitleSortMsg'));

    $sField = getValue('lastname');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' Last name : '.$sField, array('class'=>'normalText'));

    $sField = getValue('firstname');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' First name : '.$sField, array('class'=>'normalText'));

    $sField = getValue('tel');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' tel : '.$sField, array('class'=>'normalText'));

    $sField = getValue('email');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' email : '.$sField, array('class'=>'normalText'));

    $sField = getValue('address');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' Address : '.$sField, array('class'=>'normalText'));

    $sField = getValue('contact_relation');
    if(!empty($sField))
    {
      $asRelation = getCompanyRelation((int)$sField);
      $sMessage.= $this->coHTML->getText(' Contact Relation : '.$asRelation['Label'], array('class'=>'normalText'));
      }

    $sField = getValue('company');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' company : '.$sField, array('class'=>'normalText'));

    $sField = getValue('followerfk');
    if(!empty($sField))
    {
      $oLogin = CDependency::getCpLogin();
      $asLoginData = $oLogin->getUserDataByPk((int)$sField);
      $sMessage.= $this->coHTML->getText(' Account Manager : '.$oLogin->getUserNameFromData($asLoginData, true), array('class'=>'normalText'));
      }

     /*
    $sField = getValue('contact_industry');
    if(!empty($sField))
    {
     $asIndustry =  $this->_getIndustry($sField);
     $sIndustryName = '';
     foreach($asIndustry as $asIndustryName)
      {
        $sIndustryName.= $asIndustryName['industry_name'].', ';
      }
     $sMessage.= $this->coHTML->getText(' Industry : '.$sIndustryName, array('class'=>'normalText'));
    }*/

    $sField = getValue('position');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' position : '.$sField, array('class'=>'normalText'));

    $sField = getValue('character');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' position : '.$sField, array('class'=>'normalText'));

    $asField = (array)getValue('contact_industry');
    if(!empty($asField) && !empty($asField[0]))
      $sMessage.= $this->coHTML->getText(count($asField).' industries selected', array('class'=>'normalText'));

    $sField = getValue('event');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' event : '.$sField, array('class'=>'normalText'));

    $sField = getValue('date_eventStart');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' avtivities from : '.$sField, array('class'=>'normalText'));

    $sField = getValue('date_eventEnd');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' to: '.$sField, array('class'=>'normalText'));

    $sField = getValue('event_type');
    if(!empty($sField))
      $sMessage.= $this->coHTML->getText(' activity type : '.$sField, array('class'=>'normalText'));

    if(!empty($gbNewSearch) && !empty($sMessage))
      $sMessage = $this->coHTML->getText(' for ').$sMessage;

    return $this->coHTML->getText($pnNbResult.' results') . $sMessage.' '.$sSortMsg;
  }

  /**
   * Search Query for the contact
   * @return array of query
   */

  private function _getSqlContactSearch()
  {
    $srefID = trim(getValue('refID'));
    $sbcmPk = trim(getValue('bcmPK'));
    $sLastame = trim(getValue('lastname'));
    $sFirstame = trim(getValue('firstname'));
    $sTel = trim(getValue('tel'));
    $sEmail = trim(getValue('email'));
    $sCompany = trim(getValue('company'));
    $nFollowerfk = trim(getValue('followerfk'));
    $sPosition = trim(getValue('position'));
    $sAddress = trim(getValue('address'));
    $sCharacter = trim(getValue('character'));
    $sEvent = trim(getValue('event'));
    $sStartDate = trim(getValue('date_eventStart'));
    $sEndDate = trim(getValue('date_eventEnd'));
    $sEventType = trim(getValue('event_type'));
    $sRelation = trim(getValue('contact_relation'));
    $anIndustry = (array)getValue('contact_industry', array());
    $nLoginPk = trim(getValue('loginpk'));

    $sSearchMode = getValue('search_mode');
    $oDb = CDependency::getComponentByName('database');

    $asResult = array();
    $asResult['join'] = '';
    $asResult['where'] = '';
    $asResult['groupby'] = '';
    $asWhereSql = array();

    if(!empty($srefID))
      $asWhereSql[] = ' ct.externalkey = '.$oDb->dbEscapeString($srefID);

    if(!empty($sbcmPk))
      $asWhereSql[] = ' ct.addressbook_contactpk = '.$oDb->dbEscapeString($sbcmPk);

    if(!empty($sLastame))
      $asWhereSql[] = ' ct.lastname LIKE '.$oDb->dbEscapeString('%'.  strtolower($sLastame).'%');

    if(!empty($sFirstame))
      $asWhereSql[] = ' ct.firstname LIKE '.$oDb->dbEscapeString('%'.strtolower($sFirstame).'%');

    if(!empty($sTel))
      $asWhereSql[] = ' (ct.phone LIKE '.$oDb->dbEscapeString('%'.$sTel.'%').' OR ct.cellphone LIKE '.$oDb->dbEscapeString('%'.$sTel.'%').')';

    if(!empty($sEmail))
      $asWhereSql[] = ' ct.email LIKE '.$oDb->dbEscapeString('%'.$sEmail.'%');

    if(!empty($sAddress))
       $asWhereSql[] = ' ct.address_1 LIKE '.$oDb->dbEscapeString('%'.$sAddress.'%').' OR ct.address_2 LIKE '.$oDb->dbEscapeString('%'.$sAddress.'%');

    if(!empty($sCharacter))
      $asWhereSql[] = ' ct.comments like '.$oDb->dbEscapeString('%'.$sCharacter.'%');

    if(!empty($sRelation))
      $asWhereSql[] = ' ct.relationfk = '.$oDb->dbEscapeString($sRelation);

    if(!empty($anIndustry))
    {
      foreach($anIndustry as $vKey => $nIndustry)
        $anIndustry[$vKey] = $oDb->dbEscapeString($nIndustry);

      $asWhereSql[] = ' ind.addressbook_industrypk IN ('.implode(',', $anIndustry).') ';
    }

    if(!empty($nFollowerfk))
    {
      $asResult['join'].= ' LEFT JOIN addressbook_account_manager as acmn ON (acmn.contactfk = ct.addressbook_contactpk AND acmn.loginfk='.$nFollowerfk.') ';
      $asWhereSql[] = '( acmn.loginfk = '.$oDb->dbEscapeString($nFollowerfk).' OR ct.followerfk = '.$oDb->dbEscapeString($nFollowerfk).') ';
    }
    if(!empty($nLoginPk))
      $asWhereSql[] = ' ct.followerfk = '.$oDb->dbEscapeString($nLoginPk);

    if(!empty($sCompany))
    {
      $asWhereSql[] = ' cpt.company_name LIKE '.$oDb->dbEscapeString('%'.strtolower($sCompany).'%');
      $asResult['join'].= ' LEFT JOIN addressbook_profile as p ON (p.contactfk = ct.addressbook_contactpk and p.date_end IS NULL) ';
      $asResult['join'].= ' INNER JOIN addressbook_company as cpt ON (cpt.addressbook_companypk = p.companyfk AND cpt.company_name LIKE '.$oDb->dbEscapeString('%'.$sCompany.'%').') ';
    }
    if(!empty($sPosition))
    {
      $asResult['join'].= ' LEFT JOIN addressbook_profile as pfl ON (pfl.contactfk = ct.addressbook_contactpk and pfl.date_end IS NULL) ';
      $asWhereSql[] = ' pfl.position LIKE '.$oDb->dbEscapeString('%'.$sPosition.'%');
    }

    if(!empty($sEvent) || !empty($sEventType) || (!empty($sStartDate) && !empty($sEndDate)))
    {
      $asResult['join'].= 'INNER JOIN event_link as evelnk ON (evelnk.cp_pk = ct.addressbook_contactpk and evelnk.cp_type = "ct")';
      $asResult['join'].= 'INNER JOIN event as even ON (even.eventpk = evelnk.eventfk)';
      if(!empty($sEvent))
        $asWhereSql[] = 'even.title LIKE '.$oDb->dbEscapeString('%'.$sEvent.'%').' OR even.content LIKE '.$oDb->dbEscapeString('%'.$sEvent.'%');

      if(!empty($sEventType))
        $asWhereSql[] = 'even.type LIKE '.$oDb->dbEscapeString('%'.($sEventType).'%');

      if(!empty($sStartDate))
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") >= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sStartDate)));

      if(!empty($sEndDate))
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") <= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sEndDate)));

      if(!empty($sStartDate) && !empty($sEndDate))
      {
        $asWhereSql[] = ' date_format(even.date_display,"%Y-%m-%d") >= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sStartDate)));
        $asWhereSql[].= ' date_format(even.date_display,"%Y-%m-%d") <= '.$oDb->dbEscapeString(date('Y-m-d',strtotime($sEndDate)));
      }
    }

    if($sSearchMode == 'or')
      $asResult['where'] =  implode(' OR ', $asWhereSql);
    else
      $asResult['where'] = implode(' AND ', $asWhereSql);

    return $asResult;
  }

  /**
   * Display the Detail information of connection
   * @param array $pasContactData
   * @return string HTML
   */

  private function _getContactDetailTab($pasContactData)
  {
    $oLogin = CDependency::getCpLogin();

    $oCustomFields = CDependency::getComponentByName('customfields');
    $oWEBMAIL = CDependency::getComponentByName('webmail');
    $oWebmail = CDependency::getComponentUidByName('webmail');

    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return $this->coHTML->getBlocMessage('No data available.');

    $sHTML =  $this->coHTML->getBlocStart('',array('class'=>'containerClass'));

      $sHTML.= $this->coHTML->getField('','Industry', $pasContactData['industry_name']);
      $sHTML.= $this->coHTML->getField('','Grade', getContactGrade($pasContactData['grade']));
      if(!empty($pasContactData['nationalityfk']))
        $sHTML.= $this->coHTML->getField('','Nationality', $this->getNationalityName((int)$pasContactData['nationalityfk']));

      switch($pasContactData['language'])
      {
        case 1: $sEnglish = 'Yes'; break;
        case 0: $sEnglish = 'No'; break;
        default: $sEnglish = '-'; break;
      }
      $sHTML.= $this->coHTML->getField('','English', $sEnglish);

      if($pasContactData['phone'])
        $sPhone = $pasContactData['phone'];
      else
        $sPhone = $pasContactData['prfPhone'];

      if(substr($sPhone, 0, 1) == ',')
        $sPhone = substr($sPhone, 1);

      $sHTML.= $this->coHTML->getField('', 'Phone', $sPhone);

      if($pasContactData['email'])
        $sEmail = $pasContactData['email'];
      else
        $sEmail = $pasContactData['prfEmail'];

      if(substr($sEmail, 0, 1) == ',')
        $sEmail = substr($sEmail, 1);


      $sContentMail = '';
      if(!empty($sEmail) && !empty($oWebmail))
      {
        $oSettings = CDependency::getComponentByName('settings');
        $sMailApp = $oSettings->getSettingValue('mail_client');
        $sMailApp = 'xzczxc';

        $asEmail = explode(',', $sEmail);
        foreach($asEmail as $sMail)
        {
          $sContentMail.= $this->coHTML->getSpanStart('', array('class' => 'webmailLinkContainer', 'email' => $sMail));

          switch($sMailApp)
          {
            case 'webmail':
              $sContentMail.= $this->coHTML->getLink($sMail, 'javascript:;', array('onclick' => 'window.open(\'mailto:'.$sMail.'\', \'page_mail\');'));

              $bBcMailLink = true;
              break;

            case 'bcm_mail':
              $sURL = $oWEBMAIL->getURL('webmail', CONST_ACTION_ADD, CONST_WEBMAIL,(int)$pasContactData['addressbook_contactpk'],array('ppaty'=>CONST_AB_TYPE_CONTACT,'ppaid'=>(int)$pasContactData['addressbook_contactpk']));
              $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body', '', '650', '800', 1);
              $sContentMail.= $this->coHTML->getLink($sMail, 'javascript:;', array('onclick' => $sAjax));

              $bBcMailLink = false;
              break;

            //case 'local_client':
            default:
              $sContentMail.= $this->coHTML->getLink($sMail, 'mailto:'.$sMail);

              $bBcMailLink = true;
              break;
          }

          if($bBcMailLink)
          {
            $sURL = $oWEBMAIL->getURL('webmail', CONST_ACTION_ADD, CONST_WEBMAIL,(int)$pasContactData['addressbook_contactpk'],array('ppaty'=>CONST_AB_TYPE_CONTACT,'ppaid'=>(int)$pasContactData['addressbook_contactpk']));
            $sAjax = $this->coHTML->getAjaxPopupJS($sURL, 'body', '', '650', '800', 1);
            $sPic = $this->coHTML->getPicture('/component/display/resources/pictures/webmail_24.png', 'BCM internal mail', '', array('class' => 'tooltip'));
            $sContentMail.= $this->coHTML->getLink($sPic, 'javascript:;', array('onclick' => $sAjax));
          }

          $sContentMail.= $this->coHTML->getSpanEnd();
        }
      }

      if(!empty($sContentMail))
        $sHTML.= $this->coHTML->getField('', 'Email', $sContentMail);

      if(!empty($pasContactData['fax']))
        $sHTML.= $this->coHTML->getField('', 'Fax', $pasContactData['fax']);

      if(!empty($pasContactData['cellphone']))
        $sHTML.= $this->coHTML->getField('', 'Mobile', $pasContactData['cellphone']);

      if(!empty($pasContactData['department_name']))
        $sHTML.= $this->coHTML->getField('', 'Department', $pasContactData['department_name']);


      $asUserData = $oLogin->getUserDataByPk((int)$pasContactData['updated_by']);
      $sUpdater = $oLogin->getUserNameFromData($asUserData);

      if ($pasContactData['date_update'] != '0000-00-00 00:00:00')
        $sHTML.= $this->coHTML->getField('','Last Edited', getFormatedDate('Y-m-d',$pasContactData['date_update']).' - by '.$sUpdater);

      if(!empty($pasContactData['birthdate']) && $pasContactData['birthdate']!='0000-00-00' )
      {
        $sToday = date('Y-m-d');
        $sAge = floor((strtotime($sToday)-strtotime($pasContactData['birthdate']))/(365*60*60*24));
       }
       else
        $sAge = '';

      $sHTML.= $this->coHTML->getField('','Approx Age', $sAge);

      $asUserData = $oLogin->getUserList((int)$pasContactData['created_by'],false,true);
      $sUser = $oLogin->getUserNameFromData(current($asUserData));

      $sHTML.= $this->coHTML->getField('','Creation date', getFormatedDate('Y-m-d',$pasContactData['date_create']).' - by '.$sUser);
      $sHTML.= $this->coHTML->getField('','Address', $this->_getAddress($pasContactData));

      $sHTML.= $oCustomFields->displayCustomFields($this->getCpValues());

    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

 /**
 * Display different profile of the connection
 * @param array $pasContactData
 * @param array $pasProfile
 * @return string HTML
 */
  private function _getContactProfileTab($pasContactData, $pasProfile)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return '';

    if(!assert('is_array($pasProfile)'))
      return '';

    $nContactPk = (int)$pasContactData['addressbook_contactpk'];
    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_ADD, CONST_AB_TYPE_COMPANY_RELATION, $nContactPk);
    $sHTML = $this->coHTML->getActionButton('Add a new business or personal profile', '', CONST_PICTURE_ADD,
            array('onclick' => 'goPopup.setLayerFromAjax(null, \''.$sURL.'\');'));
    $sHTML.= $this->coHTML->getCR(2);

    if(empty($pasProfile) || count($pasProfile) < 2)
      return $sHTML.$this->coHTML->getBlocMessage('No other profiles for this connection.' , true);

    //Sort it and remove the first profile because it is default

    /*krsort($pasProfile);
    unset($pasProfile[0]);*/

    $nCount = 0;
    foreach($pasProfile as $asProfileData)
    {
      $nCompanyPk = (int)$asProfileData['companyfk'];
      $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'abTabProfile_container shadow'));

      //actions float on the right
      $sHTML.= $this->coHTML->getBlocStart('', array('style'=>'float:right; margin:5px;'));

        $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_EDIT, CONST_AB_TYPE_COMPANY_RELATION, (int)$asProfileData['contactfk'],array('profileId'=>(int)$asProfileData['addressbook_profilepk']));
        $sHTML.= $this->coHTML->getPicture(CONST_PICTURE_EDIT, 'Edit the profile', 'javascript:;', array('onclick' => 'goPopup.setLayerFromAjax(null, \''.$sURL.'\');'));
        $sHTML.= $this->coHTML->getSpace(2);

        $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_DELETE, CONST_AB_TYPE_COMPANY_RELATION,(int)$asProfileData['contactfk'],array('profileId'=>(int)$asProfileData['addressbook_profilepk']));
        $sPic = $this->coHTML->getPicture(CONST_PICTURE_DELETE, 'Delete profile' );
        $sHTML.= ' '.$this->coHTML->getLink($sPic, 'javascript:;', array('onclick' => 'if(window.confirm(\'Delete this profile ?\')){ AjaxRequest(\''.$sURL.'\'); }'));

      $sHTML.= $this->coHTML->getBlocEnd('');

      //Top section business or personal data
      $sHTML.= $this->coHTML->getBlocStart();

      $sDate = $this->coHTML->getText(' - '.$asProfileData['date_update'], array('class' => 'abTabProfile_title_date'));

        if(!empty($nCompanyPk))
        {
          //business profile
          $sURL = $this->coPage->geturl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
          $asCompany = $this->_getModel()->getCompanyByPk($nCompanyPk);
          $sCompanyName = $asCompany['company_name'];

          if($nCount == 0)
            $sHTML.= $this->coHTML->getText('Original profile', array('class' => 'abTabProfile_title')).$sDate;
          else
            $sHTML.= $this->coHTML->getText('Business profile', array('class' => 'abTabProfile_title')).$sDate;

          $sHTML.= $this->coHTML->getCR(2);
          $sHTML.= $this->coHTML->getText('Company: ');
          $sHTML.= $this->coHTML->getLink($sCompanyName, $sURL);
          if(!empty($asCompany['corporate_name']))
          {
            $sHTML.= $this->coHTML->getText(' ('.$asCompany['corporate_name'].')');
          }

          if(!empty($asCompany['phone']))
          {
            $sHTML.= $this->coHTML->getCR();
            $sHTML.= $this->coHTML->getText('Phone: '.$asCompany['phone']);
          }

          if(!empty($asCompany['email']))
          {
            $sHTML.= $this->coHTML->getCR();
            $sHTML.= ' '.$this->coHTML->getText('Email: '.$asCompany['email']);
          }


          if(!empty($asProfileData['position']))
          {
            $sHTML.= $this->coHTML->getCR();
            $sHTML.= $this->coHTML->getText('working as : '.$asProfileData['position']);
          }

          $sHTML.= $this->coHTML->getCR(2);
        }
        else
        {
          //other personal contact details
          $sHTML.= $this->coHTML->getText('Personal contact details', array('class' => 'abTabProfile_title')).$sDate;
          $sHTML.= $this->coHTML->getCR(2);
        }

      $sHTML.= $this->coHTML->getBlocEnd('');



    $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'abTabProfile_details'));

      if(!empty($asProfileData['comment']))
      {
        $sHTML.= $this->coHTML->getText('Comment: '.$asProfileData['comment']);
        $sHTML.= $this->coHTML->getCR();
      }

      if($asProfileData['phone'])
      {
        $sHTML.= $this->coHTML->getText('Phone: ', array('class' => 'ab_view_strong')) . $asProfileData['phone'];
        $sHTML.= $this->coHTML->getCR();
      }

      if($asProfileData['fax'])
      {
        $sHTML.= $this->coHTML->getText('Fax: ', array('class' => 'ab_view_strong')) . $asProfileData['fax'];
        $sHTML.= $this->coHTML->getCR();
      }

      if($asProfileData['email'])
      {
        $sHTML.= $this->coHTML->getText('Email: ', array('class' => 'ab_view_strong')) . $asProfileData['email'];
        $sHTML.= $this->coHTML->getCR();
      }

      $sAddress = $this->_getAddress($asProfileData);
      if(!empty($sAddress))
      {
        $sHTML.= $this->coHTML->getText('Address: ', array('class' => 'ab_view_strong'));
        $sHTML.= $this->coHTML->getCR();
        $sHTML.= $this->coHTML->getBlocStart('', array('class' => 'abTabProfile_address'));
        $sHTML.= $sAddress;
        $sHTML.=  $this->coHTML->getBlocEnd();
      }

    $sHTML.=  $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();
    $nCount++;
  }

  $sHTML.= $this->coHTML->getFloatHack();
  return $sHTML;
}

/**
 * Delete the connection profile
 * @param integer $pnPk
 * @return string|boolean
 */

  private function _deleteProfile($pnPk)
  {
    $nProfilePk = (int)getValue('profileId');

    if(!assert('is_key($pnPk) && is_key($nProfilePk)'))
      return array('');

    $oDbResult = $this->_getModel()->update(array('date_end' => date('Y-m-d').' 00:00', 'addressbook_profilepk' => $nProfilePk), 'addressbook_profile');

    if(!$oDbResult)
      return array('error' => 'Oops, could not delete the profile. Please contact the administrator.');

    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnPk);
    return array('notice' => 'Profile has been removed.', 'timedUrl' => $sURL);
  }

  /**
   * Display Co workes tab
   * @param type $pasContactData
   * @param type $pasProfile
   * @return string
   */

  private function _getContactCoworkersTab($pasContactData, $pasProfile)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return '';

    if(!assert('is_array($pasProfile) '))
      return '';

    $nContactPk = (int)$pasContactData['addressbook_contactpk'];

    $sHTML = '';
    $sHTML.= $this->coHTML->getBlocStart('', array('style' => ''));

    $nProfile = count($pasProfile);

    if(!empty($pasProfile))
    {
    foreach($pasProfile as $asProfileData)
    {
      $nCompanyPk = (int)$asProfileData['addressbook_companypk'];
      $asCoworkers = $this->_getModel()->getCompanyEmployees(array($nCompanyPk), array($nContactPk));

      if(!empty($asCoworkers))
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $nCompanyPk);
        $sHTML.= $this->coHTML->getBlocStart();
        $sTitle = $this->coHTML->getPicture($this->getResourcePath().'/pictures/detail_16.png', '', 'javascript:;', array('class' => 'co_worker_container',  'style' => 'height:9px;', 'onclick' => '$(\'.coworker_list:not(#coworkers_list_'.$nCompanyPk.')\').hide(); $(\'#coworkers_list_'.$nCompanyPk.'\').fadeToggle(); '));
        $sTitle.= $this->coHTML->getSpace(2);
        $sTitle.= $this->coHTML->getLink($asProfileData['company_name'], $sURL);
        $sHTML.= $this->coHTML->getTitle($sTitle, 'h4', false);

        if($nProfile > 1)
          $sHTML.= $this->coHTML->getBlocStart('coworkers_list_'.$nCompanyPk, array('class' => 'coworker_list hidden'));
        else
          $sHTML.= $this->coHTML->getBlocStart('coworkers_list_'.$nCompanyPk);

       $sHTML.= $this->_getContactRowSmallHeader();

        $nRow = 0;
        foreach($asCoworkers as $asContactData)
        {
          $sHTML.= $this->_getContactRow($asContactData, $nRow,1);
          $nRow++;
        }

        $sHTML.= $this->coHTML->getBlocEnd();
        $sHTML.= $this->coHTML->getBlocEnd();
       }
       else
        $sHTML.= $this->coHTML->getBlocMessage( 'No co-workers obtained for this connection');
      }
    }
    else
      $sHTML.= $this->coHTML->getBlocMessage( 'This connection doesn\'t have company .');

    $sHTML.= $this->coHTML->getBlocEnd();
    return $sHTML;
  }

  private function _getContactRowAction($pasContactData)
  {
     if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
       return '';

    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(), CONST_ACTION_DELETE, $this->getType(), 0);
    $oEvent = CDependency::getComponentByName('event');

    $nContactPk = (int)$pasContactData['addressbook_contactpk'];

    $sHTML = '';
    if(!empty($oEvent))
    {
      $sURL = $this->coPage->getUrl('event', CONST_ACTION_ADD, CONST_EVENT_TYPE_EVENT, 0, array(CONST_URL_ACTION_RETURN => CONST_ACTION_LIST));
      $sURL = $this->coPage->addUrlParams($sURL, array(CONST_CP_UID => $this->_getUid(), CONST_CP_PK => $nContactPk));
      $sHTML.= $this->coHTML->getPicture($oEvent->getResourcePath().'pictures/add_event_16.png', 'Add activity', $sURL);
      $sHTML.= $this->coHTML->getSpace(2);
    }
    $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_EDIT, CONST_AB_TYPE_CONTACT, $nContactPk, array(CONST_URL_ACTION_RETURN => CONST_ACTION_LIST));
    $sHTML.= $this->coHTML->getPicture(CONST_PICTURE_EDIT, 'Edit connection', $sURL);
    $sHTML.= $this->coHTML->getSpace(2);

    if($sAccess)
    {
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_DELETE, CONST_AB_TYPE_CONTACT, $nContactPk);
      $sPic= $this->coHTML->getPicture(CONST_PICTURE_DELETE);
      $sHTML.= ' '.$this->coHTML->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\'You are about to permanently delete this connection with all its linked data. \\nDo you really want to proceed ?\')){ return false; }'));
    }
    return $sHTML;
  }

  /**
   * Function to link the connecton to company
   * @param integer $pnPk
   * @return string
   */

  private function _displayFormContactLink($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return '';

    $this->coPage->addCssFile(array($this->getResourcePath().'css/addressbook.css'));

    $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEMANAGE, CONST_AB_TYPE_CONTACT, $pnPk);

      $oForm = $this->coHTML->initForm('ctAddForm');
      $oForm->setFormParams('', true, array('submitLabel' => 'Save','action' => $sURL));

      $oForm->addField('misc', '', array('type' => 'title', 'titlw'=> 'Link the connection to existing Company'));

      $oForm->addField('input', 'contactfk', array('type'=> 'hidden', 'value' => $pnPk));

      $oForm->addField('input', 'email', array('label'=> 'Email Address', 'value' =>''));
      $oForm->setFieldControl('email', array('jsFieldTypeEmail' => '','jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => ''));
      $oForm->setFieldControl('phone', array('jsFieldNotEmpty' => '','jsFieldMinSize' => 4));

      $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => ''));
      $oForm->setFieldControl('fax', array('jsFieldMinSize' => 8));

      $oForm->addField('textarea', 'address_1', array('label'=> 'Address', 'value' =>''));
      $oForm->setFieldControl('address_1', array('jsFieldNotEmpty' => ''));

      $oForm->addField('input', 'postcode', array('label'=> 'Postcode', 'value' => ''));
      $oForm->setFieldControl('postcode', array('jsFieldMinSize' => 4, 'jsFieldMaxSize' => 12));

      $oForm->addField('selector_city', 'cityfk', array('label'=> 'City', 'url' => CONST_FORM_SELECTOR_URL_CITY));
      $oForm->setFieldControl('cityfk', array('jsFieldTypeIntegerPositive' => ''));

      $oForm->addField('selector_country', 'countryfk', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
      $oForm->setFieldControl('countryfk', array('jsFieldTypeIntegerPositive' => ''));

      $oForm->addField('misc', '', array('type'=> 'br'));

      $sURLSearch = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, CONST_AB_TYPE_COMPANY);
      $oForm->addField('selector', 'companyfk', array('label'=> 'Company', 'url' => $sURLSearch, 'nbresult' => 1));

      $sHTML= $oForm->getDisplay();

    return $sHTML;
  }

  private function _formContact($pnPk)
  {
    if(!assert('is_integer($pnPk)'))
      return '';

    $oLogin = CDependency::getCpLogin();
    $this->coPage->addCssFile(array($this->getResourcePath().'css/addressbook.css'));
    $this->coPage->addJsFile(array($this->getResourcePath().'js/formContact.js'));

    $bIsEdition = !empty($pnPk);

    $oDB = CDependency::getComponentByName('database');
    $oRight = CDependency::getComponentByName('right');
    $sAccess = $oRight->canAccess($this->_getUid(),CONST_ACTION_TRANSFER,$this->getType(), 0);
    $nProspectForm = (int)getValue('prospect', 0);

    $asSelectManager = array();
    //If editing the contact
    if($bIsEdition)
    {
      $sQuery = 'SELECT c.*,p.department as department,
        GROUP_CONCAT(DISTINCT p.companyfk SEPARATOR ",") as profiles,
        GROUP_CONCAT(DISTINCT p.position SEPARATOR ",") as positions,
        l.lastname as follower_lastname, l.firstname as follower_firstname
        FROM addressbook_contact as c
        LEFT JOIN addressbook_profile as p ON (p.contactfk = c.addressbook_contactpk and p.companyfk != 0 and p.date_end is NULL)
        LEFT JOIN shared_login as l ON (l.loginpk = c.followerfk)
        WHERE addressbook_contactpk = '.$pnPk.'
        GROUP BY addressbook_contactpk';

      $oDbResult = $oDB->ExecuteQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return __LINE__.' - The contact doesn\'t exist.';
    }
    else
    {
      $oDbResult = new CDbResult();
      $nConpanyFk = (int)getValue('cppk', 0);
      $oDbResult->setFieldValue('profiles', $nConpanyFk);

      if(!empty($nConpanyFk))
        $asCompanyDetail = $this->_getModel()->getCompanyByPk($nConpanyFk);
    }

    if($this->coPage->getActionReturn())
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEADD, CONST_AB_TYPE_CONTACT, $pnPk, array(CONST_URL_ACTION_RETURN => $this->coPage->getActionReturn(), 'prospect' => $nProspectForm));
    else
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SAVEADD, CONST_AB_TYPE_CONTACT, $pnPk, array('prospect' => $nProspectForm));

    $sHTML = $this->coHTML->getBlocStart();
    $sHTML.= $this->coHTML->getBlocStart();


    $oForm = $this->coHTML->initForm('ctAddForm');
    $oForm->setFormParams('', true, array('action' => $sURL, 'submitLabel' => 'Save', 'id' => 'formContact'));

    if(!$bIsEdition)
      $oForm->setFormDisplayParams(array('noSubmitButton' => true, 'noCancelButton' => true));

    $oForm->addField('misc', '', array('type' => 'text', 'text'=> '<div class="h2">Add a connection </div>'));
    $oForm->addField('misc', 'profile', array('type' => 'title', 'title'=> 'Profile'));
    if($nProspectForm)
    {
      $oForm->addField('misc', 'prospect_note', array('type' => 'text',
          'text'=> '<div class="prospectNote shadow"><span class="h4">Temporary propsect</span><br /><br />
            We allow you to create low quality entries in BCM to help you manage your sales & opportunities.<br /><br />
            Those prospects do not require as much data as standard connections, but are created temporarily. <br /><br />As such, you\'ll
            have to delete or complete the profile later, and will be reminded if you forget.<br /></div>'));
    }

    $oForm->addField('input', 'doubleChecked', array('type' => 'hidden', 'value' => (int)!empty($pnPk), 'id' => 'doubleCheckedId'));
    $oForm->addField('input', 'pnPk', array('type' => 'hidden', 'value' => $pnPk));
    $oForm->addField('input', 'type', array('type' => 'hidden', 'value' => 'ct'));

    $sCourtesy = $oDbResult->getFieldValue('courtesy');
    $oForm->addField('select', 'courtesy', array('label'=>'Courtesy', 'class' => '', 'value' => $oDbResult->getFieldValue('courtesy')));
    $oForm->setFieldControl('courtesy', array('jsFieldNotEmpty' => ''));

    if($sCourtesy == 'mr')
      $oForm->addOption('courtesy', array('label' => ' Mr ', 'value' =>'mr', 'selected' =>'selected'));
    else
      $oForm->addOption('courtesy', array('label' => ' Mr ', 'value' =>'mr'));

    if($sCourtesy == 'ms')
      $oForm->addOption('courtesy', array('label' => ' Ms ', 'value' =>'ms', 'selected' =>'selected'));
    else
      $oForm->addOption('courtesy', array('label' => ' Ms ', 'value' =>'ms'));

    if($nProspectForm)
    {
      $oForm->addField('input', 'lastname', array('label'=>'Name', 'class' => '', 'value' => $oDbResult->getFieldValue('lastname')));
      $oForm->setFieldControl('lastname', array('jsFieldMinSize' => '2', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));
    }
    else
    {
      $oForm->addField('input', 'firstname', array('label'=> 'Firstname', 'value' => $oDbResult->getFieldValue('firstname')));
      $oForm->setFieldControl('firstname', array('jsFieldMinSize' => '2',  'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'lastname', array('label'=>'Lastname', 'class' => '', 'value' => $oDbResult->getFieldValue('lastname')));
      $oForm->setFieldControl('lastname', array('jsFieldMinSize' => '2', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));
    }

    if($pnPk)
    {
      $sToday = date('Y-m-d');
      $sBirthYear= $oDbResult->getFieldValue('birthdate');
      if(isset($sBirthYear)&& $sBirthYear!='0000-00-00')
          $sAge = floor((strtotime($sToday)-strtotime($sBirthYear))/(365*60*60*24));
      else
          $sAge = '';
    }
    else
      $sAge = '';

    $oForm->addField('input', 'birthdate', array('label'=> 'Approx Age', 'value' => $sAge ));
    $oForm->setFieldControl('birthdate', array('jsFieldMaxSize' => 2));

    $oForm->addField('input', 'email', array('label'=> 'Email', 'value' => $oDbResult->getFieldValue('email')));
    $oForm->setFieldControl('email', array('jsFieldTypeEmail' => '', 'jsFieldMaxSize' => 255));

    $oForm->addField('input', 'phone', array('label'=> 'Phone', 'value' => $oDbResult->getFieldValue('phone')));
    $oForm->setFieldControl('phone', array('jsFieldMinSize' => 4));

    $oForm->addField('input', 'cellphone', array('label'=> 'Mobile phone', 'value' => $oDbResult->getFieldValue('cellphone')));
    $oForm->setFieldControl('cellphone', array('jsFieldMinSize' => 8));

    $oForm->addField('input', 'fax', array('label'=> 'Fax', 'value' => $oDbResult->getFieldValue('fax')));
    $oForm->setFieldControl('fax', array('jsFieldMinSize' => 8));


    $oForm->addField('select', 'nationalityfk', array('label' => 'Nationality'));
    $oForm->addOption('nationalityfk', array('value'=>'', 'label' => 'Select'));
    $oForm->addOption('nationalityfk', array('value'=>'', 'label' => ' - - '));

    $nDefaultNat = (int)$oDbResult->getFieldValue('nationalityfk');
    $asNationalities = $this->getNationality(true, false);

    foreach($asNationalities as $nPriority => $asNationality)
    {
      if($nPriority > 0)
        $sGroup = 'Frequently used';
      else
        $sGroup = 'Others';

      foreach($asNationality as $nkNationality => $sNationality)
      {
        if($nkNationality == $nDefaultNat)
          $oForm->addOption('nationalityfk', array('value'=>$nkNationality, 'label' => $sNationality, 'selected'=>'selected', 'group' => $sGroup));
        else
          $oForm->addOption('nationalityfk', array('value'=>$nkNationality, 'label' => $sNationality, 'group' => $sGroup));
      }
    }

    $oForm->addField('misc', '', array('type'=> 'br'));

    $oForm->addField('select', 'language', array('label' => 'English'));
    $aValues = array(
        array('value' => -1, 'label' => 'Unknown'),
        array('value' => 0, 'label' => 'No'),
        array('value' => 1, 'label' => 'Yes')
    );

    foreach ($aValues as $aOption)
    {
      if($aOption['value'] == $oDbResult->getFieldValue('language'))
        $aOption['selected']='selected';

      $oForm->addOption('language', $aOption);
    }

    $oForm->addField('misc', '', array('type'=> 'br'));
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Business Profile'));
    $asProfiles = explode(',', $oDbResult->getFieldValue('profiles'));

    if(count($asProfiles) <= 1)
    {
      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_ADD, CONST_AB_TYPE_COMPANY);

      $sPopup = 'var oConf = goPopup.getConfig(); ';
      $sPopup.= 'oConf.width = 1150; ';
      $sPopup.= 'oConf.height = 700; ';
      $sPopup.= 'oConf.modal = true; ';
      $sPopup.= 'goPopup.setLayerFromAjax(oConf, "'.$sURL.'"); ';
      $sLabel = 'Select company<br /><a href=\'javascript:;\' onclick=\''.$sPopup.'\' >add new</a>';

      $sURL = $this->coPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, CONST_AB_TYPE_COMPANY);
      $oForm->addField('selector', 'companyfk', array('label'=> $sLabel, 'url' => $sURL, 'nbresult' => 1));

      //TODO: manage different profiles
      if($oDbResult->getFieldValue('profiles'))
      {
        $asCompany = $this->_getModel()->getCompanyByPk($asProfiles);

        foreach($asProfiles as $nCompany)
          $oForm->addOption('companyfk', array('label' => $asCompany[$nCompany]['company_name'], 'value' =>$nCompany));

        if(count($asProfiles) > 1)
          $oForm->addField('misc', '', array('type' => 'text', 'text'=> '!! Multi profile, half implemented: Display will be weird if you link a contact to multiple companies.<br />'));
      }

      $oForm->addField('input', 'position', array('label'=> 'Position', 'value' => $oDbResult->getFieldValue('positions')));
      $oForm->addField('misc', '', array('type'=> 'br'));

      $oForm->addField('input', 'department', array('label'=> 'Department', 'value' => $oDbResult->getFieldValue('department')));
      $oForm->setFieldControl('department', array('jsFieldMaxSize' => 255));


      //Account manager section
      if($oDbResult->getFieldValue('addressbook_contactpk'))
        $asSelectManager = $this->_getModel()->getAccountManager((int)$oDbResult->getFieldValue('addressbook_contactpk'), 'addressbook_contact');
      else
        $asSelectManager[0] = $oLogin->getUserPk();

      if($sAccess || empty($pnPk))
      {
        //$oForm->addField('select', 'account_manager[]', array('label' => 'Account Manager', 'multiple' => 'multiple'));
        $sURL = $this->coPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1, 'friendly' => 1));
        $oForm->addField('selector', 'account_manager', array('label' => 'Account Manager', 'nbresult' => '5', 'url' => $sURL));
        $oForm->setFieldControl('account_manager', array('jsFieldNotEmpty' => ''));

        $asManagers = $oLogin->getUserList(0,false,true);
        foreach($asSelectManager as $nLoginPk)
        {
          $oForm->addOption('account_manager', array('value' => $nLoginPk,'label' => $asManagers[$nLoginPk]['firstname'].' '.$asManagers[$nLoginPk]['lastname']));
        }
      }
      else
      {
        $oForm->addField('input', 'account_manager', array('type' => 'hidden', 'value' => implode(',', $asSelectManager)));
      }

      if($nProspectForm)
      {
        $oForm->addField('input', 'prospec', array('label' => 'Contact Relation', 'value' => 'Temporary Prospect', 'readonly' => 'readonly', 'class' => 'prospectField'));
        $oForm->addField('input', 'relationfk', array('type' => 'hidden', 'value' => CONST_AB_PROSPECT_PK));
      }
      else
      {
        $oForm->addField('select', 'relationfk', array('label' => 'Contact Relation'));
        $oForm->setFieldControl('relationfk', array('jsFieldNotEmpty' => ''));

        $nRelation = $oDbResult->getFieldValue('relationfk');

        if(empty($nRelation) && !empty($asCompanyDetail))
          $nRelation = $asCompanyDetail['company_relation'];

        $asContactRelation= getCompanyRelation();
        $oForm->addOption('relationfk', array('value'=>'', 'label' => 'Select'));

        foreach($asContactRelation as $sRelationType => $vRelationType)
        {
          if($sRelationType == $nRelation)
            $oForm->addOption('relationfk', array('value' => $sRelationType, 'label' => $vRelationType['Label'],'selected'=>'selected'));
          else
            $oForm->addOption('relationfk', array('value' => $sRelationType, 'label' => $vRelationType['Label']));
        }

        $oForm->addField('misc', '', array('type'=> 'br'));
      }
    }
    else
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'There are multiple profiles for this contact,please go to profile tab to edit profiles.<br/><br/>'));



    $oForm->addField('select', 'grade', array('label' => 'Grade'));
    $oForm->setFieldControl('grade', array('jsFieldNotEmpty' => ''));
    $asGrade = getContactGrade();

    if($nProspectForm)
      $nGrade = 5;
    else
      $nGrade = $oDbResult->getFieldValue('grade');

    foreach($asGrade as $sGradeType =>$vGradeType)
    {
      if($sGradeType == $nGrade)
        $oForm->addOption('grade', array('value'=> $sGradeType, 'label' => $vGradeType, 'selected'=>'selected'));
      else
        $oForm->addOption('grade', array('value'=> $sGradeType, 'label' => $vGradeType));
    }


    $oForm->addField('misc', '', array('type'=> 'br'));
    $oForm->addField('textarea', 'comments', array('label'=> 'Character ', 'value' =>$oDbResult->getFieldValue('comments')));
    $oForm->setFieldControl('comments', array('jsFieldMinSize' => 5));
    $oForm->addField('misc', '', array('type' => 'title','title'=> 'Address'));

    $oForm->addField('textarea', 'address_1', array('label'=> 'Adress 1', 'value' => $oDbResult->getFieldValue('address_1')));
    $oForm->addField('textarea', 'address_2', array('label'=> 'Adress 2', 'value' => $oDbResult->getFieldValue('address_2')));
    $oForm->addField('input', 'postcode', array('label'=> 'Postcode', 'value' => $oDbResult->getFieldValue('postcode')));
    $oForm->setFieldControl('postcode', array('jsFieldJpPostCode' => 1));

    $oForm->addField('selector_city', 'cityfk', array('label'=> 'City', 'url' => CONST_FORM_SELECTOR_URL_CITY));
    $oForm->setFieldControl('cityfk', array('jsFieldTypeIntegerPositive' => ''));
    $nCityFk = $oDbResult->getFieldValue('cityfk', CONST_PHP_VARTYPE_INT);
    if(!empty($nCityFk))
      $oForm->addCitySelectorOption('cityfk', $nCityFk);

    $oForm->addField('selector_country', 'countryfk', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
    $oForm->setFieldControl('countryfk', array('jsFieldTypeIntegerPositive' => ''));
    $nCountryFk = $oDbResult->getFieldValue('countryfk', CONST_PHP_VARTYPE_INT);
    if(!empty($nCountryFk))
    {
      //$asCountry = $oForm->getCountryData($nCountryFk);
      $oForm->addCountrySelectorOption ('countryfk', $nCountryFk);
    }
    else
      $oForm->addCountrySelectorOption ('countryfk', 107);

    if(empty($pnPk))
    {
      $oForm->addField('input', 'redirect', array('type'=> 'hidden'));

      //classic save button
      $sButtons = $this->coHTML->getBlocStart('', array('style' =>'width: 395px; margin: 0 auto;'));
      $sButtons.= $this->coHTML->getActionButton('Save & view connection', 'javascript:;', '', array('style' => 'float: left; height: 16px; line-height: 16px;', 'onclick' => '$(this).closest(\'form\').find(\'input[name=redirect]\').val(\'\'); $(this).closest(\'form\').submit();'));

      $sButtons.= $this->coHTML->getText('&nbsp;or&nbsp;&nbsp;&nbsp;', array('style' => 'float: left; display: block;'));

      //independent buttons displayed base on an array of actions
      $sUid = CDependency::getComponentUidByName('event');
      if(!empty($sUid))
        $asButtons[] = array('url' => 'javascript:;', 'label' => 'Save & add activity',  'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=redirect]\').val(\''.$sUid.'\'); $(this).closest(\'form\').submit();'));

      $sUid = CDependency::getComponentUidByName('opportunity');
      if(!empty($sUid))
        $asButtons[] = array('url' => 'javascript:;', 'label' => 'Save & add an opportunity', 'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=redirect]\').val(\''.$sUid.'\'); $(this).closest(\'form\').submit();'));

      $sUid = CDependency::getComponentUidByName('webmail');
      if(!empty($sUid))
        $asButtons[] = array('url' => 'javascript:;', 'label' => 'Save & send an email', 'params' => array('onclick' => '$(this).closest(\'form\').find(\'input[name=redirect]\').val(\''.$sUid.'\'); $(this).closest(\'form\').submit();'));

      $sButtons.= $this->coHTML->getActionButtons($asButtons, 1, 'Save and ...', array('style' => 'float: left;'));

      $sButtons.= $this->coHTML->getBlocEnd();
      $oForm->addCustomButton($sButtons);
    }


    $oForm->addCustomFields($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnPk, 'folded');
    $sHTML.= $oForm->getDisplay();
    $sUrlCheck = $this->coPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCHDUPLICATES, CONST_AB_TYPE_CONTACT, $pnPk);
    $sHTML.= $this->coHTML->getBloc('duplicates', $this->coHTML->getTitle('Checking duplicates', 'h4').$this->coHTML->getPicture(CONST_PICTURE_SMALL_LOADING), array('url' => $sUrlCheck));

    $sHTML.= $this->coHTML->getBlocEnd();
    $sHTML.= $this->coHTML->getBlocEnd();

    return $sHTML;
  }

  /**
   * Get the Nationality
   * @return array of records
   */

  public function getNationality($pbPriority = false, $pbByArea = false)
  {
    if(!assert('is_bool($pbPriority) && is_bool($pbPriority)'))
      return array();

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT * FROM system_nationality WHERE 1 ORDER BY ';

    if($pbByArea)
      $sQuery.= ' area ASC, ';

    if($pbPriority)
      $sQuery.= ' priority DESC, ';

    $sQuery.= ' nationality_name ASC ';

    $oResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    $asResult = array();

    if($pbByArea)
    {
      while($bRead)
      {
        $asResult[$oResult->getFieldValue('area')][$oResult->getFieldValue('system_nationalitypk')] = $oResult->getFieldValue('nationality_name');
        $bRead = $oResult->readNext();
      }
      return $asResult;
    }

    if($pbPriority)
    {
      while($bRead)
      {
        $asResult[$oResult->getFieldValue('priority')][$oResult->getFieldValue('system_nationalitypk')] = $oResult->getFieldValue('nationality_name');
        $bRead = $oResult->readNext();
      }
      return $asResult;
    }

    while($bRead)
    {
      $asResult[$oResult->getFieldValue('system_nationalitypk')] = $oResult->getFieldValue('nationality_name');
      $bRead = $oResult->readNext();
    }
    return $asResult;
  }

  /**
   * Save the contact details
   * @param integer $pnContactPk
   * @return array
   */

  private function _saveContact($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk)'))
      return array('error' => 'No connection found.');

    if(empty($_POST['courtesy']) || empty($_POST['lastname']))
      return array('error' => __LINE__.' - Courtesy and Lastname are required.');

    if(!empty($_POST['email']) && !isValidEmail($_POST['email']))
      return array('error' => __LINE__.' - Email format is incorrect.');

    $oLogin = CDependency::getCpLogin();
    $nContactPk = $pnContactPk; // Needed to create url at the end of the function
    $sMessage = '';


    // Preparing an array of name / values to be inserted in database
    // cleaning useless fields and controling some values
    $aData = $_POST;
    $aData['created_by'] = $oLogin->getUserPk();
    $aData['prospect'] = (int)getValue('prospect', 0);
    unset($aData['doubleChecked']);
    unset($aData['bsmSelectbsmContainer0']);
    unset($aData['account_manager']);
    unset($aData['type']);

    $aDataProfil['position'] = $aData['position'];
    $aDataProfil['department'] = $aData['department'];
    $aDataProfil['companyfk'] = (int)$aData['companyfk'];
    unset($aData['position']);
    unset($aData['department']);
    unset($aData['companyfk']);

    if($aData['prospect'])
      $aData['firstname'] = '';


    $sRedirectUid = getValue('redirect');
    unset($aData['redirect']);

    $nBirthAge = (int)getValue('birthdate');
    if(!empty($nBirthAge))
    {
      $nYear = date('Y') - $nBirthAge;
      $aData['birthdate'] = date('Y-m-d', mktime(0, 0, 0, 1, 1, $nYear));
    }
    else
      $aData['birthdate'] = '0000-00-00';

    $nCityfk = (int)getValue('cityfk', 0);
    if(!empty($nCityfk))
    {
      $oForm = CDependency::getComponentByName('form');
      $asCityData = $oForm->getCityData($nCityfk);

      if(empty($asCityData))
        return array('error' => __LINE__.' - Couldn\'t find the city you\'ve selected.');
    }

    if(empty($aData['postcode']) && !empty($nCityfk))
     $aData['postcode'] = $asCityData['postcode'];

    if(empty($aData['countryfk']) && !empty($nCityfk))
     $aData['countryfk'] = (int)$asCityData['countryfk'];

    $sFollowers = getValue('account_manager');
    if(empty($sFollowers))
      return array('alert' => __LINE__.' - Account manager is required');

    $asFollowers = explode(',', $sFollowers);
    if(empty($asFollowers))
      return array('alert' => __LINE__.' - Account manager is required');


    $bDoubleEntryControl = (bool)getValue('doubleChecked', 0);

    //force inputing companies if not a temporary prospect
    if(empty($aData['prospect']) && ((!empty($aData['position']) || !empty($aData['departement'])) && empty($aData['companyfk'])))
       return array('alert' => 'Department ? position ? You must input a company.');

    $nUserFk = $oLogin->getUserPk();
    $bEdit = false;
    $asContactData = array();

    $sPopupHtml = '';

    if(empty($pnContactPk) && $bDoubleEntryControl == 0)
    {
      $sPopupHtml = $this->_getCheckDuplicates('ct',$aData['email'],$aData['firstname'],$aData['lastname'],$aData['address_1'],$aData['phone']);
      if(!empty($sPopupHtml))
      {
        $sJavascript = '
          var oConf = goPopup.getConfig();
          oConf.width = 500;
          oConf.height = 475;
          oConf.modal = true;
          sPopupId = goPopup.setLayerByConfig("", oConf, "'.addslashes($sPopupHtml).'");
          ';
        return array('action' => $sJavascript);
      }
    }


    if(!empty($pnContactPk))
    {
      // -----------------------------------------------------------------------------------
      // -----------------------------------------------------------------------------------
      // Edit a connection

      $bEdit = true;
      $aData['date_update'] = date('Y-m-d H:i:s');
      $aData['addressbook_contactpk'] = $pnContactPk;
      //keep loginfk unchanged, update the updated_by field
      $aData['updated_by'] = $oLogin->getUserPk();

      $oContact = $this->_getModel()->getContactData($pnContactPk);

      if(!$oContact)
        return array('error' => __LINE__.' - Couldn\'t find the contact you want to edit. It may have been deleted.');

      $asContactData = $oContact->getData();


      // -------------------------------------------
      //manage profile(s)

      $nProfile = $this->_getModel()->getByFk($pnContactPk, 'addressbook_profile', 'contact')->numRows();

      if($nProfile > 1)
      {
        $sMessage.= '<br /><span style="color: orange;">&loz; Edit profiles in the profile tab. </span>';
      }
      elseif($nProfile == 1)
      {
        if(isEmptyArray($aDataProfil))
        {
          $this->_getModel()->deleteByFk($pnContactPk, 'addressbook_profile', 'contact');
          $sMessage.= '<br />&loz; Company unlinked.</span>';
        }
        else
          $this->_getModel()->update($aDataProfil, 'addressbook_profile', '`contactfk` = '.$pnContactPk);
      }
      else
      {
        if(!isEmptyArray($aDataProfil))
        {
          $aDataProfil['contactfk'] = $pnContactPk;
          $this->_getModel()->add($aDataProfil, 'addressbook_profile');
        }
      }

      // -------------------------------------------
      //manageaccount managers / followers

      if(isset($asFollowers) && !empty($asFollowers))
      {
        $nFollowerFk = (int)$asFollowers[0];
        $aData['followerfk'] = $nFollowerFk;

        $this->_getModel()->deleteByFk($pnContactPk, 'addressbook_account_manager', 'contact');

        array_shift($asFollowers);
        foreach($asFollowers as $sManagerPk)
        {
          $oResult = $this->_getModel()->add(array('contactfk' => $pnContactPk, 'loginfk' => (int)$sManagerPk), 'addressbook_account_manager');
        }
      }

      // -------------------------------------------
      //Save personal data

      //If the user changes the synopsis, we log it in the activities
      $asSetting = CDependency::getComponentByName('settings')->getSystemSettings('ab_log_comments');
      if(!empty($asSetting['ab_log_comments']) && !empty($asContactData['comments']) && $asContactData['comments'] != $aData['comments'])
      {
        $oEvent = CDependency::getComponentByName('event');
        if($oEvent)
        {
          $sContent = 'This connection synopsis has been changed. Previous entry was: <br /><br />';
          $sContent.= $asContactData['comments'];

          $oEvent->quickAddEvent('new-synopsis', 'Synopsis changed', $sContent, $this->csUid, CONST_AB_TYPE_CONTACT, CONST_ACTION_VIEW, $pnContactPk, true);
        }
      }

      $bUpdate = $this->_getModel()->updateContact($aData);
      if(!$bUpdate)
        return array('error' => __LINE__.' - Sorry, can\'t update connection data. ['.$this->_getModel()->getErrors(true).']');

      $sLink = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnContactPk);
      $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, CONST_ACTION_SAVEEDIT, CONST_AB_TYPE_CONTACT, $pnContactPk, 'Update connection', $aData['firstname'].' '.$aData['lastname'], $sLink);
    }
    else
    {
      // -----------------------------------------------------------------------------------
      // -----------------------------------------------------------------------------------
      // Add a new connection

      $aData['date_create'] = date('Y-m-d H:i:s');
      $aData['followerfk'] = (int)$asFollowers[0];
      $aData['loginfk'] = $oLogin->getUserPk();

      if(empty($aData['followerfk']))
        $aData['followerfk'] = $nUserFk;

      $nContactPk = (int)$this->_getModel()->add($aData, 'addressbook_contact');

      if($nContactPk == 0)
        return array('error' => __LINE__.' - Couldn\'t save the contact:'. $this->_getModel()->getErrors(true));

      $nFirstProfileCp = 0;
      if(!empty($aDataProfil['position']) || !empty($aDataProfil['department']) || !empty($aDataProfil['companyfk']))
      {
        $aDataProfil['contactfk'] = $nContactPk;
        $nFirstProfileCp = (int)$aDataProfil['companyfk'];
        $nProfilePk = $this->_getModel()->add($aDataProfil, 'addressbook_profile');
        if(empty($nProfilePk))
          return array('error' => __LINE__.' - Couldn\'t save the connection profile');
      }


      array_shift($asFollowers);
      foreach($asFollowers as $sManagerFk)
        $oResult = $this->_getModel()->add(array('contactfk' => $nContactPk, 'loginfk' => (int)$sManagerFk), 'addressbook_account_manager');

      $asCompanyFk = explode(',', $aDataProfil['companyfk']);
      $asSql = array();
      if(!empty($asCompanyFk))
      {
        if(isset($asContactData['profiles']))
          $asProfiles = explode(',', $asContactData['profiles']);
        else
          $asProfiles = array();

        foreach($asCompanyFk as $nCompanyfk)
        {
          //check we aren't recreating the a profile we've just created above and rights
          if( !empty($nCompanyfk) && ($nFirstProfileCp != $nCompanyfk)
              && ((($bEdit) && (!in_array($nCompanyfk, $asProfiles))) || (!$bEdit)))
          {
            $asSql['contactfk'][] = $nContactPk;
            $asSql['companyfk'][] = (int)$nCompanyfk;
            $asSql['position'][] = $aDataProfil['position'];
            $asSql['department'][] = $aDataProfil['department'];
          }
        }

        if(!empty($asSql))
        {
          $oResult = $this->_getModel()->add($asSql, 'addressbook_profile');
          if(!$oResult)
            return array('error' => __LINE__.' - Couldn\'t save the connection profile. ['.  var_export($asSql, true).']');
        }

        $sLink = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk);
        $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, CONST_ACTION_SAVEADD, CONST_AB_TYPE_CONTACT, $nContactPk, 'Added a connection', $aData['firstname'].' '.$aData['lastname'], $sLink);
      }
    }


    $oCustomField = CDependency::getComponentByName('customfields');
    if($oCustomField)
    {
      $oCustomField->saveCustomFields($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk);
    }

    //user asked for a specific redirection in the form
    if(!empty($sRedirectUid))
    {
      $sUid = CDependency::getComponentUidByName('event');
      if($sUid == $sRedirectUid)
      {
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk, array('relact' => 'event'));
        return array('notice' => 'Connection saved.'.$sMessage, 'timedUrl' => $sURL);
      }

      $sUid = CDependency::getComponentUidByName('webmail');
      if($sUid == $sRedirectUid)
      {
         $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk, array('relact' => 'email'));
        return array('notice' => 'Connection saved.'.$sMessage, 'timedUrl' => $sURL);
      }

      $sUid = CDependency::getComponentUidByName('opportunity');
      if($sUid == $sRedirectUid)
      {
        $sReturnURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk, array('relact' => 'none'));
        $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk, array('relact' => 'opportunity', CONST_URL_ACTION_RETURN => $sReturnURL));
        return array('notice' => 'Connection saved.'.$sMessage, 'timedUrl' => $sURL);
      }
    }

    if($this->coPage->getActionReturn())
      $sURL = $this->coPage->getUrl('addressbook', $this->coPage->getActionReturn(), CONST_AB_TYPE_CONTACT, $nContactPk);
    else
      $sURL = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $nContactPk);

    return array('notice' => 'Connection saved.'.$sMessage, 'timedUrl' => $sURL);
  }



  /**
   * Remove the contact from the system
   * @param integer $pnPk
   * @return array of message
   */

  private function _deleteContact($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk) && !empty($pnContactPk)'))
      return array('error' => 'No connection found. It may have already been deleted.');

    $oDbResult = $this->_getModel()->getByPk($pnContactPk, 'addressbook_contact');
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => __LINE__.' - No connection to delete.');

    $oDelete = $this->_getModel()->deleteByPk($pnContactPk, 'addressbook_contact');
    if(!$oDelete)
      return array('error' => __LINE__.' - Could\'t delete the contact');

    $oDeleteProfile = $this->_getModel()->deleteByFk($pnContactPk, 'addressbook_profile', 'contact');
    if(!$oDeleteProfile)
      return array('error' => __LINE__.' - Could\'t delete the profile');

    CDependency::notifyListeners($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $pnContactPk, CONST_ACTION_DELETE);

    return array('notice' => 'Connection has been deleted.', 'timedUrl' => $this->coPage->getUrl('addressbook', CONST_ACTION_LIST, CONST_AB_TYPE_CONTACT));
  }

/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* *********************** Global / generic functions ***************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */
/* ******************************************************************************** */


  // Deprecated : use _checkCompanyDuplicates() or _checkContactDuplicates()
  private function _getCheckDuplicates($psType, $psEmail, $psFirstName, $psLastName, $psAddress, $psPhone, $psFax = '')
  {
    $oDB = CDependency::getComponentByName('database');
    $sPopupHtml = '';

    if($psType == CONST_AB_TYPE_COMPANY && !empty($psFirstName))
    {
      $asQuery = array();
      $asName = explode(' ', $psFirstName);
      $nCount = 0;

      //check exact name
      $sSelect = ' IF(company_name LIKE '.$oDB->dbEscapeString($psFirstName).', 50, ';
      $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$psFirstName).', 10,  ';
      $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$psFirstName.'%').', 1, 0))) as r'.++$nCount;
      $asQuery['select'][] = $sSelect;
      $asQuery['where'][] = ' company_name LIKE '.$oDB->dbEscapeString(strtolower($psFirstName)).'  ';
      $nMaxPts = 50;

      //check company name againt corporate names
      $asQuery['select'][] = 'IF(corporate_name LIKE '.$oDB->dbEscapeString($psFirstName).', 5, 0) as r'.++$nCount;
      $asQuery['where'][] = ' corporate_name LIKE '.$oDB->dbEscapeString($psFirstName).' ';
      $nMaxPts+= 5;

      //check parts of the company name
      if(count($asName) > 1)
      {
        foreach($asName as $sFragment)
        {
          if(strlen(trim($sFragment)) > 1)
          {
            $sSelect = ' IF(company_name LIKE '.$oDB->dbEscapeString($sFragment).', 20, ';
            $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment).', 10,  ';
            $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').', 1, 0))) as r'.++$nCount;

            $asQuery['select'][] = $sSelect;
            $asQuery['where'][] = ' company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').'  ';
            $nMaxPts+= 5;

            //check fragments against corporate name
            $asQuery['select'][] = 'IF(corporate_name LIKE '.$oDB->dbEscapeString($sFragment).', 3, 0) as r'.++$nCount;
            $asQuery['where'][] = ' corporate_name LIKE '.$oDB->dbEscapeString($sFragment).' ';
            $nMaxPts+= 3;
          }
        }
      }

      //check matching corporate names
      if(!empty($psLastName))
      {
        $asQuery['select'][] = 'IF(corporate_name LIKE '.$oDB->dbEscapeString($psLastName).', 3, 0) as r'.++$nCount;
        $asQuery['where'][] = ' corporate_name LIKE '.$oDB->dbEscapeString($psLastName).' ';
        $nMaxPts+= 3;
      }

      if(!empty($psEmail))
      {
        //check full email address
        $asQuery['select'][] = 'IF(email = '.$oDB->dbEscapeString($psEmail).', 6, 0) as r'.++$nCount;
        $asQuery['where'][] = 'email = '.$oDB->dbEscapeString($psEmail);
        $nMaxPts+= 6;

        //check domain name only
        $asEmail = explode('@', $psEmail);
        if(isset($asEmail[1]))
        {
          $asQuery['select'][] = 'IF(email LIKE '.$oDB->dbEscapeString('%'.$asEmail[1]).', 3, 0) as r'.++$nCount;
          $asQuery['where'][] = 'email LIKE '.$oDB->dbEscapeString('%'.$asEmail[1]);
          $nMaxPts+= 3;
        }
      }

      if(!empty($psPhone))
      {
        $psPhone = trim($psPhone);
        $psPhone = preg_replace('/[^0-9]/', '', $psPhone);

        //not great, but don't want to install UDF for that
        $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psPhone).', 5, 0) as r'.++$nCount;
        $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psPhone);
        $nMaxPts+= 5;
      }

      if(!empty($psFax))
      {
        $psFax = trim($psFax);
        $psFax = preg_replace('/[^0-9]/', '', $psFax);

        //not great, but don't want to install UDF for that
        $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psFax).', 5, 0) as r'.++$nCount;
        $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psFax);
        $nMaxPts+= 5;
      }

      $sQuery = 'SELECT addressbook_company.*, '.implode(' , ', $asQuery['select']);
      $sQuery.= ' FROM addressbook_company WHERE '.implode(' OR ', $asQuery['where']);

      $asRatio = array();
      for($nKey = 1; $nKey <= $nCount; $nKey++)
        $asRatio[] = 'r'.$nKey;

      $sMainQuery = 'SELECT ('.implode(' + ', $asRatio);
      $sMainQuery.= ') as nTotal, T.* FROM ('.$sQuery.') as T HAVING nTotal >= 5 ORDER BY nTotal DESC, company_name, date_create LIMIT 20 ';

      $oDbResult = $oDB->ExecuteQuery($sMainQuery);
      $bRead = $oDbResult->readFirst();
      $asMatch = array();
      $asField = array_keys($asQuery);

      $nHighestMatch=0;
      while($bRead)
      {
        $nMatching = $oDbResult->getFieldValue('nTotal');
        $sName = $oDbResult->getFieldValue('company_name');

        if($oDbResult->getFieldValue('corporate_name'))
          $sName.= ' ('.$oDbResult->getFieldValue('corporate_name').')';

        //return in javascript, " make it crash and no breaking line in the string, \n crashes too
        $sName = str_replace('"', ' ', $sName);
        $nMatchingPer=round(((int)$oDbResult->getFieldValue('nTotal')/$nMaxPts)*100);
        if($nMatchingPer>$nHighestMatch)
          $nHighestMatch=$nMatchingPer;
        $sMatch = '<div class=\'duplicateEntries\'><div><span class=\'duplicateName h4\'>Name : '.$sName.'</span>';
        $sMatch.= '<span class=\'duplicateRatio\'>matching at '.$nMatchingPer.'%</span></div></div>';

        $asMatch[] = $sMatch;
        $bRead = $oDbResult->readNext();
      }

      if($nHighestMatch > 75)
      {
        if(count($asMatch) >= 20)
          $asMatch[] = '... and there\'s more results ...';

        $sPopupHtml = '<div class=\'doubleEntryContainer\'><strong>Multiple entries are matching with this company.</strong><br /> ';
        $sPopupHtml.= "<div style ='margin-top:5px;'><span class='diplicateTitle h4'>".$psFirstName." </span></div>";
        $sPopupHtml.= "<div style='margin-top:5px;margin-bottom:10px;'> Matches with following records :</div>";
        $sPopupHtml.= implode('',$asMatch);
        $sPopupHtml.= '<br /></div><br />';
        //$sPopupHtml.= $sMainQuery.'<br />';
        $sPopupHtml.= '<div class="duplicateConfirm">Are you sure to create this new company ?</div>';
        $sPopupHtml.= '<strong><a href=\'javascript:;\' onclick=\' $("form[name=cpAddForm] #doubleCheckedId").val(1); $("form[name=cpAddForm] input[type=submit]").click();\'>Yes </a></strong>';
        $sPopupHtml.= $this->coHTML->getSpace(4);
        $sPopupHtml.= '<strong><a href=\'javascript:;\' onclick=\'goPopup.removeActive();\'> No </a></strong>';
      }

      return $sPopupHtml;
    }




    if($psType == CONST_AB_TYPE_CONTACT && !empty($psLastName))
    {
      $asQuery = array();
      $asName = explode(' ', $psLastName);
      $nCount = 0;

      $sSelect = ' IF(lastname LIKE '.$oDB->dbEscapeString($psLastName).', 40, ';
      $sSelect.= ' IF(lastname LIKE '.$oDB->dbEscapeString('%'.$psLastName).', 10,  ';
      $sSelect.= ' IF(lastname LIKE '.$oDB->dbEscapeString('%'.$psLastName.'%').', 1, 0))) as r'.++$nCount;
      $asQuery['select'][]= $sSelect;
      $asQuery['where'][] = ' lastname LIKE '.$oDB->dbEscapeString('%'.$psLastName.'%');
      $nMaxPts = 40;

      //check parts of the company name
      if(count($asName) > 1)
      {
        foreach($asName as $sFragment)
        {
          if(strlen(trim($sFragment)) > 1)
          {
            $sSelect = ' IF(lastname LIKE '.$oDB->dbEscapeString($sFragment).', 15, ';
            $sSelect.= ' IF(lastname LIKE '.$oDB->dbEscapeString('%'.$sFragment).', 7,  ';
            $sSelect.= ' IF(lastname LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').', 1, 0))) as r'.++$nCount;

            $asQuery['select'][] = $sSelect;
            $asQuery['where'][] = ' lastname LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').'  ';
            $nMaxPts+= 5;
          }
        }
      }

      if(!empty($psFirstName) && strlen($psFirstName) > 1)
      {
        $asQuery['select'][] = ' IF(firstname LIKE '.$oDB->dbEscapeString($psFirstName).', 2, 0) as r'.++$nCount;
        $asQuery['where'][] = ' firstname LIKE '.$oDB->dbEscapeString($psFirstName);
        $nMaxPts+= 2;
      }

      if(!empty($psEmail))
      {
        //check full email address
        $asQuery['select'][] = 'IF(email = '.$oDB->dbEscapeString($psEmail).', 6, 0) as r'.++$nCount;
        $asQuery['where'][] = 'email = '.$oDB->dbEscapeString($psEmail);
        $nMaxPts+= 6;

        //check domain name only
        $asEmail = explode('@', $psEmail);
        if(isset($asEmail[1]))
        {
          $asQuery['select'][] = 'IF(email LIKE '.$oDB->dbEscapeString('%'.$asEmail[1]).', 3, 0) as r'.++$nCount;
          $asQuery['where'][] = 'email LIKE '.$oDB->dbEscapeString('%'.$asEmail[1]);
          $nMaxPts+= 3;
        }
      }

      if(!empty($psPhone))
      {
        $psPhone = trim($psPhone);
        $psPhone = preg_replace('/[^0-9]/', '', $psPhone);

        //not great, but don't want to install UDF for that
        $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psPhone).', 5, 0) as r'.++$nCount;
        $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psPhone);
        $nMaxPts+= 5;
      }

      if(!empty($psFax))
      {
        $psFax = trim($psFax);
        $psFax = preg_replace('/[^0-9]/', '', $psFax);

        //not great, but don't want to install UDF for that
        $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psFax).', 5, 0) as r'.++$nCount;
        $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($psFax);
        $nMaxPts+= 5;
      }

      $sQuery = 'SELECT addressbook_contact.*, '.implode(' , ', $asQuery['select']);
      $sQuery.= ' FROM addressbook_contact WHERE '.implode(' OR ', $asQuery['where']);

      $asRatio = array();
      for($nKey = 1; $nKey <= $nCount; $nKey++)
        $asRatio[] = 'r'.$nKey;

      $sMainQuery = 'SELECT ('.implode(' + ', $asRatio).') as nTotal, T.* FROM ('.$sQuery.') as T HAVING nTotal > 4 ORDER BY nTotal DESC, lastname, firstname LIMIT 20 ';

      $oDbResult = $oDB->ExecuteQuery($sMainQuery);
      $bRead = $oDbResult->readFirst();
      $asMatch = array();
      $asField = array_keys($asQuery);

      $nHighestMatch = 0;
      while($bRead)
      {
        $nMatching = $oDbResult->getFieldValue('nTotal');
        $sName = $oDbResult->getFieldValue('courtesy').' '.$oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');

        $nCompanyPk = $this->_getModel()->getContactCompany($oDbResult->getFieldValue('addressbook_contactpk', CONST_PHP_VARTYPE_INT));
        if(!empty($nCompanyPk))
          $asCompanyDetail = $this->_getModel()->getCompanyByPk($nCompanyPk);

        //return in javascript, " make it crash and no breaking line in the string, \n crashes too
        $sName = str_replace('"', ' ', $sName);
        $nMatchingPer=round(((int)$oDbResult->getFieldValue('nTotal')/$nMaxPts)*100);
        if($nMatchingPer>$nHighestMatch)
          $nHighestMatch=$nMatchingPer;
        $sMatch = '<div class=\'duplicateEntries\'><div><span class=\'duplicateName h4\'>Name : '.$sName.'</span>'; if(!empty($asCompanyDetail))
        $sMatch.= '<br/><span> Company: '.$asCompanyDetail['company_name'].'</span>';
        $sMatch.= '<span class=\'duplicateRatio\'>matching at '.$nMatchingPer.'%</span></div></div>';
        $asMatch[] = $sMatch;

        $bRead = $oDbResult->readNext();
      }

      if($nHighestMatch > 75)
      {
        if(count($asMatch) >= 20)
          $asMatch[] = '... and there\'s more results ...';

        $sPopupHtml = '<div class=\'doubleEntryContainer\'><strong>Multiple entries are matching with this connection.</strong><br /> ';
        $sPopupHtml.= "<div style ='margin-top:5px;'><span class='diplicateTitle h4'>".$psLastName.' '.$psFirstName." </span></div>";
        $sPopupHtml.= "<div style='margin-top:5px;margin-bottom:10px;'> Matches with following records :</div>";
        $sPopupHtml.= implode('',$asMatch);
        $sPopupHtml.= '<br /></div><br />';
        //$sPopupHtml.= $sMainQuery.'<br />';
        $sPopupHtml.= '<div class="duplicateConfirm">Are you sure to create this new connection ?</div>';
        $sPopupHtml.= '<strong><a href=\'javascript:;\' onclick=\' $("form[name=ctAddForm] #doubleCheckedId").val(1); $("form[name=ctAddForm] input[type=submit]").click();\'>Yes </a></strong>';
        $sPopupHtml.= $this->coHTML->getSpace(4);
        $sPopupHtml.= '<strong><a href=\'javascript:;\' onclick=\'goPopup.removeActive();\'> No </a></strong>';
      }

      return $sPopupHtml;
    }
 }

  private function _checkCompanyDuplicates($psCompanyName, $psCorporateName = '', $psAddress = '', $psPhone = '', $psFax = '')
  {
    if(!assert('is_string($psCompanyName) && !empty($psCompanyName)'))
      return array('error' => 'Company Name missing');

    $oDB = CDependency::getComponentByName('database');
    $sPopupHtml = '';

    $asQuery = array();
    $nCount = $nMaxPts = 0;

    $aWeights = array();
    $aWeights['fragment'] =   array( 'full' => 20, 'starts-by' => 10, 'includes' => 1);
    $aWeights['fragment_corporate'] =   array( 'full' => 3);
    $aWeights['address_1'] =      array( 'full' => 20, 'one-mistake' => 10, 'value' => trim($psAddress));
    $aWeights['phone'] =      array( 'full' => 10, 'value' => trim($psPhone));
    $aWeights['fax'] =        array( 'full' => 10, 'value' => trim($psFax));

    // Name is not treated the same, we will compare two fields with the value entered corporate_name and name
    $aWeightsb = array();
    $aWeightsb['name'] =  array( 'full' => 60, 'one-mistake' => 45, 'value' => trim($psCompanyName));
    $sCorporateName = trim($psCorporateName);

    $nMinPerc = 75; // Matches with a lower percentage wont be shown

    // Looking for the company name
    $sSelect = ' IF((company_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value']);
    $sSelect.= ' OR corporate_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value']);
    if(!empty($sCorporateName))
    {
      $sSelect.= ' OR company_name LIKE '.$oDB->dbEscapeString($sCorporateName);
      $sSelect.= ' OR corporate_name LIKE '.$oDB->dbEscapeString($sCorporateName);
    }
    $sSelect.='), '.$aWeightsb['name']['full'].', ';

    $aSelect = array();
    for ($i=0; $i<strlen($aWeightsb['name']['value']); $i++)
    {
      $sMatch = $aWeightsb['name']['value'];
      $sMatch[$i]='_';
      $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString($sMatch);
      $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString($sMatch);
    }
    $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value'].'_');
    $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value'].'_');
    $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString('_'.$aWeightsb['name']['value']);
    $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString('_'.$aWeightsb['name']['value']);

    if(!empty($sCorporateName))
    {
      for ($i=0; $i<strlen($sCorporateName); $i++)
      {
        $sMatch = $sCorporateName;
        $sMatch[$i]='_';
        $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString($sMatch);
        $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString($sMatch);
      }
      $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString($sCorporateName.'_');
      $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString($sCorporateName.'_');
      $aSelect[] = 'company_name LIKE '.$oDB->dbEscapeString('_'.$sCorporateName);
      $aSelect[] = 'corporate_name LIKE '.$oDB->dbEscapeString('_'.$sCorporateName);
    }

    $sSelectCondition = implode(' OR ', $aSelect);
    $sSelect.= ' IF('.$sSelectCondition.', '.$aWeightsb['name']['one-mistake'].', 0)) as r'.++$nCount;
    $asQuery['select'][]= $sSelect;
    $asQuery['where'][] = ' company_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value']).' OR
                            corporate_name LIKE '.$oDB->dbEscapeString($aWeightsb['name']['value']).' OR
                              '.$sSelectCondition;
    $nMaxPts += $aWeightsb['name']['full'];

    $asName = explode(' ', $psCompanyName);
    if(count($asName) > 1)
    {
      $nCountb = 0;
      foreach($asName as $sFragment)
      {
        if(strlen(trim($sFragment)) > 1)
        {
          $nCountb++;
          $sSelect = ' IF(company_name LIKE '.$oDB->dbEscapeString($sFragment).', '.$aWeights['fragment']['full'].', ';
          $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment).', '.$aWeights['fragment']['starts-by'].',  ';
          $sSelect.= ' IF(company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').', '.$aWeights['fragment']['includes'].', 0))) as r'.++$nCount;

          $asQuery['select'][] = $sSelect;
          $asQuery['where'][] = ' company_name LIKE '.$oDB->dbEscapeString('%'.$sFragment.'%').'  ';

          //check fragments against corporate name
          $asQuery['select'][] = 'IF(corporate_name LIKE '.$oDB->dbEscapeString($sFragment).', '.$aWeights['fragment_corporate']['full'].', 0) as r'.++$nCount;
          $asQuery['where'][] = ' corporate_name LIKE '.$oDB->dbEscapeString($sFragment).' ';
        }
      }
      if($nCountb)
      {
        $nMaxPts+= $aWeights['fragment_corporate']['full'];
        $nMaxPts+= $aWeights['fragment']['full'];
      }
    }

    $this->_checkDuplicatesQueryBuilder($aWeights, $asQuery, $nMaxPts, $nCount);

    $sQuery = 'SELECT addressbook_company.*, '.implode(' , ', $asQuery['select']);
    $sQuery.= ' FROM addressbook_company WHERE ('.implode(' OR ', $asQuery['where']).')';
    $sQuery.= ' AND addressbook_companypk!='.$this->cnPk;

    $asRatio = array();
    for($nKey = 1; $nKey <= $nCount; $nKey++)
      $asRatio[] = 'r'.$nKey;

    $sMainQuery = 'SELECT ('.implode(' + ', $asRatio).')/'.$nMaxPts.'*100 as nTotal,
                    T.* FROM ('.$sQuery.') as T HAVING nTotal >= '.$nMinPerc.' ORDER BY nTotal DESC, company_name, date_create LIMIT 5 ';

    $oDbResult = $oDB->ExecuteQuery($sMainQuery);
    $bRead = $oDbResult->readFirst();
    $asMatch = array();

    while($bRead)
    {
      $sName = $oDbResult->getFieldValue('company_name');

      $asMatch[] = array('name' => $sName, 'matching' => (int)$oDbResult->getFieldValue('nTotal'), 'pk' => (int)$oDbResult->getFieldValue('addressbook_companypk'), 'corporate_name' => $oDbResult->getFieldValue('corporate_name'));

      $bRead = $oDbResult->readNext();
    }

    if(!empty($asMatch))
    {
      $sPopupHtml.= $this->coHTML->getTitle($this->coHTML->getPicture(CONST_PICTURE_IMPORTANT, 'Important', '', array('width' => '24px')).' Possible Duplicates were found', 'h4');
      $sPopupHtml.= 'Please make sure the company you want to add is not one of the following:';
      $sPopupHtml.= $this->coHTML->getBlocStart('matches');
      foreach ($asMatch as $aMatch)
      {
        $sUrl = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, $aMatch['pk']);
        $sPopupHtml.= $this->coHTML->getBlocStart('', array('class' => 'duplicateEntries', 'onClick' => 'window.open(\''.$sUrl.'\');'));
        $sPopupHtml.= $aMatch['name'];
        if(isset($aMatch['corporate_name']) && !empty($aMatch['corporate_name']))
          $sPopupHtml.= $this->coHTML->getCR(1).$aMatch['corporate_name'];
        $sPopupHtml.= $this->coHTML->getText($aMatch['matching'].'%', array('class' => 'duplicateRatio'));
        $sPopupHtml.= $this->coHTML->getBlocEnd();
      }
      $sPopupHtml.= $this->coHTML->getBlocEnd();
      $sPopupHtml.= $this->coHTML->getLink('Add Company Anyway', 'javascript:;', array('class' => 'button-like', 'onClick' => '$(\'.submitBtnClass\').show(); $(\'#duplicates\').hide();'));
      $sPopupHtml.= '<script>$(\'.submitBtnClass\').hide();</script>';
    }

    if(empty($sPopupHtml))
      $sPopupHtml = $this->coHTML->getPicture(CONST_PICTURE_CHECK_OK).' No Duplicate found.<script>$(\'.submitBtnClass\').show();</script>';

    return array('data' => $sPopupHtml);
 }

  private function _checkContactDuplicates($psFirstName, $psLastName, $psEmail='', $psPhone = '', $psFax = '')
  {
    if(!assert('is_string($psFirstName) && !empty($psFirstName)'))
      return array('error' => 'First Name missing');

    if(!assert('is_string($psLastName) && !empty($psLastName)'))
      return array('error' => 'Last Name missing');

    $oDB = CDependency::getComponentByName('database');
    $sPopupHtml = '';

    $aWeights = array();
    $aWeights['lastname'] =   array( 'full' => 60, 'one-mistake' => 45, 'value' => trim($psLastName));
    $aWeights['firstname'] =  array( 'full' => 50, 'one-mistake' => 35, 'value' => trim($psFirstName));
    $aWeights['email'] =      array( 'full' => 20, 'one-mistake' => 10, 'value' => trim($psEmail));
    $aWeights['phone'] =      array( 'full' => 10, 'value' => trim($psPhone));
    $aWeights['fax'] =        array( 'full' => 10, 'value' => trim($psFax));
    $nMinPerc = 75; // Matches with a lower percentage wont be shown

    $asQuery = array();
    $nMaxPts = $nCount = 0;

    $this->_checkDuplicatesQueryBuilder($aWeights, $asQuery, $nMaxPts, $nCount);

      $sQuery = 'SELECT addressbook_contact.*, '.implode(' , ', $asQuery['select']);
      $sQuery.= ' FROM addressbook_contact WHERE ('.implode(' OR ', $asQuery['where']).')';
      $sQuery.= ' AND addressbook_contactpk!='.$this->cnPk;

      $asRatio = array();
      for($nKey = 1; $nKey <= $nCount; $nKey++)
        $asRatio[] = 'r'.$nKey;

      $sMainQuery = 'SELECT ('.implode(' + ', $asRatio).')/'.$nMaxPts.'*100 as nTotal, T.*
                      FROM ('.$sQuery.') as T HAVING nTotal > '.$nMinPerc.'
                        ORDER BY nTotal DESC, lastname, firstname LIMIT 10';

      $oDbResult = $oDB->ExecuteQuery($sMainQuery);
      $bRead = $oDbResult->readFirst();
      $asMatch = array();

      while($bRead)
      {
        $sName = $oDbResult->getFieldValue('courtesy').' '.$oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');

        $nCompanyPk = $this->_getModel()->getContactCompany($oDbResult->getFieldValue('addressbook_contactpk', CONST_PHP_VARTYPE_INT));
        if(!empty($nCompanyPk))
          $asCompanyDetail = $this->_getModel()->getCompanyByPk($nCompanyPk);

        $aRow = array('name' => $sName, 'matching' => (int)$oDbResult->getFieldValue('nTotal'), 'pk' => (int)$oDbResult->getFieldValue('addressbook_contactpk'));
        if(!empty($asCompanyDetail))
          $aRow['company'] = $asCompanyDetail['company_name'];

        $asMatch[] = $aRow;

        $bRead = $oDbResult->readNext();
      }

      if(!empty($asMatch))
      {
        $sPopupHtml.= $this->coHTML->getTitle($this->coHTML->getPicture(CONST_PICTURE_IMPORTANT, 'Important', '', array('width' => '24px')).' Possible Duplicates were found', 'h4');
        $sPopupHtml.= 'Please make sure the contact you want to add is not one of the following:';
        $sPopupHtml.= $this->coHTML->getBlocStart('matches');
        foreach ($asMatch as $aMatch)
        {
          $sUrl = $this->coPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, $aMatch['pk']);
          $sPopupHtml.= $this->coHTML->getBlocStart('', array('class' => 'duplicateEntries', 'onClick' => 'window.open(\''.$sUrl.'\');'));
          $sPopupHtml.= $aMatch['name'];
          if(isset($aMatch['company']))
            $sPopupHtml.= $this->coHTML->getCR(1).$aMatch['company'];
          $sPopupHtml.= $this->coHTML->getText($aMatch['matching'].'%', array('class' => 'duplicateRatio'));
          $sPopupHtml.= $this->coHTML->getBlocEnd();
        }
        $sPopupHtml.= $this->coHTML->getBlocEnd();
        $sPopupHtml.= $this->coHTML->getLink('Add Contact Anyway', 'javascript:;', array('class' => 'button-like', 'onClick' => '$(\'.submitBtnClass\').show(); $(\'#duplicates\').hide();'));
        $sPopupHtml.= '<script>$(\'.submitBtnClass\').hide();</script>';
      }

      if(empty($sPopupHtml))
        $sPopupHtml = $this->coHTML->getPicture(CONST_PICTURE_CHECK_OK).' No Duplicate found.<script>$(\'.submitBtnClass\').show();</script>';

      return array('data' => $sPopupHtml);
 }

 private function _checkDuplicatesQueryBuilder($paWeights, &$asQuery, &$nMaxPts, &$nCount)
  {
    $oDB =  CDependency::getComponentByName('database');

    foreach ($paWeights as $sFieldName => $aFieldValues)
    {
      if (isset($aFieldValues['one-mistake']) && !empty($aFieldValues['value']))
      {
        $sSelect = ' IF('.$sFieldName.' LIKE '.$oDB->dbEscapeString($aFieldValues['value']).', '.$paWeights[$sFieldName]['full'].', ';
        $aSelect = array();
        for ($i=0; $i<strlen($aFieldValues['value']); $i++)
        {
          $sMatch = $aFieldValues['value'];
          $sMatch[$i]='_';
          $aSelect[] = $sFieldName.' LIKE '.$oDB->dbEscapeString($sMatch);
        }
        $aSelect[] = $sFieldName.' LIKE '.$oDB->dbEscapeString($aFieldValues['value'].'_');
        $aSelect[] = $sFieldName.' LIKE '.$oDB->dbEscapeString('_'.$aFieldValues['value']);

        $sSelectCondition = implode(' OR ', $aSelect);
        $sSelect.= ' IF('.$sSelectCondition.', '.$paWeights[$sFieldName]['one-mistake'].', 0)) as r'.++$nCount;
        $asQuery['select'][]= $sSelect;
        $asQuery['where'][] = ' '.$sFieldName.' LIKE '.$oDB->dbEscapeString($aFieldValues['value']).' OR '.$sSelectCondition;
        $nMaxPts += $paWeights[$sFieldName]['full'];
      }
    }

    if(!empty($paWeights['phone']['value']))
    {
      $sPhone = preg_replace('/[^0-9]/', '', $paWeights['phone']['value']);

      $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($sPhone).', '.$paWeights['phone']['full'].', 0) as r'.++$nCount;
      $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($sPhone);
      $nMaxPts += $paWeights['phone']['full'];
    }

    if(!empty($paWeights['fax']['value']))
    {
      $sFax = preg_replace('/[^0-9]/', '', $paWeights['fax']['value']);

      $asQuery['select'][] = 'IF((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($sFax).', '.$paWeights['fax']['full'].', 0) as r'.++$nCount;
      $asQuery['where'][] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""),")", ""),"-", "")," ", ""),"+", "")) LIKE '.$oDB->dbEscapeString($sFax);
      $nMaxPts += $paWeights['fax']['full'];
    }
  }

 public function getContactByPk($pnPk)
  {
     if(!assert('is_key($pnPk)'))
       return '';

     $oResult = $this->_getModel()->getByPk($pnPk, 'addressbook_contact');

     return $oResult->getData();
  }

  /**
   * Get the Name of company or conenction
   * @param string $psItemType
   * @param integer $pnPk
   * @return string
   */

  public function getItemName($psItemType, $pnPk = 0, $pasData = array())
  {
    if(!assert('!empty($psItemType) && is_integer($pnPk) && is_array($pasData)'))
      return '';

    if(empty($pnPk) && empty($pasData))
    {
      assert('false; // Need pk or data to fetch the item name');
      return '';
    }

    if($psItemType == CONST_AB_TYPE_COMPANY)
    {
      if(empty($pasData))
      {
        $oResult = $this->_getModel()->getByPk($pnPk, 'addressbook_company');
        $sItemName = $oResult->getFieldValue('company_name');
      }
      else
        $sItemName = $pasData['company_name'];
    }
    else
    {
      if(empty($pasData))
      {
        $oResult = $this->_getModel()->getByPk($pnPk, 'addressbook_contact');
        $sItemName = $oResult->getFieldValue('firstname').' '.$oResult->getFieldValue('lastname');
      }
      else
        $sItemName = $pasData['firstname'].' '.$pasData['lastname'];
    }

    return $sItemName;
  }


  /**
   * Function to return the address
   * @param array $pasData
   * @param string $psSeparator
   * @return string
  */
  private function _getAddress($pasData, $psSeparator = 'br')
  {
    if(!assert('is_array($pasData)') || empty($pasData))
      return '';

    switch($psSeparator)
    {
      case 'space':
      case ' ': $psSeparator = $this->coHTML->getSpace(2); break;
      case 'br': $psSeparator = $this->coHTML->getCR(); break;
      case ',':
      default:  $psSeparator = $this->coHTML->getText('<span style="color: red;">,</span> '); break;
    }
    $asAddressPart = array();

    if(isset($pasData['address_1']) && !empty($pasData['address_1']))
    {
      $asAddressPart[] = $this->coHTML->getText($pasData['address_1']);
    }
    else if(isset($pasData['prfAddress']) && !empty($pasData['prfAddress']))
    {
      $asAddressPart[] = $this->coHTML->getText($pasData['prfAddress']);
    }

    if(isset($pasData['address_2']) && !empty($pasData['address_2']))
    {
      $asAddressPart[] = $this->coHTML->getText($pasData['address_2']);
    }

    $sCity = '';
    if(isset($pasData['EngLocal']) && !empty($pasData['EngLocal']))
    {
     $sCity.= $this->coHTML->getText($pasData['EngLocal'].' '.$pasData['EngCity'].' ');
    }

    if(isset($pasData['ctpostcode']) && !empty($pasData['ctpostcode']))
    {
      $sCity.= $this->coHTML->getText($pasData['ctpostcode'].' ');
    }

    if(isset($pasData['country_name']) && !empty($pasData['country_name']))
    {
      $sCity.= $this->coHTML->getText($pasData['country_name']);
    }

    if(!empty($sCity))
      $asAddressPart[] = $sCity;

    return implode($psSeparator, $asAddressPart);
  }

  /**
   * Function to give autocomplete with connection data
   * @return string
   */

  private function _getSelectorContact()
  {
    $sSearch = getValue('q');
    if(empty($sSearch))
      return json_encode(array());

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM addressbook_contact WHERE lastname LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR firstname LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' ORDER BY lastname, firstname ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return json_encode(array());

    $asJsonData = array();
    while($bRead)
    {
      $asData['id'] = $oDbResult->getFieldValue('addressbook_contactpk');
      $asData['name'] = '#'.$asData['id'].' - '.$oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');
      $asJsonData[] = json_encode($asData);
      $bRead = $oDbResult->readNext();
    }

    echo '['.implode(',', $asJsonData).']';
  }

  /**
   * Function to display company/connection details from pk
   * @param type $psItemType
   * @param type $pnPk
   * @return array
   */

  public function getItemCardByPk($psItemType, $pnPk)
  {
     if(!assert('is_key($pnPk)'))
       return array();

     $oDB = CDependency::getComponentByName('database');
     $sHTML = '';

     if($psItemType == CONST_AB_TYPE_COMPANY)
     {
        $oResult = $this->_getModel()->getByPk($pnPk, 'addressbook_company');
        $bRead = $oResult->readFirst();
        if(!$bRead)
          return array();

        $asData = $oResult->getData();
        $asData['item_label'] = $asData['company_name'];

        $sHTML = $this->coHTML->getBlocStart('',array('style'=>'border:1px solid #CECECE;margin:5px;padding:5px;'));
          $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Company Name :'.$asData['company_name']));

          if(!empty($asData['phone']))
            $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Phone :'.$asData['phone']));

          if(!empty($asData['cellphone']))
            $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Mobile :'.$asData['cellphone']));

          if(!empty($asData['email']))
            $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Email :'.$asData['email']));

          if(!empty($asData['website']))
            $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Website :'.$asData['website']));

          if(!empty($asData['address_1']) || !empty($asData['address_2']))
            $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Address :').$this->_getAddress($asData, ','));

        $sHTML.= $this->coHTML->getBlocEnd();
        $asData['html'] = $sHTML;

        return $asData;
     }

     if($psItemType == CONST_AB_TYPE_CONTACT)
     {
       $sQuery = 'SELECT ct.phone as phonenum, prf.companyfk as companyfk, ct.*,
         acom.company_name, acom.corporate_name, acom.email as company_email, acom.website as company_website
         FROM addressbook_contact as ct
         LEFT JOIN addressbook_profile as prf ON (prf.contactfk = ct.addressbook_contactpk)
         LEFT JOIN addressbook_company as acom ON (acom.addressbook_companypk = prf.companyfk)
         WHERE  ct.addressbook_contactpk= '.$pnPk;

       $oResult = $oDB->ExecuteQuery($sQuery);
       $bRead = $oResult->readFirst();
       if(!$bRead)
         return array();

       $asData = $oResult->getData();

       $asData['name'] = ucfirst($asData['courtesy']).' '.$asData['firstname'].' '.$asData['lastname'];
       $asData['item_label'] = $asData['name'];

       $sHTML = $this->coHTML->getBlocStart('',array('style'=>'border:1px solid #CECECE;margin:5px;padding:5px;'));
        $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText(' Name :'.$this->getItemName('ct', $pnPk)));

        if(isset($asData['companyfk']) && !empty($asData['companyfk']))
         $sHTML.= $this->coHTML->getBloc('', $this->coHTML->getText('Company Name:'.$asData['company_name']));

        if(isset($asData['phonenum']) && !empty($asData['phonenum']))
         $sHTML.= $this->coHTML->getBloc('',$this->coHTML->getText('Phone :'.$asData['phonenum']));

        $sHTML.= $this->coHTML->getBlocStart('');
        $sHTML.= $this->coHTML->getText('Address :');
        $sHTML.= $this->_getAddress($asData,',');
        $sHTML.= $this->coHTML->getBlocEnd();
       $sHTML.= $this->coHTML->getBlocEnd();
     }

     $asData['html'] = $sHTML;
    return  $asData;
  }

  /**
   * Get the Nationality name
   * @param integer $pnPK
   * @return string
   */

  public function getNationalityName($pnPk)
  {
    if(!assert('is_key($pnPk)'))
     return '';

    $oDbresult = $this->_getModel()->getByPk($pnPk, 'system_nationality');

    return $oDbresult->getFieldValue('nationality_name');

  }

  public function setLanguage($psLanguage)
  {
    require_once('language/language.inc.php5');
    if(isset($gasLang[$psLanguage]))
      $this->casText = $gasLang[$psLanguage];
    else
      $this->casText = $gasLang[CONST_DEFAULT_LANGUAGE];
  }

  public function getContactName($pasContactData)
  {
    if(!assert('is_array($pasContactData)'))
      return '';

    $sName = '';

    if(isset($pasContactData['courtesy']))
      $sName.= ucfirst($pasContactData['courtesy']).' ';

    if(isset($pasContactData['firstname']))
      $sName.= ucfirst($pasContactData['firstname']).' ';

    if(isset($pasContactData['lastname']))
      $sName.= ucfirst($pasContactData['lastname']).' ';

    return $sName;
  }

  public function getContactNameFromData($pasContactData, $pbWithIcon = false)
  {
    if(!assert('is_array($pasContactData) && !empty($pasContactData)'))
      return '';

    if(!isset($pasContactData['courtesy']) || !isset($pasContactData['lastname']) || !isset($pasContactData['firstname']))
    {
      assert('false; // contact data missing');
      return '';
    }

    if($pbWithIcon)
      $sName = $this->_getDisplayIcon($pasContactData).' ';
    else
      $sName = '';

    return $sName.ucfirst($pasContactData['courtesy']).' '.$pasContactData['lastname'].' '.$pasContactData['firstname'];
  }

  public function getEmployeeList($pnCompanyPk = 0, $pnContactPk = 0, $pbExtended = false)
  {
    return $this->_getModel()->getEmployeeList($pnCompanyPk, $pnContactPk, $pbExtended);
  }

  private function _sendProspectReminders()
  {
    $sDay = date('D');
    $bForce = (bool)getValue('force_cron', 0);
    echo '<br />Prospect cron:<br />';

    if(!$bForce && $sDay != 'Mon' && $sDay != 'Thu')
    {
      echo 'wrong day for prospects';
      return true;
    }

    $oSetting = CDependency::getComponentByName('settings');
    if(!$oSetting)
    {
      assert('false; // can not fetch settings for prospect cron');
      return false;
    }

    //chack last time it's been launched
    $sLastUpdate = $oSetting->getSettingValue('cron_prospect_date');
    if(!$bForce && !empty($sLastUpdate) && $sLastUpdate > date('Y-m-d', strtotime('-2 days')))
    {
      echo 'already launched on '.$sLastUpdate;
      return true;
    }

    $bSaved = $oSetting->setSystemSettings('cron_prospect_date', date('Y-m-d'));
    if(!$bSaved)
    {
      assert('false; // can not save prospect date... avoinding spamming here T_T');
      return false;
    }

    //Fetch prospect created more than 2 months ago
    $sWhere = ' date_create <= "'.date('Y-m-d', strtotime('-2 months')).'" ';

    $oDbResult = $this->_getModel()->getProspect(true, false, $sWhere);
    $bRead = $oDbResult->readFirst();
    if($oDbResult->numRows() < 1)
    {
      echo 'No prospect reminders to send<br />';
      return true;
    }

    $asProspect = array();
    while($bRead)
    {
      //1 follower in contact table, n others in accountmanager table

      $nFollowerfk = (int)$oDbResult->getFieldValue('followerfk');
      if(!empty($nFollowerfk))
        $asProspect[$nFollowerfk][(int)$oDbResult->getFieldValue('addressbook_contactpk')] = $this->_getProspectMessage($oDbResult->getData());

      $nManagerfk = $oDbResult->getFieldValue('managerfk');
      if(!empty($nManagerfk) && $nManagerfk != $nFollowerfk)
        $asProspect[$nManagerfk][(int)$oDbResult->getFieldValue('addressbook_contactpk')] =  $this->_getProspectMessage($oDbResult->getData());

      $bRead = $oDbResult->readNext();
    }

    if(empty($asProspect))
    {
      echo 'No prospect reminders to send<br />';
      return true;
    }

    $oLogin = CDependency::getCpLogin();
    $oMail = CDependency::getComponentByName('mail');
    $asUser = $oLogin->getUserList(0, true, true);


    foreach($asProspect as $nLoginfk => $asData)
    {
      if(isset($asUser[$nLoginfk]))
      {
        $sUserName = $oLogin->getUserNameFromData($asUser[$nLoginfk], true);
        $sContent = 'Dear '.$sUserName.', <br /> <br />';
        $sContent.= count($asData).' prospect(s) you\'ve created need to be updated to maintain BCM\'s data quality. Please update or delete the following.<br /> <br />';
        $sContent.= ' - '.implode('<br /> - ', $asData);
        $sContent.='<br /><br />Thank you.';

        $oMail->createNewEmail();
        echo 'recipient: '.$asUser[$nLoginfk]['email'], $asUser[$nLoginfk]['firstname'].'  ||  '.$asUser[$nLoginfk]['lastname'].'<br />';

        $oResult = $oMail->sendRawEmail(CONST_PHPMAILER_EMAIL, 'sboudoux@bulbouscell.com', 'BCM - Your proscpects are waiting...', 'For: '.$asUser[$nLoginfk]['email'].'<hr /><br/><br/>'.$sContent);
        $oResult = $oMail->sendRawEmail(CONST_PHPMAILER_EMAIL, $asUser[$nLoginfk]['email'], 'BCM - Your proscpects are waiting...', $sContent);
      }
    }

    return true;
  }

  /**
   * Generate the mail text based on 1 prospect data
   * @return string html
   */
  private function _getProspectMessage($pasProspectData)
  {
    if(!assert('is_array($pasProspectData)') || empty($pasProspectData))
      return '';

    $sUrl = $this->coPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, (int)$pasProspectData['addressbook_contactpk']);
    $sName = $pasProspectData['lastname'];

    if(!empty($pasProspectData['firstname']))
      $sName.= ' '.$pasProspectData['firstname'];

    $sHtml = $this->coHTML->getLink($sName, $sUrl).', ';
    $sHtml.= 'created the : '.date('Y-m-d', strtotime($pasProspectData['date_create'])).' need to be updated or deleted. <br/>';

    return $sHtml;
  }


  public function getSharedSQL($psShortname)
  {
    $asArgs = func_get_args();
    $asSql = array('select' => '', 'join' => '', 'where' => '', 'order' => '', 'group' => '', 'limit' => '');

    switch($psShortname)
    {
      case 'opp_list':

        $asSql['select'] = 'cp.company_name, cp.addressbook_companypk, ct.firstname, ct.lastname, ct.addressbook_contactpk';
        $asSql['join'] = ' LEFT JOIN `addressbook_company` cp ON (opl.cp_type = "cp" AND opl.cp_pk = cp.addressbook_companypk)
                           LEFT JOIN `addressbook_contact` ct ON (opl.cp_type = "ct" AND opl.cp_pk = ct.addressbook_contactpk) ';
        break;

      case 'event_profile':

        $nCompanyPk = (int)$asArgs[1];
        $asSql['select'] = ', prf.*';
        $asSql['where'] = 'prf.contactfk';
        $asSql['join'] = ' LEFT JOIN addressbook_profile as prf on (prf.companyfk= '.$nCompanyPk.' ) ';
        break;

      default:
        assert('false; // no sql available for this query name');
    }

    return $asSql;
  }


  public function getAccountManager($pnItemPk, $psTable)
  {
    return $this->_getModel()->getAccountManager($pnItemPk, $psTable);
  }
}
