<?php

class CAddressBookModelEx extends CAddressBookModel
{
  public function __construct()
  {
    parent::__construct();
    return true;
  }


  // ================================================================
  // Redifining methods
  // ================================================================

  protected function _testFields($avFields, $psTablename, $pbAllFieldRequired = true, $pbAllowExtra = true, $psAction = 'add')
  {
    $this->casError = array();

    if($psTablename == 'addressbook_contact')
    {
      if(!isset($avFields['prospect']))
        $bProspect = false;
      else
        $bProspect = (bool)$avFields['prospect'];


      if($psAction != 'add' && (!isset($avFields['addressbook_contactpk']) || !is_integer($avFields['addressbook_contactpk'])))
      {
        $this->casError[] = __LINE__.' - Missing data to save connection';
        return false;
      }

      if($psAction != 'add' && empty($avFields['addressbook_contactpk']))
      {
        $this->casError[] = __LINE__.' - Missing connection id';
        return false;
      }

      if(!isset($avFields['courtesy']) || empty($avFields['courtesy']))
      {
        $this->casError[] = __LINE__.' - Missing courtesy ';
        return false;
      }

      if(!isset($avFields['lastname']) || strlen($avFields['lastname']) < 2)
      {
        $this->casError[] = __LINE__.' - Lastname invalid ';
        return false;
      }

      if(!$bProspect && (!isset($avFields['firstname']) || strlen($avFields['firstname']) < 2))
      {
        $this->casError[] = __LINE__.' - Firstname invalid ';
        return false;
      }

      if(!empty($avFields['email']) && !isValidEmail($avFields['email'], false))
      {
        $this->casError[] = __LINE__.' - Email invalid ';
        return false;
      }

      if($psAction != 'update' && (!isset($avFields['loginfk']) || !is_key($avFields['loginfk'])))
      {
        $this->casError[] = __LINE__.' - User id invalid | '.$psAction;
        return false;
      }

      if($psAction == 'add' && (!isset($avFields['followerfk']) || !is_integer($avFields['followerfk']) || $avFields['followerfk'] < 0))
      {
        $this->casError[] = __LINE__.' - Account manager invalid ';
        return false;
      }

      return true;
    }
    else
      return parent::_testFields($avFields, $psTablename, $pbAllFieldRequired, $pbAllowExtra);
  }




  public function updateContact($pasValues)
  {
    return (bool)$this->update($pasValues, 'addressbook_contact');
  }

  public function updateCompany($pasValues)
  {
    return (bool)$this->update($pasValues, 'addressbook_company');
  }

   /**
   * Search for contacts
   * @param string $psFilter
   * @return array
   */

  public function getSearchContactList($psFilter)
  {
    if(!assert('is_string($psFilter) && !empty($psFilter)'))
     return array();

    $sQuery = 'SELECT count(*) as nCount FROM addressbook_contact as ct '.$psFilter.' ';
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->ReadFirst();
    if(!$bRead)
      return array('nb' => 0, 'data' => '');

    $nResult = $oDbResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);
    if($nResult == 0)
      return array('nb' => 0, 'data' => '');

    return array('nb' => $nResult, 'data' => $this->_getContactList($psFilter));
  }

  /**
   * Search for company
   * @param string $psFilter
   * @return array
   */

  public function getSearchCompanyList($psFilter)
  {
    if(!assert('is_string($psFilter) && !empty($psFilter)'))
      return array();

    $sQuery = 'SELECT count(*) as nCount FROM addressbook_company as cp '.$psFilter.' ';
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->ReadFirst();
    if(!$bRead)
      return array('nb' => 0, 'data' => '');

    $nResult = $oDbResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);
    if($nResult == 0)
      return array('nb' => 0, 'data' => '');

    return array('nb' => $nResult, 'data' => $this->_getCompanyList($psFilter));
  }


  /**
   *Return a array with all the companies related to each other: holiding and childs
   * @param integer $pnComapnyPk
   * @return array
   */

  public function getRelatedCompanies($pnComapnyPk)
  {
    if(!assert('is_integer($pnComapnyPk)') || empty($pnComapnyPk))
      return array();

    $sQuery = 'SELECT cp.addressbook_companypk as cp1, cp.parentfk as cp2, cp_parent.addressbook_companypk as cp3, cp_parent.parentfk as cp4, cp_child.addressbook_companypk as cp5, cp_child.parentfk as cp6  FROM addressbook_company as cp ';
    $sQuery.= ' LEFT JOIN addressbook_company as cp_parent ON (cp_parent.addressbook_companypk = cp.parentfk) ';
    $sQuery.= ' LEFT JOIN addressbook_company as cp_child ON (cp_child.parentfk = cp.addressbook_companypk) ';
    $sQuery.= ' WHERE cp.addressbook_companypk = "'.$pnComapnyPk.'" OR cp.parentfk = "'.$pnComapnyPk.'" ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

    if(!$bRead)
      return array();

    $asCompany = array();
    while($bRead)
    {
      $asData = $oResult->getData();
      foreach($asData as $sCompanyPk)
      {
        if(!empty($sCompanyPk))
          $asCompany[(int)$sCompanyPk] = (int)$sCompanyPk;
      }

      $bRead = $oResult->readNext();
    }

    return $asCompany;
  }


  /**
   * Display the company employees
   * @param array $panCompanyPk
   * @param array $panExcludedContact
   * @return array of records
   */

  public function getCompanyEmployees($panCompanyPk = array(), $panExcludedContact = array())
  {
    if(!assert('is_array($panCompanyPk) && is_array($panExcludedContact)'))
      return array();

    $sSelect = $sJoin = '';

    $sEventUid = CDependency::getComponentUidByName('event');
    if(!empty($sEventUid))
    {
      $sSelect = ', event.*';
      $sJoin = 'LEFT JOIN shared_event as event ON (c.addressbook_contactpk = event.cp_pk and event.cp_type ="ct") ';
    }

    $sQuery = 'SELECT c.*, GROUP_CONCAT(DISTINCT CONCAT(lg.firstname) SEPARATOR ",") as follower_firstname, ';
    $sQuery.= ' GROUP_CONCAT(DISTINCT CONCAT(lg.lastname) SEPARATOR ",") as follower_lastname, ';
    $sQuery.= ' GROUP_CONCAT(DISTINCT ind.industry_name) as industry_name,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT prf.position) AS position,GROUP_CONCAT(DISTINCT prf.department) AS department,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT prf.email) AS profileEmail, GROUP_CONCAT(DISTINCT prf.phone) AS profilePhone,';
    $sQuery.= ' GROUP_CONCAT(DISTINCT prf.fax) AS profileFax, cp.company_name ';
    $sQuery.= ' '.$sSelect.'  FROM addressbook_contact as c ';
    $sQuery.= ' LEFT JOIN shared_login AS lg ON (lg.loginpk = c.followerfk)';
    $sQuery.= ' INNER JOIN addressbook_profile as prf ON (prf.contactfk = c.addressbook_contactpk and prf.date_end IS NULL ';

    if(!empty($panExcludedContact))
       $sQuery.= ' AND c.addressbook_contactpk NOT IN ('.implode(',', $panExcludedContact).') ';

    if(empty($panCompanyPk))
      $sQuery.= ' ) ';
    else
      $sQuery.= ' AND prf.companyfk IN ('.implode(',', $panCompanyPk).') ) ';

    $sQuery.= ' INNER JOIN addressbook_company as cp ON (cp.addressbook_companypk = prf.companyfk) ';
    $sQuery.= ' LEFT JOIN addressbook_company_industry AS cmpid ON (cp.addressbook_companypk = cmpid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON (cmpid.industryfk = ind.addressbook_industrypk)';
    $sQuery.= $sJoin;
    $sQuery.= ' GROUP BY c.addressbook_contactpk ORDER BY cp.company_name, c.lastname, c.firstname';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    $asResult = array();
    while($bRead)
    {
      $asResult[] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }


  /**
   *Return an array with all the employee of a company
   * fetched by companypk or by an an employee pk. If extended is true, return employees of holding/child companies
   * @param integer $pnCompanyPk
   * @param integer $pnContactPk
   * @param boolean $pbExtended
   * @return array
  */
  public function getEmployeeList($pnCompanyPk = 0, $pnContactPk = 0, $pbExtended = false)
  {
    if(!assert('is_integer($pnCompanyPk) && is_integer($pnContactPk) && is_bool($pbExtended)'))
      return array();

    if((empty($pnCompanyPk) && empty($pnContactPk)) || (!empty($pnCompanyPk) && !empty($pnContactPk)))
    {
      assert('false; // trying to get employee list but no cp or ct pk given');
      return array();
    }

    $sQuery = 'SELECT ct.*, prf.*, cp.company_name FROM addressbook_profile as prf ';
    $sQuery.= ' INNER JOIN addressbook_company as cp ON (cp.addressbook_companypk = prf.companyfk) ';
    $sQuery.= ' INNER JOIN addressbook_contact as ct ON (ct.addressbook_contactpk = prf.contactfk) ';

    if(!empty($pnCompanyPk))
    {
      if($pbExtended)
      {
        $anCompany = $this->getRelatedCompanies($pnCompanyPk);
        if(empty($anCompany))
          return array();

        $sQuery.= ' WHERE prf.companyfk IN ('.implode(',', $anCompany).') ';
      }
      else
        $sQuery.= ' WHERE prf.companyfk = "'.$pnCompanyPk.'" ';
    }
    else
    {
      if($pbExtended)
      {
        $nCompanyPk = $this->getContactCompany($pnContactPk);
        if(empty($nCompanyPk))
          $sQuery.= ' WHERE false ';
        else
        {
          $anCompany = $this->getRelatedCompanies($nCompanyPk);
          if(empty($anCompany))
            $sQuery.= ' WHERE false ';
          else
            $sQuery.= ' WHERE prf.companyfk IN ('.implode(',', $anCompany).') ';
        }
      }
      else
        $sQuery.= ' WHERE  prf.companyfk IN (SELECT prf.companyfk FROM profil as prf WHERE contactfk = "'.$pnContactPk.'")';
    }

    $sQuery.= ' ORDER BY cp.company_name, ct.lastname, ct.firstname ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

    if(!$bRead)
      return array();

    $asEmployee = array();
    while($bRead)
    {
      $asEmployee[(int)$oResult->getFieldValue('addressbook_contactpk')] = $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asEmployee;
  }

  public function getContactCompany($pnContactPk)
  {
    if(!assert('is_integer($pnContactPk) && !empty($pnContactPk)'))
      return 0;

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM addressbook_profile WHERE contactfk ='.$pnContactPk.' AND companyfk <> 0';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
      return $oDbResult->getFieldValue('companyfk', CONST_PHP_VARTYPE_INT);

    return 0;
  }

  /**
   * Fetch all data of the Pked company(ies)
   * @param variant $pvCompanyPk
   * @return array of company data
   */
  public function getCompanyByPk($pvCompanyPk)
  {
    if(!assert('(is_integer($pvCompanyPk) || is_array($pvCompanyPk)) && !empty($pvCompanyPk)'))
      return array();

    //check and convert array of company PKs
    if(is_array($pvCompanyPk))
    {
      foreach($pvCompanyPk as $vKey => $pvPk)
      {
        if(empty($pvPk) || $pvPk < 0)
        {
          assert('false; // company pk can\'t be empty');
          return array();
        }

        $pvCompanyPk[$vKey] = (int)$pvPk;
      }
    }

    $bIsArray = is_array($pvCompanyPk);

    if($bIsArray)
      $sQuery = 'SELECT * FROM `addressbook_company` WHERE addressbook_companypk IN ('.implode(',', $pvCompanyPk).') ';
    else
      $sQuery = 'SELECT * FROM `addressbook_company` WHERE addressbook_companypk = '.$pvCompanyPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    if(!$bIsArray)
      return $oDbResult->getData();

    $asResult = array();
    while($bRead)
    {
      $asResult[$oDbResult->getFieldValue('addressbook_companypk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asResult;
  }


  /**
   * Fetch child companies from a company
   * @param int $pnParentFk
   */

  public function getCompanyChildsByPk($pnParentFk)
  {

    if(!assert('is_key($pnParentFk)'))
      return new CDbResult();

    $sQuery = 'SELECT * FROM `addressbook_company` WHERE parentfk='.$pnParentFk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getIndustry($pvIndustryPk = 0)
  {
    if(!assert('is_array($pvIndustryPk) || is_integer($pvIndustryPk)'))
      return false;

    if(empty($pvIndustryPk))
    {
      $pvIndustryPk = array();
    }
    else
    {
      if(is_array($pvIndustryPk))
      {
        foreach($pvIndustryPk as $nValue)
        {
          if(!is_numeric($nValue))
            !assert('false; // not a numeric value ');
        }
      }
      else
        $pvIndustryPk = (array)$pvIndustryPk;
    }

    $sQuery = 'SELECT * FROM addressbook_industry ';
    if(!empty($pvIndustryPk))
      $sQuery.= ' WHERE addressbook_industrypk IN ('.implode(',', $pvIndustryPk).')';

    $sQuery.= '  ORDER BY industry_name';
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asIndustry = array();
    while($bRead)
    {
      $asIndustry[$oDbResult->getFieldValue('addressbook_industrypk', CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asIndustry;
  }

  public function getCompagnyContacts($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    $sSelect = $sJoin = '';

    $sEventUid = CDependency::getComponentUidByName('event');
    if(!empty($sEventUid))
    {
      $sSelect = ', event.* ';
      $sJoin = 'LEFT JOIN shared_event as event ON (ct.addressbook_contactpk = event.cp_pk and event.cp_type ="ct") ';
    }

    $sQuery = 'SELECT ct.*, group_concat(DISTINCT CONCAT(lg.firstname) SEPARATOR ",") as follower_firstname,
      GROUP_CONCAT(DISTINCT CONCAT(lg.lastname) SEPARATOR ",") as follower_lastname,
      GROUP_CONCAT(distinct prf.email) as profileEmail,GROUP_CONCAT(DISTINCT prf.position) AS position,
      GROUP_CONCAT(DISTINCT prf.department) AS department,group_concat(distinct prf.phone) as profilePhone,
      GROUP_CONCAT(DISTINCT cp.company_name) as company_name,
      COUNT(DISTINCT cp.company_name) as ncount,
      GROUP_CONCAT(DISTINCT ind.industry_name SEPARATOR ",") as industry_name
      '.$sSelect.' FROM addressbook_contact as ct ';

    $sQuery.= ' INNER JOIN addressbook_profile as prf ON (ct.addressbook_contactpk = prf.contactfk and prf.companyfk='.$pnPk.')';
    $sQuery.= ' INNER JOIN addressbook_company as cp ON (cp.addressbook_companypk = prf.companyfk and cp.addressbook_companypk='.$pnPk.' )';
    $sQuery.= ' LEFT JOIN addressbook_company_industry AS cmpid ON (cp.addressbook_companypk = cmpid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON (cmpid.industryfk = ind.addressbook_industrypk)';
    $sQuery.= ' LEFT JOIN login AS lg ON (lg.loginpk = ct.followerfk)';
    $sQuery.= $sJoin;
    $sQuery.= ' GROUP BY ct.addressbook_contactpk';

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;

  }

  public function getContactData($pvPk)
  {
    if(!assert('is_arrayOfInt($pvPk) || is_key($pvPk)'))
      return array();

    $sQuery = 'SELECT *, GROUP_CONCAT(p.companyfk SEPARATOR ",") as profiles FROM addressbook_contact as c ';
    $sQuery.= ' LEFT JOIN addressbook_profile as p ON (p.contactfk = c.addressbook_contactpk) ';

    if(is_key($pvPk))
      $sQuery.= ' WHERE addressbook_contactpk = '.$pvPk;
    elseif(is_arrayOfInt($pvPk))
      $sQuery.= ' WHERE addressbook_contactpk IN ('.  implode(',', $pvPk).')';

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function getContact($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    $sQuery = 'SELECT ct.*, ci.*,ct.postcode as ctpostcode, prf.department as department_name,
      GROUP_CONCAT(DISTINCT CONCAT(l.firstname) SEPARATOR ",") as follower_firstname,
      GROUP_CONCAT(DISTINCT CONCAT(l.lastname) SEPARATOR ",") as follower_lastname ,
      GROUP_CONCAT(DISTINCT CONCAT(acm.loginfk) SEPARATOR ",") as followers,
      GROUP_CONCAT(DISTINCT prf.email SEPARATOR ",") as prfEmail,
      GROUP_CONCAT(DISTINCT prf.phone SEPARATOR ",") as prfPhone,
      GROUP_CONCAT(DISTINCT prf.address_1 SEPARATOR ",") as prfAddress,
      co.*,prf.companyfk as companyfk,ct.addressbook_contactpk as contactfk,
      GROUP_CONCAT(DISTINCT prf.position SEPARATOR ",") as position,
      GROUP_CONCAT(DISTINCT ind.industry_name SEPARATOR " , ") as industry_name

      FROM addressbook_contact as ct
      ';
    $sQuery.= ' LEFT JOIN system_city as ci ON (ci.system_citypk = ct.cityfk) ';
    $sQuery.= ' LEFT JOIN system_country as co ON (co.system_countrypk = ct.countryfk) ';
    $sQuery.= ' LEFT JOIN shared_login as l ON (l.loginpk = ct.followerfk) ';
    $sQuery.= ' LEFT JOIN addressbook_profile as prf ON (ct.addressbook_contactpk = prf.contactfk and prf.date_end  IS NULL)';
    $sQuery.= ' LEFT JOIN addressbook_company as cp ON (cp.addressbook_companypk = prf.companyfk) ';
    $sQuery.= ' LEFT JOIN addressbook_company_industry AS cmpid ON (cp.addressbook_companypk = cmpid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry AS ind ON (cmpid.industryfk = ind.addressbook_industrypk)';
    $sQuery.= ' LEFT JOIN addressbook_account_manager as acm ON (acm.contactfk = ct.addressbook_contactpk)';
    $sQuery.= ' WHERE ct.addressbook_contactpk = '.$pnPk.' group by ct.addressbook_contactpk';

    //echo $sQuery;
    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function fetchProfiles($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    $sQuery = 'SELECT  p.*, DATE_FORMAT(p.date_update, \'%Y-%m-%d\') as date_update FROM addressbook_profile as p ';
    $sQuery.= ' WHERE p.contactfk = '.$pnPk.' AND (p.date_end IS NULL OR p.date_end > "'.date('Y-m-d').'") ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function fetchCoWorkers($pnCompanyFk)
  {
    if(!assert('is_key($pnCompanyFk)'))
      return new CDbResult;

    $sQuery = 'SELECT count(distinct p.contactfk) as nCount, c.*, p.* FROM addressbook_profile as p ';
    $sQuery.= ' INNER JOIN addressbook_contact as ct ON (ct.addressbook_contactpk = p.contactfk)';
    $sQuery.= ' LEFT JOIN addressbook_company as c ON(c.addressbook_companypk = p.companyfk )  ';
    $sQuery.= ' WHERE p.companyfk = '.$pnCompanyFk.' AND (p.date_end IS NULL OR p.date_end > "'.date('Y-m-d').'")';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function getCompany($pnPK)
  {
    if(!assert('is_key($pnPK)'))
      return new CDbResult;

    $sQuery = 'SELECT cp.*, ci.*, co.*, GROUP_CONCAT(DISTINCT acm.loginfk SEPARATOR ",") as followers, l.lastname as follower_lastname,
      l.firstname as follower_firstname, GROUP_CONCAT(DISTINCT ind.industry_name SEPARATOR ", ")
      as industry_name FROM addressbook_company as cp ';
    $sQuery.= ' LEFT JOIN system_city as ci ON (ci.system_citypk = cp.cityfk)';
    $sQuery.= ' LEFT JOIN system_country as co ON (co.system_countrypk = cp.countryfk)';
    $sQuery.= ' LEFT JOIN login as l ON (l.loginpk = cp.followerfk)';
    $sQuery.= ' LEFT JOIN addressbook_company_industry as cid ON (cp.addressbook_companypk = cid.companyfk)';
    $sQuery.= ' LEFT JOIN addressbook_industry as ind ON (ind.addressbook_industrypk = cid.industryfk)';
    $sQuery.= ' LEFT JOIN addressbook_account_manager as acm ON (acm.companyfk=cp.addressbook_companypk)';
      $sQuery.= ' WHERE cp.addressbook_companypk = '.$pnPK.' GROUP BY cp.addressbook_companypk';

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function getCompanyEmployeesCount($pnPK)
  {
    if(!assert('is_key($pnPK)'))
      return new CDbResult;

    $sQuery = 'SELECT count(distinct p.contactfk) as nCount';
    $sQuery .= ' FROM addressbook_profile as p, addressbook_contact as c';
    $sQuery .= ' WHERE p.contactfk=c.addressbook_contactpk AND p.companyfk='.$pnPK;

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function getCompanyDocuments($pnPK)
  {

    if(!assert('is_key($pnPK)'))
      return new CDbResult;

    $sQuery = 'SELECT adi.*,  ad.* , ct.addressbook_contactpk, ct.firstname, ct.lastname, ct.courtesy
      FROM addressbook_company as cp
      LEFT JOIN addressbook_profile as pro ON (pro.companyfk = cp.addressbook_companypk AND pro.companyfk = '.$pnPK.')
      LEFT JOIN addressbook_contact as ct ON (ct.addressbook_contactpk = pro.contactfk)
      LEFT JOIN addressbook_document_info as adi ON
      (
        (adi.itemfk = cp.addressbook_companypk AND adi.type="cp" )
        OR (pro.contactfk IS NOT NULL AND adi.itemfk = pro.contactfk AND adi.type="ct")
      )
      LEFT JOIN addressbook_document as ad ON (ad.addressbook_documentpk = adi.docfk)
      WHERE cp.addressbook_companypk='.$pnPK.'
      GROUP BY ad.addressbook_documentpk
      ORDER BY adi.type, ad.date_create DESC';

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }


  /**
   * Find the account manager
   * @param integer $pnItemPk
   * @param string $psType
   * @return array of account manager data
   */

  public function getAccountManager($pnItemPk, $psTable)
  {
    if(!assert('is_key($pnItemPk)'))
      return array();

    if(!assert('$psTable==\'addressbook_contact\' || $psTable==\'addressbook_company\''))
      return array();

    if($psTable == 'addressbook_contact')
      $oResult = $this->getByFk($pnItemPk, 'addressbook_account_manager', 'contact');
    else
      $oResult = $this->getByFk($pnItemPk, 'addressbook_account_manager', 'company');

    $bRead = $oResult->readFirst();
    while($bRead)
    {
       $asSelectManager[] = (int)$oResult->getFieldValue('loginfk');
       $bRead = $oResult->readNext();
    }

    $oResult = $this->getByPk($pnItemPk, $psTable, 'followerfk');
    $bRead = $oResult->readFirst();
    while($bRead)
    {
      $asSelectedManager[] = (int)$oResult->getFieldValue('followerfk');
      $bRead = $oResult->readNext();
    }

    if(!empty($asSelectManager))
       $asSelectedManager = array_merge($asSelectedManager,$asSelectManager) ;

    return $asSelectedManager;
  }


  /**
   * Return the list of prospects in the database with the list of followers for each of those
   * @return oDbResult
   */
  public function getProspect($pbWithFollowers = true, $pbGrouped = false, $psExtraWhere = '')
  {
    if(!assert('is_bool($pbWithFollowers) && is_bool($pbGrouped)'))
      return new CDbResult;

    if($pbWithFollowers)
      $sSelect = ' , am.loginfk as managerfk ';
    else
      $sSelect = '';

    if($pbGrouped)
      $sSelect.= ' , GROUP_CONCAT(am.loginfk) as followers ';

    $sQuery = 'SELECT ct.* '.$sSelect.' FROM addressbook_contact as ct ';

    if($pbWithFollowers)
      $sQuery.= ' LEFT JOIN  addressbook_account_manager as am ON (am.contactfk = ct.addressbook_contactpk) ';

    $sQuery.= ' WHERE ct.relationfk = "'.CONST_AB_PROSPECT_PK.'" ' ;

    if(!empty($psExtraWhere))
      $sQuery.= ' AND '.$psExtraWhere;

    if($pbGrouped)
      $sQuery.= ' GROUP BY ct.addressbook_contactpk ';

    $sQuery.= ' ORDER BY ct.date_create ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();

    if(!$bRead)
      return new CDbResult;

    return  $oResult;
  }

}