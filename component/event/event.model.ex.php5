<?php

class CEventModelEx extends CEventModel
{

  public function __construct()
  {
    parent::__construct();
    return true;
  }

  /**
   * Return the number of events matching the passed parameters
   * @param string $psUid
   * @param string $psType
   * @param string $psAction
   * @param integer $pnPk
   * @param string $psEventType
   * @return integer
   */
  public function getCountFromCpValues($asValues, $psEventType = '')
  {
    if(!assert('is_cpValues($asValues)'))
      return 0;

    $this->oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT count(distinct ev.eventpk) as nCount FROM `event_link` as el ';

    if($asValues[CONST_CP_TYPE] == CONST_AB_TYPE_COMPANY)
    {
      if(!empty($psEventType))
        $sQuery.= ' INNER JOIN event as ev ON (eventpk = eventfk AND type = "'.$psEventType.'")';
      else
        $sQuery.= ' INNER JOIN event as ev ON (eventpk = eventfk)';

      $oAB = CDependency::getComponentByName('addressbook');
      $asSQL = $oAB->getSharedSQL('event_profile', $asValues[CONST_CP_PK]);

      $sQuery.= $asSQL['join'];
      $sQuery.=  ' WHERE (el.cp_uid = "'.$asValues[CONST_CP_UID].'" AND el.cp_action = "'.$asValues[CONST_CP_ACTION].'" AND  el.cp_type = "'.$asValues[CONST_CP_TYPE].'" AND el.cp_pk = '.$asValues[CONST_CP_PK].')';
      $sQuery.=  ' OR ( el.cp_type = "ct" AND el.cp_pk = '.$asSQL['where'].') ';
    }
    else
    {
      if(!empty($psEventType))
        $sQuery.= ' INNER JOIN event as ev ON (eventpk = eventfk AND type = "'.$psEventType.'")';
      else
        $sQuery.= ' INNER JOIN event as ev ON (eventpk = eventfk)';

      $sQuery.= ' WHERE cp_uid = "'.$asValues[CONST_CP_UID].'" AND cp_action = "'.$asValues[CONST_CP_ACTION].'" AND cp_type="'.$asValues[CONST_CP_TYPE].'" AND cp_pk = '.$asValues[CONST_CP_PK];
    }

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return 0;

    return $oResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);
  }

  /**
   * Return an array containing the events matching the passed parameters
   * @param array containing  ($psUid,$psType, $psAction, $pnPk)
   * @param string $psEventType
   * @return array
   */
  public function getFromCpValues($pasValues, $psEventType = '', $psOrder = '', $pasExcludeType = array())
  {
    if(!assert('is_cpValues($pasValues)'))
      return array();

    $this->oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `event_link` as elin ';

    if($pasValues[CONST_CP_TYPE] == CONST_AB_TYPE_COMPANY)
    {
      if(!empty($psEventType))
        $sQuery.= ' INNER JOIN event as even ON (even.eventpk = elin.eventfk AND even.type = "'.$psEventType.'")';
      else
        $sQuery.= ' INNER JOIN event as even ON (even.eventpk = elin.eventfk)';

      $oAB = CDependency::getComponentByName('addressbook');
      $asSQL = $oAB->getSharedSQL('event_profile', $pasValues[CONST_CP_PK]);

      $sQuery.= $asSQL['join'];
      $sQuery.=  ' WHERE (elin.cp_uid = "'.$pasValues[CONST_CP_UID].'" AND elin.cp_action = "'.$pasValues[CONST_CP_ACTION].'" AND  elin.cp_type = "'.$pasValues[CONST_CP_TYPE].'" AND elin.cp_pk = '.$pasValues[CONST_CP_PK].')';
      $sQuery.=  ' OR ( elin.cp_type = "ct" AND elin.cp_pk = '.$asSQL['where'].') ';
    }
    else
    {
      if(!empty($psEventType))
        $sQuery.= ' INNER JOIN event as even ON (even.eventpk = elin.eventfk AND even.type = "'.$psEventType.'")';
      else
        $sQuery.= ' INNER JOIN event as even ON (even.eventpk = elin.eventfk)';

      $sQuery.= ' WHERE elin.cp_uid = "'.$pasValues[CONST_CP_UID].'" AND elin.cp_action = "'.$pasValues[CONST_CP_ACTION].'" AND elin.cp_type="'.$pasValues[CONST_CP_TYPE].'" AND elin.cp_pk = '.(int)$pasValues[CONST_CP_PK];
    }

    if(!empty($pasExcludeType))
    {
      $sQuery.= ' AND  even.type NOT IN ("'.implode('", "', $pasExcludeType).'") ';
    }

    if(empty($psOrder))
      $sQuery.= ' ORDER BY even.date_create DESC ';
    else
      $sQuery.= ' ORDER BY '.$psOrder;

    //dump($sQuery);
    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return array();

    return $oResult->getAll();
  }


  public function getEventsFromPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getEventsFromPk');

    $sQuery = 'SELECT * FROM event as ev ';
    $sQuery.= ' INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND el.eventfk = '.$pnPk.') ';
    $sQuery.= ' INNER JOIN shared_login as lo ON (lo.loginpk = ev.created_by) ';
    $sQuery.= ' LEFT JOIN event_reminder as evr ON (evr.eventfk = ev.eventpk) ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oResult;
  }

  public function getEventsFromContact($aValues, $psExtraSelect = '', $psExtraSql = '')
  {
    if(!assert('is_cpValues($aValues)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getEventsFromCompagny');

    $sQuery = 'SELECT ev.*, lo.*, el.*, GROUP_CONCAT(evr.loginfk) as reminder_recipient '.$psExtraSelect.' FROM event as ev ';
    $sQuery.= ' INNER JOIN event_link as el ON (el.eventfk = ev.eventpk AND el.cp_uid = '.$this->oDB->dbEscapeString($aValues[CONST_CP_UID]).' ';
    $sQuery.= ' AND el.cp_action = '.$this->oDB->dbEscapeString($aValues[CONST_CP_ACTION]).' AND el.cp_type = '.$this->oDB->dbEscapeString($aValues[CONST_CP_TYPE]).' ';
    $sQuery.= ' AND el.cp_pk = '.$this->oDB->dbEscapeString($aValues[CONST_CP_PK]).') ';
    $sQuery.= $psExtraSql;
    $sQuery.= ' INNER JOIN shared_login as lo ON (lo.loginpk = ev.created_by) ';
    $sQuery.= ' LEFT JOIN event_reminder as evr ON (evr.eventfk = ev.eventpk) ';
    $sQuery.= ' GROUP BY ev.eventpk ORDER BY ev.custom_type, ev.type, ev.date_create desc ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oResult)
      return new CDbResult();

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oResult;
  }

  public function getEventsFromCompany($aValues, $sExtraSelect = '', $sExtraSql = '')
  {
    if(!assert('is_cpValues($aValues)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getEventsFromCompany');

    $oAB = CDependency::getComponentByName('addressbook');
    $asSQL = $oAB->getSharedSQL('event_profile', (int)$aValues[CONST_CP_PK]);

    $sQuery = 'SELECT ev.*, lo.*, el.*, GROUP_CONCAT(evr.loginfk) as reminder_recipient '.$sExtraSelect.' '.$asSQL['select'].'FROM event as ev';
    $sQuery.= ' INNER JOIN event_link as el ON (el.eventfk = ev.eventpk )';
    $sQuery.= ' INNER JOIN shared_login as lo ON (lo.loginpk = ev.created_by)';
    $sQuery.= ' LEFT JOIN event_reminder as evr ON (evr.eventfk = ev.eventpk) ';
    $sQuery.= $asSQL['join'];
    $sQuery.= $sExtraSql;
    $sQuery.= ' WHERE (el.cp_uid = '.$this->oDB->dbEscapeString($aValues[CONST_CP_UID]).' AND el.cp_action = '.$this->oDB->dbEscapeString($aValues[CONST_CP_ACTION]).' AND  el.cp_type = '.$this->oDB->dbEscapeString($aValues[CONST_CP_TYPE]).' AND el.cp_pk = '.$this->oDB->dbEscapeString($aValues[CONST_CP_PK]).')';
    $sQuery.='  OR (el.cp_type = "ct" AND el.cp_pk = '.$asSQL['where'].')';
    $sQuery.= ' GROUP BY ev.eventpk ORDER BY ev.custom_type ASC, ev.type, ev.date_create DESC ';

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oResult)
      return new CDbResult();

    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oResult;
  }

  public function deleteFromCpValues($pasValues)
  {
    if(!assert('is_cpValues($pasValues)'))
     return false;

    if($pasValues[CONST_CP_TYPE] == CONST_AB_TYPE_CONTACT)
    {
      $oDbResult = $this->getEventsFromContact($pasValues);
    }
    elseif ($pasValues[CONST_CP_TYPE] == CONST_AB_TYPE_COMPANY)
    {
      $oDbResult = $this->getEventsFromCompany($pasValues);
    }
    else
      return false;

    $bRead = $oDbResult->readFirst();

    while($bRead)
    {
      $nFk = (int)$oDbResult->getFieldValue('eventpk');
      $this->deleteByFk($nFk,'event_link','event');
      $this->deleteByFk($nFk,'event_reminder','event');
      $this->deleteByPk($nFk,'event');

      $bRead = $oDbResult->readNext();
    }

    return true;
  }
}