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
$anNewFolderPk = array();
$nNewKey = 100000;
$nNewItemKey = 0;

while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT *
    FROM candidate_tbl as can 
    INNER JOIN cons_tbl as con ON (con.cons_cid = can.candi_playfor)
    WHERE candi_play = \'t\'

    ORDER BY con.consultantpk ';

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyLinkInsert = array();
  $asMyItemInsert = array();
  $nFolderRank = 0;
  $anItemRank = array();
  var_dump('nb rows found: '.pg_numRows($oPgResult));

  while($asData = pg_fetch_assoc($oPgResult))
  {
    $asData['consultantpk'] = (int)$asData['consultantpk'];
    $asData['itemfk'] = (int)$asData['candi_rno'];
    $asData['candi_rno'] = (int)$asData['candi_rno'];
    
    if(!isset($anNewFolderPk[$asData['consultantpk']]))
    {
      $nNewKey++;
      $anNewFolderPk[$asData['consultantpk']] = $nNewKey;
      $nFolderId = $nNewKey;
      $nNewItemRank = 0;
      
      //folderpk parentfolderfk 	label 	rank 	ownerloginfk  	private
      $asMyInsert[$asData['consultantpk']] = '('.$nFolderId.', 0, \' - Slistem2 inplay - \', '.$nFolderId.', '.$asData['consultantpk'].', 0)';

      // folderfk 	cp_uid 	cp_action 	cp_type
      $asMyLinkInsert[$asData['consultantpk']] = '('.$nFolderId.', \'555-001\', \'ppav\', \'candi\')';
    }
    else
      $nFolderId = $anNewFolderPk[$asData['consultantpk']];

    //label 	parentfolderfk 	rank 	itemfk
    $nNewItemRank++;
    $asMyItemInsert[] = '("Candidate #'.$asData['candi_rno'].'", '.$nFolderId.', '.$nNewItemRank.', '.$asData['candi_rno'].')';
    
  }

  $nCandidate = count($asMyInsert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/



  if(!empty($asMyInsert))
  {
    $sMyQuery = 'INSERT INTO `folder` (folderpk, parentfolderfk, `label`, `rank`, ownerloginfk, `private`) VALUES '.implode(' ,', $asMyInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      echo '<pre>';
      var_dump($sMyQuery);
      echo '</pre>';
      exit('error inserting folder during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
    
    echo 'inserted '.count($asMyInsert).' folders';
  }



  if(!empty($asMyLinkInsert))
  {
    $sMyQuery = 'INSERT INTO `folder_link` (folderfk,cp_uid,cp_action,cp_type) VALUES '.implode(' ,', $asMyLinkInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting folder link during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
    echo 'inserted '.count($asMyLinkInsert).' folder links';
  }



  if(!empty($asMyItemInsert))
  {
    $sMyQuery = 'INSERT INTO `folder_item` (`label`,parentfolderfk,`rank`,itemfk) VALUES '.implode(' ,', $asMyItemInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting folder items during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
    echo 'inserted '.count($asMyItemInsert).' folder items';
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
