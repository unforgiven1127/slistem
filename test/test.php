<?php

if(isset($_GET['size']))
{
  $sSize = strtolower($_GET['size']);
}
else
  $sSize = 'small';



switch($sSize)
{
  case 'small':

    echo 'This is a small file<br />';
    //100*10 characters
    for($nCount = 0; $nCount < 500; $nCount++)
    {
      echo rand(1111111111, 9999999999);
    }
    break;


    case 'medium':

    echo 'This is a medium file<br />';
    //100*10 characters
    for($nCount = 0; $nCount < 75000; $nCount++)
    {
      echo rand(1111111111, 9999999999);
    }
    break;



    case 'big':

    echo 'This is a big file<br />';
    //100*10 characters
    for($nCount = 0; $nCount < 200000; $nCount++)
    {
      echo rand(1111111111, 9999999999);
    }
    break;


    case 'xxl':

    echo 'This is a xxl file<br />';
    //100*10 characters
    for($nCount = 0; $nCount < 500000; $nCount++)
    {
      echo rand(1111111111, 9999999999);
    }
    break;


    case 'sql':

      $mysqli = new mysqli("127.0.0.1", "slistem", "THWj8YerbMWfK3yW", "slistem");
      if ($mysqli->connect_errno)
      {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        for($nCount = 0; $nCount < 500; $nCount++)
        {
          echo rand(1111111111, 9999999999);
        }
      }

      $oResult = $mysqli->query('SELECT * FROM sl_candidate WHERE sl_candidatepk LIKE "%'.rand(1, 100000).'%" ');
      echo '<pre>';
      while($asResult = $oResult->fetch_array(MYSQLI_ASSOC))
      {
        var_dump($asResult);
      }
      echo '</pre>';
    break;
}
