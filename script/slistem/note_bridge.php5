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
  $sPgQuery = 'SELECT no.*, co.consultantpk FROM notes_tbl as no
    INNER JOIN cons_tbl as co ON (co.cons_cid = no.note_cid)
    ORDER BY note_regist ASC
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.' ';

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

      $asData['note_rno'] = (int)$asData['note_rno'];
      $asData['notepk'] = (int)$asData['notepk'] + 500000;  // 0-10000 for company / 100K to 500K charnote
      $asData['note_html'] = (int)$asData['note_html'];
      $asData['consultantpk'] = (int)$asData['consultantpk'];
      $asData['note_regist'] = '"'.mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($asData['note_regist']))).'"';

      $sEncoding = mb_detect_encoding($asData['note_notes']);
      $asData['note_notes'] = mb_convert_encoding($asData['note_notes'], 'UTF-8', $sEncoding);

      $asData['note_notes'] = html_entity_decode($asData['note_notes']);
      $sRowNote = strip_tags($asData['note_notes']);

      //remove crappy html if not an htmlnote
      if($asData['note_html'])
      {
        $asData['note_notes'] = '"'.mysql_real_escape_string(addslashes(trim($asData['note_notes']))).'"';
      }
      else
      {
        $asData['note_notes'] = str_replace("\r\n", "\n", trim($sRowNote));
        $asData['note_notes'] = str_replace("\r", "\n", $asData['note_notes']);
        $asData['note_notes'] = str_replace( array("\n\n\n\n", "\n\n\n"), array("\n\n", "\n"), $asData['note_notes']);

        /*$asData['note_notes'] = trim($sRowNote);*/

        $asData['note_notes'] = str_replace('\\\\', '', $asData['note_notes']);
        $asData['note_notes'] = str_replace('\\\\\\', '', $asData['note_notes']);
        //echo '<pre>'.$asData['note_notes'].'</pre><hr />';
        //echo '<pre>'.$sRowNote.'</pre><hr /><hr /><hr /><hr />';

        $asData['note_notes'] = '"'.mysql_real_escape_string(nl2br($asData['note_notes'])).'"';

      }

      /*if(strlen($sRowNote) > 100)
        $asData['title'] = substr($sRowNote, 0, 97).'...';
      else
        $asData['title'] = $sRowNote;*/

      $asData['title'] = '""';

      //(`eventpk`,`type`,`title`,`content`,`date_create`,`date_display`,`created_by`)
      $asMyInsert[] = '('.$asData['notepk'].' , "note", '.$asData['title'].', '.$asData['note_notes'].',
        '.$asData['note_regist'].', '.$asData['note_regist'].', '.$asData['consultantpk'].')';

      //(`eventfk`,`cp_uid`,`cp_action`,`cp_type`,`cp_pk`)
      $asMyLinkInsert[] = '('.$asData['notepk'].' ,"555-001", "ppav", "candi", '.$asData['note_rno'].')';

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
