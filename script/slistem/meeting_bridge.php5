<?php

require($_SERVER['DOCUMENT_ROOT'].'/common/lib/global_func.inc.php5');


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
$nMaxPass = $nPass+25;


if(isset($_GET['qtype']) && !empty($_GET['qtype']))
{
  $nType = (int)$_GET['qtype'];
}
else
  $nType = 1;


while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;

  if($nType == 1)
  {
    //Meeting = candi status changed from interview to more
    $sPgQuery = '
        SELECT upd.*, cons.consultantpk as set_by,
        cons2.consultantpk as met_by, upd2.update_date met_on
        FROM update_tbl as upd

        INNER JOIN cons_tbl as cons ON (cons.cons_cid = upd.update_cons)

        INNER JOIN update_tbl as upd2 ON (upd2.update_id > upd.update_id AND upd2.update_candi = upd.update_candi
          AND upd2.updated_field = \'candi_status_no\'
          AND upd2.updated_to IN(\'5\', \'6\', \'7\'))
        INNER JOIN cons_tbl as cons2 ON (cons2.cons_cid = upd2.update_cons)

        WHERE upd.updated_field = \'candi_status_no\'
        AND upd.updated_to = \'3\'
        AND upd.update_date > \'2007-01-01\'

      ORDER BY update_candi
      LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;
  }
  elseif($nType == 2)
  {
    //Meeting = having candidate date_met changed
    $sPgQuery = '
        SELECT upd.*, cons.consultantpk as set_by,
        cons.consultantpk as met_by, upd.update_date met_on
        FROM update_tbl as upd

        INNER JOIN cons_tbl as cons ON (cons.cons_cid = upd.update_cons)

        WHERE upd.updated_field = \'candi_date_met\'
        AND upd.updated_to <> \'\'
        AND upd.updated_to <> \'0-00-00\'
        AND upd.updated_to <> \'0000-00-00\'
        AND upd.update_date > \'2007-01-01\'

      ORDER BY update_candi
      LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;
  }
  elseif($nType == 3)
  {
    //Meeting = having candidate date_met right now in the candidate field
    // and now entry in update table (first input or when candi created)
    $sPgQuery = '
        SELECT can.candi_rno as update_candi,
        can.candi_update as update_date,
        cons.consultantpk as set_by,
        cons.consultantpk as met_by,
        can.candi_date_met as met_on,
        CAST(can.candi_date_met as varchar(128)) as  daaaate
        FROM candidate_tbl as can

      INNER JOIN cons_tbl as cons ON (cons.cons_cid = can.candi_cid)

      LEFT JOIN update_tbl as upd ON
      (
        upd.update_candi = can.candi_rno AND upd.updated_field = \'candi_date_met\'
        AND upd.updated_to > \'2007-01-01\'
        AND upd.updated_to <> CAST(can.candi_date_met as varchar(128))
      )

      WHERE can.candi_date_met > \'2007-01-01\'
      AND upd.update_candi IS NULL

      ORDER BY can.candi_cid
      LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;
  }



  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }


  $asMyInsert = array();
  $asMyLinkInsert = array();
  $asMyDetailInsert = array();

  while($asData = pg_fetch_assoc($oPgResult))
  {

    $asData['update_candi'] = (int)$asData['update_candi'];
    $asData['set_by'] = (int)$asData['set_by'];
    $asData['update_date'] = date('Y-m-d H:i:s', strtotime($asData['update_date']));

    $asData['met_by'] = (int)$asData['met_by'];
    $asData['met_on'] = date('Y-m-d H:i:s', strtotime($asData['met_on']));


    // date_created, created_by, candidatefk, attendeefk, `type`, date_meeting, meeting_done, date_met
    $asMyInsert[] = '("'.$asData['update_date'].'", '.$asData['set_by'].', '.$asData['update_candi'].',  '.$asData['set_by'].', 1, "'.$asData['met_on'].'", 1, "'.$asData['met_on'].'")';
  }

  $nCandidate = count($asMyInsert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/



  if(!empty($asMyInsert))
  {
    $sMyQuery = 'INSERT INTO `sl_meeting` (date_created, created_by, candidatefk, attendeefk, `type`, date_meeting, meeting_done, date_met)
      VALUES '.implode(' ,', $asMyInsert);

    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      echo '<pre>';
      var_dump($sMyQuery);
      echo '</pre>';
      exit('error inserting meeting during pass #'.$nPass.' / offset: '.$nLimitOffset);
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