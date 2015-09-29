<?php

define('URL_FORMAT','_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS');

function array_search_multi($needle, $haystack)
{
  for($i = 0, $l = count($haystack); $i < $l; ++$i)
  {
    $bResult = strpos($haystack[$i],$needle);
    if($bResult !== false)
    return $i;
  }
  return false;
}

function array_first($pavArray)
{
  if(!assert('is_array($pavArray)'))
    return null;

  //PHP >= 5.4
  //return array_values($pavArray)[0];

  foreach($pavArray as $vData)
    return $vData;
}
function array_first_key($pavArray)
{
  if(!assert('is_array($pavArray)'))
    return null;

  foreach($pavArray as $vkey => $vData)
    return $vkey;
}


function is_date($psDate, $psFormat = 'Y-m-d', $pbStrict = true)
{
  if(empty($psDate))
  {
    if($pbStrict)
      return false;
    else
      return true;
  }

  //bad format and date = 0000-00-00 00:00:00
  $nTime = strtotime($psDate);
  if(!$nTime)
    return false;

  if(empty($psFormat))
    $psFormat = 'Y-m-d';

  if($psDate == date($psFormat, $nTime))
    return true;

  return false;
}

//alias of previous for easier asserts
function is_datetime($psDate, $pbStrict = true)
{
  return is_date($psDate, 'Y-m-d H:i:s', $pbStrict);
}


function getDateDifference($psStartDate, $psEndDate = '', $pbCloserScale = true, $psFormat = 'd')
{
  if(!assert('!empty($psStartDate) && is_bool($pbCloserScale)'))
    return '-';

  if(empty($psEndDate))
    $psEndDate = date('Y-m-d H:i:s');

  $asFormat = array('s' => 'sec', 'i' => 'min', 'h' => 'h', 'd' => 'd', 'm' => 'mth', 'y' => 'y');

  if(!isset($asFormat[$psFormat]))
    return '-';

  $oStartDate = date_create($psStartDate);
  $oEndDate = date_create($psEndDate);

  if(!$oStartDate || !$oStartDate)
    return '-';

  $oInterval = date_diff($oStartDate, $oEndDate);
  if(!$oInterval)
    return '-';

  if($pbCloserScale)
  {
    $sValue = $oInterval->format('%y,%m,%d,%h,%i,%s');
    $asValue = explode(',', $sValue);

    if($asValue[0] > 0)
    {
      if($asValue[1] > 2)
        return ((int)$asValue[0]).' ~ '.((int)$asValue[0]+1).' years';
      else
        return (int)$asValue[0].' year'.((((int)$asValue[0]) > 1)? 's':'');
    }

    if($asValue[1] > 0)
    {
      if($asValue[2] > 5)
        return ((int)$asValue[1]).'~'.((int)$asValue[1] + 1).' months';
      else
        return (int)$asValue[1].' month'.((((int)$asValue[1]) > 1)? 's':'');
    }

    if($asValue[2] > 0)
    {
      return (int)$asValue[2].' day'.((((int)$asValue[2]) > 1)? 's':'');
    }

    if($asValue[3] > 0)
    {
      return (int)$asValue[3].' hr';
    }

    if($asValue[4] > 0)
    {
      return (int)$asValue[4].' min';
    }

    return $asValue[5].' sec';
  }

  return $oInterval->format('%'.$psFormat).$asFormat[$psFormat];
}


function is_key($pnPk)
{
  if((empty($pnPk)) || (!is_integer($pnPk)) || ($pnPk < 1))
    return false;

  return true;
}

function is_integerString($psString, $pnMinLength = 1, $pnMaxLength = PHP_INT_SIZE, $pnMax = PHP_INT_MAX)
{
  if(preg_match('/^[0-9]{'.$pnMinLength.','.$pnMaxLength.'}$/', $psString) === false)
    return false;

  if((int)$psString > $pnMax)
    return false;

  return true;
}

function is_listOfInt($psList, $psDelimiter = ',')
{
  if(!assert('!empty($psDelimiter)') || empty($psList))
    return false;

  if(is_array($psList))
    return false;

  $asList = explode($psDelimiter, $psList);
  if(empty($asList) || !is_array($asList))
    return false;

  foreach($asList as $sInteger)
  {
    if((int)$sInteger != trim($sInteger))
      return false;
  }

  return true;
}

function is_arrayOfInt($pasArray)
{
  if(!is_array($pasArray) || empty($pasArray))
    return false;

  foreach($pasArray as $sInteger)
  {
    if((string)((int)$sInteger) != trim($sInteger))
      return false;
  }

  return true;
}
function cast_arrayOfInt($pasArray)
{
  if(!assert('is_array($pasArray)') || empty($pasArray))
    return $pasArray;

  foreach($pasArray as $vKey => $vValue)
  {
    $pasArray[$vKey] = (int)$vValue;
  }

  return $pasArray;
}

function is_cpUid($psUid)
{
  if(!assert('!empty($psUid) && is_string($psUid)'))
    return false;

  return true;
}

function is_cpAction($psAction)
{
  if(!assert('is_string($psAction)'))
    return false;

  return true;
}

function is_cpType($psType)
{
 if(!assert('is_string($psType)'))
   return false;

 return true;
}

function is_cpPk($pnPk)
{
  if (!assert('(is_integer($pnPk)) && ($pnPk>=0)'))
    return false;

  return true;
}

function is_cpValues($avValues)
{
  if(!is_array($avValues) || count($avValues) < 4)
    return false;

  if (!is_cpUid($avValues[CONST_CP_UID]) || !is_cpAction($avValues[CONST_CP_ACTION]) || !is_cpType($avValues[CONST_CP_TYPE]) || !is_cpPk($avValues[CONST_CP_PK]))
    return false;

  return true;
}

function is_cpValuesInPost()
{
  $sCpUid=getValue(CONST_CP_UID);
  $sCpAction=getValue(CONST_CP_ACTION);
  $sCpType=getValue(CONST_CP_TYPE);
  $sCpPk=(int)getValue(CONST_CP_PK);

  if  (
         (is_string($sCpType) && !empty($sCpType))
      && (is_string($sCpAction) && !empty($sCpAction))
      && (is_string($sCpUid) && !empty($sCpUid))
      && (is_key($sCpPk))
      )
    return true;

  return false;
}

function getCpValuesFromPost()
{
  $sCpUid=getValue(CONST_CP_UID);
  $sCpAction=getValue(CONST_CP_ACTION);
  $sCpType=getValue(CONST_CP_TYPE);
  $nCpPk=(int)getValue(CONST_CP_PK);

    if  (
         (is_string($sCpType) && !empty($sCpType))
      && (is_string($sCpAction) && !empty($sCpAction))
      && (is_string($sCpUid) && !empty($sCpUid))
      && (is_key($nCpPk))
      )
    return array(
        CONST_CP_UID => $sCpUid,
        CONST_CP_ACTION => $sCpAction,
        CONST_CP_TYPE => $sCpType,
        CONST_CP_PK => $nCpPk
        );
    else
      return array();
}

function displayAssert($psFile, $pnLine, $psMessage)
{
  //limit at  100 asserts. More than that is a loop (maybe infinite)
  if($_SESSION['assert'] > 100)
     return false;

  $_SESSION['assert']++;

  echo '<div class="assert" style="width:100%;">
        <div>
          <strong>Assert spotted in '.$psFile.' line '.$pnLine.": <br /> \n".$psMessage." <br /> \n</strong>
        </div>";

  $asDebug = debug_backtrace();

  foreach($asDebug as $asLine)
  {
    echo ' -> ';

    if(isset($asLine['file']))
      echo 'called in '.$asLine['file'].' / ';

    echo $asLine['function'].' ';

    if(isset($asLine['file']))
      echo 'line '.$asLine['line'].' ';

    echo '<br />';
  }

  echo '</div>';
}

function logAssert($psFile, $pnLine, $psMessage)
{
  //limit at  100 asserts. More than that is a loop (maybe infinite)
  if($_SESSION['assert'] > 100)
     return false;

  $_SESSION['assert']++;

  $sText = date('Y-m-d H:i:s')."\n".'Assert spotted in '.$psFile.' line '.$pnLine.": \n".$psMessage." \n ";

  $asDebug = debug_backtrace();

  foreach($asDebug as $asLine)
  {
    $sText.= ' -> ';

    if(isset($asLine['file']))
      $sText.= 'called in '.$asLine['file'].' / ';

    $sText.= $asLine['function'].' ';

    if(isset($asLine['file']))
      $sText.= 'line '.$asLine['line'].' ';

    //echo 'with['.implode(' | ', $asLine['args']).'] <br />';
    $sText.= "\n";
  }

  $sText.= "\n\n\n".'Url: '.$_SERVER['REQUEST_METHOD'].' - '.$_SERVER['SERVER_PROTOCOL'].'://'.$_SERVER['SERVER_NAME'].'/'.$_SERVER['REQUEST_URI'];
  $sText.= "\n\n".'POST: '.  var_export($_POST, true);
  $sText.= "\n\n".'SESSION: '. getAssertSessionDump("\n");

  $sText.= "\n";

  $oFs = fopen(CONST_DEBUG_ASSERT_LOG_PATH, 'a+');
  if($oFs)
  {
    fwrite($oFs, $sText);
    fclose($oFs);

    //rotate file if size > 2Mo
    if(filesize(CONST_DEBUG_ASSERT_LOG_PATH) > (2*1024*1024))
    {
      copy(CONST_DEBUG_ASSERT_LOG_PATH, CONST_DEBUG_ASSERT_LOG_PATH.'-'.time());
    }
  }
}

function mailAssert($psFile, $pnLine, $psMessage)
{
  //limit 5 assert email per page, to avoid infinite loops
  if($_SESSION['mail_assert'] > 5)
     displayAssert($psFile, $pnLine, $psMessage);

  $_SESSION['mail_assert']++;

  //limit at  100 asserts. More than that is a loop (maybe infinite)
  if($_SESSION['assert'] > 100)
     return false;

  $_SESSION['assert']++;

  $sAssert = '<div style="width:100%;">
        <div>
          <strong>Assert spotted in '.$psFile.' line '.$pnLine.": <br /> \n".$psMessage." <br /> \n</strong>
        </div>";

  $asDebug = debug_backtrace();

  foreach($asDebug as $asLine)
  {
    $sAssert.= ' -> ';

    if(isset($asLine['file']))
      $sAssert.= 'called in '.$asLine['file'].' / ';

    $sAssert.= $asLine['function'].' ';

    if(isset($asLine['file']))
      $sAssert.= 'line '.$asLine['line'].' ';

    $sAssert.= '<br />';
  }

  $sAssert.= '</div>';


  $sAssert.= '<br /><br /><br />Url: '.$_SERVER['REQUEST_METHOD'].' - '.$_SERVER['SERVER_PROTOCOL'].'://'.$_SERVER['SERVER_NAME'].'/'.$_SERVER['REQUEST_URI'];
  $sAssert.= '<br /><br />POST: '.  var_export($_POST, true);
  $sAssert.= '<br /><br />SESSION: '.  getAssertSessionDump();


  try
  {
    $bSent = false;
    imap_timeout(IMAP_OPENTIMEOUT, 10);
    $oMail =  imap_open('{mail.bulbouscell.com:143/debug/readonly/imap/tls/novalidate-cert}inbox', 'bcm@bulbouscell.com', 'AB1gOne!', OP_HALFOPEN, 0);

    if($oMail)
    {
      $sHeaders = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=utf-8' . "\r\n";
      $sHeaders.= 'From: '.CONST_CRM_MAIL_SENDER . "\r\n" . 'Reply-To: '.CONST_CRM_MAIL_SENDER . "\r\n" . 'X-Mailer: PHP/' . phpversion();

      ini_set ("SMTP", "mail.bulbouscell.com");
      $bSent = imap_mail(CONST_DEV_EMAIL, 'BCM assertion', $sAssert, $sHeaders);

      if($bSent && CONST_DEV_EMAIL_2 != '' && CONST_DEV_EMAIL != CONST_DEV_EMAIL_2)
        $bSent = imap_mail(CONST_DEV_EMAIL_2, 'BCM assertion', $sAssert, $sHeaders);

      imap_close($oMail);
    }
  }
  catch(Exception $oEx)
  {
    displayAssert($psFile, $pnLine, $psMessage);
  }

  if(!$bSent)
    displayAssert($psFile, $pnLine, $psMessage);
}

function getAssertSessionDump($psSeparator = '<br />', $pasArray = null, $pnIndent = 0)
{
  $asToIgnore = array('menunav1','menunav2','menunav3','user_rights','user_data_rights','searchHistory','debug');
  $sText = '';

  if($pasArray === null)
  {
    $pasArray = $_SESSION;
    $pasArray = array_reverse($pasArray, true);
  }

  $sIndent = '';
  for($nCount=0; $nCount < $pnIndent; $nCount++)
    $sIndent.= '&nbsp;&nbsp;&nbsp;';

  foreach((array)$pasArray as $sName => $vValue)
  {
    if(!in_array($sName, $asToIgnore))
    {
      if(is_array($vValue) && !empty($vValue))
        $sText.=  $psSeparator.$sIndent.'['.$sName.'] = '.getAssertSessionDump($psSeparator, $vValue, ++$pnIndent);
      else
        $sText.= $psSeparator.$sIndent.'['.$sName.'] = '.var_export($vValue, true).' ';
    }
  }

  return $sText;
}


/*function dump($pvVar, $psLabel=null, $psDisplay=true)
{
  // format the label
  $psLabel = ($psLabel===null) ? '' : rtrim($psLabel) . ' ';

  // var_dump the variable into a buffer and keep the output
  ob_start();
  var_dump($pvVar);
  $output = ob_get_clean();

  // neaten the newlines and indents
  $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);

  $output = '<pre>'
  . $psLabel
  . htmlspecialchars($output, ENT_QUOTES)
  . '</pre>';

  if($psDisplay)
    echo '<div class="dump" style="padding-left: 100px;">'.$output.'</div>';

  return '<div class="dump" style="padding-left: 100px;">'.$output.'</div>';
}*/

function dev_dump($var, $label=null, $echo=true)
{
  if($_SESSION['userData']['loginpk'] == 1 || $_SESSION['userData']['loginpk'] == 5)
    return dump($var, $label, $echo);
}

function debugLine($pvVariable, $pnLine = 0, $psFile = '', $pbHtml = true)
{
  if($pbHtml)
  {
    echo '<hr />line '.$pnLine.' '.$psFile.':<br /><pre>';
    dump($pvVariable);
    echo '</pre><br />';
  }
  else
  {
    echo "-------------------------------\nline '.$pnLine.' '.$psFile.':\n";
    dump($pvVariable);
    echo "\n";
  }

}

function _live_dump($pvTrace, $psTitle = null)
{
  $sIp = $_SERVER['REMOTE_ADDR'];
  if($sIp == '127.0.0.1' || $sIp == '118.243.81.245')
  {
    if($psTitle)
      echo '| -> '.$psTitle.'<br />';

    dump($pvTrace);
  }

  return true;
}

  /**
   * get the value of a superglobal field
   * Control if isset, as default value, and manage post, get, session, cookie
   */
  function getValue($psVarName, $pvDefaultValue = null, $psSpecificVar = '', $pbStoreInSession = false)
  {

    $avDataSrc = array();

    if(!empty($psSpecificVar))
    {
      switch(strtolower($psSpecificVar))
      {
        case 'post':
          $avDataSrc = $_POST;
          break;

        case 'get':
          $avDataSrc = $_GET;
          break;

        case 'session':
          $avDataSrc = $_SESSION;
          break;

        case 'cookie':
          $avDataSrc = $_COOKIE;
          break;

        case 'redis':
          if(check_redis_key($psVarName))
            return $GLOBALS['redis']->get($psVarName);
          break;

        default:
          assert('false; // wrong type');
          return '';

      }

      if(empty($avDataSrc) || !isset($avDataSrc[$psVarName]))
      {
        if($pbStoreInSession)
          $_SESSION[$psVarName] = $pvDefaultValue;

        return $pvDefaultValue;
      }
      else
      {
        if($pbStoreInSession)
          $_SESSION[$psVarName] = $avDataSrc[$psVarName];

        return $avDataSrc[$psVarName];
      }

    }

    //try to fetch the value from POSt, then GEt, then Session, then cookie

    if(isset($_POST[$psVarName]))
    {
      if($pbStoreInSession)
          $_SESSION[$psVarName] = $_POST[$psVarName];

      if (empty($_POST[$psVarName]))
        return null;
      else
        return $_POST[$psVarName];
    }

    if(isset($_GET[$psVarName]))
    {
      if($pbStoreInSession)
          $_SESSION[$psVarName] = $_GET[$psVarName];

      if (empty($_GET[$psVarName]))
        return null;
      else
        return $_GET[$psVarName];
    }

    if(isset($_SESSION[$psVarName]))
    {
      if (empty($_SESSION[$psVarName]))
        return null;
      else
        return $_SESSION[$psVarName];
    }

    if(isset($_COOKIE[$psVarName]))
    {
      if($pbStoreInSession)
        $_SESSION[$psVarName] = $_COOKIE[$psVarName];

      if (empty($_COOKIE[$psVarName]))
        return null;
      else
        return $_COOKIE[$psVarName];
    }

    if(check_redis_key($psVarName))
    {
      $temp_var = $GLOBALS['redis']->get($psVarName);

      if (empty($temp_var))
        return null;
      else
        return $temp_var;
    }

    return $pvDefaultValue;
  }

  function save_to_redis($key_name, $value, $expiration = 0)
  {
    if ($expiration > 0)
    {
      $GLOBALS['redis']->set($key_name, $value);
      $GLOBALS['redis']->setTimeout($key_name, $expiration);
    }
    else
      return $GLOBALS['redis']->set($key_name, $value);
  }

  function delete_from_redis($key_name)
  {
    return $GLOBALS['redis']->delete($key_name);
  }

  function check_redis_key($key_name)
  {
    return $GLOBALS['redis']->exists($key_name);
  }

  function getFormatedDate($psFormat, $psDate = '')
  {
    if(!assert('!empty($psFormat)'))
      return '';

    if(empty($psDate))
      return date($psFormat);

    $psDate = trim($psDate);

    switch($psFormat)
    {
      case 'Y-m-d':
        $asDate = explode(' ', $psDate);
        $asDate[0] = str_replace('/', '-', $asDate[0]);
        return $asDate[0];
        break;

      case 'Y-m-d H:i:s':
        $asDate = explode(' ', $psDate);
        $asDate[0] = str_replace('/', '-', $asDate[0]);

        if(!isset($asDate[1]) || empty($asDate[1]))
        $asDate[1] = '00:00:00';

        return implode(' ', $asDate);
        break;

      default:
        assert('formatedDate doesn\'t know this format. ');
        break;
    }
  }

  function getRelativeUploadPath($sPath)
  {
    return str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $sPath);
  }


  function makePath($psPath)
  {
    if(!assert('is_string($psPath) && !empty($psPath)'))
      return false;

    //remove filename if present
    $asPath = pathinfo($psPath);
    if(isset($asPath['extension']))
      $psPath = $asPath['dirname'];

    // be sure we won't try to create forlders outside the website directory
    // if given a absolute path
    $psPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $psPath);
    if(!$psPath)
    {
      assert('false; // path is not valid.');
      return false;
    }

    if(!mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$psPath, 0755, true))
    {
      assert('false; // couldn\'t create directories.');
      return false;
    }

    return true;
  }

  function isValidEmail($psString, $pbStrict = true)
  {
    if($pbStrict && empty($psString))
      return false;

    if(!$pbStrict && empty($psString))
      return true;

    return filter_var($psString, FILTER_VALIDATE_EMAIL) !== false;
  }


  function isEmptyArray($paArray)
  {
    if(!assert('is_array($paArray)'))
      return false;

    if(empty($paArray))
      return true;

    foreach($paArray as $vValue)
    {
      if(!empty($vValue))
        return false;

      if(is_array($vValue))
      {
        if(!isEmptyArray($vValue))
          return false;
      }
    }

    return true;
  }

  function formatUrl($psString)
  {
    $psString = trim($psString);

    if(empty($psString) || strlen($psString) < 4)
      return '';

    $asUrl = parse_url($psString);

    //based on scheme presence in the url, the hostname goes in path or host
    if(!$asUrl || ( (!isset($asUrl['host']) || empty($asUrl['host'])) && (!isset($asUrl['path']) || empty($asUrl['path'])) ))
      return '';

    if(!isset($asUrl['port']) || empty($asUrl['port']))
      $asUrl['port'] = '';

    $bHasScheme = true;
    if(!isset($asUrl['scheme']) || empty($asUrl['scheme']))
    {
      $bHasScheme = false;

      switch($asUrl['port'])
      {
        case 443:
          $asUrl['scheme'] = 'https'; break;

        case 22:
          $asUrl['scheme'] = 'ssh'; break;

        case 21:
          $asUrl['scheme'] = 'ftp'; break;

        default:
          $asUrl['scheme'] = 'http'; break;
      }
    }

    if( ($bHasScheme && substr_count($asUrl['host'], '.') == 1) || (!$bHasScheme && substr_count($asUrl['path'], '.') == 1))
    {
      if($bHasScheme)
        $asUrl['host'] = 'www.'.$asUrl['host'];
      else
        $asUrl['path'] = 'www.'.$asUrl['path'];
    }

    //rebuild url from update array
    $sUser = isset($asUrl['user']) ? $asUrl['user'] : '';
    $sPass = isset($asUrl['pass']) ? ':' . $asUrl['pass']  : '';
    $sPass = ($sUser || $sPass) ? $sPass.'@' : '';

    $sUrl = isset($asUrl['scheme']) ? $asUrl['scheme'] . '://' : '';
    $sUrl.= $sUser.$sPass;

    //no port if we can't detect the hostname
    if(isset($asUrl['host']) && !empty($asUrl['host']))
    {
      $sUrl.= isset($asUrl['host']) ? $asUrl['host'] : '';
      $sUrl.= isset($asUrl['port']) && !empty($asUrl['port']) ? ':' . $asUrl['port'] : '';
    }

    $sUrl.= isset($asUrl['path']) ? $asUrl['path'] : '';
    $sUrl.= isset($asUrl['query']) ? '?' . $asUrl['query'] : '';
    $sUrl.= isset($asUrl['fragment']) ? '#' . $asUrl['fragment'] : '';
    return $sUrl;
  }

  /**
   * Return an integer to indicate if the url is correct.
   * 1: ok, 0: bad format url, -1: when we can't reach the destination
   * @param string $psString
   * @param boolean $pbFormatbefore
   * @param boolean $pbLiveTestUrl
   * @return integer
   */
  function isValidUrl($psString, $pbFormatbefore = false, $pbLiveTestUrl = false)
  {
    $psString = trim($psString);
    if(empty($psString) || strlen($psString) < 4)
      return false;

    if($pbFormatbefore)
    {
      $psString = formatUrl($psString);
    }

    //remove the starting www. from the url
    $psString = preg_replace('|^([a-z]{3,6}://)?(www\.)?(.*)|i', '$1$3', $psString);

    //Add http:// if no protocole has been defined
    $sScheme = strtolower(substr($psString, 0, 6));
    if(!in_array($sScheme, array('http:/','https:', 'ftp://', 'ftps:/', 'nfs://', 'smtp:/', 'imap:/', 'xmpp:/', 'lpd://', 'smb://', 'samba:')))
      $psString = 'http://'.$psString;

    //apply the perfect regexp (better than filter_var)  http://mathiasbynens.be/demo/url-regex
    $nResult = (int)preg_match(URL_FORMAT, $psString);

    if($nResult > 0)
      return 1;

    if(checkUrlAvailability($psString))
      return 1;

    return 0;
  }

  function checkUrlAvailability($psUrl)
  {
    if(empty($psUrl) || strlen($psUrl) < 4)
      return false;

    $oCurl = curl_init($psUrl);
    curl_setopt($oCurl,  CURLOPT_RETURNTRANSFER, true);
    curl_setopt($oCurl,  CURLOPT_FAILONERROR, true);
    curl_setopt($oCurl,  CURLOPT_FRESH_CONNECT, true);
    curl_setopt($oCurl,  CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($oCurl,  CURLOPT_TIMEOUT, 3);
    curl_setopt($oCurl,  CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($oCurl,  CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($oCurl,  CURLOPT_AUTOREFERER, true);

    /* Get the HTML or whatever is linked to the $url. */
    curl_exec($oCurl);

    /* Check for 404 (file not found). */
    $nHttpCode = (int)curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
    curl_close($oCurl);

    if($nHttpCode >= 200 && $nHttpCode < 300)
      return true;

    return false;
  }


  function isDevelopment()
  {
    if(isset($_GET['set_debug']))
    {
      $_SESSION['debug'] = (int)$_GET['set_debug'];
    }

    if(CONST_DEV_SERVER || getValue('debug, 0') || !empty($_SESSION['debug']))
      return true;

    return false;
  }


  function getEventTypeList($pbOnlyValues = false, $psType = '', $is_admin = false)
  {

    if(CONST_WEBSITE == 'slistem')
    {

      if($pbOnlyValues)
      {
        if(empty($psType) || $psType == CONST_CANDIDATE_TYPE_CANDI)
        {
          $asEvent['character'] = 'character';
          $asEvent['note'] = 'note';
          $asEvent['email'] = 'email';
          $asEvent['meeting'] = 'meeting';
          $asEvent['phone'] = 'phone';
          $asEvent['update'] = 'update';
          $asEvent['update'] = 'update';
          $asEvent['cp_history'] = 'cp_history';
        }
        if(empty($psType) || $psType == CONST_CANDIDATE_TYPE_COMP)
        {
          $asEvent['note'] = 'note';
          $asEvent['email'] = 'email';
          $asEvent['phone'] = 'phone';
          $asEvent['update'] = 'update';
          $asEvent['description'] = 'description';
        }
      }
      else
      {
        if(empty($psType) || $psType == CONST_CANDIDATE_TYPE_CANDI)
        {
          $asEvent['character'] = array('label' => 'Character note', 'value' => 'character', 'group' => '');
          $asEvent['note'] = array('label' => 'Note', 'value' => 'note', 'group' => '');
          $asEvent['email'] = array('label' => 'Email', 'value' => 'email', 'group' => '');
          $asEvent['meeting'] = array('label' => 'Meeting', 'value' => 'meeting', 'group' => '');
          $asEvent['phone'] = array('label' => 'Phone call', 'value' => 'phone', 'group' => '');
          $asEvent['update'] = array('label' => 'Update', 'value' => 'update', 'group' => '');
          if ($is_admin)
            $asEvent['cp_history'] = array('label' => 'Company history', 'value' => 'cp_history', 'group' => '');
        }

        if(empty($psType) || $psType == CONST_CANDIDATE_TYPE_COMP)
        {
          $asEvent['note'] = array('label' => 'Note', 'value' => 'note', 'group' => '');
          $asEvent['email'] = array('label' => 'Email', 'value' => 'email', 'group' => '');
          $asEvent['phone'] = array('label' => 'Phone call', 'value' => 'phone', 'group' => '');
          $asEvent['update'] = array('label' => 'Update', 'value' => 'update', 'group' => '');
          $asEvent['description'] = array('label' => 'Description', 'value' => 'Company description', 'group' => '');
        }
      }
    }
    else
    {
      if($pbOnlyValues)
      {
        $asEvent[] = 'article';
        $asEvent[] = 'email';
        $asEvent[] = 'meeting';
        $asEvent[] = 'phone';
        $asEvent[] = 'update';
        $asEvent[] = 'deal';
        $asEvent[] = 'proposal';
        $asEvent[] = 'invoice';
        $asEvent[] = 'payment';
        $asEvent[] = 'info';
        $asEvent[] = 'it-project';
      }
      else
      {
        $asEvent['article'] = array('label' => 'Article', 'value' => 'article', 'group' => 'Info');
        $asEvent['email'] = array('label' => 'Email', 'value' => 'email', 'group' => 'Info');
        $asEvent['meeting'] = array('label' => 'Meeting', 'value' => 'meeting', 'group' => 'Info');
        $asEvent['phone'] = array('label' => 'Phone call', 'value' => 'phone', 'group' => 'Info');
        $asEvent['update'] = array('label' => 'Update', 'value' => 'update', 'group' => 'Info');
        $asEvent['info'] = array('label' => 'General Info', 'value' => 'info', 'group' => 'Info');
        $asEvent['deal'] = array('label' => 'Deal', 'value' => 'deal', 'group' => 'Business');
        $asEvent['proposal'] = array('label' => 'Proposal', 'value' => 'proposal', 'group' => 'Business');
        $asEvent['invoice'] = array('label' => 'Invoice', 'value' => 'invoice', 'group' => 'Finance');
        $asEvent['payment'] = array('label' => 'Payment', 'value' => 'payment', 'group' => 'Finance');
        $asEvent['it-project'] = array('label' => 'IT project', 'value' => 'it-project', 'group' => 'IT');
      }
    }

    return $asEvent;
  }

  function getCompanyRelation($pnPk=0)
  {
    $asCompany = array(1 =>array('Label'=>'Client','icon'=>'client.png','icon_small'=>'client_small.png', 'style' => ''),
        2 =>array('Label'=>'Supplier','icon'=>'supplier.png','icon_small'=>'supplier_small.png', 'style' => ''),
        3 =>array('Label'=>'Candidate','icon'=>'candidate.png','icon_small'=>'candidate_small.png', 'style' => ''),
        4 =>array('Label'=>'Collaborator','icon'=>'collaborator.png','icon_small'=>'collaborator_small.png', 'style' => ''),
        5 =>array('Label'=>'Prospect','icon'=>'prospect.png','icon_small'=>'prospect_small.png', 'style' => ''),
        6 =>array('Label'=>'Prospect (temp)','icon'=>'prospect.png','icon_small'=>'prospect_small.png', 'style' => 'color: #D4662A;'));

    if($pnPk)
     return $asCompany[$pnPk];
    else
     return $asCompany;
  }


  function getContactGrade($pnPk=0)
  {
    $asGrade = array('1'=>'Top decision maker', '2'=>'Mid management, senior staff', '4'=>'Staff, Junior ', '5'=>'Unknown');
    if($pnPk)
      return $asGrade[$pnPk];
    else
      return $asGrade;
  }


  function isUidAvailable($psUid)
  {
      $asDependency = array('database'=>'124-546','page'=>'845-187','display'=>'569-741','form'=>'668-313','login'=>'579-704'
          , 'addressbook'=>'777-249','project'=>'456-789','sharedspace'=>'999-111','search'=>'898-775','pager'=>'140-510',
          'event'=>'007-770','webmail'=>'009-724','mail'=>'008-724','querybuilder'=>'210-482');
      if(in_array($psUid,$asDependency))
          return true;
      else
         return false;
  }

  //================================================
  //SearchHistory functions
  function manageSearchHistory($psGuid='', $psType='', $pbForceNew = false)
  {
    //starts with maintenance: keep session array size under 50 for every component/type
    if(isset($_SESSION['searchHistory'][$psGuid][$psType]) && count($_SESSION['searchHistory'][$psGuid][$psType]) > 50)
    {
      $_SESSION['searchHistory'][$psGuid][$psType] = array_reverse($_SESSION['searchHistory'][$psGuid][$psType], true);
      //array_pop($_SESSION['searchHistory']);
      array_pop($_SESSION['searchHistory'][$psGuid][$psType]);
      $_SESSION['searchHistory'][$psGuid][$psType] = array_reverse($_SESSION['searchHistory'][$psGuid][$psType], true);
    }

    //to prevent dev errors, let's check lower case name too
    $sSearchId = getValue('searchId', 0);

    if(empty($sSearchId))
      $sSearchId = getValue('searchid', 0);

    //Create a new id and save current post/get datas (undefined too prevent javascript errors)
    if($pbForceNew || empty($sSearchId) || $sSearchId == 'undefined' || !isset($_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]) || empty($_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]))
    {
      $sSearchId = uniqid('search_');
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['post'] = $_POST;
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['get'] = $_GET;
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortfield'] = '';
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] = '';
      return $sSearchId;
    }

    global $gbNewSearch;
    $gbNewSearch = false;

    //store sorting and pager options
    $sSortField = getValue('sortfield');
    $sSortOrder = getValue('sortorder');
    $nPageOffset = (int)getValue('pageoffset', 0);
    $nNbResult = (int)getValue('nbresult', 0);

    if(!empty($sSortField))
    {
      if(!empty($sSortOrder))
      {
        $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortfield'] = $sSortField;
        $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] = $sSortOrder;
      }
      else
      {
        //check for sort order in history
        if($sSortField == $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortfield'])
        {
          if($_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] == 'ASC')
            $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] = 'DESC';
          else
            $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] = 'ASC';
        }
        else
        {
          $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortfield'] = $sSortField;
          $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['sortorder'] = 'ASC';
        }
      }
    }

    //update the page number and page result
    if($nPageOffset)
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['post']['pageoffset'] = $nPageOffset;

    if($nNbResult)
      $_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['post']['nbresult'] = $nNbResult;


    //we ve got an id: overwrite current POST/GET data with saved ones
    //exclude sortfield if passed in parameters
    foreach($_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['post'] as $sParamName => $sParamValue)
    {
      if(substr($sParamName, 0, 2) != '__' && empty($sSortField) || $sParamName != 'sortfield')
        $_POST[$sParamName] = $sParamValue;
    }

    foreach($_SESSION['searchHistory'][$psGuid][$psType][$sSearchId]['get'] as $sParamName => $sParamValue)
    {
      if(substr($sParamName, 0, 2) != '__' && empty($sSortField) || $sParamName != 'sortfield')
        $_GET[$sParamName] = $sParamValue;
    }

    //restore the new offset if erased just above
    /*$_POST['pageoffset'] = $nPageOffset;
    $_POST['nbresult'] = $nNbResult;*/

    return $sSearchId;
  }

  function saveSearchHistorySql($psSearchId, $psSql, $psGuid='', $psType='')
  {
    if(empty($psSearchId) || empty($psSql))
      return false;

    if(!isset($_SESSION['searchHistory'][$psGuid][$psType][$psSearchId]))
      return false;

    $_SESSION['searchHistory'][$psSearchId]['sql'] = $psSql;
    return true;
  }

  function getSearchHistory($psSearchId, $psGuid='', $psType='')
  {
    if(empty($psSearchId))
      return array();

    if(!isset($_SESSION['searchHistory'][$psGuid][$psType][$psSearchId]))
      return array();

    return $_SESSION['searchHistory'][$psGuid][$psType][$psSearchId];
  }

  function setSearchHistory($asHistoryData, $psSearchId, $psGuid='', $psType='')
  {
    if(empty($psSearchId) || !is_array($asHistoryData))
      return false;

    if(!isset($_SESSION['searchHistory'][$psGuid][$psType][$psSearchId]))
      return false;

    $_SESSION['searchHistory'][$psGuid][$psType][$psSearchId] = $asHistoryData;
    return true;
  }

  function reloadLastSearch($psGuid = '', $psType = '')
  {
    $sId = getLastSearchId($psGuid, $psType);
    $_POST['searchId'] = $sId;

    manageSearchHistory($psGuid, $psType);
      return $sId;
   }

  function getLastSearchId($psGuid='', $psType='')
  {
    if(!isset($_SESSION['searchHistory'][$psGuid][$psType]))
      return '';

    $asSearchId = array_keys($_SESSION['searchHistory'][$psGuid][$psType]);
      return end($asSearchId);
  }

  function getLanguageLevel($pnLanguagelevel)
  {
    $asLangArray =   array(0 => 'None',1 =>'Basic',2 => 'Conversational',3 =>'Business',4 => 'Fluent',5 =>'Native');
    return  $asLangArray[$pnLanguagelevel];
  }

  function showHideSearchForm($psSetTime, $psType)
  {
    $oPage = CDependency::getCpPage();

    if(!isset($_SESSION['storetime'][$psType]))
      $_SESSION['storetime'][$psType] = 0;

    if(!empty($psSetTime) && ($_SESSION['storetime'][$psType] != $psSetTime))
    {
      $_SESSION['storetime'][$psType] = $psSetTime;
      $sJavascript = " $(document).ready(function(){ $('.searchContainer').show(); }); ";
      $oPage->addCustomJs($sJavascript);
    }
    else
    {
      $sJavascript = " $(document).ready(function(){ $('.searchContainer').hide(); }); ";
      $oPage->addCustomJs($sJavascript);
     }
   }

   function curPageURL()
   {
      $sPageURL = 'http';
      if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$sPageURL.= "s";}
      $sPageURL .= "://";
      if($_SERVER["SERVER_PORT"] != "80")
      {
        $sPageURL.= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
      }
      else
      {
        $sPageURL.= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
      }
     return $sPageURL;
  }



  //========================================================================================================
  //========================================================================================================
  // String related functions


  //Check for kanji
  function isKanji($sStr)
  {
     return preg_match('/[\x{4E00}-\x{9FBF}]/u', $sStr) > 0;
  }

  //Check for Hiragana
  function isHiragana($sStr)
  {
     return preg_match('/[\x{3040}-\x{309F}]/u', $sStr) > 0;
  }

  //Check for all japanese
  function isJapanese($sStr)
  {
    return isKanji($sStr) || isHiragana($sStr) || isKatakana($sStr);
  }

  //Check for Katakana
  function isKatakana($sStr)
  {
    return preg_match('/[\x{30A0}-\x{30FF}]/u', $sStr) > 0;
  }

  function isCJK($sStr)
  {
    if(preg_match('/[\x{1100}-\x{11FF}]/u', $sStr) == 1)
      return true;

    if(preg_match('/[\x{2E80}-\x{D7FF}]/u', $sStr) == 1)
      return true;

    if(preg_match('/[\x{20000}-\x{2B73F}]/u', $sStr) == 1)
      return true;

    if(preg_match('/[\x{2F800}-\x{2FA1F}]/u', $sStr) == 1)
      return true;

    return false;
  }

  function cutString($psStringToCut, $pnLetters = 25, $psCutedEol = ' ...')
  {
    if(isJapanese($psStringToCut))
      return $psStringToCut;

    $nLength = strlen($psStringToCut);
    if($nLength <= $pnLetters)
      return $psStringToCut;

    return substr($psStringToCut, 0, ($pnLetters-strlen($psCutedEol))).$psCutedEol;
  }

  function cutStringByWords($psStringToCut, $pnWords = 5)
  {
    if(isJapanese($psStringToCut))
      return $psStringToCut;

    $asString = explode(' ', $psStringToCut);
    if(count($asString) <= $pnWords)
      return $psStringToCut;

    $asString = array_slice($asString, 0, $pnWords);
    return implode(' ', $asString);
  }


  function array_trim($pasArray, $pbRemoveEmpty = false, $pbUnique = false)
  {
    if(!assert('is_array($pasArray)'))
      return array();

    foreach($pasArray as $vKey => $vValue)
    {
      if(!is_object($vValue) || !is_array($vValue))
      {
        $vValue = trim($vValue);
        if($pbRemoveEmpty && empty($pbRemoveEmpty))
          unset($pasArray[$vKey]);
        else
          $pasArray[$vKey] = $vValue;
      }
    }

    if($pbUnique)
      return array_unique($pasArray);

    return $pasArray;
  }



  /**
   * Check a string an return the "language" it contains en/cjk/mix
   * If strict it check if the string is 100% en or CJK, if not a specific ratio is applied
   *
   * @param string $psFileContent
   * @param boolean $pbStrict
   * @return string
   */
  function getTextLangType($psFileContent, $pbStrict = false)
  {
    $nLength = (int)mb_strlen($psFileContent);

    if(empty($psFileContent) || empty($nLength))
      return 'mix';

    //remove number working for both
    $sEnText = preg_replace('/[0-9\x{1100}-\x{11FF}\x{2E80}-\x{D7FF}\x{20000}-\x{2B73F}\x{2F800}-\x{2FA1F}]/u', '', $psFileContent);

    $nEnText = (int)mb_strlen($sEnText);
    $nCjkText = (($nLength-$nEnText) /3);   //1 CJK char length = 3, so i need to balance it
    $nTotal = $nEnText + $nCjkText;
    $fRatio = ($nEnText / $nTotal);
    //dump($nTotal);  dump($nEnText);  dump($fRatio);

    if($pbStrict)
    {
      if($fRatio == 1)
        return 'en';
      elseif($fRatio == 0)
        return 'cjk';

      return 'mix';
    }


    if($fRatio > 0.95)
      return 'en';

    //japanese is meant to contain some english words, at least abreviation or signs
    if($fRatio < 0.15)
      return 'cjk';

    return 'mix';
  }


  //========================================================================================================
  //========================================================================================================




  function addIndustry($pnPk, $psLabel, $pnStatus, $pnParentfk)
  {
    if(!assert('is_integer($pnPk) && is_integer($pnStatus) && is_integer($pnStatus)'))
      return false;

    $oDb = CDependency::getComponentByName('database');

    if(!empty($pnPk))
    {
      $sQuery = 'INSERT INTO `addressbook_industry` (`addressbook_industrypk`, `name`, `status`, `parentfk`) VALUES ';
      $sQuery.= ' ('.$pnPk.', '.$oDb->dbEscapeString($psLabel).', '.$oDb->dbEscapeString($pnStatus).', '.$oDb->dbEscapeString($pnParentfk).') ';
    }
    else
    {
      $sQuery = 'INSERT INTO `addressbook_industry` (`name`, `status`, `parentfk`) VALUES ';
      $sQuery.= ' ('.$oDb->dbEscapeString($psLabel).', '.$oDb->dbEscapeString($pnStatus).', '.$oDb->dbEscapeString($pnParentfk).') ';
    }

    $oDbResult = $oDb->ExecuteQuery($sQuery);
    if(!$oDbResult)
      return false;

    return true;
  }


  function formatNumber($pvValue, $psDisplayMode = '', $psSeparator = ',', $psCurrency = '')
  {
    if(!assert('is_numeric($pvValue)'))
      return ' - ';

    if(is_float($pvValue))
      $vValue = (float)$pvValue;
    else
      $vValue = (int)$pvValue;

    if($vValue == 0)
      return 0;

    switch($psDisplayMode)
    {
      case 'compact':

        if($vValue > 1000000)
        {
          if($psCurrency)
            $psCurrency.= ' M';

           return number_format(round(($vValue/1000000), 3), 0, '.', $psSeparator).$psCurrency;
        }

        if($vValue > 1000)
        {
          if($psCurrency)
            $psCurrency.= ' K';

          return number_format(round(($vValue/1000), 2), 0, '.', $psSeparator).$psCurrency;
        }

        if($psCurrency)
            $psCurrency.= ' ';

        return number_format(round($vValue, 2), 0, '.', $psSeparator).$psCurrency;
        break;

      case 'M':
        if($psCurrency)
            $psCurrency.= ' M';

        if($vValue >= 500000)
          return number_format(round(($vValue/1000000), 3), 1, '.', $psSeparator).$psCurrency;
        else
          return number_format(round(($vValue/1000000), 3), 2, '.', $psSeparator).$psCurrency;
        break;

       case 'K':
         if($psCurrency)
            $psCurrency.= ' K';

         if($vValue >= 500)
           return number_format(round(($vValue/1000), 2), 1, '.', $psSeparator).$psCurrency;
         else
           return number_format(round(($vValue/1000), 2), 2, '.', $psSeparator).$psCurrency;
        break;

      default:
        return number_format($vValue, 0, '.', $psSeparator).' '.$psCurrency;
    }



  }

function trace($psMessage, $pbForce = false)
{
  if($pbForce || isDevelopment())
  {
    $_SESSION['debug']['trace'][] = date('H:i:s').': '.$psMessage;

    $nTraces = count($_SESSION['debug']['trace']);
    if($nTraces > 25)
      array_splice ($_SESSION['debug']['trace'] , 0, $nTraces-25 );
  }

  return true;
}




  //========================================================================================================
  //========================================================================================================
  // Array related functions

  function simplexmlToArray($poXml, $pasResult = array ())
  {
    if(!is_object($poXml))
      return $pasResult;

    foreach ((array)$poXml as $vKey => $oNode)
    {
      $pasResult[(string)$vKey] = ( is_object ($oNode) ) ? xml2array ($oNode) : (string)$oNode;
    }

    return $pasResult;
  }

/**
 *Multi-usage function:
 *
 *    - initialize array indexes if used like:              set_array($asArray['non_existant_key']);
 *
 *    - replace if(!isset(){ ... } :                        set_array($asArray['key'], 'aaa');
 *
 *    - replace if(!isset(){ $asArray['key'] = 'aaa'; }
 *              else{ $asArray['key'] = 'bbb'; } :          set_array($asArray['key'], 'aaa', 'bbb');
 *
 *    - as a consequence, initialize variables !! to not use for that!!
 *                                                          set_array($vDoNotExist);
 *
 * @param php variable $poSrcString
 * @param mixed $pvNotSet
 * @param mixed $pvIfExist
 * @return true
 */
function set_array(&$poSrcString, $pvNotSet = null, $pvIfExist = null)
{
  //simply initialize $poSrcString with null
  if($pvIfExist === null && $pvNotSet === null)
  {
    return $poSrcString = '';
  }

  //based on params, concat $pvNotSet or $pvIfExist to $poSrcString
  if($pvIfExist !== null && $poSrcString !== null)
  {
    $poSrcString.= $pvIfExist;
  }
  elseif($poSrcString === null && $pvNotSet !== null)
  {
    $poSrcString.= $pvNotSet;
  }

  return true;
}


function highlightKeywords($psText, $psKeywords)
{
  if(!assert('is_string($psText) && !empty($psText) && is_string($psKeywords) && !empty($psKeywords)'))
    return '';

  $sOutput = str_replace(strtolower($psKeywords), '<span class=\'highlight\'>'.strtolower($psKeywords).'</span>', strtolower($psText));

  return $sOutput;
}

function getNationality($pvNationalityPk = 0, $pbCompact = true)
{
  $oDB = CDependency::getComponentByName('database');
  if(!$oDB)
    return array();

  if(!assert('is_integer($pvNationalityPk) || is_arrayOfInt($pvNationalityPk)'))
    return array();

  $sQuery = ' SELECT * FROM system_nationality ';

  if(!empty($pvNationalityPk))
  {
    if(is_integer($pvNationalityPk))
    {
      $sQuery.= ' WHERE system_nationalitypk = '.$pvNationalityPk;
    }
    else
      $sQuery.= ' WHERE system_nationalitypk IN ('.implode(',', $pvNationalityPk).') ';
  }

  $sQuery.= ' ORDER BY nationality_name ';

  $oDbResult = $oDB->executeQuery($sQuery);
  $bRead = $oDbResult->readFirst();
  if(!$bRead)
    return array();

  $asNationality = array();
  while($bRead)
  {
    if($pbCompact)
      $asNationality[$oDbResult->getFieldValue('system_nationalitypk')] = $oDbResult->getFieldValue('iso');
    else
      $asNationality[$oDbResult->getFieldValue('system_nationalitypk')] = $oDbResult->getData();

    $bRead = $oDbResult->readNext();
  }

  return $asNationality;
}


function getDateRange($psFieldName, $psDefaultStart = '', $psDefaultEnd = '+1 week')
{
  $sDateStart = getValue($psFieldName);
  $sDateEnd = '';

  if(!empty($sDateStart))
  {
    $asDate = explode(' to ', $sDateStart);
    if(is_date($asDate[0]))
      $sDateStart = $asDate[0];

    if(!isset($asDate[1]) || !is_date($asDate[1]))
      $sDateEnd = date('Y-m', strtotime('+1 months')).'-01';
  }

  if(empty($sDateStart))
    $sDateStart = date('Y-m', strtotime($psDefaultStart)).'-01';

  if(empty($sDateEnd))
    $sDateEnd = date('Y-m', strtotime($psDefaultEnd)).'-01';

  return array('start' => $sDateStart, 'end' => $sDateEnd);
}

$gasEncoding = mb_list_encodings();
function convertToUtf8(&$psString)
{
  global $gasEncoding;
  $sEncoding = mb_detect_encoding($psString);

  if(!$sEncoding || !in_array($sEncoding, $gasEncoding))
    $sEncoding = 'UTF-8';

  return mb_convert_encoding($psString, 'UTF-8', $sEncoding);

}


function cleanXmlString($psString)
{
  if(empty($psString))
    return '';

  $psString = str_replace('&', 'and', $psString);
  $sEncoding = mb_detect_encoding($psString);
  $sUTF8 = @mb_convert_encoding($psString, 'utf8'. $sEncoding);

  if(!empty($sUTF8))
   return $sUTF8;

  return $psString;
}

/**
 * Remove "dangerous" html tags from a string
 * @param string $psHTML
 * @param boolean $pbStrict
 * @param string $pbCustomTags
 * @return type
 */
function sanitizeHtml($psHTML, $pbStrict = false, $psCustomTags = '')
{
  if(empty($psCustomTags))
  {
    if($pbStrict)
      $psCustomTags = '<a><b><em><p><i><span><br>';
    else
      $psCustomTags = '<a><b><em><p><div><i><ul><li><span><br>';
  }

  $psHTML = strip_tags($psHTML, $psCustomTags);
  $psHTML = preg_replace('/(javascript|javascript\:|onload=|onclick=|onchange=|onfocus=|onblur=|onkeypress=|onkeydown=|onkeyup=|onmousedown=|onmouseup=|onmouseover=|onmouseout=)/i', '', $psHTML);

  return $psHTML;
}

/**
 * array_merge_recursive + distinct + not null
 */
function &array_clean_merge(array &$pasArray1, &$pasArray2 = null)
{
  /*dump('merging.... ');
  dump($pasArray1);
  dump(' ++++++ ');
  dump($pasArray2);*/

  $asMerged = $pasArray1;

  if(is_array($pasArray2))
  {
    foreach($pasArray2 as $vKey => $vValue)
    {
      if(!empty($vValue))
      {
        if(is_object($vValue))
          $vValue = (array)$vValue;

        if(is_object($pasArray2[$vKey]))
          $pasArray2[$vKey] = (array)$pasArray2[$vKey];

        if(is_array($pasArray2[$vKey]))
        {
          if(isset($asMerged[$vKey]) && is_array($asMerged[$vKey]))
          {
            $asMerged[$vKey] = array_clean_merge($asMerged[$vKey], $pasArray2[$vKey]);
          }
          else
          {
            if(!empty($pasArray2[$vKey]))
              $asMerged[$vKey] = $pasArray2[$vKey];
          }
        }
        else
        {
          if(isset($asMerged[$vKey]))
          {
            //add the element to existing entry of array 1
            if(is_array($asMerged[$vKey]))
              $asMerged[$vKey][] = $vValue;
            else
            {
              $asMerged[$vKey] = array($asMerged[$vKey]);
              $asMerged[$vKey][] = $vValue;
            }
          }
          else  //insert new element from array2 in array 1
            $asMerged[$vKey] = array($asMerged[$vKey]);

          $asMerged[$vKey] = array_unique((array)$asMerged[$vKey]);
        }
      }
    }
  }

  /*dump(' ===== ');
  dump($asMerged);*/

  return $asMerged;
}


/**
 * maximize compatibility with html-ified urls: copied/paste or comming from emails ...
 * check if we have &amp; symbol in urls and convert variables acordingly
 */
function sanitizeUrl()
{

  if(strpos($_SERVER['QUERY_STRING'], '&amp;'))
  {
    $_SERVER['QUERY_STRING'] = str_replace('&amp;', '&', $_SERVER['QUERY_STRING']);
    $_SERVER['REQUEST_URI'] = str_replace('&amp;', '&', $_SERVER['REQUEST_URI']);

    foreach($_GET as $sVarName => $sValue)
    {
      if(substr($sVarName, 0, 4) == 'amp;')
      {
        $_GET[substr($sVarName, 4)] = $sValue;
      }
    }
  }
}

/**
 * Sorts multidimensional array by string value using natural order, can be reversed
 * @param string $field
 * @param string $order
 * @return -1, 0, 1
 */
function sort_multi_array_by_value($field, $order = 'natural')
{
  return function ($a, $b) use ($field, $order) {
    if ($order == 'reverse')
    {
      return strnatcmp($b[$field], $a[$field]);
    }
    else
    {
      return strnatcmp($a[$field], $b[$field]);
    }
  };
}