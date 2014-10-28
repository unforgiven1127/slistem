<?php

class CGbUserModelEx extends CGbUserModel
{
  public $aFilters = array('active', 'inactive', 'all');

  public function __construct()
  {
    parent::__construct();
    return true;
  }

  // Returns user data : groupfk and type of user

  public function getUserData($pnLoginFk = 0)
  {
    if(!assert('is_integer($pnLoginFk)'))
      return new CDbResult();

    if(!is_key($pnLoginFk))
      $pnLoginFk = (int)$_SESSION['userData']['loginpk'];

    $sQuery = 'SELECT u.gbuserpk, u.type as gbusertype, g.name as groupname, g.gbuser_grouppk, u.gbuser_companyfk
                FROM gbuser u
                LEFT JOIN gbuser_group_member gm ON u.gbuserpk = gm.gbuserfk
                LEFT JOIN gbuser_group g ON g.gbuser_grouppk = gm.gbuser_groupfk
                WHERE u.loginfk='.$pnLoginFk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getGroupsCount($pnCompanyFk=0, $psFilter='all')
  {
    $aCount = $this->getGroups($pnCompanyFk, $psFilter, '', true);
    return (int)$aCount['nb'];
  }

  public function getGroups($pnCompanyFk=0, $psFilter='all', $psLimit = '', $pbCountOnly = false, $pnUserFk = 0)
  {
    if(!assert('is_bool($pbCountOnly)'))
      return array();

    if(!assert('is_string($psLimit)'))
      return array();

    if(!assert('is_numeric($pnUserFk)'))
      return array();

    if(!assert('is_numeric($pnCompanyFk)'))
      return array();

    if(!assert('in_array($psFilter, $this->aFilters)'))
      return array();

    $sSql = '(SELECT COUNT(*) FROM gbuser u, gbuser_group_member gm
                WHERE u.type=\'student\' AND gm.gbuserfk=u.gbuserpk AND gm.gbuser_groupfk=g.gbuser_grouppk) as nbStudents';

    $sFields = ($pbCountOnly) ? 'COUNT(*) as nb' : 'g.gbuser_grouppk, g.name, '.$sSql.', g.created_on, g.gbuser_companyfk';

    $sQuery = 'SELECT '.$sFields.'
                  FROM gbuser_group g';

    if(is_key($pnUserFk))
      $sQuery.= ' LEFT JOIN gbuser_group_member gm ON gm.gbuser_groupfk=g.gbuser_grouppk';

    $sQuery .=    ' WHERE (1) ';

    if(is_key($pnCompanyFk))
      $sQuery .= ' AND g.gbuser_companyfk='.$pnCompanyFk;

    if(is_key($pnUserFk))
      $sQuery .= ' AND gm.gbuserfk='.$pnUserFk;

    if($psFilter=='active')
      $sQuery .= ' AND g.active=1 ';
    elseif ($psFilter=='inactive')
      $sQuery .= ' AND g.active=0 ';

    if(!empty($psLimit))
      $sQuery .= ' LIMIT '.$psLimit;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    if($pbCountOnly)
      return array('nb' => $oDbResult->getFieldValue('nb'));
    else
      return $this->formatOdbResult($oDbResult, 'gbuser_grouppk');
  }

  public function getGroupsIdsForSupervisor($pnUserFk, $psFilter = 'all')
  {
    if(!assert('is_key($pnUserFk)'))
      return array();

    if(!assert('in_array($psFilter, $this->aFilters)'))
      return array();

    $sQuery = 'SELECT g.gbuser_grouppk
                  FROM gbuser_group g
                  LEFT JOIN gbuser_group_member gm ON gm.gbuser_groupfk = g.gbuser_grouppk
                  WHERE gm.gbuserfk='.$pnUserFk;

    if($psFilter=='active')
      $sQuery .= ' AND g.active=1 ';
    elseif ($psFilter=='inactive')
      $sQuery .= ' AND g.active=0 ';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();
    else
    {
      $aData = array();

      while($bRead)
      {
        $aData[]=$oDbResult->getFieldValue('gbuser_grouppk');

        $bRead = $oDbResult->readNext();
      }
      return $aData;
    }
  }


  // Returns users public data to be used in other components

  public function getUsersDataForSupervisor($pnUserFk)
  {
    if(!assert('is_key($pnUserFk)'))
      return array();

    /*$sQuery = 'SELECT sl.firstname, sl.lastname, sl.email, g.loginfk, g.gbuserpk, g.gbuser_companyfk,
      c.name as company_name, gu.name as group_name, gm.gbuser_groupfk
      FROM gbuser g
      LEFT JOIN shared_login sl ON g.loginfk=sl.loginpk
      LEFT JOIN gbuser_group_member gm ON gm.gbuserfk=g.gbuserpk
      LEFT JOIN gbuser_group gu ON gu.gbuser_grouppk=gm.gbuser_groupfk
      LEFT JOIN gbuser_company c ON c.gbuser_companypk=gu.gbuser_companyfk
      WHERE g.type= \'student\'
            AND gu.gbuser_grouppk IN (SELECT g.gbuser_grouppk
                  FROM gbuser_group g
                  LEFT JOIN gbuser_group_member gm ON gm.gbuser_groupfk = g.gbuser_grouppk
                  WHERE gm.gbuserfk='.$pnUserFk.' AND gu.active=1 AND sl.status=1)';*/

    $sQuery = 'SELECT sl.firstname, sl.lastname, sl.email, g.loginfk, g.gbuserpk, g.gbuser_companyfk,
      c.name as company_name, gu.name as group_name, gm.gbuser_groupfk
      FROM gbuser g
      LEFT JOIN shared_login sl ON g.loginfk=sl.loginpk
      LEFT JOIN gbuser_group_member gm ON gm.gbuserfk=g.gbuserpk
      LEFT JOIN gbuser_group gu ON gu.gbuser_grouppk=gm.gbuser_groupfk
      LEFT JOIN gbuser_company c ON c.gbuser_companypk=gu.gbuser_companyfk
      WHERE g.type= \'student\'
            AND gu.gbuser_grouppk IN (
SELECT ggme.gbuser_groupfk
FROM `gbuser` as user
LEFT JOIN `gbuser`  as coworker ON (coworker.`gbuser_companyfk` = user.`gbuser_companyfk`)
LEFT JOIN gbuser_group_member as ggme ON (ggme.gbuserfk = coworker.gbuserpk)
WHERE user.gbuserpk = '.$pnUserFk.') ';

    //dump($sQuery);
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    $aData = array();
    $bRead = $oDbResult->readFirst();

    while($bRead)
    {
      $nUserPk = $oDbResult->getFieldValue('gbuserpk');
      $aData[$nUserPk] = $oDbResult->getData();

      $bRead = $oDbResult->readNext();
    }

    return $aData;
  }

  public function getActiveUsersData($pnCompanyFk=0)
  {
    if(!assert('is_numeric($pnCompanyFk)'))
      return array();

    $sQuery = 'SELECT sl.firstname, sl.lastname, sl.email, g.loginfk, g.gbuserpk, gu.name as group_name, gm.gbuser_groupfk, g.gbuser_companyfk, gc.name as company_name
      FROM gbuser g
      LEFT JOIN shared_login sl ON g.loginfk=sl.loginpk
      LEFT JOIN gbuser_group_member gm ON gm.gbuserfk=g.gbuserpk
      LEFT JOIN gbuser_group gu ON gu.gbuser_grouppk=gm.gbuser_groupfk
      LEFT JOIN gbuser_company gc ON gc.gbuser_companypk=gu.gbuser_companyfk';

    if(is_key($pnCompanyFk))
      $sQuery .= ' LEFT JOIN gbuser_company uc ON gu.gbuser_companyfk='.$pnCompanyFk;

    $sQuery .= ' WHERE g.type= \'student\' AND sl.status=1 AND gu.active=1';

    if(is_key($pnCompanyFk))
      $sQuery .= ' AND uc.active=1 AND uc.gbuser_companypk='.$pnCompanyFk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    return $this->formatOdbResult($oDbResult, 'gbuserpk');
  }

  public function getGroupMembers($pnGroupPk, $psType='', $pbJustIds = false)
  {
    if(!assert('is_key($pnGroupPk)'))
      return new CDbResult();

    if(!assert('is_bool($pbJustIds)'))
      return new CDbResult();

    if(!empty($psType))
    {
      if(!assert('in_array($psType,array(\'student\', \'teacher\', \'hrmanager\'))'))
        return new CDbResult();
    }

    $sSelect = (!$pbJustIds) ? ' sl.firstname, sl.lastname, sl.email, g.loginfk, g.gbuserpk ' : ' g.gbuserpk ';

    $sQuery = 'SELECT '.$sSelect.'
                 FROM gbuser g
                 LEFT JOIN shared_login sl ON g.loginfk=sl.loginpk
                 LEFT JOIN gbuser_group_member gm ON g.gbuserpk = gm.gbuserfk
                 WHERE gm.gbuser_groupfk='.$pnGroupPk;

    if(!empty($psType))
      $sQuery .= ' AND g.type=\''.$psType.'\'';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();
    else
      return $oDbResult;
  }

  public function getMembersCount($pnCompanyPk, $psType, $psFilter)
  {
    $oResult = $this->getMembers($pnCompanyPk, $psType, $psFilter, true);
    return (int)$oResult->getFieldValue('nb');
  }

  public function getMembers($pnCompanyPk=0, $psType='', $psFilter = 'all', $pbCountOnly = false, $psLimit = '', $paGroupIds = array())
  {
    if(!assert('is_array($paGroupIds)'))
      return new CDbResult();

    if(!assert('is_numeric($pnCompanyPk)'))
      return new CDbResult();

    if(!empty($psType))
    {
      if(!assert('in_array($psType,array(\'student\', \'teacher\', \'hrmanager\'))'))
        return new CDbResult();
    }

    if(!assert('in_array($psFilter, $this->aFilters)'))
      return  new CDbResult();

    if(!assert('is_string($psLimit)'))
      return new CDbResult();

    $sSelect = (!$pbCountOnly) ? ' l.firstname, l.lastname, l.email, u.gbuserpk, u.created_on' : ' COUNT(*) as nb ';
    $sSelect .= ($psType=='student' && !$pbCountOnly) ? ', g.name as group_name' : '';

    if($psType=='student')
      $sSelect .= ' , gm.* ';

    $sQuery = 'SELECT '.$sSelect.'
                 FROM gbuser u
                    LEFT JOIN shared_login l ON u.loginfk = l.loginpk';

    if($psType=='student')
      $sQuery.=  '    LEFT JOIN gbuser_group_member gm ON gm.gbuserfk = u.gbuserpk
                      LEFT JOIN gbuser_group g ON gm.gbuser_groupfk = g.gbuser_grouppk';

    $sQuery.= ' WHERE (1)';

    if(is_key($pnCompanyPk))
      $sQuery.= ' AND u.gbuser_companyfk='.$pnCompanyPk;

    if(!empty($paGroupIds) && $psType=='student')
      $sQuery .= ' AND gm.gbuser_groupfk IN('.implode(',',$paGroupIds).')';

    if(!empty($psType))
      $sQuery .= ' AND u.type=\''.$psType.'\'';

    if($psFilter=='active')
      $sQuery .= ' AND l.status=1 ';
    elseif ($psFilter=='inactive')
      $sQuery .= ' AND l.status=0 ';

    if(!empty($psLimit))
      $sQuery .= ' LIMIT '.$psLimit;

    //dump($sQuery);
    $oDbResult = $this->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();


    /*if(!$bRead)
      return new CDbResult();*/

    return $oDbResult;
  }

  public function getCompanyFromGroupPk($pnGroupPk)
  {
    if(!assert('is_key($pnGroupPk)'))
      return array();

    $sQuery = 'SELECT c.* FROM gbuser_company c
                LEFT JOIN gbuser_group g ON g.gbuser_companyfk = c.gbuser_companypk
                WHERE g.gbuser_grouppk='.$pnGroupPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();
    else
      return $oDbResult;
  }

  public function getCompany($pnCompanyPk)
  {
    if(!assert('is_key($pnCompanyPk)'))
      return array();

    $oCompany = $this->getCompanies($pnCompanyPk);
    return $oCompany->getData();
  }

  public function getActiveCompanies()
  {
    $oDbResult = $this->getCompanies(0, 'active');

    return $this->formatOdbResult($oDbResult, 'gbuser_companypk');
  }

  public function getCompanies($pnCompanyPk = 0, $psFilter='all')
  {
    if(!assert('is_numeric($pnCompanyPk)'))
      return new CDbResult();

    if(!assert('in_array($psFilter, $this->aFilters)'))
      return  new CDbResult();

    $sSql = '(SELECT COUNT(*) FROM gbuser_group g
                WHERE g.gbuser_companyfk=c.gbuser_companypk AND g.active=1) as nbActiveGroups';

    $sQuery = 'SELECT c.name, c.gbuser_companypk, c.active, COUNT(g.gbuser_grouppk) as nbGroups, '.$sSql.', i.industry_name, n.nationality_name
                FROM gbuser_company c
                  LEFT JOIN gbuser_group g ON c.gbuser_companypk=g.gbuser_companyfk
                  LEFT JOIN industry i ON c.industryfk=i.industrypk
                  LEFT JOIN nationality n ON c.nationalityfk=n.nationalitypk
                  WHERE (1) ';

    if(is_key($pnCompanyPk))
      $sQuery .= ' AND c.gbuser_companypk='.$pnCompanyPk;
    else
    {
      if($psFilter=='active')
        $sQuery .= ' AND c.active=1 ';
      elseif ($psFilter=='inactive')
        $sQuery .= ' AND c.active=0 ';
      $sQuery .= ' GROUP BY c.gbuser_companypk';
    }
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();
    else
      return $oDbResult;
  }

  public function getTeachers()
  {
     $sQuery = 'SELECT sl.firstname, sl.lastname, sl.email, g.loginfk, g.gbuserpk
                  FROM gbuser g
                  LEFT JOIN shared_login sl ON g.loginfk=sl.loginpk
                  WHERE g.type=\'teacher\' AND sl.status=1';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getStudentsForTeacher($pnTeacherFk)
  {
    if(!assert('is_key($pnTeacherFk)'))
      return array();

    $sQuery = 'SELECT u.gbuserpk, sl.firstname, sl.lastname, ug.name as group_name
                  FROM gbuser u
                  LEFT JOIN shared_login sl ON u.loginfk=sl.loginpk
                  LEFT JOIN gbuser_group_member ugm ON u.gbuserpk = ugm.gbuserfk
                  LEFT JOIN gbuser_group ug ON ugm.gbuser_groupfk = ug.gbuser_grouppk
                  LEFT JOIN gbuser_company uc ON ug.gbuser_companyfk = uc.gbuser_companypk
                  WHERE u.type=\'student\' AND ug.active=1 AND uc.active=1 AND sl.status=1
                    AND ug.gbuser_grouppk
                      IN (SELECT gbuser_groupfk FROM gbuser_group_member WHERE gbuserfk='.$pnTeacherFk.')';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    return $this->formatOdbResult($oDbResult, 'gbuserpk');
  }

  public function getStudentsIdsForGroup($pnGroupFk)
  {
    if(!assert('is_key($pnGroupFk)'))
      return array();

    $sQuery = 'SELECT u.gbuserpk
                  FROM gbuser u
                  LEFT JOIN gbuser_group_member ugm ON u.gbuserpk = ugm.gbuserfk
                  WHERE ugm.gbuser_groupfk='.$pnGroupFk.' AND u.type=\'student\'';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();
    else
    {
      $aData = array();

      while($bRead)
      {
        $aData[]=$oDbResult->getFieldValue('gbuserpk');

        $bRead = $oDbResult->readNext();
      }
      return $aData;
    }
  }


  public function getStudentsEmailsForGroup($pnGroupFk)
  {
    if(!assert('is_key($pnGroupFk)'))
      return array();

    $sQuery = 'SELECT u.gbuserpk, sl.email, sl.firstname as name
                  FROM gbuser u
                  LEFT JOIN shared_login sl ON sl.loginpk = u.loginfk
                  LEFT JOIN gbuser_group_member ugm ON u.gbuserpk = ugm.gbuserfk
                  WHERE ugm.gbuser_groupfk='.$pnGroupFk.' AND u.type=\'student\'';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();
    else
      return $this->formatOdbResult($oDbResult, 'gbuserpk');
  }

  public function getStudentsIdsForCompany($pnCompanyFk)
  {
    if(!assert('is_key($pnCompanyFk)'))
      return array();

    $sQuery = 'SELECT gbuserpk
                  FROM gbuser u
                  LEFT JOIN shared_login l ON u.loginfk = l.loginpk
                  LEFT JOIN gbuser_group_member ugm ON u.gbuserpk = ugm.gbuserfk
                  LEFT JOIN gbuser_group ug ON ugm.gbuser_groupfk = ug.gbuser_grouppk
                  WHERE ug.active=1 AND u.type=\'student\' AND l.status=1 AND ug.gbuser_companyfk='.$pnCompanyFk;


    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();
    else
    {
      $aData = array();

      while($bRead)
      {
        $aData[]= (int)$oDbResult->getFieldValue('gbuserpk');

        $bRead = $oDbResult->readNext();
      }
      return $aData;
    }
  }

  public function getUser($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult();

    $sQuery = 'SELECT u.*, l.*, gm.gbuser_groupfk, g.name as group_name, gc.name as company_name, gc.gbuser_companypk
      FROM gbuser u
      LEFT JOIN shared_login l ON u.loginfk = l.loginpk
      LEFT JOIN gbuser_group_member gm ON gm.gbuserfk = u.gbuserpk
      LEFT JOIN gbuser_group g ON gm.gbuser_groupfk = g.gbuser_grouppk
      LEFT JOIN gbuser_company gc ON gc.gbuser_companypk = g.gbuser_companyfk
      WHERE u.gbuserpk='.$pnPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getUserByMail($psEmail)
  {
     $sQuery = 'SELECT * FROM gbuser as gbus
       INNER JOIN shared_login as slog ON (slog.loginpk = gbus.loginfk)
       WHERE slog.email LIKE '.$this->dbEscapeString($psEmail).' ';

     $oDbResult = $this->oDB->ExecuteQuery($sQuery);
     $bRead = $oDbResult->readFirst();
     if(!$bRead)
       return array();

     $asRole = array();
     while($bRead)
     {
       $asRole[$oDbResult->getFieldvalue('type')] = (int)$oDbResult->getFieldvalue('loginfk');
       $bRead = $oDbResult->readNext();
     }

     return $asRole;
   }
}