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


if(isset($_GET['batchoffset']) && !empty($_GET['batchoffset']) && is_numeric($_GET['batchoffset']))
{
  $nBatchOffset = (int)$_GET['batchoffset'];
  echo 'batch starts at offset  '.$nBatchOffset.'<br />';
}
else
  $nBatchOffset = 0;


$bError = $bDone = false;
$nMaxPass = $nPass+20;

if(isset($_GET['qType']) && $_GET['qType'] == 2)
  $nType = 2;
else
  $nType = 1;

while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nBatchOffset + ($nPass * $nRowsByBatch);
  echo 'offset is now LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.' <br />';

  if($nType == 1)
  {
    $sPgQuery = 'SELECT
      log.log_date as date_created,
      con.consultantpk,
      log.note as content,
      log.candi_id as candidatefk

      FROM logging_tbl as log
      INNER JOIN cons_tbl as con ON (con.cons_cid = log.user_id)

      WHERE log.log_type IS NULL
      OR log.log_type = \'candimig\'
      OR log.log_type = \'cnotemig\'
      OR log.log_type = \'jdmig\'
      OR log.log_type = \'docmig\'

      ORDER BY log.user_id
      LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.' ';
  }
  else
  {
    //pickup some of the update history
    $sPgQuery = 'SELECT
      upd.update_date as date_created,
      con.consultantpk,
      upd.update_candi as candidatefk,
      upd.updated_field,
      upd.updated_to

      FROM update_tbl as upd
      INNER JOIN cons_tbl as con ON (con.cons_cid = upd.update_cons)
      WHERE

      upd.updated_field IN (\'candi_birth\',\'candi_bonus\', \'candi_collab\', \'candi_company\',
      \'candi_cpa\', \'candi_dept\', \'candi_grade\',\'candi_industry\', \'candi_location\', \'candi_mba\',
      \'candi_nationality\', \'candi_occupation\', \'candi_play\',\'candi_salary\', \'candi_status_no\', \'candi_title\',
      \'conta_contacts\', \'conta_memo\', \'is_cc\'
      )
      AND upd.updated_to <> \'Array\'

      ORDER BY upd.update_cons
      LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.' ';
  }

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
    $asData['consultantpk'] = (int)$asData['consultantpk'];
    $asData['candidatefk'] = (int)$asData['candidatefk'];
    $asData['date_created'] = '"'.mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($asData['date_created']))).'"';

    if($nType == 1)
    {
      $sEncoding = mb_detect_encoding($asData['content']);
      $asData['content'] = mb_convert_encoding($asData['content'], 'UTF-8', $sEncoding);
    }
    else
    {
      $asData['content'] = $asData['updated_field'].' changed to '.$asData['updated_to'];
      $asData['content'] = str_replace('candi_', '', $asData['content']);

      $sEncoding = mb_detect_encoding($asData['content']);
      $asData['content'] = mb_convert_encoding($asData['content'], 'UTF-8', $sEncoding);
    }

    $asData['content'] = '"'.mysql_real_escape_string($asData['content']).'"';

    $asData['title'] = '""';

    //date, userfk, action, description,
    //table, component, cp_uid ,cp_action, cp_type, cp_pk, uri
    //555-001_ppasa_candi_500001
    $sID = '555-001_ppasa_candi_'.$asData['candidatefk'];
    //$sUrl = '"https://slistem.slate.co.jp/index.php5?uid=555-001&ppa=ppasa&ppt=candi&ppk='.$asData['candidatefk'].'&pg=ajx"';
    $sUrl = 'NULL';

    $asMyInsert[] = '('.$asData['date_created'].', '.$asData['consultantpk'].', '.$asData['content'].', "",
      "sl_candidate", "'.$sID.'", "555-001", "ppav", "candi", '.$asData['candidatefk'].', '.$sUrl.')';
  }

  $nCandidate = count($asMyInsert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `login_system_history` '
 . '(`date`, userfk, `action`, `description`, `table`, component, cp_uid ,cp_action, cp_type, cp_pk, uri ) VALUES '.implode(' ,', $asMyInsert);
   $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting notes during pass #'.$nPass.' / offset: '.$nLimitOffset);
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

