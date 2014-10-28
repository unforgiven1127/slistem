<?php

/*$oPGCx = @pg_connect("dbname=slystem_live user=slate password=slate");*/
$oPGCx = @pg_connect("host=10.0.81.110 port=5432 dbname=slystem_live user=slate password=slate");
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




/*$sPgQuery = 'SELECT * FROM cons_tbl as co ';
$oPgResult = pg_query($sPgQuery);
if(!$oPgResult)
  exit('gaaaa');

$asGroups = array();
while($asData = pg_fetch_assoc($oPgResult))
{
  $asGroups[(int)$asData['group_id']][] = (int)$asData['consultantpk'];
}

echo '<pre />';
var_dump($asGroups);
exit();*/



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
  $sPgQuery = 'SELECT *  FROM contacts_tbl as con
    LEFT JOIN contacts_visibility as cvi ON (cvi.contactsfk = con.contactspk AND con.conta_visibility = 4)
    ORDER BY contactspk
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyGroupInsert = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
      $asData['conta_rno'] = (int)$asData['conta_rno'];
      $asData['contactspk'] = (int)$asData['contactspk'];
      $asData['conta_contacts'] = (int)$asData['conta_contacts'];
      $asData['consultantpk'] = (int)$asData['conta_consfk'];
      $asData['conta_visibility'] = (int)$asData['conta_visibility'];
      $asData['conta_group'] = (int)$asData['conta_group'];

      $sEncoding = mb_detect_encoding($asData['conta_contacts']);
      $asData['conta_contacts'] = mb_convert_encoding($asData['conta_contacts'], 'UTF-8', $sEncoding);

      $sEncoding = mb_detect_encoding($asData['conta_memo']);
      $asData['conta_memo'] = mb_convert_encoding($asData['conta_memo'], 'UTF-8', $sEncoding);

      //visibility table FK not PK
      $asData['consultantfk'] = (int)$asData['consultantfk'];

      //from conta_visib
      if(empty($asData['consultantpk']))
        $asData['consultantpk'] = -2;


      $asData['conta_regist'] = '"'.mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($asData['conta_regist']))).'"';
      $asData['conta_no'] = '"'.mysql_real_escape_string(trim($asData['conta_no'])).'"';

      if(empty($asData['conta_memo']))
        $asData['conta_memo'] = 'NULL';
      else
        $asData['conta_memo'] = '"'.mysql_real_escape_string(trim($asData['conta_memo'])).'"';

      //sl_contactspk` , `type` , `item_type` , `itemfk` , `date_create` , `loginfk` , `value` , `description` , `visibility`
      $asMyInsert[$asData['contactspk']] = '('.$asData['contactspk'].', '.$asData['conta_contacts'].', "candi", '.$asData['conta_rno'].',
        '.$asData['conta_regist'].', '.$asData['consultantpk'].', '.$asData['conta_no'].', '.$asData['conta_memo'].' , '.$asData['conta_visibility'].', '.$asData['conta_group'].') ';


      if($asData['conta_visibility'] == 4 && !empty($asData['consultantfk']))
      {
        echo '('.$asData['contactspk'].', '.$asData['consultantfk'].') <br /> ';
        $asMyGroupInsert[] = '('.$asData['contactspk'].', '.$asData['consultantfk'].') ';
      }

  }

  $nCandidate = count($asMyInsert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `sl_contact` (`sl_contactpk` , `type` , `item_type` , `itemfk` , `date_create` , `loginfk` , `value` , `description` , `visibility`, `groupfk`) VALUES '.implode(' ,', $asMyInsert);

  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting contacts during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }

  if(!empty($asMyGroupInsert))
  {
    $sMyQuery = ' INSERT INTO `sl_contact_visibility` (`sl_contactfk` , `loginfk`) VALUES '.implode(' ,', $asMyGroupInsert);

    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting contact visibility during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
  }


  //a few lines may be duplicated because of the visivibility table
  if($nCandidate < (0.90 *$nRowsByBatch))
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

?>
