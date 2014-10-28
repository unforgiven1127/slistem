<?php

class CLoginModelEx extends CLoginModel
{

  public function __construct()
  {
    parent::__construct();
    return true;
  }



  public function createMysqlView()
  {
    $sQuery = 'CREATE OR REPLACE VIEW shared_login AS ';
    $sQuery.= ' (SELECT loginpk, pseudo, birthdate, gender, courtesy, email, lastname, firstname,';
    $sQuery.= ' CONCAT(firstname,\' \', lastname) as fullname, phone, phone_ext, status, login_groupfk, is_admin,';
    $sQuery.= ' IF(LENGTH(pseudo)>0, pseudo, firstname) as friendly FROM login l LEFT JOIN login_group_member lg ON lg.loginfk = l.loginpk ) ';

    return $this->oDB->executeQuery($sQuery);
  }


  public function getUserList()
  {
    $sQuery = 'SELECT * FROM `login` WHERE is_admin <> 1 ORDER BY is_admin, status DESC, firstname asc';
    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();

    if(!$bRead)
        return new CDbResult;

    return $oResult;
  }

  // ----------------------------------------------
  // Switch an account to disable status when
  // its password hasn't been changed soon enough
  //
  // @param $psExpirationDate date
  // ---------------------------------------------

  public function disableExpiredAccounts($psExpirationDate)
  {
    if(!assert('is_date($psExpirationDate, \'Y-m-d H:i:s\')'))
      return false;

    $sQuery = 'UPDATE `login` SET status=2 WHERE date_passwd_changed<\''.$psExpirationDate.'\'';
    $bSuccess = $this->oDB->ExecuteQuery($sQuery);

    return $bSuccess;
  }

  // -----------------------------------------------
  // Get the list of user whom accounts are soon to
  // expire.
  //
  // @param $psExpirationDate date
  //
  // -----------------------------------------------

  public function getUsersToRemind($psExpirationDate)
  {
    if(!assert('is_date($psExpirationDate, \'Y-m-d H:i:s\')'))
      return false;

    $sQuery = 'SELECT * FROM login WHERE date_passwd_changed<\''.$psExpirationDate.'\'';
    $oUsers = $this->oDB->executeQuery($sQuery);

    return $oUsers;
  }

  // -----------------------------------------------
  // Checks if a record with the same field value
  // already exists
  // @param various $pvValue
  // @param string $psField
  // @param integer $pnLoginPk
  // -----------------------------------------------

  public function exists($psField, $pvValue, $pnLoginPk)
  {
    if(!assert('is_string($psField) && !empty($psField)'))
      return false;

    $sQuery = 'SELECT '.$psField.' FROM login WHERE '.$psField.'=\''.$pvValue.'\' AND loginpk!='.$pnLoginPk;
    $oResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oResult->readFirst();

    return $bRead;
  }


  /**
   * fetch user groups. If $pbGetAllGroups, return all the groups with the pk of the user in the one he is member of
   * @param integer $pnUserPk
   * @param boolean $pbGetAllGroups
   * @param boolean $pbAddInvisible
   * @return array
  */
  public function getUserGroup($pnUserPk = 0, $pbGetAllGroups = false, $pbAddInvisible = false, $panGroup = array())
  {
    if(!assert('is_integer($pnUserPk) && is_bool($pbGetAllGroups) && is_array($panGroup)'))
      return array();

    if(empty($pnUserPk))
      $sUserSql = '';
    else
      $sUserSql = ' AND lgm.loginfk = "'.$pnUserPk.'" ';

    $sQuery = 'SELECT lg.*, lgm.loginfk FROM login_group as lg ';

    if($pbGetAllGroups)
      $sQuery.= ' LEFT JOIN login_group_member as lgm ON (lgm.login_groupfk = lg.login_grouppk '.$sUserSql.')';
    else
      $sQuery.= ' INNER JOIN login_group_member as lgm ON (lgm.login_groupfk = lg.login_grouppk '.$sUserSql.')';

    $sQuery.= ' WHERE 1 ';

    if(!$pbAddInvisible)
      $sQuery.= ' AND lg.visible = 1 ';

    if(!empty($panGroup))
      $sQuery.= ' AND lg.login_grouppk IN ('.implode(',', $panGroup).') ';

    $sQuery.= ' ORDER BY lg.title ';



    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    $asGroup = array();

    while($bRead)
    {
      $asGroup[$oResult->getFieldValue('login_grouppk')] = $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asGroup;
  }

  /**
   * fetch user groups. If $pbGetAllGroups, return all the groups with the pk of the user in the one he is member of
   * @param integer $pnUserPk
   * @param boolean $pbGetAllGroups
   * @return array
  */
  public function getUsersAndGroup($panUserPk, $pbGroup = true, $pbCompact = false, $pbAddInvisible = false)
  {
    if(!assert(' (empty($panUserPk) || is_arrayOfInt($panUserPk)) && is_bool($pbGroup) && is_bool($pbCompact)'))
      return array();

    if($pbCompact)
    {
      $sSelect = ' l.loginpk ';
      $sOrder = '';
    }
    else
    {
      $sSelect = ' l.* ';
      $sOrder = ' ORDER BY lastname, firstname ';
    }
    $sWhere = 'WHERE 1 ';

    if($pbGroup)
      $sQuery = 'SELECT '.$sSelect.', GROUP_CONCAT(lg.title SEPARATOR ", ") as group_list FROM login as l ';
    else
      $sQuery = 'SELECT '.$sSelect.', lg.* FROM login as l ';

    $sQuery.= ' LEFT JOIN login_group_member as lgm ON (lgm.loginfk = l.loginpk)';

    if(!$pbAddInvisible)
      $sQuery.= ' LEFT JOIN login_group as lg ON (lg.login_grouppk = lgm.login_groupfk AND lg.visible = 1)';
    else
      $sQuery.= ' LEFT JOIN login_group as lg ON (lg.login_grouppk = lgm.login_groupfk)';

    if(!empty($panUserPk))
      $sWhere.= ' WHERE l.loginpk IN ('.implode(',', $panUserPk).')';

    if($pbGroup)
      $sQuery.= ' GROUP BY l.loginpk ';

    $sQuery.= $sOrder;


    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    $asGroup = array();

    while($bRead)
    {
      if($pbGroup)
        $asGroup[$oResult->getFieldValue('loginpk')] = $oResult->getData();
      else
        $asGroup[$oResult->getFieldValue('loginpk')][] = $oResult->getData();

      $bRead = $oResult->readNext();
    }

    return $asGroup;
  }


  public function saveUserGroups($pnLoginPk, $pasGroup)
  {
    if(!assert('is_key($pnLoginPk) && is_array($pasGroup)'))
      return false;

    $asGroup = $this->getUserGroup($pnLoginPk, true);

    $asSql = array();
    foreach($pasGroup as $nGroupFk)
    {
      if(!isset($asGroup[$nGroupFk]))
      {
        assert('false; // using login_group that doesn\'t exist. ');
        return false;
      }

      $asSql[] = ' ('.(int)$nGroupFk.', '.(int)$pnLoginPk.') ';
    }

    //checked for errors --> empty or not we empty the current groups
    $this->deleteByFk($pnLoginPk, 'login_group_member', 'loginfk');

    if(empty($asSql))
      return true;

    $sQuery = 'INSERT INTO login_group_member (login_groupfk, loginfk) VALUES '.implode(',', $asSql);
    $oResult = $this->oDB->ExecuteQuery($sQuery);

    if($oResult)
      return true;

    return false;
  }

  public function getGroups($pbWithUser = false, $pbCountUser = false, $psOrder = '', $pbAddInvisible = false)
  {
    if(!assert('is_bool($pbWithUser) && is_bool($pbCountUser) && is_bool($pbAddInvisible)'))

    //can't count user
    if(!$pbCountUser)
      $pbWithUser = true;


    if($pbCountUser)
      $sQuery = 'SELECT lg.*, COUNT(lgm.loginfk) as count FROM login_group as lg ';
    else
      $sQuery = 'SELECT * FROM login_group as lg ';


    if($pbWithUser)
    {
      $sQuery.= 'LEFT JOIN login_group_member as lgm ON (lgm.login_groupfk = lg.login_grouppk) ';

      if(!$pbCountUser)
        $sQuery.= 'LEFT JOIN login as l ON (l.loginpk = lgm.loginfk) ';
    }

    if(!$pbAddInvisible)
      $sQuery.= ' WHERE lg.visible = 1 ';

    if($pbCountUser)
      $sQuery.= ' GROUP BY lg.login_grouppk ';

    if($psOrder)
      $sQuery.= ' '.$psOrder;
    else
      $sQuery.= ' ORDER BY system DESC, lg.title ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return array();

    $asResult = array();
    while($bRead)
    {
      $asResult[(int)$oResult->getFieldValue('login_grouppk')] = $oResult->getData();
      $bRead = $oResult->readNext();
    }

    return $asResult;
  }


  public function getSystemHistory($pasParams, $psLimit = '')
  {
    if(!assert('is_array($pasParams) && !empty($pasParams)'))
      return array();

    $asWhere = array();

    if(isset($pasParams['date_start']) && !empty($pasParams['date_start']))
      $asWhere[]= ' lshi.date >= '.$this->oDB->dbEscapeString($pasParams['date_start']);

    if(isset($pasParams['date_end']) && !empty($pasParams['date_end']))
      $asWhere[] = ' lshi.date <= '.$this->oDB->dbEscapeString($pasParams['date_end']);

    if(isset($pasParams['component']) && !empty($pasParams['component']))
      $asWhere[] = ' lshi.component = '.$this->oDB->dbEscapeString($pasParams['component']);


    if(isset($pasParams['cp_key']) && !empty($pasParams['cp_key']))
    {
      $asCpWhere = array();

      //restrict logs from specific tables
      if(isset($pasParams['cp_key']['table']) && !empty($pasParams['cp_key']['table']))
      {
        if(is_array($pasParams['cp_key']['table']))
        {
          $asWhere[]= ' lshi.table IN ("'.implode('","', $pasParams['cp_key']['table']).'") ';
        }
        else
          $asWhere[]= ' lshi.table = '.$this->oDB->dbEscapeString($pasParams['cp_key']['table']);
      }

      //fetch logs from 1 or multiple components
      if(isset($pasParams['cp_key']['uids']) && is_array($pasParams['cp_key']['uids']) && !empty($pasParams['cp_key']['uids']))
      {
        $asWhere[]= ' lshi.cp_uid IN ("'.implode('","', $pasParams['cp_key']['uids']).'") ';
      }
      elseif(isset($pasParams['cp_key'][CONST_CP_UID]) && !empty($pasParams['cp_key'][CONST_CP_UID]))
      {
        $asCpWhere[] = ' lshi.cp_uid = '.$this->oDB->dbEscapeString($pasParams['cp_key'][CONST_CP_UID]);
      }

      if(isset($pasParams['cp_key'][CONST_CP_ACTION]) && !empty($pasParams['cp_key'][CONST_CP_ACTION]))
        $asCpWhere[] = ' lshi.cp_action = '.$this->oDB->dbEscapeString($pasParams['cp_key'][CONST_CP_ACTION]);

      if(isset($pasParams['cp_key'][CONST_CP_TYPE]) && !empty($pasParams['cp_key'][CONST_CP_TYPE]))
        $asCpWhere[] = ' lshi.cp_type = '.$this->oDB->dbEscapeString($pasParams['cp_key'][CONST_CP_TYPE]);

      if(isset($pasParams['cp_key'][CONST_CP_PK]) && !empty($pasParams['cp_key'][CONST_CP_PK]))
        $asCpWhere[] = ' lshi.cp_pk = '.$this->oDB->dbEscapeString($pasParams['cp_key'][CONST_CP_PK]);

      $asWhere[] = implode(' AND ', $asCpWhere);
    }

    if(isset($pasParams['loginfk']) && !empty($pasParams['loginfk']))
      $asWhere[] = ' lshi.userfk = '.$this->oDB->dbEscapeString($pasParams['loginfk']);

    if(isset($pasParams['logins']) && !empty($pasParams['logins']))
      $asWhere[] = ' lshi.userfk IN ('.$pasParams['logins'].')';

    if(!assert('!empty($asWhere)'))
      return new CDbResult();

    if(isset($pasParams['operator']) && $pasParams['operator'] == 'OR')
      $sQuery = 'SELECT * FROM login_system_history as lshi WHERE '.implode(' OR ', $asWhere);
    else
      $sQuery = 'SELECT * FROM login_system_history as lshi WHERE '.implode(' AND ', $asWhere);

    $sQuery.= ' AND action IS NOT NULL
    AND action <> ""
    ORDER BY `date` DESC, action ';

    if(!empty($psLimit))
      $sQuery.= ' LIMIT '.$psLimit;

    //echo $sQuery;
    return $this->oDB->ExecuteQuery($sQuery);
  }




}