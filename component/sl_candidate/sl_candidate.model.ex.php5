<?php

class CSl_candidateModelEx extends CSl_candidateModel
{
  private $casAttribute = array(
      'candi' => array('candi_lang', 'candi_indus', 'candi_occu'),
      'comp' => array('cp_indus')
      );

  public function __construct()
  {
    $this->cbIsAdmin = CDependency::getCpLogin()->isAdmin();

    parent::__construct();
    return true;
  }



  public function getCandidateData($pvPk, $pbFullProfile = false, $pbForceArray = false)
  {
    if(!assert('(is_key($pvPk) || is_arrayOfInt($pvPk)) && is_bool($pbFullProfile)'))
      return array();

    $oQB = $this->getQueryBuilder();
    $oQB->setTable('sl_candidate', 'scan');

    if($pbFullProfile)
    {
      $oQB->addSelect('scpr.*, scrs.*, scan.*,
        scan.date_created as date_added,
        slog.firstname as cons_firstname, slog.lastname as cons_lastname,
        slog.loginpk as creatorfk, CONCAT(slog.firstname, " ", slog.lastname) as creator, slog.pseudo as creator_short,
        sind.label as industry, socc.label as occupation,
        scom.name as company_name, scom.is_client as cp_client, scom.sl_companypk, scom.sl_companypk as companyfk,
        scrs.date_created as date_rss, sloc.location, slan.language, snat.nationality,
        (scpr.salary + scpr.bonus) as full_salary, scpr.grade, scpr.title,

        satt.`type` as attribute_type,
        satt.attributefk as attribute_value,
        IF(sind2.label IS NOT NULL, sind2.label, IF(socc2.label IS NOT NULL, socc2.label,  satt.attributefk)) as attribute_label
        ');


      $oQB->addJoin('left', 'sl_location', 'sloc', 'sloc.sl_locationpk = scan.locationfk');
      $oQB->addJoin('left', 'sl_language', 'slan', 'slan.sl_languagepk = scan.languagefk');
      $oQB->addJoin('left', 'sl_nationality', 'snat', 'snat.sl_nationalitypk = scan.nationalityfk');
      $oQB->addJoin('left', 'sl_candidate_profile', 'scpr', 'scpr.candidatefk = scan.sl_candidatepk');
      $oQB->addJoin('left', 'sl_industry', 'sind', 'sind.sl_industrypk = scpr.industryfk');
      $oQB->addJoin('left', 'sl_occupation', 'socc', 'socc.sl_occupationpk = scpr.occupationfk');
      $oQB->addJoin('left', 'sl_company', 'scom', 'scom.sl_companypk = scpr.companyfk');
      $oQB->addJoin('left', 'sl_company_rss', 'scrs', 'scrs.companyfk = scom.sl_companypk');
      $oQB->addJoin('left', 'shared_login', 'slog', 'slog.loginpk = scan.created_by');

      $oQB->addJoin('left', 'sl_attribute', 'satt', 'satt.`type` IN ("'.implode('","', $this->casAttribute['candi']).'") AND itemfk = scan.sl_candidatepk');
      $oQB->addJoin('left', 'sl_industry', 'sind2', 'satt.`type` = "candi_indus" AND sind2.sl_industrypk = satt.attributefk');
      $oQB->addJoin('left', 'sl_occupation', 'socc2', 'satt.`type` = "candi_occu" AND socc2.sl_occupationpk = satt.attributefk');
    }
    else
      $oQB->addSelect('scan.*');


    if(is_integer($pvPk))
      $oQB->addWhere('scan.sl_candidatepk = '.$pvPk);
    else
      $oQB->addWhere('scan.sl_candidatepk IN ('.implode(',', $pvPk).') ');


    /*if(!$this->cbIsAdmin)
    {
      $oQB->addWhere('scan._sys_status = 0 ');
    }*/



    /*$fStart = microtime();
    echo('<br /><br /><br />'.str_replace('LEFT JOIN', '<br />LEFT JOIN', $oQB->getSql()).'<br /><br /><br />');
    dump(' - - - - - - ');
    dump($oQB->getSql());
    $fEnd = microtime();
    echo 'In '.round((($fEnd-$fStart)*1000), 5).'ms <br />';*/
    $sQuery = $oQB->getSql();




    //echo $sQuery;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asCandidate = array();
    while($bRead)
    {
      $nPk = (int)$oDbResult->getFieldValue('sl_candidatepk');

      if(empty($asCandidate[$nPk]))
      {
        $asCandidate[$nPk] = $oDbResult->getData();
        $asCandidate[$nPk]['sl_candidatepk'] = (int)$asCandidate[$nPk]['sl_candidatepk'];
        $asCandidate[$nPk]['attribute'] = array();
      }

      $sAttribute = $oDbResult->getFieldValue('attribute_type');
      if(!empty($sAttribute))
        $asCandidate[$nPk]['attribute'][$sAttribute][$oDbResult->getFieldValue('attribute_value')] = $oDbResult->getFieldValue('attribute_label');

      $bRead = $oDbResult->readNext();
    }

    if(is_integer($pvPk) && !$pbForceArray)
       return $asCandidate[$pvPk];

    return $asCandidate;
  }

  public function getCandidateFormData($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult();

    $oQB = $this->getQueryBuilder();
    $oQB->setTable('sl_candidate', 'scan');

    $oQB->addSelect('scan.*, scpr.*,
      scan.date_created as date_added, scan.is_client as client,
      sind.label as industry, socc.label as occupation,
      scom.name as company_name, scom.is_client, scom.sl_companypk, scom.sl_companypk as companyfk,
      sloc.location, slan.language, snat.nationality,
      (scpr.salary + scpr.bonus) as full_salary, scpr.grade, scpr.title,

      GROUP_CONCAT(satt.`type`) as attribute_type,
      GROUP_CONCAT(satt.attributefk) as attribute_value,
      GROUP_CONCAT(IF(sind2.label IS NOT NULL, sind2.label, IF(socc2.label IS NOT NULL, socc2.label,  satt.attributefk))) as attribute_label
    ');

    $oQB->addJoin('left', 'sl_location', 'sloc', 'sloc.sl_locationpk = scan.locationfk');
    $oQB->addJoin('left', 'sl_language', 'slan', 'slan.sl_languagepk = scan.languagefk');
    $oQB->addJoin('left', 'sl_nationality', 'snat', 'snat.sl_nationalitypk = scan.nationalityfk');
    $oQB->addJoin('left', 'sl_candidate_profile', 'scpr', 'scpr.candidatefk = scan.sl_candidatepk');
    $oQB->addJoin('left', 'sl_industry', 'sind', 'sind.sl_industrypk = scpr.industryfk');
    $oQB->addJoin('left', 'sl_occupation', 'socc', 'socc.sl_occupationpk = scpr.occupationfk');
    $oQB->addJoin('left', 'sl_company', 'scom', 'scom.sl_companypk = scpr.companyfk');


    $oQB->addJoin('left', 'sl_attribute', 'satt', 'satt.`type` IN ("'.implode('","', $this->casAttribute['candi']).'") AND itemfk = scan.sl_candidatepk');
    $oQB->addJoin('left', 'sl_industry', 'sind2', 'satt.`type` = "candi_indus" AND sind2.sl_industrypk = satt.attributefk');
    $oQB->addJoin('left', 'sl_occupation', 'socc2', 'satt.`type` = "candi_occu" AND socc2.sl_occupationpk = satt.attributefk');

    $oQB->addWhere('scan.sl_candidatepk = '.$pnPk);
    $oQB->addWhere('scan.sl_candidatepk = '.$pnPk);

    $oQB->addGroup('scan.sl_candidatepk');


    if(!$this->cbIsAdmin)
    {
      $oQB->addWhere('scan._sys_status = 0');
    }


    $sQuery = $oQB->getSql();


    return $this->oDB->ExecuteQuery($sQuery);
  }




  public function getContact($pnItemPk, $psItemType = 'candi', $pnUser = 0, $panUserGroup = array(), $pbVisibleOnly = false, $pbGrouped = true)
  {
    if(!assert('is_key($pnItemPk) && is_integer($pnUser) && !empty($psItemType) '))
      return new CDbResult();

    if(!assert('empty($panUserGroup) || is_arrayOfInt($panUserGroup)'))
      return new CDbResult();

    if(!assert('is_bool($pbVisibleOnly) && is_bool($pbGrouped)'))
      return new CDbResult();

    /*if($pbVisibleOnly)
    {
      $oLogin = CDependency::getCpLogin();
      if(empty($pnUser))
      {
        $pnUser = $oLogin->getUserPk();
      }

      if(empty($panUserGroup))
        $sRestrictionSQL = ', IF(scon.visibility = 1 OR scon.loginfk = '.$pnUser.', 1, 0 ) as visible ';
      else
        $sRestrictionSQL = ', IF(scon.visibility = 1 OR scon.loginfk = '.$pnUser.' OR scon.groupfk IN ('.implode(',', $panUserGroup).'), 1, 0) as visible ';

    }
    else
      $sRestrictionSQL = ', 1 as visible';*/

    $sQuery = 'SELECT *, scon.loginfk as creatorfk
      ,GROUP_CONCAT(scvi.loginfk) as custom_visibility
      FROM sl_contact as scon
      LEFT JOIN sl_contact_visibility as scvi ON (scvi.sl_contactfk = scon.sl_contactpk )
      WHERE item_type = '.$this->oDB->dbEscapeString($psItemType).' AND itemfk = '.$pnItemPk.'

      GROUP BY sl_contactpk
      ORDER BY date_create DESC ';


    //echo $sQuery;
    return $this->oDB->ExecuteQuery($sQuery);
  }


  public function getContactByPk($pvItemPk)
  {
    if(!assert('is_key($pvItemPk) || is_arrayOfInt($pvItemPk) '))
      return new CDbResult();


    if(is_array($pvItemPk))
    {
      $pvItemPk = implode(',', $pvItemPk);
    }

    $sQuery = 'SELECT *, scon.loginfk as creatorfk ,GROUP_CONCAT(scvi.loginfk) as custom_visibility
      FROM sl_contact as scon
      LEFT JOIN sl_contact_visibility as scvi ON (scvi.sl_contactfk = scon.sl_contactpk )
      WHERE scon.sl_contactpk IN ('.$pvItemPk.')

      GROUP BY sl_contactpk
      ORDER BY date_create DESC ';


    //echo $sQuery;
    return $this->oDB->ExecuteQuery($sQuery);
  }






  /* ****************************************************** */
  /* ****************************************************** */
  //Company related

  public function getCompanyData($pvPk, $pbFullProfile = false)
  {
    if(!assert('(is_key($pvPk) || is_arrayOfInt($pvPk)) && is_bool($pbFullProfile)'))
      return array();

    if($pbFullProfile)
    {
      $sQuery = 'SELECT scom.*, sind.label as indus_name, sind.sl_industrypk FROM sl_company as scom
        LEFT JOIN sl_attribute as satt ON (satt.`type` = \'cp_indus\' AND satt.itemfk = scom.sl_companypk)
        LEFT JOIN sl_industry as sind ON (sind.sl_industrypk = satt.attributefk)';
    }
    else
      $sQuery = 'SELECT * FROM sl_company as scom ';

    if(is_integer($pvPk))
      $sQuery.= ' WHERE scom.sl_companypk = '.$pvPk;
    else
      $sQuery.= ' WHERE scom.sl_companypk IN ('.implode(',', $pvPk).') ';

    //echo $sQuery;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();



    $asCompany = array();
    while($bRead)
    {
      $nCompanyPk = (int)$oDbResult->getFieldValue('sl_companypk');
      if(!isset($asCompany[$nCompanyPk]))
      {
        $asCompany[$nCompanyPk] = $oDbResult->getData();
        $asCompany[$nCompanyPk]['industry'] = array();
        $asCompany[$nCompanyPk]['industry_id'] = array();
      }

      $asCompany[$nCompanyPk]['industry'][] = $oDbResult->getFieldValue('indus_name');
      $asCompany[$nCompanyPk]['industry_id'][] = (int)$oDbResult->getFieldValue('sl_industrypk');
      $bRead = $oDbResult->readNext();
    }

     if(is_integer($pvPk))
       return $asCompany[$pvPk];

    return $asCompany;
  }


  public function countCompanies()
  {
    $sQuery = 'SELECT count(*) as nCount FROM sl_company ';

    //echo $sQuery;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return 0;

    return (int)$oDbResult->getFieldValue('nCount');
  }


  public function getCompanyDepartment($pnCompanyFk)
  {
    if(!assert('is_key($pnCompanyFk)'))
      return new CDbResult();

    $sQuery = 'SELECT scpr.department, count(*) as nCount FROM sl_candidate_profile as scpr';
    $sQuery.= ' WHERE scpr.companyfk = '.$pnCompanyFk.' GROUP BY scpr.department ';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getFeedByCompanyFk($pnCompanyFk)
  {
    if(!assert('is_key($pnCompanyFk)'))
      return new CDbResult();

    $sQuery = 'SELECT *, scom.name as company_name ';
    $sQuery.= ' FROM sl_company as scom ';
    $sQuery.= ' LEFT JOIN sl_company_rss as scrs ON (scrs.companyfk = scom.sl_companypk) ';
    $sQuery.= ' WHERE scom.sl_companypk = '.$pnCompanyFk;

    return $this->oDB->ExecuteQuery($sQuery);
  }







  /* ****************************************************** */
  /* ****************************************************** */
  //Common

  public function getIndustry($pbIncludeCategory = true, $pbIgnoreRights = false)
  {
    if(!assert('is_bool($pbIncludeCategory) && is_bool($pbIgnoreRights)'))
      return array();

    if($pbIgnoreRights)
    {
      $sQuery = 'SELECT sind.*, parent.sl_industrypk AS parent_industry_id, parent.label AS parent_label
        FROM sl_industry AS sind
        LEFT JOIN  sl_industry AS parent ON (parent.sl_industrypk = sind.parentfk) ';

      if(!$pbIncludeCategory)
        $sQuery.= ' WHERE sind.parentfk <> 0 ';

      $sQuery.= ' ORDER BY parent.label, sind.label ';
    }
    else
    {

      $oQB = $this->getQueryBuilder();
      $oQB->setTable('sl_industry', 'sind');
      $oQB->addSelect('sind.*');
      $oQB->addJoin('left', 'sl_industry', 'parent', 'parent.sl_industrypk = sind.parentfk');
      $oQB->setOrder('parent.label, sind.label');

      if(!$pbIncludeCategory)
        $oQB->addWhere('parentfk <> 0');

      $sQuery = $oQB->getSql();
    }
    return $this->oDB->ExecuteQuery($sQuery);
  }


  public function getOccupation($pbIncludeCategory = true, $pbIgnoreRights = false)
  {
    if(!assert('is_bool($pbIncludeCategory) && is_bool($pbIgnoreRights)'))
      return array();

    if($pbIgnoreRights)
    {
      $sQuery = 'SELECT socc.* FROM sl_occupation as socc
        LEFT JOIN  sl_occupation as parent ON (parent.sl_occupationpk = socc.parentfk)';

      if(!$pbIncludeCategory)
        $sQuery.= ' WHERE socc.is_category = 0 ';

      $sQuery.= ' ORDER BY parent.label, socc.label ';
    }
    else
    {

      $oQB = $this->getQueryBuilder();
      $oQB->setTable('sl_occupation', 'socc');
      $oQB->addSelect('socc.*');
      $oQB->addJoin('left', 'sl_occupation', 'parent', 'parent.sl_occupationpk = socc.parentfk');
      $oQB->setOrder('parent.label, socc.label');

      if(!$pbIncludeCategory)
        $oQB->addWhere('is_category = 0');

      $sQuery = $oQB->getSql();
    }

    return $this->oDB->ExecuteQuery($sQuery);
  }


  public function getCandidateRm($pnPk, $pbActiveOnly = true, $pbFriendly = true)
  {
    if(!assert('is_key($pnPk)'))
      return array();

    $oLogin = CDependency::getCpLogin();
    $asRm = array();

    $sQuery = 'SELECT * FROM sl_candidate_rm as scrm
      INNER JOIN shared_login as slog ON (slog.loginpk = scrm.loginfk)
      WHERE scrm.candidatefk = '.$pnPk.' AND scrm.date_expired IS NULL ';

    if($pbActiveOnly)
      $sQuery.= ' AND slog.status > 0 ';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asManagerData = $oDbResult->getData();
      $asManagerData['loginfk'] = (int)$asManagerData['loginfk'];

      $asRm[$asManagerData['loginfk']] = array(
          'email' => $asManagerData['email'],
          'name' => $oLogin->getUserNameFromData($asManagerData, $pbFriendly),
          'link' => $oLogin->getUserLink($asManagerData['loginfk'], $pbFriendly)
          );

      $bRead = $oDbResult->readNext();
    }

    return $asRm;
  }


  public function getMonthlyMeeting($pnLoginPk, $psDateStart, $psDateEnd)
  {
    $sQuery = 'SELECT count(*) as nb_meeting,
      SUM(IF(smee.attendeefk = '.$pnLoginPk.', 1, 0)) as nb_mine,
      SUM(IF(smee.attendeefk = '.$pnLoginPk.' AND smee.meeting_done = 1, 1, 0)) as nb_done,
      SUM(IF(smee.attendeefk = '.$pnLoginPk.' AND smee.meeting_done = -1, 1, 0)) as nb_cancel,
      DATE_FORMAT(smee.date_meeting,"%Y-%m-01") as date_month

      FROM sl_meeting as smee
      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = smee.candidatefk)
      WHERE (smee.created_by = '.$pnLoginPk.' OR smee.attendeefk = '.$pnLoginPk.')
      AND date_meeting >= "'.$psDateStart.'" AND date_meeting <= "'.$psDateEnd.'"
      GROUP BY date_month
      ORDER BY date_month DESC ';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asMeeting = array();
    while($bRead)
    {
      $asMeetingData = $oDbResult->getData();

      $asMeeting[$asMeetingData['date_month']] = array('nb_meeting' => (int)$asMeetingData['nb_meeting'],
          'nb_mine' => (int)$asMeetingData['nb_mine'],
          'nb_done' => (int)$asMeetingData['nb_done'], 'nb_cancel' => (int)$asMeetingData['nb_cancel'],
          'nb_pending' => ((int)$asMeetingData['nb_mine'] - (int)$asMeetingData['nb_done'] - (int)$asMeetingData['nb_cancel']));

      //dump($asMeetingData['nb_meeting'].' - '.$asMeetingData['nb_done'].' - '.$asMeetingData['nb_cancel']);
      $bRead = $oDbResult->readNext();
    }
    //dump($asMeeting);

    return $asMeeting;
  }

  public function getDuplicate($candidate_info, $force_target = 0, $merge_data = false, $skip_company = false)
  {
    if(!assert('(is_key($candidate_info) || is_array($candidate_info))'))
      return new CDbResult();

    if(is_array($candidate_info))
    {
      $candidate_data = $candidate_info;
      $candidate_id = 0;
      set_array($candidate_data['company_name']);
    }
    else
    {
      $candidate_id = $candidate_info;

      $candidate_data = $this->getCandidateData($candidate_id, true);
      if(empty($candidate_data))
        return new CDbResult();
    }

    $firstname = $candidate_data['firstname'];
    $lastname = $candidate_data['lastname'];
    $company_id = $candidate_data['companyfk'];

    $duplicate_array = array('company' => array(), 'other' => array());
    $duplicate_temp = array();


    if (!empty($company_id) && !$skip_company)
    {
      $duplicate_array['company'] = $this->duplicate_finder($company_id, $lastname, $firstname, false, $force_target);

      // requested by Pam: check reversed lname/fname in the same company
      $duplicate_temp = $this->duplicate_finder($company_id, $firstname, $lastname, false, $force_target);

      foreach ($duplicate_temp as $key => $value)
      {
        if (!isset($duplicate_array['company'][$key]))
          $duplicate_array['company'][$key] = $value;
      }

      uasort($duplicate_array['company'], sort_multi_array_by_value('ratio', 'reverse'));
    }

    $duplicate_array['other'] = $this->duplicate_finder(0, $lastname, $firstname, true, $force_target);

    if ($merge_data)
    {
      foreach ($duplicate_array['company'] as $key => $value)
      {
        if (!isset($duplicate_array['other'][$key]))
          $duplicate_array['other'][$key] = $value;
      }
    }
    else
    {
      foreach ($duplicate_array['other'] as $key => $value)
      {
        if (isset($duplicate_array['company'][$key]))
          unset($duplicate_array['other'][$key]);
      }
    }

    return $duplicate_array;
  }


  private function duplicate_finder($company_id, $lastname, $firstname, $skip_company = false, $force_target = 0)
  {
    $minimum_ratio = 40;
    $duplicate_array = array();

    $clean_lastname = $this->oDB->dbEscapeString(strtolower($lastname));
    $clean_firstname = $this->oDB->dbEscapeString(strtolower($firstname));
    $clean_name = $this->oDB->dbEscapeString(strtolower($lastname.$firstname));

    if (empty($company_id))
      $skip_company = true;

    $query = 'SELECT ca.sl_candidatepk, ca.lastname, ca.firstname, com.name AS company, ocu.label AS occupation,';
    $query.= ' ind.label AS industry, levenshtein('.$clean_lastname.', LOWER(ca.lastname)) AS lastname_lev,';
    $query.= ' levenshtein('.$clean_firstname.', LOWER(ca.firstname)) AS firstname_lev ';
    $query.= ', 100-(levenshtein('.$this->oDB->dbEscapeString(strtolower($lastname.$firstname)).', LOWER(CONCAT(ca.lastname, ca.firstname)))*100/LENGTH(CONCAT(ca.lastname, ca.firstname))) AS ratio ';
    $query.= ' FROM sl_candidate AS ca ';
    $query.= ' LEFT JOIN sl_candidate_profile AS cap ON (cap.candidatefk = ca.sl_candidatepk)';
    $query.= ' LEFT JOIN sl_occupation AS ocu ON (ocu.sl_occupationpk = cap.occupationfk)';
    $query.= ' LEFT JOIN sl_industry AS ind ON (ind.sl_industrypk = cap.industryfk)';
    $query.= ' LEFT JOIN sl_company AS com ON (com.sl_companypk = cap.companyfk)';
    $query.= ' WHERE ';

    if (!$skip_company)
      $query.= ' cap.companyfk = '.$company_id.' AND ';

    if (!empty($force_target))
    {
      $query.= ' ca.sl_candidatepk = '.$force_target.' ';
    }
    else
    {
      $query.= ' ( (ca.lastname LIKE '.$this->oDB->dbEscapeString(strtolower($lastname.'%')).' AND levenshtein('.$clean_firstname.', LOWER(ca.firstname)) < 3) ';
      $query.= ' OR (ca.lastname LIKE '.$this->oDB->dbEscapeString(strtolower('%'.$lastname)).' AND levenshtein('.$clean_firstname.', LOWER(ca.firstname)) < 3) )';

      $query.= ' ORDER BY ratio DESC, lastname_lev ASC, ca.firstname ASC LIMIT 100 OFFSET 0';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    if ($read)
    {
      while($read)
      {
        $temp = $db_result->getData();

        if ($temp['ratio'] > $minimum_ratio || !empty($force_target))
          $duplicate_array[$temp['sl_candidatepk']] = $db_result->getData();

        $read = $db_result->readNext();
      }
    }

    return $duplicate_array;
  }

  public function getLastPositionPlayed($pnCandidatePk)
  {
    if(!assert('is_key($pnCandidatePk)'))
      return false;


    $sQuery = 'SELECT MAX(date_created)
      FROM sl_position_link as spos
      WHERE candidatefk = '.$pnCandidatePk.' ';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return '';

    return $oDbResult->getFieldValue('date_created');
  }

  public function getLastInterView($pnCandidatePk)
  {
    if(!assert('is_key($pnCandidatePk)'))
      return false;


    $sQuery = 'SELECT MAX(date_meeting) as meeting, MAX(date_met) as met
      FROM sl_meeting as smee
      WHERE candidatefk = '.$pnCandidatePk.' AND meeting_done >= 0';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return '';

    return array('meeting' => $oDbResult->getFieldValue('meeting'), 'met' => $oDbResult->getFieldValue('met'));
  }
}
