<?php

require ($_SERVER['DOCUMENT_ROOT'].'/common/lib/global_func.inc.php5');


/*$oPGCx = @pg_connect("dbname=slystem_live user=slate password=slate");*/ $oPGCx = @pg_connect("host=10.0.81.110 port=5432 dbname=slystem_live user=slate password=slate");
if(!$oPGCx)
{
  exit('can not connect to postgresql');
}


/*$oMyCx = @mysql_connect('localhost', 'bccrm', 'bcmedia');*/ $oMyCx = @mysql_connect('localhost', 'slistem', 'THWj8YerbMWfK3yW');
if(!$oMyCx)
{
  echo mysql_error();
  exit('can not connect to mysql');
}

mysql_select_db('slistem', $oMyCx);


if(isset($_GET['pass']) && !empty($_GET['pass']) && is_numeric($_GET['pass']))
{
  $nPass = (int)$_GET['pass'];
}
else
  $nPass = 0;

if(isset($_GET['batch']) && !empty($_GET['batch']) && is_numeric($_GET['batch']))
{
  $nRowsByBatch = (int)$_GET['batch'];
}
else
  $nRowsByBatch = 2000;


$bError = $bDone = false;
$nMaxPass = $nPass+20;



//convertion for first table
$asJdStatus = array();
$asJdStatus['resume_sent'] = 2;
$asJdStatus['on_deck'] = 1;
$asJdStatus['ccm'] = 50;
$asJdStatus['offer'] = 100;
$asJdStatus['placed'] = 101;
$asJdStatus['failed'] = 200;
$asJdStatus['stalled'] = 150;
$asJdStatus['no_interest'] = 201;

//empty to remove

/**
 *
 * $asJdStatus[8] = 201; //null
$asJdStatus[7] = 1; //deck
$asJdStatus[1] = 2; //resume
$asJdStatus[4] = 200; //deck
$asJdStatus[3] = 101; //placed
$asJdStatus[6] = 100; //offerk
$asJdStatus[2] = 51; //ccm
$asJdStatus[0] = 150; //inactive
$asJdStatus[5] = 150; //stalled





 *
 *
 * select jct.status, count(jct.status), cjs.cjd_status
FROM jd_candi_tbl as jct
LEFT JOIN candi_jd_status_tbl as cjs ON (cjs.candi_jd_status_id = jct.status)
GROUP BY jct.status, cjs.cjd_status
 *
 *
 *
 *  UNION

    (SELECT 2 as tableOrder,
    jd_id, candi_id, cjs.cjd_status as activity, created as date,
    cons.consultantpk, (\'ccm\' || ccm_num+1) as ccm , cons.consultantpk as ccm_consultantpk, created as ccm_date

    FROM jd_candi_tbl as  jct
    INNER JOIN cons_tbl as cons ON (cons.cons_cid = jct.cons_id)
    LEFT JOIN candi_jd_status_tbl as cjs ON (cjs.candi_jd_status_id = jct.status))


 */


$anCCM = array();

while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = '
    (SELECT 1 as tableOrder,
    jat.jd_id, jat.candi_id, jat.activity, jat.date,
    cons.consultantpk, jca.activity as ccm, cons2.consultantpk as ccm_consultantpk, jca.date as ccm_date

    FROM jd_activity_tbl jat
    INNER JOIN  cons_tbl as cons ON (cons.cons_cid = jat.cons_id)

    LEFT JOIN jd_candi_activity_tbl as jca ON (jca.jd_id = jat.jd_id AND jca.candi_id = jat.candi_id AND jat.activity = \'ccm\')
    LEFT JOIN cons_tbl as cons2 ON (cons2.cons_cid = jca.cons_id))

    ORDER BY jat.jd_id, jat.date DESC
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  //echo $sPgQuery;
  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyLinkInsert = array();
  $asMyDetailInsert = array();
  $anTreated = array();
  $sNow = date('Y-m-d').' 00:00:00';

  while($asData = pg_fetch_assoc($oPgResult))
  {
    if(!empty($asData['activity']))
    {
      $nStatus = @$asJdStatus[strtolower(trim($asData['activity']))];
      if(!empty($nStatus))
      {

        // 	jd_id 	jd_company_id 	jd_cons_id 		jd_title 	jd_dept 	jd_location 	jd_responsibilities 	jd_desc 	jd_type
        //	jd_requirements 	jd_status 	jd_public 		jd_created 	jd_industry 	jd_age_from 	jd_age_to
        //	jd_english 	jd_salary_from 	jd_salary_to 	jd_updated 	jd_confidential 	jd_owner 	jd_group_id
        //	jd_career 	jd_japanese 	jd_priority
        $asData['jd_id'] = (int)$asData['jd_id'];
        $asData['candi_id'] = (int)$asData['candi_id'];
        $asData['consultantpk'] = (int)$asData['consultantpk'];
        $asData['date'] = date('Y-m-d H:i:s', strtotime($asData['date']));



        $asData['ccm'] = strtolower($asData['ccm']);
        if(substr($asData['ccm'], 0, 3) != 'ccm')
        {
          $asData['ccm'] = 0;
          $asData['ccm_consultantpk'] = 0;
          $asData['ccm_date'] = '';
        }
        else
        {
          $sKey = $asData['jd_id'].'_'.$asData['candi_id'];
          if(!isset($anCCM[$sKey]))
            $anCCM[$sKey] = 1;
          else
            $anCCM[$sKey]++;

          /*var_dump($asData['ccm']); echo '<br />';
          var_dump(preg_replace('/[^0-9]/', '', $asData['ccm'])); echo '<br />';
          var_dump((int)preg_replace('/[^0-9]/', '', $asData['ccm'])); echo '<hr />';
          $asData['ccm'] = (int)preg_replace('/[^0-9]/', '', $asData['ccm']);*/

          //var_dump($anCCM[$sKey]);echo '<hr />';

          $asData['ccm'] = $anCCM[$sKey];
          $asData['ccm_consultantpk'] = (int)$asData['ccm_consultantpk'];
          $asData['ccm_date'] = date('Y-m-d H:i:s', strtotime($asData['ccm_date']));
        }

        //echo ($asData['activity'].' // '.$nStatus.' + '.$asData['ccm'].' <br />');

        if($nStatus == 50)
        {
          $nStatus+= $asData['ccm'];
        }

        $asData['date_expire'] = date('Y-m-d H:i:s', strtotime('+6 months', strtotime($asData['date'])));



        //the first entry (sorted by deate desc) is the active row
        //if(!isset($anTreated[$asData['jd_id']]) || ($anTreated[$asData['jd_id']] > 100 && $nStatus <= 100))
        $bExpired = false;
        $sKey = $asData['jd_id'].'_'.$asData['candi_id'];

        if(!isset($anTreated[$sKey]))
        {
          //dump('first link for position '.$sKey);

          //if the last active status (not placed / fallen/ rejected..) from slistem 2 has expired... I create an entry for expired
          if($nStatus < 101 && $asData['date_expire'] < $sNow)
          {
             //dump('first has expired, active 0 but create a 151 !!! ');

             $asMyInsert[] = '('.$asData['jd_id'].', '.$asData['candi_id'].', "'.$asData['date'].'", '.$asData['consultantpk'].',
                151, "'.$asData['date_expire'].'", 1,
                "This is an automatic update from Slistem.<br />
              The previous status has reached the expiration date, the candidate is now [expired], not considered active/in play anymore.<br />
              If the candidate is not updated during the next 3 months, it will be considered [fallen off].")';

            $bExpired = true;
            $nActive = 0;
          }
          else
          {
            //dump('not expired, this is the last one active = 1 ');
            $nActive = 1;
          }

          $anTreated[$sKey] = $nStatus;
        }
        else
        {
          $nActive = 0;
          //dump('another link for position '.$asData['jd_id']);
        }

        /*if($asData['candi_id'] == 333684)
        {
          dump($asData['jd_id']);
          dump('created: '.$asData['date']);
          dump('expires if '.$asData['date_expire'].' < '.$sNow);
          dump($bExpired);
          dump($nStatus);
          dump($nActive);

          echo '<hr />';
        }*/


        // positionfk, candidatefk, date_created, created_by,
        //  status, date_expire, active
        $asMyInsert[] = '('.$asData['jd_id'].', '.$asData['candi_id'].', "'.$asData['date'].'", '.$asData['consultantpk'].',
          '.$nStatus.', "'.$asData['date_expire'].'", '.$nActive.', "")';

      }
    }
  }

  $nCandidate = count($asMyInsert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/



  if(!empty($asMyInsert))
  {
    $sMyQuery = 'INSERT INTO `sl_position_link` (positionfk, candidatefk, date_created, created_by, status, date_expire, active, `comment`)
      VALUES '.implode(' ,', $asMyInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      echo '<pre>';
      var_dump($sMyQuery);
      echo '</pre>';
      exit('error inserting position link during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
  }


  if($nCandidate < $nRowsByBatch)
  {
    $bDone = true;
    echo '<br /><span style="color: green;"> --> treatead '.$nCandidate.' on last batch, looks done. </span> ';
  }

  flush();
  ob_flush();

  $nPass++;
}

if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}