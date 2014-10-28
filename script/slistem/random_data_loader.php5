<?php

$oMyCx = mysqli_init();
if(!$oMyCx->real_connect('localhost', 'bccrm', 'bcmedia'))
{
  echo $oMyCx->error();
  exit('can not connect to mysql');
}

$oMyCx->select_db('slistem');




/*
for($nCount = 350555; $nCount >= 300000; $nCount--)
{
  $nActivity = rand(1, 5);
  $asInsert = array();
  for($nActivities = $nActivity; $nActivities >= 0; $nActivities--)
    $asInsert[] = '("'.date('Y-m-d H:i:s').'", '.rand(1, 394).', "autogen activity", "", "555-001", "ppav", "candi", '.$nCount.', "https://slistem.devserv.com/", "asdasdasdas")';

  $sQuery = "INSERT INTO `login_system_history` (`date`, `userfk`, `action`, `component`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `uri`, `value`) VALUES ";
  $sQuery.= implode(', ', $asInsert);

  $bInserted = mysqli_query($oMyCx, $sQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sQuery);
    exit('error inserting data ');
  }

  //echo '-->'.$nCount.' ('.$nActivity.' activities)<br />';

  if($nCount%2000 == 0)
  {
    echo '-->2000 activity added<br />';
    flush(); ob_flush();
  }
}*/

/*
for($nCount = 351000; $nCount >= 300000; $nCount--)
{
  $nActivity = rand(0, 2);
  $asInsert = array();
  for($nActivities = $nActivity; $nActivities >= 0; $nActivities--)
    $asInsert[] = '("'.date('Y-m-d H:i:s').'", '.rand(1, 394).', "autogen activity update", "", "555-001", "ppau", "candi", '.$nCount.', "https://slistem.devserv.com/", "asdasdasdas")';

  $sQuery = "INSERT INTO `login_system_history` (`date`, `userfk`, `action`, `component`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `uri`, `value`) VALUES ";
  $sQuery.= implode(', ', $asInsert);

  $bInserted = mysqli_query($oMyCx, $sQuery);
  if(!$bInserted)
  {
    echo mysql_error();
    var_dump($sQuery);
    exit('error inserting data ');
  }

  //echo '-->'.$nCount.' ('.$nActivity.' activities)<br />';

  if($nCount%2000 == 0)
  {
    echo '-->2000 activity added<br />';
    flush(); ob_flush();
  }
}
*/

for($nCount = 351000; $nCount >= 300000; $nCount--)
{
  $nActivity = rand(0, 1);
  if($nActivity)
  {
    $asInsert = array();
    for($nActivities = $nActivity; $nActivities >= 0; $nActivities--)
      $asInsert[] = '("'.date('Y-m-d H:i:s').'", '.rand(1, 394).', "autogen activity update", "", "555-001", "ppax", "candi", '.$nCount.', "https://slistem.devserv.com/", "asdasdasdas")';

    $sQuery = "INSERT INTO `login_system_history` (`date`, `userfk`, `action`, `component`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `uri`, `value`) VALUES ";
    $sQuery.= implode(', ', $asInsert);

    $bInserted = mysqli_query($oMyCx, $sQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      var_dump($sQuery);
      exit('error inserting data ');
    }

    //echo '-->'.$nCount.' ('.$nActivity.' activities)<br />';

    if($nCount%2000 == 0)
    {
      echo '-->2000 activity added<br />';
      flush(); ob_flush();
    }
  }
}






?>
