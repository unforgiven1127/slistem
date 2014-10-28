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
$nMaxPass = $nPass+25;



while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT rev.*, cons.consultantpk
    FROM CONS_REVENUE as rev

    INNER JOIN cons_tbl as cons ON (cons.cons_cid = rev.cons_id)
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyDetailInsert = array();

  $anTreated = array();

  while($asData = pg_fetch_assoc($oPgResult))
  {
    $asData['consultantpk'] = (int)$asData['consultantpk'];
    $asData['revenue'] = (int)$asData['revenue'];
    $asData['placements'] = (int)$asData['placements'];
    $asData['paid'] = (int)$asData['paid'];
    $asData['year'] = (int)$asData['year'];
    //$asData['region'] = '';

    $sUserKey = $asData['year'].'_'.$asData['consultantpk'];
    $asData['date'] = date('Y-m-d', mktime(0, 0, 0, 1, 1, $asData['year']));

    $nPaymentPk = ($asData['consultantpk'] *100)+$asData['year'];


    if(!isset($anTreated[$sUserKey]))
    {
      //add a dummy placements for previous data
      $asMyInsert[] = '('.$nPaymentPk.', "'.$asData['date'].'", 1, 1, 0, "'.$asData['date'].'")';
      $anTreated[$sUserKey] = 0;
    }

    //Create a dummy placement key
    $asMyDetailInsert[] = '('.$nPaymentPk.', "'.$asData['date'].'", '.$asData['consultantpk'].', "'.$asData['revenue'].'")';

    //!!!!!!!!!!!!!!!!!!!
    //need to calculate paid/signed revenue difference cause the paid is a status on each placemnet
  }

  $nCandidate = count($asMyInsert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/



  if(!empty($asMyInsert))
  {
    $sMyQuery = 'INSERT INTO `sl_placement` (`sl_placementpk` ,`date_created` ,`positionfk` ,`candidatefk` ,`closed_by` ,`date_signed`)
      VALUES '.implode(' ,', $asMyInsert);
    $bInserted = mysql_query($sMyQuery);

    if(!$bInserted)
    {
      echo mysql_error();
      echo '<pre>';
      var_dump($sMyQuery);
      echo '</pre>';
      exit('error inserting rm during pass #'.$nPass.' / offset: '.$nLimitOffset);
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