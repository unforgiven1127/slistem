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
  $sPgQuery = 'SELECT update_tbl.*,
    cpFrom.company_name as comp_From, cpTo.company_name as comp_To ,
    cons.consultantpk

    FROM update_tbl

    INNER JOIN cons_tbl as cons ON (cons.cons_cid = update_tbl.update_cons)
    INNER JOIN company_tbl as cpFrom ON (cpFrom.company_name LIKE update_tbl.updated_from)
    INNER JOIN company_tbl as cpTo ON (cpTo.company_name LIKE update_tbl.updated_to)

    WHERE update_id IN (

     SELECT MIN(upd.update_id) FROM update_tbl as upd

      WHERE upd.updated_field = \'candi_company\'
      AND upd.updated_from <> \'\'
      AND upd.updated_from <> upd.updated_to

      GROUP BY upd.update_candi, upd.updated_from, upd.updated_to
    )

    ORDER BY update_tbl.update_id ASC

    ';

  //  ONE TIME process  LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.'

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }


  $asMyInsert = array();
  $asMyLinkInsert = array();
  $nCount = 0;
  while($asData = pg_fetch_assoc($oPgResult))
  {

      $nCount++;
      $asData['notepk'] = 2000000 + $nCount;

      $asData['note_html'] = 1;
      $asData['consultantpk'] = (int)$asData['consultantpk'];

      $asData['update_date'] = substr($asData['update_date'], 0, 19);
      $asData['note_regist'] = '"'.mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($asData['update_date']))).'"';

      $sEncoding = mb_detect_encoding($asData['comp_from']);
      $asData['note_notes'] = 'This candidate moved from '.mb_convert_encoding('#'.$asData['updated_from'].' '.$asData['comp_from'], 'UTF-8', $sEncoding);

      $sEncoding = mb_detect_encoding($asData['comp_to']);
      $asData['note_notes'].= ' to '.mb_convert_encoding('#'.$asData['updated_to'].' '.$asData['comp_to'], 'UTF-8', $sEncoding);

      $asData['note_notes'] = addslashes($asData['note_notes']);
      $asData['title'] = '""';


      //displayed in the tab
      $asMyInsert[] = '('.$asData['notepk'].' , "cp_history", "", "'.$asData['note_notes'].'",
        '.$asData['note_regist'].', '.$asData['note_regist'].', '.$asData['consultantpk'].')';

      //(`eventfk`,`cp_uid`,`cp_action`,`cp_type`,`cp_pk`)
      $asMyLinkInsert[] = '('.$asData['notepk'].' ,"555-001", "ppav", "candi", '.$asData['notepk'].')';

      //for the complex search

      $nCount++;
      $asData['notepk'] = 2000000 + $nCount;

       $asMyInsert[] = '('.$asData['notepk'].' , "cp_hidden", "", "'.mb_convert_encoding($asData['comp_from'], 'UTF-8', $sEncoding).'",
        '.$asData['note_regist'].', '.$asData['note_regist'].', '.$asData['consultantpk'].')';

      $asMyLinkInsert[] = '('.$asData['notepk'].' ,"555-001", "ppav", "candi", '.$asData['notepk'].')';


  }

  $nCandidate = count($asMyInsert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `event` (`eventpk`,`type`,`title`,`content`,`date_create`,`date_display`,`created_by`) VALUES '.implode(' ,', $asMyInsert);
  $sMyLinkQuery = 'INSERT INTO `event_link` (`eventfk`,`cp_uid`,`cp_action`,`cp_type`,`cp_pk`) VALUES '.implode(' ,', $asMyLinkInsert);

  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting notes during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }

  $bInserted = mysql_query($sMyLinkQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyLinkQuery);
    exit('error inserting notes_link during pass #'.$nPass.' / offset: '.$nLimitOffset);
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
