<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/conf/main_config.inc.php5');
require_once($_SERVER['DOCUMENT_ROOT'].'/conf/custom_config/slistem/config.inc.php5');


if(isset($_GET['action']) && $_GET['action'] =='tips')
{
  $asTips = getTips(10);

  $sHtml = '
  <script>
      function rotateTips()
      {
        $(".tipContainer:visible").fadeOut(function()
        {
          var oNext = $(this).next(".tipContainer");
          if(!oNext.length)
            oNext = $(".tipContainer:first");

          $(oNext).fadeIn(function()
          {
            setTimeout("rotateTips();", 7500);
          });
        });
      }
      setTimeout("rotateTips();", 7500);
  </script>';

  $bFirst = true;
  foreach($asTips as $sKey => $sTip)
  {
    if($bFirst)
    {
      $sClass = '';
      $bFirst = false;
    }
    else
      $sClass = 'hidden';

    $sHtml.= '<div class="tipContainer '.$sClass.'" id="tipCont_'.$sKey.'">
                <div class="text2">Placement tips: <br><div class="text3">'.$sKey.': '.$sTip.'</div></div>
              </div>';
  }

   $sHtml.= '<div class="homeRequirements">
Copyright 2005 - '.date('Y').' Slate :: Require a recently updated browser - Javascript enabled - Cookies enabled :: Powered by the People of Slate<br />
We recommend Google Chrome (light and fast) - Alternatives: Firefox 8+ or Safari
              </div>';

  exit(json_encode(array('data' => $sHtml)));
}


if(isset($_GET['action']) && $_GET['action'] =='gallery')
{
  $sHtml = '';
  $asExtension = array('.jpg', '.jpeg', '.gif', '.png', 'tiff');

  $sPath = $_SERVER['DOCUMENT_ROOT'].'/conf/custom_config/slistem/gallery';

  if(is_dir($sPath))
  {
    $oFs = opendir($sPath);
    if($oFs)
    {
      $sHtml.= '
        <script>
        $(".home_gal").colorbox({rel:"home_gal", transition:"fade"});
        </script>';
      $sHtml.= '<ul class="homeGallery">';

      $asPic = array();
      while(false !== ($sFile = readdir($oFs)))
      {
        $sExt = strtolower(substr($sFile, -4));
        if(in_array($sExt, $asExtension))
        {
          $asPic[] = '<li><a class="home_gal" href="/conf/custom_config/slistem/gallery/'.$sFile.'" target="_blank"><img src="/conf/custom_config/slistem/gallery/'.$sFile.'" /></a></li>';
        }
      }
      shuffle($asPic);
      $sHtml.= implode('', array_slice($asPic, 0, 7));


      $sHtml.= '
      <li><a class="home_gal iframe" href="/conf/custom_config/slistem/video/video.php5?video_no=1" width="800" height="600"><img src="/common/pictures/colorbox/video.png" /></a></li>
      <li><a class="home_gal iframe" href="/conf/custom_config/slistem/video/video.php5?video_no=2" width="800" height="600" ><img src="/common/pictures/colorbox/video.png" /></a></li>
      </ul>';

      $sHtml.= '<div class="floatHack"></div>';
      closedir($oFs);
    }
  }

  exit(json_encode(array('data' => $sHtml)));
}
?>

