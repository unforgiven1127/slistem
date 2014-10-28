<?php

///*$oPGCx = @pg_connect("dbname=slystem_live user=slate password=slate");*/ $oPGCx = @pg_connect("host=10.0.81.110 port=5432 dbname=slystem_live user=slate password=slate");
//$oPGCx = @pg_connect("dbname=slystem_live user=slate password=slate");
$oPGCx = @pg_connect("host=10.0.81.110 port=5432 dbname=slystem_live user=slate password=slate");
if(!$oPGCx)
{
  var_dump(pg_errormessage($oPGCx));
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




//need to find a solution for those
$asProblematicUsers = array('fastars@slate.co.jp', 'admin@slate.co.jp', 'encoders@slate.jp', 'kwilliams@slate.co.jp',
              'aboucher@slate.co.jp', 'mc@slate.jp', 'wl@slate.jp', 'jllagatic@slate.co.jp', 'mp@slate.jp',
              'cmcternan@slate.co.jp', 'mdiraya@slate.co.jp', 'trial@slate.co.jp', 'mmoir@slate.co.jp', 'mr@slate.co.jp');

$anConsRole = array();
$anConsRole[1] = 0; //root
$anConsRole[2] = 0; //admin
$anConsRole[3] = 108;  // principal
$anConsRole[4] = 108; //group_manager
$anConsRole[5] = 108; //sr_cons
$anConsRole[6] = 108; //cons
$anConsRole[7] = 109; //team_leader
$anConsRole[8] = 109; //sr_analyst
$anConsRole[9] = 109; //analyst
$anConsRole[10] = 0; //data_entry
$anConsRole[11] = 0; //regional_recruiter




// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//conversion table nationalities
$sQuery = ' SELECT cou.iso as iso2, nat.system_nationalitypk
  FROM system_country as cou
  INNER JOIN system_nationality as nat ON (nat.iso = cou.iso3) ';
$oResult = mysql_query($sQuery);
$asNationality = array();

while($asResult = mysql_fetch_assoc($oResult))
{
  $asNationality[$asResult['iso2']] = $asResult['system_nationalitypk'];
}
$asNationality['UK'] = 50;  //GBR
$asNationality['RO'] = 38;  //ROM /vs ROU
$asNationality['KO'] = 24;  //PRK
$asNationality['SY'] = 56;  //PRK
//var_dump($asNationality);


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//conversion table teams
$anMatchGroup = array();
$anMatchGroup[1] = 6;
$anMatchGroup[2] = 1;
$anMatchGroup[3] = 7;
$anMatchGroup[4] = 4;
$anMatchGroup[5] = 2;
$anMatchGroup[6] = 3;
$anMatchGroup[7] = 8;
$anMatchGroup[8] = 9;
$anMatchGroup[9] = 9;
$anMatchGroup[10] = 10;
$anMatchGroup[11] = 109;
$anMatchGroup['SCKK (Tokyo)'] = 106;
$anMatchGroup['SGHC (Manila)'] = 107;
$anMatchGroup['SGL (HK)'] = 110;
$anMatchGroup['Slate-GHC (Canada)'] = 111;

$sDateNow = date('Y-m-d H:i:s');
$nCount = 0;


while(!$bError && !$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = 'SELECT  DISTINCT on (cons_tbl.cons_cid) cons_tbl.cons_cid, cons_tbl.*, rev.region  FROM cons_tbl

LEFT JOIN cons_revenue as rev ON (rev.cons_id = cons_tbl.cons_cid)

LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    $bError = true;
    break;
  }

  $asMyInert = array();
  $asGroup = array();
  $asPref = array();
  while($asData = pg_fetch_assoc($oPgResult))
  {
    $nCount++;


    //find a solution to those....
    /*if(in_array(trim($asData['cons_email']), $asProblematicUsers))
    {
      $asData['cons_email'] = '_'.$nCount.'_'.$asData['cons_email'];
      echo '<br /><span style="color: blue;">Need to change email address for ['.$asData['cons_cid'].'  ->'.$asData['cons_email'].'].</span> ';
    }*/


    /* sic, location, group, user type, listed, sic, sig...  in rights, preferences or other components*/
    $asData['cons_cid'] = '"'.mysql_real_escape_string(trim($asData['cons_cid'])).'"';
    $asData['cons_passwd'] = '"'.mysql_real_escape_string(trim($asData['cons_passwd'])).'"';
    $asData['consultantpk'] = (int)$asData['consultantpk'];

    $asName = explode(' ', trim($asData['cons_name']));
    if(count($asName) < 2)
    {
      echo '<br /><span style="color: orange;">consultant name is 1 word ['.$asData['cons_name'].'].</span> ';
      $asName[1] = '';
    }
    $sFirstname = '"'.mysql_real_escape_string($asName[0]).'"';
    unset($asName[0]);
    $sLastname = '"'.mysql_real_escape_string(implode(' ', $asName)).'"';


    $asData['cons_phone'] = '"'.mysql_real_escape_string(trim($asData['cons_phone'])).'"';
    $asData['cons_level'] = (int)$asData['cons_level'];
    if($asData['cons_level'] == 1 || $asData['cons_level'] == 2)
    {
      $asData['cons_level'] = 1;

      $asPref[] = '
('.$asData['consultantpk'].', 65, \'webmail\'),
('.$asData['consultantpk'].', 66, \'25\'),
('.$asData['consultantpk'].', 67, \'fullH\'),
('.$asData['consultantpk'].', 68, \'candidate_sl3\'),
('.$asData['consultantpk'].', 81, \'50\'),
('.$asData['consultantpk'].', 82, \'1\'),
('.$asData['consultantpk'].', 135, \'0\'),
('.$asData['consultantpk'].', 137, \'1\'),
('.$asData['consultantpk'].', 138, \'none\'),
('.$asData['consultantpk'].', 139, \'a:5:{i:0;s:10:"date_birth";i:1;s:6:"salary";i:2;s:10:"department";i:3;s:4:"note";i:4;s:5:"title";}\') ';
    }
    else
      $asData['cons_level'] = 0;

    $asData['cons_email'] = '"'.mysql_real_escape_string(trim($asData['cons_email'])).'"';
    $asData['cons_sig'] = '"'.mysql_real_escape_string(trim($asData['cons_sig'])).'"';
    $asData['cons_nationality'] = $asNationality[$asData['cons_nationality']];
    $asData['cons_regist'] = date('Y-m-d :h:i:s', strtotime($asData['cons_regist']));


    $sJobTitle = '';
    if(isset($anConsRole[$asData['cons_role']]))
    {
      if($anConsRole[$asData['cons_role']] == 108)
        $sJobTitle = 'Consultant';

      if($anConsRole[$asData['cons_role']] == 109)
        $sJobTitle = 'Researcher';
    }


    $asMyInert[] = '('.(int)$asData['consultantpk'].' , '.$asData['cons_cid'].', '.$asData['cons_passwd'].',
      '.$asData['cons_cid'].', '.$asData['cons_email'].', '.$sLastname.', '.$sFirstname.', '.$asData['cons_phone'].',
      '.$asData['cons_level'].',  0, '.$asData['cons_email'].', '.$asData['cons_passwd'].',
       "143", "mail.slate.co.jp", '.$asData['cons_sig'].', '.$asData['cons_nationality'].', "'.$asData['cons_regist'].'", "'.$sDateNow.'", "'.$sJobTitle.'")';


    //group management
    if(!empty($asData['group_id']))
    {
      if(isset($anMatchGroup[(int)$asData['group_id']]))
      {
        $nGroup = $anMatchGroup[(int)$asData['group_id']];
        $sKey = $nGroup.'_'.$asData['consultantpk'];

        $asGroup[$sKey] = '('.$nGroup.' , '.(int)$asData['consultantpk'].')';
      }
    }

    if(!empty($asData['region']) && isset($anMatchGroup[$asData['region']]))
    {
      $nGroup = $anMatchGroup[$asData['region']];
      $sKey = $nGroup.'_'.$asData['consultantpk'];
      $asGroup[$sKey] = '('.$nGroup.' , '.(int)$asData['consultantpk'].')';
    }

    if(!empty($asData['cons_role']) && isset($anConsRole[$asData['cons_role']]) && !empty($anConsRole[$asData['cons_role']]))
    {
      $nGroup = $anConsRole[$asData['cons_role']];
      $sKey = $nGroup.'_'.$asData['consultantpk'];
      $asGroup[$sKey] = '('.$nGroup.','.(int)$asData['consultantpk'].')';

      //analysts
      if($asData['cons_role'] == 8 || $asData['cons_role'] == 9)
      {
        $sKey = '114_'.$asData['consultantpk'];
        $asGroup[$sKey] = '(114,'.(int)$asData['consultantpk'].')';
      }
    }

    if((int)$asData['calls_id'] === 1)
    {
      $sKey = '115_'.$asData['consultantpk'];
      $asGroup[$sKey] = ' (115,'.(int)$asData['consultantpk'].')';
    }

  }

  $nCandidate = count($asMyInert);
  echo $nCandidate.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_dump($asMyInert); echo '</pre><hr />';*/

  $sMyQuery = 'INSERT INTO `login` (`loginpk`,`id`,`password`,`pseudo`,`email`,`lastname`,`firstname`,`phone_ext`,`status` , `is_admin`,
 `webmail` , `webpassword` , `mailport` , `Imap` , `signature`, `nationalityfk`, date_create, date_update, `position`) VALUES '.implode(' ,', $asMyInert);

  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting consultants during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }


  /*echo '<pre>'; var_dump($asGroup);echo '</pre>';*/
  //manage groups
  $sMyQuery = 'INSERT INTO `login_group_member` (`login_groupfk`,`loginfk`) VALUES '.implode(', ', $asGroup);
  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting group members during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }


  $sMyQuery = 'INSERT INTO `settings_user` (`loginfk`, `settingsfk`, `value`) VALUES '.implode(' ,', $asPref);
  $bInserted = mysql_query($sMyQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sMyQuery);
    exit('error inserting preferences during pass #'.$nPass.' / offset: '.$nLimitOffset);
  }


  if($nCandidate < $nRowsByBatch)
  {
    $bDone = true;
    echo '<br /><span style="color: green;"> --> treatead '.$nCandidate.' on last batch, looks done. <span> ';
  }

  $nPass++;
}

if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}

