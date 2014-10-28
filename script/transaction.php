<?php

$oMyCx = @mysqli_connect('localhost', 'bccrm', 'bcmedia');
if(!$oMyCx)
{
  echo mysql_error();
  exit('can not connect to mysql');
}

mysqli_select_db($oMyCx, 'inno');



$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);
echo '<br /><br />Starting with :  '.$asResult['nCount'].' results in table  !!';


/*
$sQuery = ' BEGIN ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);

echo '<br /><br /> '.$asResult['nCount'].' result in table ';


$sQuery = ' ROLLBACK ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);

echo '<br /><br />after roolback:  '.$asResult['nCount'].' result in table ';








echo '<br /><hr /><br /> ';


$sQuery = ' BEGIN ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);

echo '<br /><br /> '.$asResult['nCount'].' result in table ';


$sQuery = ' COMMIT ';
$oResult = mysqli_query($oMyCx, $sQuery);


$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);

echo '<br /><br />after commit:  '.$asResult['nCount'].' result in table ';
*/





mysqli_autocommit($oMyCx, FALSE);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' INSERT INTO transac (`id`, `date`, `data`) VALUES ("'.rand(1, 9999999).'", "2014-01-'.rand(1, 31).'", "asd asdas das dasd") ';
$oResult = mysqli_query($oMyCx, $sQuery);

$sQuery = ' SELECT count(*) as nCount FROM transac ';
$oResult = mysqli_query($oMyCx, $sQuery);
$asResult = mysqli_fetch_assoc($oResult);


mysqli_commit($oMyCx);

echo '<pre>';
var_dump($oResult);
var_dump(mysqli_error($oMyCx));
echo '</pre>';