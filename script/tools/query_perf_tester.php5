<?php
session_start();




include_once($_SERVER['DOCUMENT_ROOT'].'/conf/main_config.inc.php5');
include_once($_SERVER['DOCUMENT_ROOT'].'/common/lib/global_func.inc.php5');
include_once($_SERVER['DOCUMENT_ROOT'].'/component/dependency.inc.php5');

ini_set('include_path', '.;..;/opt/eclipse-workspace/bcm_svn/trunk/');
chdir($_SERVER['DOCUMENT_ROOT']);

if(empty($_POST))
{
  $_POST['nb_iteration'] = 1000;
  $_POST['query'] = $_POST['query2'] = '';
}
else
{

  $nIteration = (int)@$_POST['nb_iteration'];
  $sQuery = @$_POST['query'];
  $sQuery2 = @$_POST['query2'];

  if(empty($nIteration) || empty($nIteration))
  {
    echo '<h3>bad parameters...</h3>';
  }
  else
  {
    $oDatabase = CDependency::getComponentByName('database');
    $oDatabase->clearProfilingData();
    $nTotalTime1 = $nTotalTime2 = 0;


    for($nCount = 0; $nCount < $nIteration; $nCount++)
    {
      $oResult = $oDatabase->ExecuteQuery($sQuery);
    }

    $asData = $oDatabase->getProfilingData();
    foreach($asData["sql_data"] as $asQueryData)
    {
      $nTotalTime+= $asQueryData['time'];
    }

    echo '<h3>Query 1 => Total time is '.$nTotalTime.' ms / avg: '.round(($nTotalTime/$nCount), 2).'</h3>';
    //dump($asData);


    if(!empty($sQuery2))
    {
      sleep(2);

      $oDatabase->clearProfilingData();
      $nTotalTime = 0;

      for($nCount = 0; $nCount < $nIteration; $nCount++)
      {
        $oResult = $oDatabase->ExecuteQuery($sQuery2);
      }

      $asData = $oDatabase->getProfilingData();
      foreach($asData["sql_data"] as $asQueryData)
      {
        $nTotalTime+= $asQueryData['time'];
      }

      echo '<h3>Query 2 => Total time is '.$nTotalTime.' ms / avg: '.round(($nTotalTime/$nCount), 2).'</h3>';
      //dump($asData);
    }
  }

}




?>

<html>
  <head>
    <title>Tester</title>
  </head>
  <body>

    <form action="#" method="post">

      Nb iterations<br />
      <input type="text" name="nb_iteration" value="<?php echo $_POST['nb_iteration']; ?>" /><br />
      <br />

      Query<br />
      <textarea  name="query" style="min-width: 800px; min-height: 250px;"><?php echo $_POST['query']; ?></textarea><br />
      <br />

      Query 2<br />
      <textarea  name="query2" style="min-width: 800px; min-height: 250px;"><?php echo $_POST['query2']; ?></textarea><br />
      <br />

      <input type="submit" value="Test query" /><br /><br />

    </form>

  </body>
</html>








