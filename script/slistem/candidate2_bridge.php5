<?php

/*$oPGCx = @pg_connect("dbname=slystem_live user=slate password=slate");*/ $oPGCx = @pg_connect("host=10.0.81.110 port=5432 dbname=slystem_live user=slate password=slate");
if(!$oPGCx)
{
  exit('can not connect to postgresql');
}


$oMyCx = mysqli_init();
if(!$oMyCx->real_connect('localhost', 'slistem', 'THWj8YerbMWfK3yW'))
{
  echo $oMyCx->error();
  exit('can not connect to mysql');
}

$oMyCx->select_db('slistem');


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
  $sPgQuery = 'SELECT ca.*, co.consultantpk as loginfk , co.cons_name, loca.location_name
    FROM candidate_tbl as ca

LEFT JOIN cons_tbl as co ON (co.cons_cid = ca.candi_cid)
LEFT JOIN locations_tbl as loca ON (loca.location_id = ca.candi_location)

WHERE co.consultantpk IS NULL
AND candi_rno > 0

ORDER BY candi_rno DESC
LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMonth = array('', 'A',  'B',  'C',  'D',  'E',  'F',  'G',  'H',  'I',  'J',  'K',  'L');
  $asMyInert = array();
  $asMyInert2 = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
    if(empty($asData['candi_rno']) || !is_numeric($asData['candi_rno']))
      exit('candi_rno not a number ['.$asData['candi_rno'].']');

    if(empty($asData['candi_regist']))
      exit('empty candi_regist ['.$asData['candi_regist'].']');

    $nDateCreated = strtotime($asData['candi_regist']);
    $asData['candi_regist'] = '"'.date('Y-m-d H:i:s', $nDateCreated).'"';


    /*if(empty($asData['loginfk']) || !is_numeric($asData['loginfk']))
      exit('consultantPk empty or not a number ['.$asData['loginfk'].']');*/
    $asData['loginfk'] = 101;



    if(!is_numeric($asData['candi_status']))
      exit('status not a number ['.$asData['candi_status'].']');

    if(empty($asData['candi_status']))
      $asData['candi_status'] = 1;

    if(!is_numeric($asData['candi_sex']))
      exit('candi_sex not a number ['.$asData['candi_sex'].']');

    if(!is_numeric($asData['candi_nationality']))
      exit('candi_nationality not a number ['.$asData['candi_nationality'].']');

    if(!is_numeric($asData['candi_language']))
      exit('candi_language not a number ['.$asData['candi_language'].']');

    if(empty($asData['candi_playdate']) || strtolower($asData['candi_playdate']) == 'null')
      $asData['candi_playdate'] = 'NULL';
    else
      $asData['candi_playdate'] = '"'.date('Y-m-d H:i:s', strtotime($asData['candi_playdate'])).'"';


    if(empty($asData['candi_playfor']) || strtolower($asData['candi_playfor']) == 'null')
      $asData['candi_playfor'] = 'NULL';
    else
      $asData['candi_playfor'] = (int)$asData['candi_playfor'];

    if(empty($asData['candi_birth']) || strtolower($asData['candi_birth']) == 'null')
      $asData['candi_birth'] = 'NULL';
    else
      $asData['candi_birth'] = '"'.date('Y-m-d H:i:s', strtotime($asData['candi_birth'])).'"';

    if(empty($asData['candi_birth_approx']) || strtolower($asData['candi_birth_approx']) == 'null')
      $asData['candi_birth_approx'] = 'NULL';
    else
      $asData['candi_birth_approx'] = 1;

    if(empty($asData['is_cc']) || $asData['is_cc'] === 'f')
      $asData['is_cc'] = 0;
    else
      $asData['is_cc'] = 1;

    //First firstname, first lastname, pk, ...
    $asData['cons_name'] = trim($asData['cons_name']);
    $asName = explode(' ', $asData['cons_name']);
    if(empty($asData['location_name']))
      $asData['location_name'] = 'TOK';

    switch(count($asName))
    {
      case 1: $sRefId = substr($asName[0], 0, 3); break;
      case 2: $sRefId = substr($asName[0], 0, 1).substr($asName[1], 0, 2); break;
      case 3:
      default:
        $sRefId = substr($asName[0], 0, 1).substr($asName[1], 0, 1).substr($asName[2], 0, 1); break;
    }


    $sRefId.= $asData['loginfk'].substr($asData['location_name'], 0, 3).date('y', $nDateCreated).$asMonth[(int)date('m', $nDateCreated)].$asData['candi_rno'];
    $sRefId = strtoupper($sRefId);

    $asName = explode(' ', $asData['candi_fname']);
    foreach($asName as $nKey => $sWord)
      $asName[$nKey] = ucfirst(strtolower($sWord));

    $asData['candi_fname'] = '"'.mysqli_real_escape_string($oMyCx, implode(' ', $asName)).'"';

    $asName = explode(' ', $asData['candi_lname']);
    foreach($asName as $nKey => $sWord)
      $asName[$nKey] = ucfirst(strtolower($sWord));

    $asData['candi_lname'] = '"'.mysqli_real_escape_string($oMyCx, implode(' ', $asName)).'"';

    $asData['candi_title'] = '"'.mysqli_real_escape_string($oMyCx, $asData['candi_title']).'"';
    $asData['candi_dept'] = '"'.mysqli_real_escape_string($oMyCx, $asData['candi_dept']).'"';

    if(empty($asData['candi_collab']) || $asData['candi_collab'] == 'f')
      $asData['candi_collab'] = 0;
    else
      $asData['candi_collab'] = 1;


/*
`candidatepk` , `date_created` , `created_loginfk` ,
  `statusfk` , `sex` , `firstname` , `lastname` ,
  `nationalityfk` , `languagefk` , `play_date` , `play_for` ,
  `rating` , `date_birth` , `is_birth_estimation` , `is_client`)
 */

    //var_dump($asData['candi_rating']);
    //var_dump((float)$asData['candi_rating']);


$asData['candi_ag'] = (((int)$asData['candi_ag'] == 0)? 'NULL' : (int)$asData['candi_ag']);
$asData['candi_ap'] = (((int)$asData['candi_ap'] == 0)? 'NULL' : (int)$asData['candi_ap']);
$asData['candi_am'] = (((int)$asData['candi_am'] == 0)? 'NULL' : (int)$asData['candi_am']);
$asData['candi_mp'] = (((int)$asData['candi_mp'] == 0)? 'NULL' : (int)$asData['candi_mp']);
$asData['candi_io'] = (((int)$asData['candi_io'] == 0)? 'NULL' : (int)$asData['candi_io']);
$asData['candi_ex'] = (((int)$asData['candi_ex'] == 0)? 'NULL' : (int)$asData['candi_ex']);
$asData['candi_fx'] = (((int)$asData['candi_fx'] == 0)? 'NULL' : (int)$asData['candi_fx']);
$asData['candi_ch'] = (((int)$asData['candi_ch'] == 0)? 'NULL' : (int)$asData['candi_ch']);
$asData['candi_ed'] = (((int)$asData['candi_ed'] == 0)? 'NULL' : (int)$asData['candi_ed']);
$asData['candi_pl'] = (((int)$asData['candi_pl'] == 0)? 'NULL' : (int)$asData['candi_pl']);
$asData['candi_e'] = (((int)$asData['candi_e'] == 0)? 'NULL' : (int)$asData['candi_e']);


    $asMyInert[] = '('.(int)$asData['candi_rno'].' , '.$asData['candi_regist'].', '.(int)$asData['loginfk'].',
      '.(int)$asData['candi_status'].', '.$asData['candi_sex'].', '.$asData['candi_fname'].', '.$asData['candi_lname'].',
      '.(int)$asData['candi_nationality'].' , '.(int)$asData['candi_location'].' , '.(int)$asData['candi_language'].' , '.$asData['candi_playdate'].' ,
      '.$asData['candi_playfor'].' , "'.(float)$asData['candi_rating'].'", '.$asData['candi_birth'].',
      '.$asData['candi_birth_approx'].' , '.$asData['is_cc'].'

,'.(int)$asData['candi_cpa'].', '.(int)$asData['candi_mba'].', '.(int)$asData['candi_collab'].'
,'.$asData['candi_ag'].', '.$asData['candi_ap'].', '.$asData['candi_am'].', '.$asData['candi_mp'].', '.$asData['candi_io'].'
,'.$asData['candi_ex'].', '.$asData['candi_fx'].', '.$asData['candi_ch'].', '.$asData['candi_ed'].', '.$asData['candi_pl'].', '.$asData['candi_e'].') ';




/*INSERT INTO `sl_candidate_profile` (
`candidatefk` , `companyfk`, `date_created` ,
`created_by` , `date_updated` , `updated_by` , `industryfk` , `occupationfk` ,
`title` , `department` , `grade` , `salary` , `bonus` */

    $asMyInert2[] = '('.(int)$asData['candi_rno'].' , '.(int)$asData['candi_company_id'].','.$asData['candi_regist'].',
      '.(int)$asData['loginfk'].', NULL, NULL, '.(int)$asData['candi_industry'].', '.(int)$asData['candi_occupation'].',
      '.$asData['candi_title'].', '.$asData['candi_dept'].', '.(int)$asData['candi_grade'].', '.(int)$asData['candi_salary'].',
      '.(int)$asData['candi_bonus'].', "'.$sRefId.'")';

  }



  $nCandidate = count($asMyInert);

  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInert); echo '</pre><hr />';*/

  //insert candidates
  $sMyQuery = 'INSERT INTO `sl_candidate` (`sl_candidatepk` , `date_created` , `created_by` , `statusfk` , `sex` , `firstname` , `lastname` , `nationalityfk`, `locationfk`, `languagefk` , `play_date` , `play_for` , `rating` , `date_birth` , `is_birth_estimation` , `is_client`
    ,cpa, mba, is_collaborator, skill_ag, skill_ap, skill_am, skill_mp, skill_in, skill_ex, skill_fx, skill_ch, skill_ed, skill_pl, skill_e)
    VALUES '.implode(' ,', $asMyInert);

  echo $sMyQuery.';';


  //Limk candidates to their company

  $sMyQuery = 'INSERT INTO `sl_candidate_profile` (`candidatefk` , `companyfk`, `date_created` , `created_by` , `date_updated` , `updated_by` , `industryfk` , `occupationfk` , `title` , `department` , `grade` , `salary` , `bonus`, `uid`)
    VALUES '.implode(' ,', $asMyInert2);

  echo $sMyQuery.';';

  if($nCandidate < $nRowsByBatch)
  {
    $bDone = true;
    echo '<br /><span style="color: green;"> --> treatead '.$nCandidate.' on last batch, looks done. span> ';
  }

  $nPass++;
  flush();
  ob_flush();
}

if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}
