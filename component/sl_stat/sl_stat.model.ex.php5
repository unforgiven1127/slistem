<?php

class CSl_statModelEx extends CSl_statModel
{
  public function __construct()
  {
    parent::__construct();
    return true;
  }



  public function getSicChartNew($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();


    $sQuery = 'SELECT count(*) as nCount, created_by, DATE_FORMAT(date_created, "%Y-%m") as sMonth
      FROM sl_candidate
      WHERE created_by IN ('.implode(',', $panUserPk).')
      AND date_created >= '.$this->oDB->dbEscapeString($psDateStart).' AND date_created < '.$this->oDB->dbEscapeString($psDateEnd).'

      GROUP BY sMonth, created_by
      ORDER BY sMonth ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('created_by')][$oDbResult->getFieldValue('sMonth')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function getSicChartMet($panUserPk, $psDateStart, $psDateEnd, $group = 'researcher')
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $group_switch = 'created_by';

    if ($group == 'consultant')
      $group_switch = 'attendeefk';

    //no weight difference between phone and live meetings
    $sQuery = 'SELECT count(sl_meetingpk) as nCount, attendeefk, DATE_FORMAT(date_met, "%Y-%m") as sMonth, meeting_done
      FROM sl_meeting
      WHERE '.$group_switch.' IN ('.implode(',', $panUserPk).')
      AND date_met BETWEEN '.$this->oDB->dbEscapeString($psDateStart).' AND '.$this->oDB->dbEscapeString($psDateEnd).'

      GROUP BY attendeefk
      ORDER BY sMonth';

    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('attendeefk')][$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('meeting_done')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function getSicChartPlay($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $sQuery = 'SELECT count(*) as nCount, created_by, DATE_FORMAT(date_created, "%Y-%m") as sMonth
      FROM sl_position_link
      WHERE created_by IN ('.implode(',', $panUserPk).')
      AND date_created >= '.$this->oDB->dbEscapeString($psDateStart).' AND date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
      AND status >= 1 AND status < 150

      GROUP BY positionfk, candidatefk, created_by, sMonth
      ORDER BY sMonth ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      if(!isset($asData[(int)$oDbResult->getFieldValue('created_by')][$oDbResult->getFieldValue('sMonth')]))
        $asData[(int)$oDbResult->getFieldValue('created_by')][$oDbResult->getFieldValue('sMonth')] = 0;

      $asData[(int)$oDbResult->getFieldValue('created_by')][$oDbResult->getFieldValue('sMonth')]+= (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function getSicChartPosition($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //Newly active positions, having their first CCM this month:
    // position created by me in the last 30 days (covers end of moth positions)
    // for which I've put a candidate in CCMX

    //select all the positions -30 days from start/end dates
    $sQuery = 'SELECT count(sl_positionpk) as nCount, spos.created_by, DATE_FORMAT(spos.date_created, "%Y-%m") as sMonth
      FROM sl_position as spos
      INNER JOIN sl_position_link as spli ON (spli.positionfk = spos.sl_positionpk
      AND spli.created_by = spos.created_by
      AND spli.date_created <= DATE_ADD(spos.date_created, INTERVAL 30 DAY)
      AND spli.status = 51)

      WHERE spos.created_by IN ('.implode(',', $panUserPk).')
        AND spos.date_created >= "'.date('Y-m-d', strtotime('-30 days', strtotime($psDateStart))).'"
        AND spos.date_created <= "'.$psDateEnd.'"

      GROUP BY spli.created_by, sMonth
      ORDER BY sMonth';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('created_by')][$oDbResult->getFieldValue('sMonth')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }
    return $asData;
  }

  public function getSicChartTarget($panUserPk)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $sQuery = 'SELECT * FROM sl_stat_setting  WHERE loginfk IN ('.implode(',', $panUserPk).') ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('loginfk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }


  public function getPiplelinePieData($panUserPk, $psDateStart, $psDateEnd, $pnStatus = 0, $pbTotal = true)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    if($pnStatus > 0)
      $sSqlStatus = ' AND status <= '.$pnStatus;
    else
      $sSqlStatus = '';

    if($pbTotal)
    {
      $sQuery = 'SELECT DISTINCT(CONCAT(candidatefk,"_", positionfk)), count(*) as nCount, status, DATE_FORMAT(spli.date_created, "%Y-%m") as sMonth, created_by
        FROM sl_position_link as spli
        WHERE created_by IN ('.implode(',', $panUserPk).')
        AND date_created >= '.$this->oDB->dbEscapeString($psDateStart).' AND date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
        AND active = 1 '.$sSqlStatus;
    }
    else
    {
      $sQuery = 'SELECT MAX(sl_position_linkpk) as pk FROM sl_position_link
          WHERE created_by IN ('.implode(',', $panUserPk).')
          AND date_created >= '.$this->oDB->dbEscapeString($psDateStart).'
          AND date_created < '.$this->oDB->dbEscapeString($psDateEnd).' '.$sSqlStatus.'
          GROUP BY created_by, candidatefk, positionfk, DATE_FORMAT(date_created, "%Y-%m") ';

      $oDbResult = $this->oDB->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array();

      $sIds = '0';
      while($bRead)
      {
        $sIds.= ','.$oDbResult->getFieldValue('pk');
        $bRead = $oDbResult->readNext();
      }

      $sQuery = 'SELECT count(*) as nCount,
        status, DATE_FORMAT(spli.date_created, "%Y-%m") as sMonth, created_by
        FROM sl_position_link as spli
        WHERE created_by IN ('.implode(',', $panUserPk).')
        AND date_created >= '.$this->oDB->dbEscapeString($psDateStart).'
        AND date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
        AND spli.sl_position_linkpk IN('.$sIds.')
        '.$sSqlStatus;
    }

    $sQuery.= ' GROUP BY created_by, candidatefk, positionfk, status, sMonth
      ORDER BY sMonth DESC ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      if(!isset($asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')]))
        $asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')] = 0;

      $asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')]+= (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function getPiplelineCandidate($panUserPk, $psDateStart, $psDateEnd, $pnMaxStatus = 100)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //keep spli.* at the end to overwrite eventual created_by fields
    $sQuery = 'SELECT DISTINCT(candidatefk), spli.status as position_status, spde.title as position_title, scan.*,  spli.*,
      scan.created_by as candi_created
      FROM sl_position_link as spli
      INNER JOIN sl_position_detail as spde ON (spde.positionfk = spli.positionfk)
      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)

      WHERE spli.created_by IN ('.implode(',', $panUserPk).')
      AND spli.date_created >= '.$this->oDB->dbEscapeString($psDateStart).' AND spli.date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
      AND spli.active = 1
      AND spli.status <= '.$pnMaxStatus.'
      ORDER BY spli.date_created ASC ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }


  public function getPiplelineDetails($panUserPk, $psDateStart, $psDateEnd, $pnMaxStatus = 200)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $sQuery = 'SELECT DISTINCT(CONCAT(candidatefk || "_" || spli.positionfk)), spli.*, spli.status as position_status, spde.title as position_title,
      scan.sex, scan.firstname, scan.lastname, scom.sl_companypk, scom.name as company_name
      FROM sl_position_link as spli
      INNER JOIN sl_position_detail as spde ON (spde.positionfk = spli.positionfk)
      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)

      INNER JOIN sl_position as spos ON (spos.sl_positionpk = spli.positionfk)
      INNER JOIN sl_company as scom ON (scom.sl_companypk = spos.companyfk)

      WHERE spli.created_by IN ('.implode(',', $panUserPk).')
      AND spli.date_created >= '.$this->oDB->dbEscapeString($psDateStart).' AND spli.date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
      AND spli.status <= '.$pnMaxStatus.'
      ORDER BY spli.date_created DESC ';

    //echo $sQuery;
    $asCandidate = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData = $oDbResult->getData();
      $asData['created_by'] = (int)$asData['created_by'];

      $sKey = $asData['positionfk'].'_'.$asData['candidatefk'];
      $sStatus = $asData['position_status'];


      $asCandidate[$asData['created_by']][$sKey]['data'] = $asData;

      if(!isset($asCandidate[$asData['created_by']][$sKey]['status'][$sStatus]) || $asCandidate[$asData['created_by']][$sKey]['status'][$sStatus] < $asData['date_created'])
        $asCandidate[$asData['created_by']][$sKey]['status'][$sStatus] = $asData['date_created'];

      $bRead = $oDbResult->readNext();
    }

    return $asCandidate;
  }

  public function getPiplelineDetailData($panUserPk, $psDateStart, $psDateEnd, $pnMaxStatus = 200)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();



     $sQuery = 'SELECT MAX(spli.sl_position_linkpk) as sl_position_linkpk
        FROM sl_position_link as spli

        WHERE spli.created_by IN ('.implode(',', $panUserPk).')
        AND spli.date_created >= '.$this->oDB->dbEscapeString($psDateStart).'
        AND spli.date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
        AND spli.status <= '.$pnMaxStatus.'
        GROUP BY spli.positionfk, spli.candidatefk';

    //echo $sQuery;
    $anLinkPk = array();
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $anLinkPk[] = (int)$oDbResult->getFieldValue('sl_position_linkpk');
      $bRead = $oDbResult->readNext();
    }

    if(empty($anLinkPk))
      return array();

    $sQuery = 'SELECT spli.*, spli.status, spli.date_created, (UNIX_TIMESTAMP(spli.date_created) * 1000) as chartTime,
      scan.sex, scan.firstname, scan.lastname
      FROM sl_position_link as spli

      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)
      WHERE spli.sl_position_linkpk IN ('.implode(',', $anLinkPk).')

      GROUP BY spli.created_by, spli.positionfk, spli.candidatefk';

    //WHERE spli.sl_position_linkpk IN ('.implode(',', $anLinkPk).')

    //echo $sQuery;
    $asData = array();
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('created_by')][(int)$oDbResult->getFieldValue('status')][$oDbResult->getFieldValue('chartTime')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }



  public function getPositionPipeData($panUserPk, $psDateStart, $psDateEnd, $pnMaxStatus = 200)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $sQuery = 'SELECT spli.*, (UNIX_TIMESTAMP(spli.date_created) * 1000) as chartTime,
      MAX(spli.status) as status
      FROM sl_position_link as spli

      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)
      INNER JOIN sl_position_detail as spde ON (spde.positionfk = spli.positionfk)

      WHERE spli.date_created >= '.$this->oDB->dbEscapeString($psDateStart).'
      AND spli.date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
      AND spli.status > 0 AND spli.status < 200
      AND spli.active = 1

      GROUP BY spli.positionfk
      ORDER BY spli.sl_position_linkpk ';

    //echo $sQuery;
    $asData = array();
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('status')][$oDbResult->getFieldValue('chartTime')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }


  public function getKpiSetVsMet($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    if(!assert('is_arrayOfInt($user_ids)'))
      return array();

    $group_switch = 'created_by';

    if ($group == 'consultant')
      $group_switch = 'attendeefk';

    $query = 'SELECT candidatefk, created_by, date_created, date_met, attendeefk, meeting_done';
    $query .= ' FROM sl_meeting';
    $query .= ' ORDER BY '.$group_switch;

    $data = array();
    $flip_user_ids = array_flip($user_ids);
    $meeting_array = $met_candidates_array = array();

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();
    while($read)
    {
      $temp = $db_result->getData();

      $meeting_array[] = $temp;

      if (!isset($met_candidates_array[$temp['candidatefk']]))
      {
        $met_candidates_array[$temp['candidatefk']]['times_met'] = 0;
        $met_candidates_array[$temp['candidatefk']]['oldest_meeting'] = date('Y-m-d');
      }

      if ((int)$temp['meeting_done'] > 0)
      {
        $met_candidates_array[$temp['candidatefk']]['times_met'] += 1;
        if (strtotime($met_candidates_array[$temp['candidatefk']]['oldest_meeting']) > strtotime($temp['date_created']))
          $met_candidates_array[$temp['candidatefk']]['oldest_meeting'] = $temp['date_created'];
      }

      $read = $db_result->readNext();
    }

    foreach ($meeting_array as $meeting)
    {
      if (strtotime($meeting['date_created']) >= strtotime($start_date)
        && strtotime($meeting['date_created']) <= strtotime($end_date)
        && isset($flip_user_ids[$meeting[$group_switch]]))
      {
        if (!isset($data[$meeting[$group_switch]]))
        {
          $data[$meeting[$group_switch]] = array('set' => 0, 'met' => 0, 'set_meeting_info' => array(),
            'met_meeting_info' => array());
        }

        $data[$meeting[$group_switch]]['set'] += 1;
        $data[$meeting[$group_switch]]['set_meeting_info'][] = array('candidate' => $meeting['candidatefk'],
          'date' => $meeting['date_created']);
      }

      if (strtotime($meeting['date_met']) >= strtotime($start_date)
        && strtotime($meeting['date_met']) <= strtotime($end_date)
        && isset($flip_user_ids[$meeting[$group_switch]]))
      {
        if (!isset($data[$meeting[$group_switch]]))
        {
          $data[$meeting[$group_switch]] = array('set' => 0, 'met' => 0, 'set_meeting_info' => array(),
            'met_meeting_info' => array());
        }

        $temp_validation_date = date('Y-m', strtotime($met_candidates_array[$meeting['candidatefk']]['oldest_meeting']));

        if ((int)$meeting['meeting_done'] > 0
          && ($met_candidates_array[$meeting['candidatefk']]['times_met'] <= 1 ||
          ($temp_validation_date >= date('Y-m', strtotime($start_date)) &&
            $temp_validation_date <= date('Y-m', strtotime($end_date))) ))
        {
          $data[$meeting[$group_switch]]['met'] += 1;
          $data[$meeting[$group_switch]]['met_meeting_info'][] = array('candidate' => $meeting['candidatefk'],
            'date' => $meeting['date_met']);

          $met_candidates_array[$meeting['candidatefk']]['oldest_meeting'] = '1950-05-05';
        }
      }
    }

    return $data;
  }

  public function getKpiInPlay($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //no weight difference between phone and live meetings
    /*$sQuery = 'SELECT count(*) as nCount, spli.created_by
      FROM sl_position_link as spli
      WHERE spli.created_by IN ('.implode(',', $panUserPk).')
      AND spli.status > 0
      AND spli.status < 150
      AND spli.active = 1
      AND spli.in_play = 1

      AND spli.date_created >= "'.$psDateStart.'"
      AND spli.date_created < "'.$psDateEnd.'"

      GROUP BY created_by
      ORDER BY nCount DESC ';*/


    $sQuery = 'SELECT count(*) as nCount, in_play.created_by
      FROM
      (
        SELECT * FROM sl_position_link as spli
        WHERE spli.created_by IN ('.implode(',', $panUserPk).')
        AND spli.status > 0
        AND spli.status < 150
        AND spli.in_play = 1

        AND spli.date_created >= "'.$psDateStart.'"
        AND spli.date_created < "'.$psDateEnd.'"
        GROUP BY spli.positionfk, spli.candidatefk
        ) as in_play

      GROUP BY in_play.created_by
      ORDER BY nCount DESC ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('created_by')] = (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }




  /**
   * Revenue board
   *
   * @param type $anUser
   * @param type $sDateStart
   * @param type $sDateEnd
   * @return array
   */
  public function getPlacementData($panUserPk, $psDateStart, $psDateEnd, $psLocation = 'all', $psGroupBy = '')
  {
    /*if(!assert('is_arrayOfInt($panUserPk)'))
      return array();*/

    switch($psLocation)
    {
      case 'all': $sLocationSql = '';  break;

      default:
        $sLocationSql = ' AND location = "'.substr(strtolower($psLocation), 0, 3).'" ';
        break;
    }



    if(empty($psGroupBy) || $psGroupBy == 'user')
    {
      $sGroup = ' GROUP BY sppa.loginfk ';
    }
    elseif($psGroupBy == 'location')
    {
      $sGroup = ' GROUP BY spla.location ';
    }
    elseif($psGroupBy == 'team')
    {
      $sGroup = ' GROUP BY lgme.login_groupfk ';
    }


    //sppa.loginfk IN ('.implode(',', $panUserPk).')

    $sQuery = 'SELECT DISTINCT(sppa.sl_placement_paymentpk), sppa.*, SUM(sppa.amount) as revenue_signed,
      SUM(IF(spla.date_paid, sppa.amount, 0)) as revenue_paid, spla.location,
      SUM(sppa.placed) as revenue_placed,
      lgme.login_groupfk as groupfk

      FROM sl_placement_payment as sppa
      INNER JOIN sl_placement as spla ON(spla.sl_placementpk = sppa.placementfk)
      LEFT JOIN login_group_member as lgme ON (lgme.loginfk = sppa.loginfk AND lgme.login_groupfk < 100 )

      WHERE  spla.date_signed >= "'.$psDateStart.'"
      AND spla.date_signed < "'.$psDateEnd.'"
       '.$sLocationSql.'
       '.$sGroup.'
      ORDER BY revenue_signed DESC ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('loginfk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }
  /**
   * Revenue board
   *
   * @param type $anUser
   * @param type $sDateStart
   * @param type $sDateEnd
   * @return array
   */
  public function getContributorData($panUserPk, $psDateStart, $psDateEnd, $psLocation = 'all')
  {
    /*if(!assert('is_arrayOfInt($panUserPk)'))
      return array();*/

    switch($psLocation)
    {
      case 'all': $sLocationSql = '';  break;

      default:
        $sLocationSql = ' AND location = "'.substr(strtolower($psLocation), 0, 3).'" ';
        break;
    }

    //Get all the [ positionfk, candidatefk ] active during the requested period
    $sQuery = 'SELECT positionfk, candidatefk
      FROM sl_position_link as spli
      WHERE spli.date_created >= "'.$psDateStart.'" AND spli.date_created < "'.$psDateEnd.'"
      GROUP BY positionfk, candidatefk';

    //dump($sQuery);

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asLink = array();
    while($bRead)
    {
      //dump('pos: '.$oDbResult->getFieldValue('positionfk').' - candi '.$oDbResult->getFieldValue('candidatefk'));
      $asLink[] = '(spcr.positionfk = '.$oDbResult->getFieldValue('positionfk').' AND spcr.candidatefk = '.$oDbResult->getFieldValue('candidatefk').')';
      $bRead = $oDbResult->readNext();
    }

    //get the contributors for each [ positionfk, candidatefk ] found above
    $sQuery = 'SELECT spcr.*, MAX(spli.status) as status

      FROM sl_position_credit as spcr
      INNER JOIN sl_position_link as spli ON (spli.positionfk = spcr.positionfk AND spli.candidatefk = spcr.candidatefk)

      WHERE spcr.loginfk IN ('.implode(',', $panUserPk).')
      AND( '.implode(' OR ',$asLink).' )
        AND spli.status < 150

      GROUP BY spcr.positionfk, spcr.candidatefk, spcr.loginfk
      ';

    //spcr.loginfk IN ('.implode(',', $panUserPk).')
    //dump(implode(',', $panUserPk));

    //dump($sQuery);
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asData = array();
    while($bRead)
    {
      $asContib = $oDbResult->getData();
      //dump($asContib);

      if((int)$asContib['status'] == 101)
      {
        $nPlacement = 1;
        $nActive = 0;
      }
      else
      {
        $nPlacement = 0;
        $nActive = 1;
      }

      set_array($asData[(int)$asContib['loginfk']]['active'], 0);
      set_array($asData[(int)$asContib['loginfk']]['placement'], 0);

     $asData[(int)$asContib['loginfk']]['active']+= $nActive;
     $asData[(int)$asContib['loginfk']]['placement']+= $nPlacement;

      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }


  public function getAnalystCandidatesSummary($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //no weight difference between phone and live meetings
    $sQuery = 'SELECT * FROM sl_candidate
      WHERE date_created >= "'.$psDateStart.'"
        AND date_created < "'.$psDateEnd.'"
        ORDER BY date_created ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('loginfk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }
  public function getMeetings($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //no weight difference between phone and live meetings
    $sQuery = 'SELECT * FROM sl_meeting as smee
      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = smee.candidatefk)
      WHERE date_meeting >= "'.$psDateStart.'"
        AND date_meeting < "'.$psDateEnd.'"
        AND  (smee.created_by IN ('.implode(',', $panUserPk).') OR  smee.attendeefk IN ('.implode(',', $panUserPk).') )
        ORDER BY date_meeting ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('loginfk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }


  public function getNewCandidates($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //no weight difference between phone and live meetings
    $sQuery = 'SELECT * FROM  sl_candidate as scan
      LEFT JOIN sl_candidate_profile as scpr ON (scpr.candidatefk = scan.sl_candidatepk)
      WHERE scan.date_created >= "'.$psDateStart.'"
      AND scan.date_created < "'.$psDateEnd.'"
      AND scan.created_by IN ('.implode(',', $panUserPk).')
      ORDER BY scan.date_created ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[(int)$oDbResult->getFieldValue('sl_candidatepk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function getAnalystPieData($panUserPk, $psDateStart, $psDateEnd, $pnStatus = 0)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    if($pnStatus > 0)
      $sSqlStatus = ' AND status <= '.$pnStatus;
    else
      $sSqlStatus = '';

    $sIds = implode(',', $panUserPk);

    $sQuery = 'SELECT DISTINCT(CONCAT(spli.candidatefk,"_", spli.positionfk)), count(*) as nCount, spli.status,
      DATE_FORMAT(spli.date_created, "%Y-%m") as sMonth, spli.created_by
      FROM sl_position_link as spli
      INNER JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)
      WHERE
      (spli.created_by IN ('.$sIds.') OR scan.created_by IN ('.$sIds.') )
      AND spli.date_created >= '.$this->oDB->dbEscapeString($psDateStart).'
      AND spli.date_created < '.$this->oDB->dbEscapeString($psDateEnd).'
      AND spli.active = 1 '.$sSqlStatus;

    $sQuery.= ' GROUP BY created_by, candidatefk, positionfk, status, sMonth
      ORDER BY sMonth DESC ';

    //echo $sQuery;
    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      if(!isset($asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')]))
        $asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')] = 0;

      $asData[$oDbResult->getFieldValue('sMonth')][(int)$oDbResult->getFieldValue('status')]+= (int)$oDbResult->getFieldValue('nCount');
      $bRead = $oDbResult->readNext();
    }

    return $asData;
  }

  public function get_revenue_data($request_date = '', $location = '')
  {
    $revenue_data = $revenue_data_raw = array();

    if (empty($request_date))
      $request_date = date('Y');

    $date_start = $request_date.'-01-01';
    $date_end = $request_date.'-12-31';

    $query = 'SELECT id, amount, location, status, refund_amount, currency ';
    $query .= 'FROM revenue ';
    $query .= 'WHERE date_due BETWEEN "'.$date_start.'" AND "'.$date_end.'"';

    if (!empty($location))
      $query = ' AND location = "'.$location.'"';

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();
    if ($read)
    {
      while($read)
      {
        $row = $db_result->getData();
        $revenue_data_raw[$row['id']] = $row;

        $read = $db_result->readNext();
      }

      $array_for_printing = $revenue_data_raw;

      $query = 'SELECT revenue_member.*, login.id, login.firstname, login.lastname, login.status, sl_nationality.shortname AS nationality ';
      $query .= 'FROM revenue_member ';
      $query .= 'LEFT JOIN login ON revenue_member.loginpk = login.loginpk AND login.position LIKE "Consultant"';
      $query .= 'LEFT JOIN sl_nationality ON login.nationalityfk = sl_nationality.sl_nationalitypk';

      $db_result = $this->oDB->executeQuery($query);
      $read = $db_result->readFirst();

      $revenue_data['former'] = array('name' => 'Former', 'nationality' => 0, 'do_not_count_placed' => array(), 'total_amount' => 0,
        'placed' => 0, 'paid' => 0, 'signed' => 0, 'team' => 'Not defined');

      while($read)
      {
        $row = $db_result->getData();

        if ($row['id'] == 'bizreach' || $row['id'] == 'othercollab' || empty($row['id']))
        {
          $read = $db_result->readNext();
          continue;
        }

        $array_for_printing[$row['revenue_id']]['members'][$row['loginpk']] = $row;

        if (isset($revenue_data_raw[$row['revenue_id']]))
        {
          $current_revenue_info = $revenue_data_raw[$row['revenue_id']];

          if (!$row['status'])
          {
            $user_id = 'former';

            if (empty($revenue_data[$user_id]['placed']))
              $revenue_data[$user_id]['placed'] = 0;

            if (!isset($revenue_data[$user_id]['do_not_count_placed'][$row['loginpk']]))
            {
              $temp_placed = $this->get_placement_number_revenue(array($row['loginpk']), $date_start, $date_end);
              $revenue_data[$user_id]['placed'] += $temp_placed[$row['loginpk']]['placed'];
            }

            $revenue_data[$user_id]['do_not_count_placed'][$row['loginpk']] = '';
          }
          else
          {
            $user_id = $row['loginpk'];

            if (empty($revenue_data[$user_id]['placed']))
              $revenue_data[$user_id]['placed'] = 0;

            if (empty($revenue_data[$user_id]['nationality']))
              $revenue_data[$user_id]['nationality'] = $row['nationality'];

            if (empty($revenue_data[$user_id]['placed']))
            {
              $temp_placed = $this->get_placement_number_revenue(array($user_id), $date_start, $date_end);
              $revenue_data[$user_id]['placed'] += $temp_placed[$user_id]['placed'];
            }

            if (empty($revenue_data[$user_id]['name']))
                $revenue_data[$user_id]['name'] = substr($row['firstname'], 0, 1).'. '.$row['lastname'];
          }

          if (!isset($revenue_data[$user_id]['paid']))
            $revenue_data[$user_id]['paid'] = $revenue_data[$user_id]['signed'] = $revenue_data[$user_id]['total_amount'] = 0;

          if (empty($revenue_data[$user_id]['team']))
            $revenue_data[$user_id]['team'] = $this->get_user_team($user_id);

          if (strtolower($row['user_position']) == 'consultant')
          {
            switch ($current_revenue_info['status'])
            {
              case 'paid':
              case 'refund':
              case 'retainer':
                $revenue_data[$user_id]['paid'] += ($current_revenue_info['amount'] - $current_revenue_info['refund_amount']) * ($row['percentage'] / 100);
                break;
            }

            $revenue_data[$user_id]['signed'] += $current_revenue_info['amount'] * ($row['percentage'] / 100);

            if ($row['status'])
              $revenue_data[$user_id]['total_amount'] += ($current_revenue_info['amount'] - $current_revenue_info['refund_amount']) * ($row['percentage'] / 100);
          }
        }
        $read = $db_result->readNext();
      }

      uasort($revenue_data, sort_multi_array_by_value('total_amount', 'reverse'));
    }
    return $revenue_data;
  }

  public function get_placement_number_revenue($user_ids, $date_start = '', $date_end = '')
  {
    $placements = array();

    if (empty($date_start))
      $date_start = date('Y').'-01-01';

    if (empty($date_end))
      $date_end = date('Y').'-12-31';

    $query = 'SELECT position, candidate, closed_by';
    $query .= ' FROM revenue';
    $query .= ' WHERE closed_by IN ('.implode(',', $user_ids).') AND placement_count = "yes"';
    $query .= ' AND date_due BETWEEN "'.$date_start.'" AND "'.$date_end.'"';
    $query .= ' ORDER BY closed_by';

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      $placements[$row['closed_by']]['candidates'][$row['candidate']] = $row['candidate'];

      $read = $db_result->readNext();
    }

    foreach ($user_ids as $value)
    {
      if (!empty($placements[$value]))
        $placements[$value]['placed'] = count($placements[$value]['candidates']);
      else
      {
        $placements[$value]['placed'] = 0;
        $placements[$value]['candidates'] = array();
      }
    }

    return $placements;
  }

  private function get_user_team($user_id)
  {
    $group = 'Not defined';
    $raw_info = array();
    if ($user_id != 'former')
    {
      $query = 'SELECT login_group_member.login_groupfk, login_group.title ';
      $query .= 'FROM login_group_member ';
      $query .= 'LEFT JOIN login_group ON login_group_member.login_groupfk = login_group.login_grouppk ';
      $query .= 'WHERE login_group_member.loginfk = "'.$user_id.'"';

      $db_result = $this->oDB->executeQuery($query);
      $read = $db_result->readFirst();

      while ($read)
      {
        $row = $db_result->getData();

        if ($row['login_groupfk'] >= 1 && $row['login_groupfk'] <= 10)
        {
          $group = $row['title'];
          break;
        }

        $read = $db_result->readNext();
      }
    }

    return $group;
  }

  public function get_ccm_data($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    $ccm_data = array();

    if ($group == 'consultant')
    {
      $query = 'SELECT positionfk, candidatefk, created_by, status, date_created as ccm_create_date';
      $query .= ' FROM sl_position_link';
      $query .= ' WHERE created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' AND status >= 51';
    }
    else
    {
      $query = 'SELECT sl_meeting.date_met, sl_position_link.positionfk, sl_position_link.candidatefk, sl_position_link.status,';
      $query .= ' sl_position_link.date_created as ccm_create_date, sl_meeting.created_by';
      $query .= ' FROM sl_meeting';
      $query .= ' INNER JOIN sl_position_link ON sl_meeting.candidatefk = sl_position_link.candidatefk';
      $query .= ' AND sl_position_link.status >= 51';
      $query .= ' AND sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' WHERE sl_meeting.created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND sl_meeting.meeting_done = 1';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      if (($group == 'researcher' && $row['status'] == 51) ||
        ($group == 'consultant' && $row['status'] > 51))
      {
        if ($group == 'consultant')
          $status = $row['status'];
        else
          $status = 51;

        if (isset($resume_sent_info[$row['created_by']][$status][$row['candidatefk']]))
        {
          $read = $db_result->readNext();
          continue;
        }
        else
          $resume_sent_info[$row['created_by']][$status][$row['candidatefk']] = '';
      }

      if (!isset($ccm_data[$row['created_by']]['ccm1']))
      {
        $ccm_data[$row['created_by']]['ccm1'] = 0;
        $ccm_data[$row['created_by']]['ccm1_done'] = 0;
        $ccm_data[$row['created_by']]['ccm2'] = 0;
        $ccm_data[$row['created_by']]['ccm2_done'] = 0;
        $ccm_data[$row['created_by']]['mccm'] = 0;
        $ccm_data[$row['created_by']]['mccm_done'] = 0;
        $ccm_data[$row['created_by']]['ccm_info']['ccm1'] = array();
        $ccm_data[$row['created_by']]['ccm_info']['ccm2'] = array();
        $ccm_data[$row['created_by']]['ccm_info']['mccm'] = array();
      }

      if ($row['status'] == 51)
      {
        $array_key = $row['positionfk'].$row['candidatefk'].'_51';
        $ccm_data[$row['created_by']]['ccm1'] += 1;
        $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$array_key] = array('candidate' => $row['candidatefk'],
          'date' => $row['ccm_create_date'], 'ccm_position' => $row['positionfk']);
      }
      else if ($row['status'] == 52)
      {
        $array_key = $row['positionfk'].$row['candidatefk'].'_52';
        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_51';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]) &&
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['ccm1_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_done_candidate'] = $row['candidatefk'];
        }

        $ccm_data[$row['created_by']]['ccm2'] += 1;
        $ccm_data[$row['created_by']]['ccm_info']['ccm2'][$array_key] = array('candidate' => $row['candidatefk'],
          'date' => $row['ccm_create_date'], 'ccm_position' => $row['positionfk']);
      }
      else if ($row['status'] > 52 && $row['status'] <= 61)
      {
        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_51';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_done_candidate']) &&
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['ccm1_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_done_candidate'] = $row['candidatefk'];
        }

        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_52';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_done_candidate']) &&
          $ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['ccm2_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_done_candidate'] = $row['candidatefk'];
        }

        $array_key = $row['positionfk'].$row['candidatefk'].'_mccm';
        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_mccm';

        $ccm_data[$row['created_by']]['mccm'] += 1;
        $ccm_data[$row['created_by']]['ccm_info']['mccm'][$array_key] = array('candidate' => $row['candidatefk'],
          'date' => $row['ccm_create_date'], 'ccm_position' => $row['positionfk']);

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_done_candidate'][$row['status']]) &&
          $ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_position'] == $row['positionfk'] &&
          $row['status'] > 53)
        {
          $ccm_data[$row['created_by']]['mccm_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_done_candidate'][$row['status']] = $row['candidatefk'];
        }
      }
      else
      {
        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_51';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_done_candidate']) &&
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['ccm1_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['ccm1'][$previous_ccm_key]['ccm_done_candidate'] = $row['candidatefk'];
        }

        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_52';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_done_candidate']) &&
          $ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['ccm2_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['ccm2'][$previous_ccm_key]['ccm_done_candidate'] = $row['candidatefk'];
        }

        $previous_ccm_key = $row['positionfk'].$row['candidatefk'].'_mccm';

        if (!empty($ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]) &&
          empty($ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_done_candidate'][100]) &&
          $ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_position'] == $row['positionfk'])
        {
          $ccm_data[$row['created_by']]['mccm_done'] += 1;
          $ccm_data[$row['created_by']]['ccm_info']['mccm'][$previous_ccm_key]['ccm_done_candidate'][100] = $row['candidatefk'];
        }
      }

      $read = $db_result->readNext();
    }

    return $ccm_data;
  }

  public function get_resume_sent($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    $resume_sent_info = array();

    if ($group == 'consultant')
    {
      $query = 'SELECT positionfk, candidatefk, created_by, date_created as resume_sent_date';
      $query .= ' FROM sl_position_link';
      $query .= ' WHERE created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' AND status = 2';
    }
    else
    {
      $query = 'SELECT sl_meeting.date_met, sl_position_link.positionfk, sl_position_link.candidatefk,';
      $query .= ' sl_position_link.date_created as resume_sent_date, sl_meeting.created_by';
      $query .= ' FROM sl_meeting';
      $query .= ' INNER JOIN sl_position_link ON sl_meeting.candidatefk = sl_position_link.candidatefk AND sl_position_link.status = 2';
      $query .= ' AND sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' WHERE sl_meeting.created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND sl_meeting.meeting_done = 1';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      if (!isset($resume_sent_info[$row['created_by']]['resumes_sent']))
      {
        $resume_sent_info[$row['created_by']]['resumes_sent'] = 0;
        $resume_sent_info[$row['created_by']]['resumes_sent_info'] = array();
      }

      if ($group == 'researcher')
      {
        if (isset($resume_sent_info[$row['created_by']][$row['candidatefk']]))
        {
          $read = $db_result->readNext();
          continue;
        }
        else
          $resume_sent_info[$row['created_by']][$row['candidatefk']] = '';
      }

      $resume_sent_info[$row['created_by']]['resumes_sent'] += 1;
      $resume_sent_info[$row['created_by']]['resumes_sent_info'][] = array('candidate' => $row['candidatefk'],
        'date' => $row['resume_sent_date']);

      $read = $db_result->readNext();
    }

    return $resume_sent_info;
  }

  public function get_new_in_play($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    $new_in_play_info = array();

    if ($group == 'consultant')
    {
      $query = 'SELECT positionfk, candidatefk, created_by, status, date_created';
      $query .= ' FROM sl_position_link';
      $query .= ' WHERE status = 51';
    }
    else
    {
      $query = 'SELECT sl_meeting.date_met, sl_position_link.positionfk, sl_position_link.candidatefk, sl_position_link.status,';
      $query .= ' sl_position_link.date_created, sl_meeting.created_by';
      $query .= ' FROM sl_meeting';
      $query .= ' INNER JOIN sl_position_link ON sl_meeting.candidatefk = sl_position_link.candidatefk';
      $query .= ' AND sl_position_link.status = 51';
      // $query .= ' AND sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      // $query .= ' WHERE sl_meeting.created_by IN ('.implode(",", $user_ids).')';
      $query .= ' WHERE sl_meeting.meeting_done = 1';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    $temp_new_candidate = $temp_new_position = array();

    while ($read)
    {
      $row = $db_result->getData();

      if (empty($temp_new_candidate[$row['candidatefk']])
        || strtotime($temp_new_candidate[$row['candidatefk']]['date_created']) > strtotime($row['date_created']) )
      {
        $temp_new_candidate[$row['candidatefk']] = array('date_created' => $row['date_created'],
          'created_by' => $row['created_by']);
      }

      if (empty($temp_new_position[$row['positionfk']])
        || strtotime($temp_new_position[$row['positionfk']]['date_created']) > strtotime($row['date_created']) )
      {
        $temp_new_position[$row['positionfk']] = array('date_created' => $row['date_created'],
          'created_by' => $row['created_by']);
      }

      $read = $db_result->readNext();
    }

    foreach ($temp_new_candidate as $key => $value)
    {
      if (empty($new_in_play_info[$value['created_by']]['new_candidates']))
      {
        $new_in_play_info[$value['created_by']]['new_candidates'] = 0;
        $new_in_play_info[$value['created_by']]['in_play_info']['new_candidates'] = array();
      }

      if (strtotime($value['date_created']) >= strtotime($start_date)
        && strtotime($value['date_created']) <= strtotime($end_date))
      {
        $new_in_play_info[$value['created_by']]['new_candidates'] += 1;
        $new_in_play_info[$value['created_by']]['in_play_info']['new_candidates'][] = array('candidate' => $key,
        'date' => $value['date_created']);
      }
    }

    foreach ($temp_new_position as $key => $value)
    {
      if (empty($new_in_play_info[$value['created_by']]['new_positions']))
      {
        $new_in_play_info[$value['created_by']]['new_positions'] = 0;
        $new_in_play_info[$value['created_by']]['in_play_info']['new_positions'] = array();
      }

      if (strtotime($value['date_created']) >= strtotime($start_date)
        && strtotime($value['date_created']) <= strtotime($end_date))
      {
        $new_in_play_info[$value['created_by']]['new_positions'] += 1;
        $new_in_play_info[$value['created_by']]['in_play_info']['new_positions'][] = array('candidate' => $key,
        'date' => $value['date_created']);
      }
    }

    return $new_in_play_info;
  }

  public function get_offer_sent($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    $offers_info = array();

    if ($group == 'consultant')
    {
      $query = 'SELECT positionfk, candidatefk, created_by';
      $query .= ' FROM sl_position_link';
      $query .= ' WHERE created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' AND status = 100';
    }
    else
    {
      $query = 'SELECT sl_position_link.positionfk, sl_position_link.candidatefk, sl_meeting.created_by';
      $query .= ' FROM sl_meeting';
      $query .= ' INNER JOIN sl_position_link ON sl_meeting.candidatefk = sl_position_link.candidatefk AND sl_position_link.status = 100';
      $query .= ' AND sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' WHERE sl_meeting.created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND sl_meeting.meeting_done = 1';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      if (!isset($offers_info[$row['created_by']]['offers_sent']))
      {
        $offers_info[$row['created_by']]['offers_sent'] = 0;
        $offers_info[$row['created_by']]['offer_info'] = array();
      }

      $offers_info[$row['created_by']]['offers_sent'] += 1;
      $offers_info[$row['created_by']]['offer_info'][] = array('candidate' => $row['candidatefk']);

      $read = $db_result->readNext();
    }

    return $offers_info;
  }

  public function get_placement_number($user_ids, $start_date, $end_date, $group = 'researcher')
  {
    $placed_info = array();

    if ($group == 'consultant')
    {
      $query = 'SELECT positionfk, candidatefk, created_by';
      $query .= ' FROM sl_position_link';
      $query .= ' WHERE created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' AND status = 101';
    }
    else
    {
      $query = 'SELECT sl_position_link.positionfk, sl_position_link.candidatefk, sl_meeting.created_by';
      $query .= ' FROM sl_meeting';
      $query .= ' INNER JOIN sl_position_link ON sl_meeting.candidatefk = sl_position_link.candidatefk AND sl_position_link.status = 101';
      $query .= ' AND sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
      $query .= ' WHERE sl_meeting.created_by IN ('.implode(',', $user_ids).')';
      $query .= ' AND sl_meeting.meeting_done = 1';
    }

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      if (!isset($placed_info[$row['created_by']]['placed']))
      {
        $placed_info[$row['created_by']]['placed'] = 0;
        $placed_info[$row['created_by']]['placed_info'] = array();
      }

      $placed_info[$row['created_by']]['placed'] += 1;
      $placed_info[$row['created_by']]['placed_info'][] = array('candidate' => $row['candidatefk']);

      $read = $db_result->readNext();
    }

    return $placed_info;
  }

  public function get_call_log_data($ignore_users, $start_date = '', $end_date = '')
  {
    $call_log_data = array();

    $ignore_users = implode(',', $ignore_users);

    $query = 'SELECT call_log.duration, call_log.calling_party, call_log.dialed_on_trunk, login.firstname, ';
    $query .= 'login.lastname, login.phone_ext, login.nationalityfk, sl_nationality.shortname AS nationality ';
    $query .= 'FROM call_log ';
    $query .= 'LEFT JOIN login ON login.phone_ext = call_log.calling_party ';
    $query .= 'LEFT JOIN sl_nationality ON login.nationalityfk = sl_nationality.sl_nationalitypk ';
    $query .= 'WHERE LENGTH(call_log.dialed_on_trunk) > 5 AND login.status = 1 AND login.loginpk NOT IN ('.$ignore_users.') ';

    if (!empty($start_date))
      $query .= 'AND call_log.date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ';

    $query .= 'ORDER BY call_log.calling_party';

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();
    if ($read)
    {
      while($read)
      {
        $row = $db_result->getData();

        if (empty($call_log_data[$row['calling_party']]))
        {
          $name = substr($row['firstname'], 0, 1).'. '.$row['lastname'];
          $call_log_data[$row['calling_party']] = array('name' => $name, 'nationality' => $row['nationality'],
            'calling_party' => $row['calling_party'], 'calls' => 0, 'attempts' => 0);
        }

        if ($row['duration'] > 30)
        {
          $call_log_data[$row['calling_party']]['calls'] += 1;
        }

        $call_log_data[$row['calling_party']]['attempts'] += 1;

        $read = $db_result->readNext();
      }
    }

    uasort($call_log_data, sort_multi_array_by_value('calls', 'reverse') );

    return $call_log_data;
  }
}
