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
  $sPgQuery = 'SELECT cha.*, co.consultantpk FROM character_tbl as cha
    INNER JOIN cons_tbl as co ON (co.cons_cid = cha.cha_cid)
    ORDER BY characterpk DESC
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

      $asData['cha_rno'] = (int)$asData['cha_rno'];
      $asData['characterpk'] = (100000+(int)$asData['characterpk']);  //0-100K for company desc
      $asData['cha_html'] = (int)$asData['cha_html'];
      $asData['consultantpk'] = (int)$asData['consultantpk'];

      $asData['cha_regist'] = '"'.mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($asData['cha_regist']))).'"';

      $sRowNote = strip_tags($asData['cha_character']);

      $asData['cha_character'] = html_entity_decode($asData['cha_character']);

      //remove crappy html if not an htmlnote
      if($asData['cha_html'])
      {
        $asData['cha_character'] = '"'.mysql_real_escape_string(addslashes(trim($asData['cha_character'])), $oMyCx).'"';
      }
      else
      {
        $asData['cha_character'] = str_replace("\r\n", "\n", trim($asData['cha_character']));
        $asData['cha_character'] = str_replace("\r", "\n", $asData['cha_character']);
        $asData['cha_character'] = str_replace( array("\n\n\n\n", "\n\n\n"), array("\n\n", "\n"), $asData['cha_character']);

        $asData['cha_character'] = str_replace('\\\\', '', $asData['cha_character']);
        $asData['cha_character'] = str_replace('\\\\\\', '', $asData['cha_character']);

        $asData['cha_character'] = '"'.mysql_real_escape_string(addslashes(nl2br(trim($sRowNote))), $oMyCx).'"';
      }

      /*if(strlen($sRowNote) > 100)
        $asData['title'] = substr($sRowNote, 0, 97).'...';
      else
        $asData['title'] = $sRowNote;
       $asData['title'] = '"'.mysql_real_escape_string(addslashes(trim($asData['title'])), $oMyCx).'"';*/
       $asData['title'] = '""';

      //(`eventpk`,`type`,`title`,`content`,`date_create`,`date_display`,`created_by`)
      $asMyInsert[] = '('.$asData['characterpk'].' , "character", '.$asData['title'].', '.$asData['cha_character'].',
        '.$asData['cha_regist'].', '.$asData['cha_regist'].', '.$asData['consultantpk'].')';

      //(`eventfk`,`cp_uid`,`cp_action`,`cp_type`,`cp_pk`)
      $asMyLinkInsert[] = '('.$asData['characterpk'].' ,"555-001", "ppav", "candi", '.$asData['cha_rno'].')';

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
    exit('error inserting char notes during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }

  $bInserted = mysql_query($sMyLinkQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyLinkQuery);
    exit('error inserting char_notes_link during pass #'.$nPass.' / offset: '.$nLimitOffset);
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
