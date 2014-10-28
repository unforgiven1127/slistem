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

while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT * FROM locations_tbl ORDER BY location_id ASC

    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyLinkInsert = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
    // 	indus_id 	industry 	parent_id
    $asData['location_id'] = (int)$asData['location_id'];
    if($asData['location_id'] > 0)
    {
      $asData['location_name'] = '"'.mysql_real_escape_string(addslashes(trim($asData['location_name']))).'"';
      $asData['location_abv'] = '"'.mysql_real_escape_string(addslashes(trim($asData['location_abv']))).'"';

      //(`eventpk`,`type`,`title`,`content`,`date_create`,`date_display`,`created_by`)
      $asMyInsert[] = '('.$asData['location_id'].' , '.$asData['location_name'].', '.$asData['location_abv'].')';
    }
  }

  $nCandidate = count($asMyInsert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `sl_location` (`sl_locationpk`,`location`,`shortname`) VALUES '.implode(' ,', $asMyInsert);
  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting locations during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }



  if($nCandidate < $nRowsByBatch)
  {
    $bDone = true;
    echo '<br /><span style="color: green;"> --> treatead '.$nCandidate.' on last batch, looks done. span> ';
  }

  flush();
  ob_flush();

  $nPass++;
}

if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}

?>
