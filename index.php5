<?php

//if you want session to be handled in database
require_once('./common/lib/db_session.inc.php5');

session_start();
header('Cache-Control: no-cache');

// Connect to redis
$GLOBALS['redis'] = new Redis();
$GLOBALS['redis']->pconnect('127.0.0.1');


//$nMemory = memory_get_usage();

//prevent infinite loops if assert fires another assert (mailAssert) that is firing a new assert that ...
$_SESSION['assert'] = 0;
$_SESSION['mail_assert'] = 0;

require_once('./conf/main_config.inc.php5');
require_once('./common/lib/global_func.inc.php5');
require_once('./component/dependency.inc.php5');

require_once('./conf/custom_config/'.CONST_WEBSITE.'/config.inc.php5');

//include here the list of all the tables/fields/controls/type. Used in model, but if included here it's loaded only once.
require_once('./common/lib/model.db_map.inc.php5');

CDependency::initialize();

sanitizeUrl();

$sUid = getValue(CONST_URL_UID);
$sAction = getValue(CONST_URL_ACTION);
$sType = getValue(CONST_URL_TYPE);
$nPk = (int)getValue(CONST_URL_PK, 0);
$sPg = getValue(CONST_URL_MODE, CONST_URL_PARAM_PAGE_NORMAL);

$oPage = CDependency::getCpPage();
echo $oPage->getPage($sUid, $sAction, $sType, $nPk, $sPg);
//$nNewMemory = memory_get_usage();
//echo 'size: '.round(($nNewMemory - $nMemory)/1024, 2).'KB';


//uncommnet to use
//include($_SERVER['DOCUMENT_ROOT'].'/common/maintenance/check_ab_doc.php5');


if(CONST_SQL_PROFILING)
{
  $oDb = CDependency::getComponentByName('database');

  $asDbData = $oDb->getProfilingData();
  $sData = '<div id="dbProfiling" style="width: 100%;">
      <h4>'.date('Y-m-d H:i:s').' - '.$asDbData['nb_queries'].' queries - '.$oPage->getRequestedUrl().'</h4>'.
            dump($asDbData, null, false).'
      </div>';

  $oFs = fopen($_SERVER['DOCUMENT_ROOT'].'/db_profile.html', 'a+');
  fputs($oFs, $sData);
  fclose($oFs);

  if($sPg == CONST_URL_PARAM_PAGE_NORMAL)
  {
    echo '<a href="javascript:;" onclick="document.getElementById(\'dbProfiling\').style.display = \'block\';"
      style="position: fixed; bottom: 0; right: 0;" >SQL profiling</a>
      <div id="dbProfiling" style="min-width: 900px; padding-left: 100px; display: none;">

      <h2>DB profiling for '.$oPage->getRequestedUrl().'</h2>'.
            dump($asDbData, null, false).'
      </div>';
  }

}
