<?php

require ($_SERVER['DOCUMENT_ROOT'].'/common/lib/global_func.inc.php5');


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



$asJdStatus = array();
$asJdStatus[0] = 0;
$asJdStatus[1] = 1;
$asJdStatus[2] = 1;
$asJdStatus[3] = 0;
$asJdStatus[4] = 0;
$asJdStatus[5] = 0;





while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT jd_tbl.* , cons.consultantpk
    FROM jd_tbl


    INNER JOIN cons_tbl as cons ON (cons.cons_cid = jd_cons_id)
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInsert = array();
  $asMyLinkInsert = array();
  $asMyDetailInsert = array();

  while($asData = pg_fetch_assoc($oPgResult))
  {
    // 	jd_id 	jd_company_id 	jd_cons_id 		jd_title 	jd_dept 	jd_location 	jd_responsibilities 	jd_desc 	jd_type
    //	jd_requirements 	jd_status 	jd_public 		jd_created 	jd_industry 	jd_age_from 	jd_age_to
    //	jd_english 	jd_salary_from 	jd_salary_to 	jd_updated 	jd_confidential 	jd_owner 	jd_group_id
    //	jd_career 	jd_japanese 	jd_priority
    $asData['jd_id'] = (int)$asData['jd_id'];
    $asData['jd_company_id'] = (int)$asData['jd_company_id'];
    $asData['consultantpk'] = (int)$asData['consultantpk'];


    //jd_location ? type? group id ? jd_priority?
    $asData['jd_title'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_title']))).'"';
    $asData['jd_dept'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_dept']))).'"';
    $asData['jd_responsibilities'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_responsibilities']))).'"';
    $asData['jd_desc'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_desc']))).'"';
    $asData['jd_requirements'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_requirements']))).'"';


    /*$sEncoding = mb_detect_encoding($asData['jd_title']);
    $asData['jd_title'] = mb_convert_encoding($asData['jd_title'], 'UTF-8', $sEncoding);

    $sEncoding = mb_detect_encoding($asData['jd_dept']);
    $asData['jd_dept'] = mb_convert_encoding($asData['jd_dept'], 'UTF-8', $sEncoding);

    $sEncoding = mb_detect_encoding($asData['jd_responsibilities']);
    $asData['jd_responsibilities'] = mb_convert_encoding($asData['jd_responsibilities'], 'UTF-8', $sEncoding);

    $sEncoding = mb_detect_encoding($asData['jd_desc']);
    $asData['jd_desc'] = mb_convert_encoding($asData['jd_desc'], 'UTF-8', $sEncoding);

    $sEncoding = mb_detect_encoding($asData['jd_requirements']);
    $asData['jd_requirements'] = mb_convert_encoding($asData['jd_requirements'], 'UTF-8', $sEncoding);*/


    $asData['jd_status'] = $asJdStatus[(int)$asData['jd_status']];
    if($asData['jd_public'] == 'f')
      $asData['jd_public'] = 0;
    else
      $asData['jd_public'] = 1;

    $asData['jd_created'] = date('Y-m-d H:i:s', strtotime($asData['jd_created']));
    $asData['jd_industry'] = (int)$asData['jd_industry'];
    $asData['jd_age_from'] = (int)$asData['jd_age_from'];
    $asData['jd_age_to'] = (int)$asData['jd_age_to'];
    $asData['jd_english'] = (int)$asData['jd_english'];
    $asData['jd_salary_from'] = (int)$asData['jd_salary_from'] * 1000000;
    $asData['jd_salary_to'] = (int)$asData['jd_salary_to'] * 1000000;
    //$asData['jd_updated'] = date('Y-m-d H:i:s', strtotime($asData['jd_updated']));

    $asData['jd_career'] = '"'.mysql_real_escape_string(addslashes(trim($asData['jd_career']))).'"';
    $asData['jd_japanese'] = (int)$asData['jd_japanese'];
    $asData['jd_priority'] = (int)$asData['jd_priority'];

    $sOverAllText = $asData['jd_title'].' '.$asData['jd_responsibilities'].' '.$asData['jd_desc'].' '.$asData['jd_requirements'];

    // sl_positionpk 	companyfk 	date_created 	created_by 	status
    // industryfk 	age_from 	age_to 	salary_from 	salary_to
    // 	lvl_japanese 	lvl_english
    $asMyInsert[] = '('.$asData['jd_id'].', '.$asData['jd_company_id'].', "'.$asData['jd_created'].'", '.$asData['consultantpk'].', '.$asData['jd_status'].',
      '.$asData['jd_industry'].', '.$asData['jd_age_from'].', '.$asData['jd_age_to'].', '.$asData['jd_salary_from'].', '.$asData['jd_salary_to'].',
      '.$asData['jd_japanese'].', '.$asData['jd_english'].')';


    $sLanguage = getTextLangType($sOverAllText);
    if($sLanguage != 'en')
      $sLanguage = 'jp';

    if(empty($asData['jd_priority']))
      $nModeration = 1;
    else
      $nModeration = 0;

    // 	positionfk 	date_created 	created_by 	language 	title
    // 	career_level 	description 	requirements 	responsabilities 	content_html 	is_public
    //  moderation

    $asMyDetailInsert[] = '('.$asData['jd_id'].', "'.$asData['jd_created'].'", '.$asData['consultantpk'].', "'.$sLanguage.'", '.$asData['jd_title'].',
      '.$asData['jd_career'].', '.$asData['jd_desc'].', '.$asData['jd_requirements'].', '.$asData['jd_responsibilities'].', 1, '.$asData['jd_public'].',
      '.$nModeration.')';

  }

  $nCandidate = count($asMyInsert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInsert); echo '</pre><hr />';*/



  if(!empty($asMyInsert))
  {
    $sMyQuery = 'INSERT INTO `sl_position` (sl_positionpk,companyfk,date_created,created_by,status,industryfk,age_from,age_to,salary_from,salary_to,lvl_japanese,lvl_english)
      VALUES '.implode(' ,', $asMyInsert);
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



  if(!empty($asMyDetailInsert))
  {
    $sMyQuery = 'INSERT INTO `sl_position_detail` (positionfk,date_created,created_by,language,title,career_level,description,requirements,responsabilities,content_html,is_public, moderation) VALUES '.implode(' ,', $asMyDetailInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sMyQuery);
      exit('error inserting positions link during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
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