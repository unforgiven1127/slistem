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
  $sPgQuery = 'SELECT prona.*, cons.consultantpk , pro.pro_rno as itemfk
    FROM projectname_tbl as prona

    INNER JOIN cons_tbl as cons ON (cons.cons_cid = trim(prona.prona_cid))
    LEFT JOIN project_tbl as pro ON (pro.pro_pno = prona.prona_pno)

    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

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

    //	prona_cid 	prona_state 	prona_readable 	prona_writable 	prona_type 	prona_style 	prona_locked 	prona_is_project 	prona_date

    $asData['prona_pno'] = (int)$asData['prona_pno'];
    $asData['consultantpk'] = (int)$asData['consultantpk'];
    $asData['itemfk'] = (int)$asData['itemfk'];

    $sEncoding = mb_detect_encoding($asData['prona_pname']);
    $asData['prona_pname'] = mb_convert_encoding($asData['prona_pname'], 'UTF-8', $sEncoding);

    $asData['prona_pname'] = '"'.mysql_real_escape_string(addslashes(trim($asData['prona_pname']))).'"';

    if($asData['prona_readable'] || $asData['prona_writable'])
      $nVisibility = 0; //public
    else
      $nVisibility = 1; //private

    if($asData['prona_type'] == 'company')
      $asData['prona_type'] = 'comp';
    else
      $asData['prona_type'] = 'candi';





    if(!isset($asMyInsert[$asData['prona_pno']]))
    {
      $nFolderRank++;

      //folderpk parentfolderfk 	label 	rank 	ownerloginfk  	private
      $asMyInsert[$asData['prona_pno']] = '('.$asData['prona_pno'].', 0, '.$asData['prona_pname'].',
        '.$nFolderRank.', '.$asData['consultantpk'].', '.$nVisibility.')';

      // folderfk 	cp_uid 	cp_action 	cp_type
      $asMyLinkInsert[] = '('.$asData['prona_pno'].', \'555-001\', \'ppav\', \''.$asData['prona_type'].'\')';
    }

    if($asData['itemfk'])
    {

      if($asData['prona_type'] == 'candi')
        $sLabel = 'Candidate #'.$asData['itemfk'];
      else
        $sLabel = 'Company #'.$asData['itemfk'];

      if(isset($anItemRank[$asData['prona_pno']]))
        $anItemRank[$asData['prona_pno']]++;
      else
        $anItemRank[$asData['prona_pno']] = 1;

      //label 	parentfolderfk 	rank 	itemfk
      $asMyItemInsert[] = '("'.$sLabel.'", '.$asData['prona_pno'].', '.$anItemRank[$asData['prona_pno']].', \''.$asData['itemfk'].'\')';
    }
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
