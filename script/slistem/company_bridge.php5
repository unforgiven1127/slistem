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
  $sPgQuery = 'SELECT co.*, co1.consultantpk as creatorfk, co2.consultantpk as updaterfk  FROM company_tbl as co

LEFT JOIN cons_tbl as co1 ON (co1.cons_cid = co.company_creator)
LEFT JOIN cons_tbl as co2 ON (co2.cons_cid = co.company_last_updater) ORDER BY company_id DESC
LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  //Useless description to remove           27k                     15k
  $asDummyDesc = array('auto created', 'added by the company import script');


  $asMyInert = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
    if(empty($asData['company_name']))
      $asData['company_name'] = '- unknown -';

      $asData['company_name'] = html_entity_decode($asData['company_name']);
      $asData['company_name'] = '"'.mysql_real_escape_string(trim($asData['company_name'])).'"';
      $asData['company_added'] = '"'.mysql_real_escape_string(date('Y-m-d', strtotime($asData['company_added']))).'"';

      if(empty($asData['company_last_update']) || strtolower($asData['company_last_update']) == 'null')
        $asData['company_last_update'] = 'NULL';
      else
        $asData['company_last_update'] = '"'.mysql_real_escape_string(date('Y-m-d', strtotime($asData['company_last_update']))).'"';

      if(empty($asData['creatorfk']) || strtolower( $asData['creatorfk']) == 'null')
         $asData['creatorfk'] = 0;
      else
        $asData['creatorfk'] = (int)$asData['creatorfk'];

      if(empty($asData['updaterfk']) || strtolower( $asData['updaterfk']) == 'null')
         $asData['updaterfk'] = 'NULL';
      else
        $asData['updaterfk'] = (int)$asData['updaterfk'];

      $sDescription = strtolower(trim($asData['company_desc']));
      if(empty($sDescription) || in_array($sDescription, $asDummyDesc) || $sDescription == 'null')
      {
        $asData['company_desc'] = 'NULL';
        $sFts = '';
      }
      else
      {
        $sFts = '"'.mysql_real_escape_string(strip_tags(trim($asData['company_desc']))).'"';
        $asData['company_desc'] = '"'.mysql_real_escape_string(trim($asData['company_desc'])).'"';
      }



      $asData['company_address'] = trim($asData['company_address']);
      if(empty($asData['company_address']) || strtolower($asData['company_address']) == 'null')
        $asData['company_address'] = 'NULL';
      else
        $asData['company_address'] = '"'.mysql_real_escape_string(trim($asData['company_address'])).'"';

      if(strtolower($asData['company_client']) == 't')
         $asData['company_client'] = 1;
      else
        $asData['company_client'] = 0;

      if(strtolower($asData['company_nc_ok']) == 't')
         $asData['company_nc_ok'] = 1;
      else
        $asData['company_nc_ok'] = 0;

      $asData['company_level'] = strtolower($asData['company_level']);
      if($asData['company_level'] == 'a')
        $asData['company_level'] = 1;
      elseif($asData['company_level'] == 'b')
        $asData['company_level'] = 2;
      else
        $asData['company_level'] = 3;

      $asData['company_emp_jpn'] = trim($asData['company_emp_jpn']);
      if(empty($asData['company_emp_jpn']) || strtolower($asData['company_emp_jpn']) == 'null')
        $asData['company_emp_jpn'] = 'NULL';
      else
        $asData['company_emp_jpn'] = (int)$asData['company_emp_jpn'];


      /*
       *
        INSERT INTO `sl_company` (
`sl_companypk` , `date_created` , `created_by` ,
 `date_updated` , `updated_by` , `name` , `corporate_name` , `description` ,
 `address` , `is_client` , `is_nc_ok` , `level` ,
  `num_employee`
) ;
       */
      $asMyInert[] = '('.(int)$asData['company_id'].' , '.$asData['company_added'].', '.$asData['creatorfk'].',
        '.$asData['company_last_update'].', '.$asData['updaterfk'].', '.$asData['company_name'].', "", '.$asData['company_desc'].',
        '.$asData['company_address'].',  '.$asData['company_client'].', '.$asData['company_nc_ok'].', '.$asData['company_level'].',
        '.$asData['company_emp_jpn'].')';


      if($asData['company_desc'] != 'NULL')
      {
        $nEventPk = (int)$asData['company_id'];
        $asMyEvent[] = '('.$nEventPk.', "description", '.$asData['company_desc'].', '.$asData['company_added'].', '.$asData['company_added'].',
          '.$asData['creatorfk'].', '.$sFts.')';

        $asMyEventLink[] = '('.$nEventPk.', "555-001", "ppav", "comp", '.(int)$asData['company_id'].')';
      }
    }


  $nCandidate = count($asMyInert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `sl_company` (`sl_companypk` , `date_created` , `created_by` , `date_updated` , `updated_by` , `name` , `corporate_name` , `description` , `address` , `is_client` , `is_nc_ok` , `level` ,  `num_employee`)
    VALUES '.implode(' ,', $asMyInert);

  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting companies during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }

  if(!empty($asMyEvent))
  {

    echo '<br />Need to insert '.count($asMyEvent).' descriptions <br />';
    $sMyQuery = 'INSERT INTO `event` (eventpk, `type`, `content`,`date_create`,`date_display`,`created_by`, _fts) VALUES '.implode(' ,', $asMyEvent);
    $sMyLinkQuery = 'INSERT INTO `event_link` (`eventfk`,`cp_uid`,`cp_action`,`cp_type`,`cp_pk`) VALUES '.implode(' ,', $asMyEventLink);

    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting descriptions during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }

    $bInserted = mysql_query($sMyLinkQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting descriptions link during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }



  }



  if($nCandidate < $nRowsByBatch)
  {
    $bDone = true;
    echo '<br /><span style="color: green;"> --> treatead '.$nCandidate.' on last batch, looks done. span> ';
  }

  $nPass++;
}

if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}

?>



