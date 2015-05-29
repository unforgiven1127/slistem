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

  public function getSicChartMet($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    //no weight difference between phone and live meetings
    $sQuery = 'SELECT count(*) as nCount, attendeefk, DATE_FORMAT(date_meeting, "%Y-%m") as sMonth, meeting_done
      FROM sl_meeting
      WHERE attendeefk IN ('.implode(',', $panUserPk).')
      AND date_meeting >= '.$this->oDB->dbEscapeString($psDateStart).' AND date_meeting < '.$this->oDB->dbEscapeString($psDateEnd).'

      GROUP BY sMonth, attendeefk, meeting_done
      ORDER BY sMonth ';

    //echo $sQuery;
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







  public function getKpiSetVsMet($panUserPk, $psDateStart, $psDateEnd)
  {
    if(!assert('is_arrayOfInt($panUserPk)'))
      return array();

    $sQuery = 'SELECT COUNT(sl_meetingpk) as nSet, smee.created_by, smee.date_created, smee.date_met,
      SUM( IF(smee.date_met BETWEEN "'.$psDateStart.'" AND "'.$psDateEnd.'", 1, 0)) as nMet
      FROM sl_meeting as smee

      WHERE smee.created_by IN ('.implode(',', $panUserPk).')
        AND smee.created_by <> smee.attendeefk
        AND smee.date_meeting BETWEEN "'.$psDateStart.'" AND "'.$psDateEnd.'"

      GROUP BY smee.created_by
      ORDER BY nSet DESC ';


    $asData = array();

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asData[$oDbResult->getFieldValue('created_by')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asData;
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

        if ($row['id'] == 'bizreach' || $row['id'] == 'othercollab')
        {
          $read = $db_result->readNext();
          continue;
        }

        if (isset($revenue_data_raw[$row['revenue_id']]))
        {
          $current_revenue_info = $revenue_data_raw[$row['revenue_id']];

          if (!$row['status'])
          {
            $user_id = 'former';

            if (empty($revenue_data[$user_id]['placed']))
              $revenue_data[$user_id]['placed'] = 0;

            if (!isset($revenue_data[$user_id]['do_not_count_placed'][$row['loginpk']]))
              $revenue_data[$user_id]['placed'] += $this->get_placement_number($row['loginpk'], $request_date);

            $revenue_data[$user_id]['do_not_count_placed'][$row['loginpk']] = '';
          }
          else
          {
            $user_id = $row['loginpk'];

            if (empty($revenue_data[$user_id]['nationality']))
              $revenue_data[$user_id]['nationality'] = $row['nationality'];

            if (empty($revenue_data[$user_id]['placed']))
              $revenue_data[$user_id]['placed'] = $this->get_placement_number($user_id, $request_date);

            if (empty($revenue_data[$user_id]['name']))
                $revenue_data[$user_id]['name'] = substr($row['firstname'], 0, 1).'. '.$row['lastname'];
          }

          if (!isset($revenue_data[$user_id]['paid']))
            $revenue_data[$user_id]['paid'] = $revenue_data[$user_id]['signed'] = $revenue_data[$user_id]['total_amount'] = 0;

          if (empty($revenue_data[$user_id]['team']))
            $revenue_data[$user_id]['team'] = $this->get_user_team($user_id);

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
        $read = $db_result->readNext();
      }

      uasort($revenue_data, sort_multi_array_by_value('total_amount', 'reverse'));
    }
    return $revenue_data;
  }

  private function get_placement_number($user_id, $request_date)
  {
    $placements = 0;

    $date_start = $request_date.'-01-01 00:00:00';
    $date_end = $request_date.'-12-31 23:59:59';

    $query = 'SELECT count(DISTINCT scan.sl_candidatepk) AS placed ';
    $query .= 'FROM sl_candidate AS scan ';
    $query .= 'INNER JOIN sl_position_link AS spli ON (spli.candidatefk = scan.sl_candidatepk AND spli.status = 101 AND spli.created_by = "'.$user_id.'" ';
    $query .= 'AND spli.date_created BETWEEN "'.$date_start.'" AND "'.$date_end.'")';

      $db_result = $this->oDB->executeQuery($query);
      $read = $db_result->readFirst();

      if ($read)
        $placements = $db_result->getFieldValue('placed');

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

  public function get_ccm_data($start_date, $end_date)
  {
    $ccm_result_array = array();
    // Ignore administrators and QA people unless they do normal consulting/researcher jobs
    $ignore_users = array('nicholas', 'dcepulis', 'dba', 'administrator');

    $query = 'SELECT DISTINCT sl_position_link.positionfk, login.firstname, login.lastname, login.id';
    $query .= ' FROM sl_position_link';
    $query .= ' LEFT JOIN login';
    $query .= ' ON sl_position_link.created_by = login.loginpk';
    $query .= ' WHERE sl_position_link.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'"';
    $query .= ' AND sl_position_link.status = 51';
    $query .= ' ORDER BY login.lastname';

    $db_result = $this->oDB->executeQuery($query);
    $read = $db_result->readFirst();

    while ($read)
    {
      $row = $db_result->getData();

      if (in_array($row['id'], $ignore_users))
      {
        $read = $db_result->readNext();
        continue;
      }

      if (empty($ccm_result_array[$row['id']]['ccm_count']))
      {
        $ccm_result_array[$row['id']]['ccm_count'] = 1;
        $ccm_result_array[$row['id']]['name'] = substr($row['firstname'], 0, 1).'. '.$row['lastname'];
      }
      else
        $ccm_result_array[$row['id']]['ccm_count'] += 1;

      $read = $db_result->readNext();
    }

    uasort($ccm_result_array, sort_multi_array_by_value('ccm_count', 'reverse'));

    return $ccm_result_array;
  }
}
