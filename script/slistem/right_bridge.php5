<?php

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

//sharedspace, user pref, std consultant, shared folders, access folder feature, reminders
$asAllUserRight = array(15,113, 10001, 126, 10016, 143);
//10062: placement manager yuko
//10013: adv consultant (manager)


$asRightConvertion = array();
$asRightConvertion[1] = 0;    //all candi
$asRightConvertion[2] = 15010;     //no confidential
$asRightConvertion[3] = 15011;     //no tokyo
$asRightConvertion[4] = 0;    // all cp
$asRightConvertion[5] = 0;     // all jd
$asRightConvertion[35] = 15012;    //no automotive
$asRightConvertion[36] = 15013;    //no energy
$asRightConvertion[37] = 15014;    //no finance
$asRightConvertion[38] = 15015;    //no automotive
$asRightConvertion[39] = 15016;    //no healthcare
$asRightConvertion[40] = 15017;    //no occupation consumer
$asRightConvertion[41] = 15018;    //no occupation Engineer
$asRightConvertion[42] = 15019;    //no occupation Healthcare
$asRightConvertion[43] = 15020;    //no CNS
$asRightConvertion[44] = 15021;    //no occupation Automotive
$asRightConvertion[45] = 15022;    //no occupation CNS
$asRightConvertion[46] = 15023;    //no occupation Energy
$asRightConvertion[47] = 15024;    //no occupation Fin & accounting
$asRightConvertion[48] = 15025;    //no occupation IT & telecom
$asRightConvertion[49] = 15026;    //no IT telecom
$asRightConvertion[50] = 15027;    // no (location = manila OR (location=tokyo AND industry = finance))






 $nPass = 0;
 $nRowsByBatch = 10000;
 $nCandidate = 0;

$bError = $bDone = false;
$nMaxPass = $nPass+20;

while(!$bError && !$bDone && $nPass < $nMaxPass)
{

  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT *  FROM consultant_rights ';

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $anTreated = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
    $nConsultant = (int)$asData['consultantpk'];
    $nRight = (int)$asData['rightspk'];

    if(!isset($anTreated[$nConsultant]))
    {
      foreach($asAllUserRight as $nRightfk)
      {
        $asMyInsert[] = '('.$nRightfk.', '.$nConsultant.') ';
      }

      $anTreated[$nConsultant] = 1;
    }


    if($nRight && isset($asRightConvertion[$nRight]))
      $asMyInsert[] = '('.$asRightConvertion[$nRight].', '.$nConsultant.') ';

    $nCandidate++;
  }

  /*var_dump($asMyInsert);
  exit(0);*/

  $sMyQuery = 'INSERT INTO `right_user` (`rightfk`, `loginfk`) VALUES '.implode(' ,', $asMyInsert);

  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting rights during pass #'.$nPass.' / offset: '.$nLimitOffset);
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


