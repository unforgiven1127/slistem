<?php

if(isset($_GET['trace']) && !empty($_GET['trace']))
{
  session_start();

  $sHTML = '';

  if(isset($_SESSION['debug']['trace']))
  {
    $asTrace = array_reverse($_SESSION['debug']['trace']);
    $sHTML.= '<div class="debugBarTrace">';

    foreach($asTrace as $sTrace)
      $sHTML.= '<div>'.$sTrace.'</div>';

    $sHTML.= '</div>';
  }
  exit(json_encode(array('data' => $sHTML)));
}


function getDebugbar()
{
  $sHTML = '<div id="debugbarContainer" class="debugbarContainer"><a href="javascript:;" class="debugBarLink" onclick="$(this).parent().find(\'.debugbarInner\').fadeToggle(function()
    {
      $(\'.debugbarInner:visible\').parent().css(\'opacity\', \'1\');
      $(\'.debugbarInner:not(:visible)\').parent().css(\'opacity\', \'0.25\');
    });">Debug bar </a>

  <div class="debugbarInner" >
  <span onclick="$(this).next(\'div\').fadeToggle();">Trace</span>
  <div><a href="javascript:;" onclick="AjaxRequest(\'/component/display/resources/debugbar.inc.php5?trace=1\', \'#debugbarContainer\', \'\', \'debugBarTrace\');">reload traces</a></div>
  <div id="debugBarTrace" class="debugBarTrace">
  </div>

  <span onclick="$(this).next(\'div\').fadeToggle();">POST</span>
  <div class="debugBarSection debugBarPost">';

  if(empty($_POST))
  {
    $sHTML.= '<em>no post data.</em>';
  }
  else
  {
    foreach($_POST as $sKey => $vValue)
    {
      if(is_array($vValue) || is_object($vValue))
        $sHTML.= '<div>'.$sKey.' => '.  htmlentities(var_export($vValue, true)).'</div>';
      else
        $sHTML.= '<div>'.$sKey.' => '. htmlentities($vValue).'</div>';
    }
  }
  $sHTML.= '</div>
    <span onclick="$(this).next(\'div\').fadeToggle();">GET</span>
    <div class="debugBarSection debugBarGet">';

  if(empty($_GET))
  {
    $sHTML.= '<em>no get data.</em>';
  }
  else
  {
    foreach($_GET as $sKey => $vValue)
    {
      if(is_array($vValue) || is_object($vValue))
        $sHTML.= '<div>'.$sKey.' => '.htmlentities(var_export($vValue, true)).'</div>';
      else
        $sHTML.= '<div>'.$sKey.' => '. htmlentities($vValue).'</div>';
    }
  }
  $sHTML.= '</div>';


  $sHTML.= '<span onclick="$(this).next(\'div\').fadeToggle();">SESSION</span>
    <div class="debugBarSection debugBarSession">';

  foreach($_SESSION as $sKey => $vValue)
  {
    if(is_array($vValue) || is_object($vValue))
      $sHTML.= '<div><pre>'.$sKey.' => '.htmlentities(var_export($vValue, true)).'</pre></div>';
    else
      $sHTML.= '<div>'.$sKey.' => '. htmlentities($vValue).'</div>';
  }

  $sHTML.= '</div>
    <span onclick="$(this).next(\'div\').fadeToggle();">COOKIE</span>
    <div class="debugBarSection debugBarCookie">';

  foreach($_SESSION as $sKey => $vValue)
  {
    if(is_array($vValue) || is_object($vValue))
      $sHTML.= '<div>'.$sKey.' => '.  htmlentities(var_export($vValue, true)).'</div>';
    else
      $sHTML.= '<div>'.$sKey.' => '. htmlentities($vValue).'</div>';
  }
  $sHTML.= '</div>
    </div>
    </div>';

  return $sHTML;
}

function getFontTester()
{
  $sHTML = '';

  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'expressway\');"> expressway </a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'pakenham\');"> pakenham</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'aller\');"> aller</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'allerstd\');"> allerstd</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'alte\');"> alte</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'average\');"> average</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'source\');"> source</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'pt\');"> pt</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'pontano\');"> pontano</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'noto\');">noto </a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'nobile\');"> nobile</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'liberation\');"> liberation</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'legendum\');"> legendum</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'gudea\');"> gudea</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'fira\');"> fira</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'droid\');"> droid</a> - ';
  $sHTML.= '<a href="javascript:;" onclick=" $(\'*\').css(\'font-family\', \'cabin\');"> cabin</a>  <br /><br /><br /><br />';

  return $sHTML;
}